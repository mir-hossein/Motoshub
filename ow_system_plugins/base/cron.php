<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
class BASE_Cron extends OW_Cron
{
    const EMAIL_VARIFY_CODE_REMOVE_TIMEOUT = 432000; // 5 days

    // minutes


    public function __construct()
    {
        parent::__construct();

        $this->addJob('dbCacheProcess', 1);
        $this->addJob('mailQueueProcess', 1);

        $this->addJob('deleteExpiredOnlineUserProcess', 1);

        $this->addJob('checkPluginUpdates', 60 * 24);
        $this->addJob('deleteExpiredPasswordResetCodes', 10);
        $this->addJob('rmTempAttachments', 60 * 24);
        $this->addJob('rmTempAvatars', 60 * 24);
        $this->addJob('deleteExpiredCache', 60 * 24);
        $this->addJob('dropLogFile', 60);
        $this->addJob('clearMySqlSearchIndex', 60 * 24);
        $this->addJob('expireSearchResultList', 1);
        $this->addJob('generateSitemap', 1);
        $this->addJob('removeExpiredLoginCookies', 60);

        $this->addJob('checkRealCron');

        $this->addJob('deleteExpiredEmailVerifies', 60 * 24);
    }

    /**
     * Generate sitemap
     */
    public function generateSitemap()
    {
        $service = BOL_SeoService::getInstance();

        // is it possible to start sitemap generating?
        if ( $service->isSitemapReadyForNextBuild() )
        {
            $service->generateSitemap();
        }
    }

    public function run()
    {
        BOL_UserService::getInstance()->cronSendWellcomeLetter();
    }

    public function dbCacheProcess()
    {
        // Delete expired db cache entry
        BOL_DbCacheService::getInstance()->deleteExpiredList();
    }

    public function mailQueueProcess()
    {
        // Send mails from mail queue
        BOL_MailService::getInstance()->processQueue();
    }

    public function deleteExpiredOnlineUserProcess()
    {
        BOL_UserService::getInstance()->deleteExpiredOnlineUsers();
    }

    public function expireSearchResultList()
    {
        BOL_SearchService::getInstance()->deleteExpireSearchResult();
    }

    public function clearMySqlSearchIndex()
    {
        $mysqlSearchStorage = new BASE_CLASS_MysqlSearchStorage();
        $mysqlSearchStorage->realDeleteEntities();
    }

    public function checkPluginUpdates()
    {
        BOL_StorageService::getInstance()->checkUpdates();
    }

    public function deleteExpiredPasswordResetCodes()
    {
        BOL_UserService::getInstance()->deleteExpiredResetPasswordCodes();
    }

    public function rmTempAttachments()
    {
        BOL_AttachmentService::getInstance()->deleteExpiredTempImages();
    }

    public function rmTempAvatars()
    {
        BOL_AvatarService::getInstance()->deleteTempAvatars();
    }

    public function deleteExpiredCache()
    {
        OW::getCacheManager()->clean(array(), OW_CacheManager::CLEAN_OLD);
    }

    public function dropLogFile()
    {
        $logFilePath = OW_DIR_LOG . 'log.log';

        if ( OW::getStorage()->fileExists($logFilePath) )
        {
            $logFileSize = filesize($logFilePath);
            if ( $logFileSize !== false && $logFileSize / 1024 / 1024 >= 10 * (int) OW::getConfig()->getValue('base', 'log_file_max_size_mb') )
            {
                // E-mail moderators if it became too much in a short time
                $mails = array();
                $moderators = BOL_AuthorizationService::getInstance()->getModeratorList();
                foreach ( $moderators as $moderator ) {
                    $user = BOL_UserService::getInstance()->findUserById($moderator->userId);
                    $mail = OW::getMailer()->createMail();
                    $mail->addRecipientEmail($user->email);
                    $mail->setSubject('Size of log file has grown rapidly in a short time!');
                    $mail->setHtmlContent('Size of log file has become more than '. (int)($logFileSize / 1024 / 1024) . ' MB.');
                    $mail->setTextContent('Size of log file has become more than '. (int)($logFileSize / 1024 / 1024) . ' MB.');
                    $mails[] = $mail;
                }
                OW::getMailer()->addListToQueue($mails);
            }
            if ( $logFileSize !== false && $logFileSize / 1024 / 1024 >= (int) OW::getConfig()->getValue('base', 'log_file_max_size_mb') )
            {
                $timeStr = date('Y-m-d-h-i', time());
                $filename = $logFilePath . '_' . $timeStr;
                OW::getStorage()->renameFile($logFilePath, $filename);

                //Zip file
                $zip = new ZipArchive();
                if ($zip->open($filename.'.zip', ZipArchive::CREATE)==TRUE) {
                    $zip->addFile($filename,'log.log_' . $timeStr);
                    echo "Zip log file, status:" . $zip->status . "\n";
                    $zip->close();
                    OW::getStorage()->removeFile($filename);
                }
            }
        }
    }

    public function checkRealCron()
    {
        if ( !isset($_GET['ow-light-cron']) )
        {
            if ( OW::getConfig()->configExists('base', 'cron_is_configured') )
            {
                OW::getConfig()->saveConfig('base', 'cron_is_configured', 1);
            }
            else
            {
                OW::getConfig()->addConfig('base', 'cron_is_configured', 1);
            }
        }
    }

    public function removeExpiredLoginCookies(){
        BOL_UserService::getInstance()->removeExpiredLoginCookies();
    }

    public function deleteExpiredEmailVerifies()
    {
        //clean email varify code table
        BOL_EmailVerifyService::getInstance()->deleteByCreatedStamp(time() - self::EMAIL_VARIFY_CODE_REMOVE_TIMEOUT);
    }
}
