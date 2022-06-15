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

/**
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
final class BOL_AttachmentService
{
    /**
     * @var BOL_AttachmentDao
     */
    private $attachmentDao;

    /**
     * Singleton instance.
     *
     * @var BOL_AttachmentService
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return BOL_AttachmentService
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->attachmentDao = BOL_AttachmentDao::getInstance();
    }

    public function deleteExpiredTempImages()
    {
        $attachList = $this->attachmentDao->findExpiredInactiveItems(time() - 3600);

        /* @var $item BOL_Attachment */
        foreach ( $attachList as $item )
        {
            $filePath = $this->getAttachmentsDir() . $item->getFileName();

            if ( OW::getStorage()->fileExists($filePath) )
            {
                OW::getStorage()->removeFile($filePath);
            }

            $this->attachmentDao->delete($item);
        }
    }

    public function deleteUserAttachments( $userId )
    {
        $list = $this->attachmentDao->findByUserId($userId);

        /* @var $item BOL_Attachment */
        foreach ( $list as $item )
        {
            $this->deleteAttachmentById($item->getId());

            $this->attachmentDao->delete($item);
        }
    }

    // TODO refactor - should delete attachment by id
//    public function deleteAttachmentByUrl( $url )
//    {
//        $attch = $this->attachmentDao->findAttachmentByFileName(trim(basename($url)));
//
//        if ( $attch != NULL )
//        {
//            $this->deleteAttachmentById($attch->getId());
//
//            $this->attachmentDao->delete($attch);
//        }
//        else
//        {
//            if ( OW::getStorage()->fileExists($this->getAttachmentsDir() . basename($url)) )
//            {
//                OW::getStorage()->removeFile($this->getAttachmentsDir() . basename($url));
//            }
//        }
//    }

    public function deleteAttachmentById( $id )
    {
        $attch = $this->attachmentDao->findById($id);
        /* @var $attch BOL_Attachment */
        if ( $attch !== null )
        {
            $fileName=$attch->fileName;
            $directory=$this->getAttachmentsDir();
            $filePath = $this->getAttachmentsDir() . $fileName;
            if ( OW::getStorage()->fileExists($filePath) )
            {
                $attch->setFileName('deleted_' . IISSecurityProvider::generateUniqueId() . '_' . $attch->getFileName());
                $this->attachmentDao->save($attch);
                $fileNewPath = $directory . $attch->getFileName();
                OW::getStorage()->renameFile($filePath, $fileNewPath);
//                OW::getStorage()->removeFile($filePath);
                $string=explode(".",$fileName);
                $fileExtention=".".$string[count($string)-1];
                $firstPartofName=str_replace($fileExtention,"",$fileName);
                $pattern=$directory.'/'.$firstPartofName.'*';
                $files = glob($pattern);

                for($i=0;$i<count($files);$i=$i+1){
                    if ( OW::getStorage()->fileExists($files[$i]) ){
                        OW::getStorage()->removeFile($files[$i]);
                    }
                }
            }
            $this->attachmentDao->delete($attch);
            $config=OW::getConfig();
            if($config->configExists('base','totalSize'))
            {
                $config->deleteConfig('base','totalSize');
            }
            $fileSize=BOL_AttachmentService::getInstance()->getTotalAttachmentsSize();
            $config->addConfig('base','totalSize',$fileSize);
        }
    }

//    public function saveTempImage( $id )
//    {
//        $attch = $this->attachmentDao->findById($id);
//        /* @var $attch BOL_Attachment */
//        if ( $attch === null )
//        {
//            return '_INVALID_URL_';
//        }
//
//        $filePath = $this->getAttachmentsTempDir() . $attch->getFileName();
//
//        if ( OW::getUser()->isAuthenticated() && file_exists($filePath) )
//        {
//            OW::getStorage()->copyFile($filePath, $this->getAttachmentsDir() . $attch->getFileName());
//            unlink($filePath);
//        }
//
//        $attch->setStatus(true);
//        $this->attachmentDao->save($attch);
//
//        return OW::getStorage()->getFileUrl($this->getAttachmentsDir() . $attch->getFileName());
//    }
    /*
     * @param array $fileInfo
     * @return array
     * Mohammad Agha Abbasloo $maxUploadSize must be read from config
     */

    public function processPhotoAttachment( $pluginKey, array $fileInfo, $bundle = null, $validFileExtensions = array(), $maxUploadSize = 0 )
    {
        return $this->processUploadedFile($pluginKey, $fileInfo, $bundle, array('jpeg', 'jpg', 'png', 'gif'), null);
    }
    /* attachments 1.6.1 update */

