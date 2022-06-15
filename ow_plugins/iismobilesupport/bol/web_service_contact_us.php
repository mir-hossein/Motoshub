<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Mohammad Agha Abbasloo <a.mohammad85@gmail.com>
 * @package ow_plugins.iismobilesupport.bol
 * @since 1.0
 */
class IISMOBILESUPPORT_BOL_WebServiceContactUs
{
    private static $classInstance;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
    }


    public function processSendContactUsMessage(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iiscontactus', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated())
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $data=array();

        if(!isset($_POST['subject'])){
            return array('valid' => false, 'message' => 'input_error');
        }
        if(!isset($_POST['message'])){
            return array('valid' => false, 'message' => 'input_error');
        }

        $subject=$_POST['subject'];
        $message=$_POST['message'];
        $receiverEmail = OW::getConfig()->getValue('base', 'site_email');
        $mail = OW::getMailer()->createMail();
        $mail->addRecipientEmail($receiverEmail);
        $mail->setSender(OW::getUser()->getEmail());
        $mail->setSenderSuffix(false);
        $mail->setSubject($subject);
        $mail->setTextContent($message);
        $mail->setHtmlContent($message);
        $iiscontactus = IISCONTACTUS_BOL_Service::getInstance();
        $iiscontactus->addUserInformation($subject,OW::getUser()->getEmail(),OW::getLanguage()->text('admin','site_email'),$message);
        OW::getMailer()->addToQueue($mail);
        return array('valid' => true);
    }
    
}