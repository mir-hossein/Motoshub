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
 * Mail Service
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
class BOL_MailService
{
    const MAIL_COUNT_PER_CRON_JOB = 50;
    const TRANSFER_SMTP = 'smtp';
    const TRANSFER_MAIL = 'mail';
    const TRANSFER_SENDMAIL = 'sendmail';
    const EVENT_AFTER_ADD_TO_QUEUE = 'base.after_add_to_queue';

    /**
     *
     * @var BOL_MailDao
     */
    private $mailDao;
    private $defaultMailSettingList = array();

    private function __construct()
    {
        $this->mailDao = BOL_MailDao::getInstance();

        $siteName = OW::getConfig()->getValue('base', 'site_name');
        $siteEmail = OW::getConfig()->getValue('base', 'site_email');
        //override - send using smtp email
        $siteEmail = OW::getConfig()->getValue('base', 'mail_smtp_user');
        $senderSuffix = defined('OW_SENDER_MAIL_SUFFIX') ? OW_SENDER_MAIL_SUFFIX : null;

        $this->defaultMailSettingList = array(
            'sender' => array($siteEmail, $siteName),
            'senderSuffix' => intval($senderSuffix)
        );
    }
    /**
     * Class instance
     *
     * @var BOL_MailService
     */
    private static $classInstance;

    /**
     *
     * @var PHPMailer
     */
    private $phpMailer;

    /**
     * Returns class instance
     *
     * @return BOL_MailService
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     *
     * @return PHPMailer
     */
    public function getMailer()
    {
        if ( !isset($this->phpMailer) )
        {
            $this->phpMailer = $this->initializeMailer($this->getTransfer());
        }

        return $this->phpMailer;
    }

    /**
     *
     * @return PHPMailer
     */
    private function initializeMailer( $transfer )
    {
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        $event=OW_EventManager::getInstance()->trigger(new OW_Event('check.verifypeer.phpmailer',array()));
        if(isset($event->getData()['disable_verify_peer']) && $event->getData()['disable_verify_peer']==true)
        {
            $mailer->SMTPOptions = array('ssl' => array('verify_peer'=>false,'verify_peer_name' => false, 'allow_self_signed' => true));
        }else {
            $mailer->SMTPOptions = array('ssl' => array('verify_peer_name' => false, 'allow_self_signed' => true));
        }

        switch ( $transfer )
        {
            case self::TRANSFER_SMTP :
                $this->smtpSetup($mailer);
                break;
            case self::TRANSFER_SENDMAIL :
                $mailer->IsSendmail();
                break;
            case self::TRANSFER_MAIL :
                $mailer->IsMail();
                break;
        }

        $mailer->CharSet = "utf-8";

        return $mailer;
    }

    public function getTransfer()
    {
        if ( OW::getConfig()->getValue('base', 'mail_smtp_enabled') )
        {
            return self::TRANSFER_SMTP;
        }

        return self::TRANSFER_MAIL;
    }

    private function getSMTPSettingList()
    {
        $configs = OW::getConfig()->getValues('base');

        return array(
            'connectionPrefix' => $configs['mail_smtp_connection_prefix'],
            'host' => $configs['mail_smtp_host'],
            'port' => $configs['mail_smtp_port'],
            'user' => $configs['mail_smtp_user'],
            'password' => $configs['mail_smtp_password']
        );
    }

    /**
     *
     * @param PHPMailer $mailer
     */
    private function smtpSetup( $mailer )
    {
        $settingList = $this->getSMTPSettingList();

        $mailer->SMTPSecure = $settingList['connectionPrefix'];
        $eventSMTPCheck = new OW_Event('smtp.disable.tls');
        OW::getEventManager()->trigger($eventSMTPCheck);
        if(isset($eventSMTPCheck->getData()['disable'])){
            $mailer->SMTPAutoTLS = false;
        }
        $mailer->IsSMTP();
        $mailer->SMTPAuth = true;
        $mailer->SMTPKeepAlive = true;
        $mailer->Host = $settingList['host'];

        if ( !empty($settingList['port']) )
        {
            $mailer->Port = (int) $settingList['port'];
        }

        $mailer->Username = $settingList['user'];
        $mailer->Password = $settingList['password'];
    }

    public function smtpTestConnection()
    {
        if ( $this->getTransfer() !== self::TRANSFER_SMTP )
        {
            throw new LogicException('Mail transfer is not SMTP');
        }

        $mailer = $this->getMailer();

        try
        {
            return $mailer->SmtpConnect();
        }
        catch ( phpmailerException $e )
        {
            throw new InvalidArgumentException($e->getMessage());
        }
    }

    /**
     *
     * @return BASE_CLASS_Mail
     */
    public function createMail()
    {
        $mail = new BASE_CLASS_Mail($this->defaultMailSettingList);

        return $mail;
    }

    private function createMailFromDto( BOL_Mail $mailDto )
    {
        $mail = new BASE_CLASS_Mail();
        $mail->addRecipientEmail($mailDto->recipientEmail);
        $mail->setSender($mailDto->senderEmail, $mailDto->senderName);
        $mail->setSubject($mailDto->subject);
        $mail->setTextContent($mailDto->textContent);
        $mail->setHtmlContent($mailDto->htmlContent);
        $mail->setSentTime($mailDto->sentTime);
        $mail->setPriority($mailDto->priority);
        $mail->setSenderSuffix($mailDto->senderSuffix);

        return $mail;
    }

