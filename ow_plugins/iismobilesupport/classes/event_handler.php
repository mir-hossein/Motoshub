<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 * 
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iismobilesupport.bol
 * @since 1.0
 */
class IISMOBILESUPPORT_CLASS_EventHandler
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
    
    public function init()
    {
        $service = IISMOBILESUPPORT_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        //$eventManager->bind('iismobilesupport.save.login.cookie', array($service, 'saveDeviceToken'));
        //$eventManager->bind(OW_EventManager::ON_USER_LOGOUT, array($service, 'userLogout'));
        //$eventManager->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($service, 'addMobileCss'));
        $eventManager->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($service, 'showDownloadLinks'));
        $eventManager->bind(OW_EventManager::ON_AFTER_ROUTE, array($service, 'checkForUsingOnlyMobile'));
        $eventManager->bind('notifications.on_add', array($service, 'onNotificationAdd'));
        $eventManager->bind('iismobilesupport.browser.information', array($service, 'getBrowserInformation'));
        $eventManager->bind('admin.add_auth_labels', array($service, "onCollectAuthLabels"));
        $eventManager->bind('base.password_protected_exceptions', array($service, 'onAddMembersOnlyException'));
        $eventManager->bind('base.members_only_exceptions', array($service, 'onAddMembersOnlyException'));
        $eventManager->bind('base.delete.expired.login.cookie', array($service, 'deleteDeviceToken'));
        $eventManager->bind('notifications.after_items_viewed', array($service, 'onNotificationViewed'));
        $eventManager->bind("on.before.post.request.fail.for.csrf", array($service, "onBeforePostRequestFailForCSRF"));
        $eventManager->bind("before_mobile_validation_redirect", array($service, "onBeforeMobileValidationRedirect"));
        $eventManager->bind("iismobilesupport.send_message", array($service, "onSendMessage"));
        $eventManager->bind("mailbox.send_message_attachment", array($service, "onSendMessageAttachment"));
        $eventManager->bind("mailbox.mark_conversation", array($service, "onMarkConversation"));
        $eventManager->bind("mailbox.send_message", array($service, "onMailboxSendMessage"));
        OW::getEventManager()->bind(OW_EventManager::ON_PLUGINS_INIT, array($service, 'onPluginsInit'));
        $eventManager->bind("iissecurityessentials.before_csrf_token_check", array($service, "onBeforeCSRFCheck"));
        $eventManager->bind("iisuserlogin.before_delete_session", array($service, "onBeforeSessionDelete"));
        $eventManager->bind("iissecurityessentials.before_checking_idle", array($service, "onBeforeSessionDelete"));
        $eventManager->bind("mailbox.after_message_removed", array($service, "onAfterMessageRemoved"));
        $eventManager->bind(IISEventManager::ON_AFTER_RABITMQ_QUEUE_RELEASE, array($service, "onRabbitMQNotificationRelease"));
        $eventManager->bind('base.append_markup', array($service, "addJSForWebNotifications"));
    }
}