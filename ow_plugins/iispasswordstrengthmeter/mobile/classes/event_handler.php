<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 * 
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iispasswordstrengthmeter.bol
 * @since 1.0
 */
class IISPASSWORDSTRENGTHMETER_MCLASS_EventHandler
{
    private static $classInstance;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
    }

    public function init()
    {
        $service = IISPASSWORDSTRENGTHMETER_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($service, 'onAfterDocumentRenderer'));
        $eventManager->bind(IISEventManager::ON_PASSWORD_VALIDATION_IN_JOIN_FORM, array($service, 'onPasswordValidationInJoinForm'));
        $eventManager->bind(IISEventManager::GET_PASSWORD_REQUIREMENT_PASSWORD_STRENGTH_INFORMATION, array($service, 'getMinimumReqirementPasswordStrengthInformation'));;
    }
}