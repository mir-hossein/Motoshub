<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisblockingip.bol
 * @since 1.0
 */
class IISBLOCKINGIP_CLASS_EventHandler
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

    private $service;

    private function __construct()
    {
        $this->service = IISBLOCKINGIP_BOL_Service::getInstance();
    }

    public function init()
    {
        $eventManager = OW::getEventManager();
        $service = IISBLOCKINGIP_BOL_Service::getInstance();
        $eventManager->bind('base.bot_detected', array($service, 'onTrackAttempt'));
        $eventManager->bind('base.splash_screen_exceptions', array($service, 'catchAllRequestsExceptions'));
        $eventManager->bind('base.members_only_exceptions', array($service, 'catchAllRequestsExceptions'));
        $eventManager->bind(OW_EventManager::ON_AFTER_ROUTE, array($service, 'onAfterRoute'));
        $eventManager->bind(IISEventManager::ON_BEFORE_FORM_SIGNIN_RENDER, array($service, 'onBeforeFormSigninRender'));
        $eventManager->bind(IISEventManager::ON_USER_AUTH_FAILED, array($service, 'onUserAuthFailed'));
        $eventManager->bind(OW_EventManager::ON_USER_LOGIN, array($service, 'onUserLogin'));
        $eventManager->bind(IISEventManager::ON_CAPTCHA_VALIDATE_FAILED, array($service, 'onUserCaptchaValidateFailed'));
        $eventManager->bind(IISEventManager::ON_AFTER_SIGNIN_FORM_CREATED, array($service, 'onAfterSigninFormCreated'));

        $eventManager->bind('iismobilesupport.on.login.attempt', array($service, 'onWebServiceLoginAttempt'));
        $eventManager->bind('iismobilesupport.on.login.success', array($service, 'onWebServiceLoginSuccess'));
        $eventManager->bind('iismobilesupport.on.login.failed', array($service, 'onWebServiceLoginFailed'));
    }

}