    private function prepareFromEmail( $email, $suffix )
    {
        if ( empty($email) )
        {
            return null;
        }

        $suffix = intval($suffix);

        if ( empty($suffix) )
        {
            return $email;
        }

        list($user, $provider) = explode('@', $email);

        return $user . '+' . $suffix . '@' . $provider;
    }

    public function send( BASE_CLASS_Mail $mail )
    {
        $mailer = $this->getMailer();
        /*
         * Mohammad Agha Abbasloo
         * this code is moved here
         */
        $mailer->ClearReplyTos();
        $mailer->ClearAllRecipients();
        $mailState = $mail->saveToArray();
        $event = new OW_Event('base.mail_service.send.check_mail_state', array('recipientEmailList'=>$mailState['recipientEmailList'],'mailState'=>$mailState), $mailState);
        OW::getEventManager()->trigger($event);
        $mailState = $event->getData();

        if ( empty($mailState['recipientEmailList']) )
        {
            return false;
        }
        $fromEmail = $this->prepareFromEmail($mailState['sender'][0], $mailState['senderSuffix']);
    try {
        $mailer->SetFrom($fromEmail, $mailState['sender'][1]);
    }
    catch(Exception $e){
        return false;
    }
        $mailer->Sender = $mailState['sender'][0];

        if ( !empty($mailState['replyTo']) )
        {
            $mailer->AddReplyTo($mailState['replyTo'][0], $mailState['replyTo'][1]);
        }
        foreach ( $mailState['recipientEmailList'] as $item )
        {
            $mailer->AddAddress($item);
        }

        $isHtml = !empty($mailState['htmlContent']);

        $mailer->Subject = $mailState['subject'];
        $mailer->IsHTML($isHtml);
        $mailer->Body = $isHtml ? $mailState['htmlContent'] : $mailState['textContent'];
        $mailer->AltBody = $isHtml ? $mailState['textContent'] : '';
        try {
            $result = $mailer->Send();
        }
        catch(Exception $e){
            /*
             * Mohammad Agha Abbasloo
             * code: return false; is replaced by  $result = false; to allow the rest of the code executed (It's necessary to clear recipients list)
             */
            $result = false;
        }
        /*
         * this place was the orginal place of codes:
         * $mailer->ClearReplyTos();
         * $mailer->ClearAllRecipients();
         */
        return $result;
    }

    private function mailToDtoList( BASE_CLASS_Mail $mail )
    {
        $mailState = $mail->saveToArray();
        $mailStateEvent = new OW_Event('base_before_email_create', array('mailState' => $mailState));
        OW::getEventManager()->trigger($mailStateEvent);
        if(isset($mailStateEvent->getData()['mailState'])){
            $mailState = $mailStateEvent->getData()['mailState'];
        }
        $resultList = array();

        foreach ( $mailState['recipientEmailList'] as $email )
        {
            $mailDto = new BOL_Mail();

            $mailDto->senderEmail = $mailState['sender'][0];
            $mailDto->senderName = $mailState['sender'][1];
            $mailDto->subject = $mailState['subject'];
            $mailDto->textContent = $mailState['textContent'];
            $mailDto->htmlContent = $mailState['htmlContent'];
            $mailDto->sentTime = empty($mailState['sentTime']) ? time() : $mailState['sentTime'];
            $mailDto->priority = $mailState['priority'];
            $mailDto->recipientEmail = $email;
            $mailDto->senderSuffix = intval($mailState['senderSuffix']);

            $resultList[] = $mailDto;
        }

        return $resultList;
    }

    public function addToQueue( BASE_CLASS_Mail $mail )
    {
        $dtoList = $this->mailToDtoList($mail);

        foreach ( $dtoList as $dtoMail )
        {
            $this->mailDao->save($dtoMail);
        }

        OW::getEventManager()->trigger(new OW_Event(self::EVENT_AFTER_ADD_TO_QUEUE));
    }

    public function addListToQueue( array $mailList )
    {
        $fullDtoList = array();

        foreach ( $mailList as $mail )
        {
            $dtoList = $this->mailToDtoList($mail);

            foreach ( $dtoList as $mailDto )
            {
                $fullDtoList[] = $mailDto;
            }
        }

        if ( !empty($fullDtoList) )
        {
            $this->mailDao->saveList($fullDtoList);
        }

        OW::getEventManager()->trigger(new OW_Event(self::EVENT_AFTER_ADD_TO_QUEUE));
    }

    public function processQueue( $count = self::MAIL_COUNT_PER_CRON_JOB )
    {
        $list = $this->mailDao->findList($count);

        $processedIdList = array();

        foreach ( $list as $item )
        {
            try
            {
                $mail = $this->createMailFromDto($item);
                $this->send($mail);
            }
            catch ( Exception $e )
            {
                //Skip invalid email adresses
            }
            $this->mailDao->updateSentStatus($item->id);

        }

        $this->mailDao->deleteSentMails();
}

    public function getEmailDomain()
    {
        switch ( $this->getTransfer() )
        {
            case self::TRANSFER_SMTP:
                $settings = $this->getSMTPSettingList();
                return $settings['host'];

            default:
                $urlInfo = parse_url(OW_URL_HOME);
                return $urlInfo['host'];
        }
    }

    public function deleteQueuedMailsByRecipientId( $userId )
    {
        $user = BOL_UserService::getInstance()->findUserById($userId);

        if ( $user === null )
        {
            return;
        }

        $this->mailDao->deleteByRecipientEmail($user->email);
    }

    public function __destruct()
    {
        $this->getMailer()->SmtpClose();
    }
}
