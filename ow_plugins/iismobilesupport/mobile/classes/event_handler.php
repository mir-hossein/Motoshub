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
class IISMOBILESUPPORT_MCLASS_EventHandler
{
    /**
     * @var IISMOBILESUPPORT_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * @return IISMOBILESUPPORT_MCLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct() { }

    public function init()
    {
        $service = IISMOBILESUPPORT_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind('iismobilesupport.save.login.cookie', array($service, 'saveDeviceToken'));
        $eventManager->bind(OW_EventManager::ON_USER_LOGOUT, array($service, 'userLogout'));
        $eventManager->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($service, 'addMobileCss'));
        $eventManager->bind(OW_EventManager::ON_AFTER_ROUTE, array($service, 'checkForUsingOnlyMobile'));
        $eventManager->bind('notifications.on_add', array($service, 'onNotificationAdd'));
        $eventManager->bind('iismobilesupport.browser.information', array($service, 'getBrowserInformation'));
        $eventManager->bind('base.members_only_exceptions', array($service, 'onAddMembersOnlyException'));
        $eventManager->bind('base.password_protected_exceptions', array($service, 'onAddMembersOnlyException'));
        $eventManager->bind('base.delete.expired.login.cookie', array($service, 'deleteDeviceToken'));
        $eventManager->bind('notifications.after_items_viewed', array($service, 'onNotificationViewed'));
        $eventManager->bind('mobile.notification.data.received', array($service, 'onMobileNotificationDataReceived'));
        $eventManager->bind("iismobilesupport.send_message", array($service, "onSendMessage"));
        $eventManager->bind("mailbox.send_message_attachment", array($service, "onSendMessageAttachment"));
        $eventManager->bind("mailbox.mark_conversation", array($service, "onMarkConversation"));
        $eventManager->bind("mailbox.send_message", array($service, "onMailboxSendMessage"));
        $eventManager->bind("on.before.post.request.fail.for.csrf", array($service, "onBeforePostRequestFailForCSRF"));
        $eventManager->bind("before_mobile_validation_redirect", array($service, "onBeforeMobileValidationRedirect"));
        OW::getEventManager()->bind(OW_EventManager::ON_PLUGINS_INIT, array($service, 'onPluginsInit'));
        $eventManager->bind("iissecurityessentials.before_csrf_token_check", array($service, "onBeforeCSRFCheck"));
        $eventManager->bind('iismobilesupport.exclude.catch.request', array($service, 'excludeCatchGetInformationRequest'));
        $eventManager->bind("mailbox.after_message_removed", array($service, "onAfterMessageRemoved"));

        /* extracted from iiswidgetplus */
        $eventManager->bind('iiswidgetplus.general.before.view.render', array($service, 'generalBeforeViewRender'));
        $eventManager->bind(IISEventManager::ON_BEFORE_GROUP_VIEW_RENDER, array($service, 'beforeGroupViewRender'));
        $eventManager->bind(IISEventManager::ON_BEFORE_NEWS_VIEW_RENDER, array($service, 'beforeNewsViewRender'));
        $eventManager->bind(IISEventManager::ON_BEFORE_VIDEO_RENDER, array($service, 'beforeVideoViewRender'));
        $eventManager->bind(IISEventManager::ON_BEFORE_PHOTO_RENDER, array($service, 'beforePhotoViewRender'));
        $eventManager->bind('iis.on.before.competition.view.render', array($service, 'beforeCompetitionViewRender'));
        $eventManager->bind('iis.on.before.event.view.render', array($service, 'beforeEventViewRender'));
        $eventManager->bind('iis.on.before.profile.pages.view.render', array($service, 'beforeProfilePagesViewRender'));
        $eventManager->bind('iis.on.before.group.forum.view.render', array($service, 'beforeGroupForumViewRender'));
        $eventManager->bind("iisuserlogin.before_delete_session", array($service, "onBeforeSessionDelete"));
        $eventManager->bind("iissecurityessentials.before_checking_idle", array($service, "onBeforeSessionDelete"));
        $eventManager->bind('iis.on.before.group.forum.topic.view.render', array($service, 'beforeGroupForumTopicViewRender'));
        $eventManager->bind(IISEventManager::ON_AFTER_RABITMQ_QUEUE_RELEASE, array($service, "onRabbitMQNotificationRelease"));
        $eventManager->bind('check.url.webservice', array($service, "checkUrlIsWebServiceEvent"));
        /****************************************************/


    }
}