//    public function getAttachmentsTempUrl()
//    {
//        return OW::getPluginManager()->getPlugin('base')->getUserFilesUrl() . 'attachments/temp/';
//    }
//
//    public function getAttachmentsTempDir()
//    {
//        return OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments' . DS . 'temp' . DS;
//    }

    public function getAttachmentsUrl()
    {
        return OW::getStorage()->getFileUrl(OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments') . '/';
    }

    public function getAttachmentsDir()
    {
        return OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments' . DS;
    }

    public function saveAttachment( BOL_Attachment $dto )
    {
        $this->attachmentDao->save($dto);
    }

    public function processUploadedFile( $pluginKey, array $fileInfo, $bundle = null, $validFileExtensions = array(), $maxUploadSize = null, $dimensions = null )
    {
        $language = OW::getLanguage();
        $error = false;
        $fileName=substr($fileInfo['name'],0,strrpos($fileInfo['name'],'.'));
        $explodes = explode('.', $fileInfo['name']);
        $fileInfo['name'] =  $fileName.'.'.strtolower(end($explodes));
        $eventValidateName = OW::getEventManager()->trigger(new OW_Event(IISEventManager::VALIDATE_UPLOADED_FILE_NAME, array('fileName' => $fileInfo['name'])));
        if(isset($eventValidateName->getData()['fileName'])){
                $fileValidName = $eventValidateName->getData()['fileName'];
        }
        if(isset($eventValidateName->getData()['fixedOriginalFileName'])){
            $fileInfo['name'] = $eventValidateName->getData()['fixedOriginalFileName'];
        }
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new InvalidArgumentException($language->text('base', 'user_is_not_authenticated'));
        }

        if ( empty($fileInfo) || (!is_uploaded_file($fileInfo['tmp_name']) && !OW::getStorage()->fileExists($fileInfo['tmp_name'])))
        {
            throw new InvalidArgumentException($language->text('base', 'upload_file_fail'));
        }

        if ( $fileInfo['error'] != UPLOAD_ERR_OK )
        {
            switch ( $fileInfo['error'] )
            {
                case UPLOAD_ERR_INI_SIZE:
                    $error = $language->text('base', 'upload_file_max_upload_filesize_error');
                    break;

                case UPLOAD_ERR_PARTIAL:
                    $error = $language->text('base', 'upload_file_file_partially_uploaded_error');
                    break;

                case UPLOAD_ERR_NO_FILE:
                    $error = $language->text('base', 'upload_file_no_file_error');
                    break;

                case UPLOAD_ERR_NO_TMP_DIR:
                    $error = $language->text('base', 'upload_file_no_tmp_dir_error');
                    break;

                case UPLOAD_ERR_CANT_WRITE:
                    $error = $language->text('base', 'upload_file_cant_write_file_error');
                    break;

                case UPLOAD_ERR_EXTENSION:
                    $error = $language->text('base', 'upload_file_invalid_extention_error');
                    break;

                default:
                    $error = $language->text('base', 'upload_file_fail');
            }

            throw new InvalidArgumentException($error);
        }

        if ( empty($validFileExtensions) )
        {
            $validFileExtensions = json_decode(OW::getConfig()->getValue('base', 'attch_ext_list'), true);
        }

        if ( $maxUploadSize === null )
        {
            $maxUploadSize = OW::getConfig()->getValue('base', 'attch_file_max_size_mb');
        }

        if ( !empty($validFileExtensions) && !in_array(UTIL_File::getExtension($fileInfo['name']), $validFileExtensions) )
        {
            throw new InvalidArgumentException($language->text('base', 'upload_file_extension_is_not_allowed'));
        }

        // get all bundle upload size
        $bundleSize = floor($fileInfo['size'] / 1024);
        if ( $bundle !== null )
        {
            $list = $this->attachmentDao->findAttahcmentByBundle($pluginKey, $bundle);

            /* @var $item BOL_Attachment */
            foreach ( $list as $item )
            {
                $bundleSize += $item->getSize();
            }
        }

        /**
         * commented by Mohammad Agha Abbasloo
         * Bundle is same for all attachments until the user refreshes the page. So no error must be raised in this situation
         */
       /*if ( $maxUploadSize > 0 && $bundleSize > ($maxUploadSize * 1024) )
        {
            throw new InvalidArgumentException($language->text('base', 'upload_file_max_upload_filesize_error'));
        }*/

        $attachDto = new BOL_Attachment();
        $attachDto->setUserId(OW::getUser()->getId());
        $attachDto->setAddStamp(time());
        $attachDto->setStatus(0);
        $attachDto->setSize(floor($fileInfo['size'] / 1024));
        $attachDto->setOrigFileName(htmlspecialchars($fileInfo['name']));
        if(isset($fileValidName)){
            $attachDto->setFileName(IISSecurityProvider::generateUniqueId() . '_' . UTIL_File::sanitizeName($fileValidName));
        }else {
            $attachDto->setFileName(IISSecurityProvider::generateUniqueId() . '_' . UTIL_File::sanitizeName($attachDto->getOrigFileName()));
        }
        $attachDto->setPluginKey($pluginKey);


        if ( $bundle !== null )
        {
            $attachDto->setBundle($bundle);
        }

        $this->attachmentDao->save($attachDto);
        $uploadPath = $this->getAttachmentsDir() . $attachDto->getFileName();
        $tempPath = $this->getAttachmentsDir() . 'temp_' . $attachDto->getFileName();

        if ( in_array(UTIL_File::getExtension($fileInfo['name']), array('jpg', 'jpeg', 'png', 'bmp')) )
        {
            try
            {
                $image = new UTIL_Image($fileInfo['tmp_name']);

                if ( empty($dimensions) )
                {
                    $dimensions = array('width' => UTIL_Image::DIM_FULLSCREEN_WIDTH, 'height' => UTIL_Image::DIM_FULLSCREEN_HEIGHT);
                }

                $image->resizeImage($dimensions['width'], $dimensions['height'])->orientateImage()->saveImage($tempPath);
                $image->destroy();
                OW::getStorage()->removeFile($fileInfo['tmp_name'], true);
            }
            catch ( Exception $e )
            {
                throw new InvalidArgumentException($language->text('base', 'upload_file_fail'));
            }
        }
        else
        {
            if(OW::getStorage()->fileExists($fileInfo['tmp_name']))
            {
                OW::getStorage()->copyFile($fileInfo['tmp_name'], $tempPath,true);
            }
            else {
                OW::getStorage()->moveFile($fileInfo['tmp_name'], $tempPath);
            }
        }

        OW::getStorage()->copyFile($tempPath, $uploadPath);
        OW::getStorage()->removeFile($tempPath);

        return array('uid' => $attachDto->getBundle(), 'dto' => $attachDto, 'path' => $uploadPath, 'url' => $this->getAttachmentsUrl() . $attachDto->getFileName());
    }

    public function getFilesByBundleName( $pluginKey, $bundle )
    {
        $list = $this->attachmentDao->findAttahcmentByBundle($pluginKey, $bundle);

        $resultArray = array();

        /* @var $item BOL_Attachment */
        foreach ( $list as $item )
        {
            $resultArray[] = array('dto' => $item, 'path' => $this->getAttachmentsDir() . $item->getFileName(), 'url' => $this->getAttachmentsUrl() . $item->getFileName());
        }

        return $resultArray;
    }

    /**
     * @param string $bundle
     * @param int $status
     */
    public function updateStatusForBundle( $pluginKey, $bundle, $status )
    {
        $this->attachmentDao->updateStatusByBundle($pluginKey, $bundle, $status);
        if($status==1) {
            $config = OW::getConfig();
            if ($config->configExists('base', 'totalSize')) {
                $config->deleteConfig('base', 'totalSize');
            }
            $fileSize = BOL_AttachmentService::getInstance()->getTotalAttachmentsSize();
            $config->addConfig('base', 'totalSize', $fileSize);
        }
    }

    /**
     * @param int $userId
     * @param int $attachId
     */
    public function deleteAttachment( $userId, $attachId )
    {
        /* @var $attachId BOL_Attachment */
        $attach = $this->attachmentDao->findById($attachId);

        if ( $attach !== null && OW::getUser()->isAuthenticated() && OW::getUser()->getId() == $attach->getUserId() )
        {
            $this->deleteAttachmentById($attach->getId());

            $this->attachmentDao->delete($attach);
        }
    }

    /**
     * @param string $pluginKey
     * @param string $bundle
     */
    public function deleteAttachmentByBundle( $pluginKey, $bundle )
    {
        $attachments = $this->attachmentDao->findAttahcmentByBundle($pluginKey, $bundle);

        /* @var $attach BOL_Attachment */
        foreach ( $attachments as $attach )
        {
            $this->deleteAttachmentById($attach->getId());

            $this->attachmentDao->delete($attach);
        }
    }


    public function getTotalAttachmentsSize()
    {
        return $this->attachmentDao->getTotalAttachmentsSize();
    }
}
