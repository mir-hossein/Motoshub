<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 * 
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iispasswordchangeinterval.bol
 * @since 1.0
 */
class IISPASSWORDCHANGEINTERVAL_MCLASS_EventHandler
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
        $service = IISPASSWORDCHANGEINTERVAL_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(OW_EventManager::ON_AFTER_ROUTE, array($service, 'onAfterRoute'));
        $eventManager->bind(IISEventManager::ON_AFTER_PASSWORD_UPDATE, array($service, 'onAfterPasswordUpdate'));
        $eventManager->bind(IISEventManager::ON_BEFORE_RESET_PASSWORD_FORM_RENDERER, array($service, 'onBeforeResetPasswordFormRenderer'));
        $eventManager->bind('base.members_only_exceptions', array($service, 'catchAllRequestsExceptions'));
        $eventManager->bind(OW_EventManager::ON_USER_REGISTER, array($service, 'onUserRegistered'));
        OW::getEventManager()->bind('mobile.notifications.on_item_render', array($this, 'onNotificationRender'));
    }

    public function onNotificationRender( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['pluginKey'] != 'iispasswordchangeinterval'|| $params['entityType'] != 'iispasswordchangeinterval' )
        {
            return;
        }

        $data = $params['data'];

        if ( !isset($data['avatar']['urlInfo']['vars']['username']) )
        {
            return;
        }

        $userService = BOL_UserService::getInstance();
        $user = $userService->findByUsername($data['avatar']['urlInfo']['vars']['username']);
        if ( !$user )
        {
            return;
        }
        $iisprofilemanagementPlugin = BOL_PluginService::getInstance()->findPluginByKey('iisprofilemanagement');
        if(isset($iisprofilemanagementPlugin) && $iisprofilemanagementPlugin->isActive()) {
            $data['string'] = OW::getLanguage()->text('iispasswordchangeinterval', 'description_change_password', array('value' => OW::getRouter()->urlForRoute('iisprofilemanagement.edit')));
        }

        //Notification on click logic is set here
        $event = new OW_Event('mobile.notification.data.received', array('pluginKey' => $params['pluginKey'],
            'entityType' => $params['entityType'],
            'data' => $data));
        OW::getEventManager()->trigger($event);
        if(isset($event->getData()['url'])){
            $data['url']=$event->getData()['url'];
        }

        if(isset($iisprofilemanagementPlugin) && $iisprofilemanagementPlugin->isActive()){
            $e->setData($data);
        }
    }
}