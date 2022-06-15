<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/25/18
 * Time: 2:50 PM
 */

class IISQUESTIONS_CLASS_EventHandler
{

    public function __construct()
    {
    }
    public function init()
    {
        $eventManager = OW::getEventManager();
        $service = IISQUESTIONS_BOL_Service::getInstance();
        $credits = new IISQUESTIONS_CLASS_Credits();
        $eventManager->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));
        $eventManager->bind('usercredits.get_action_key', array($credits, 'getActionKey'));
        $eventManager->bind('notifications.collect_actions', array($service, 'onNotifyActions'));
        $eventManager->bind(IISEventManager::ON_AFTER_UPDATE_STATUS_FORM_RENDERER, array($service, 'addButtonToNewsfeed'));
        $eventManager->bind(IISEventManager::ON_BEFORE_UPDATE_STATUS_FORM_RENDERER, array($service, 'addInputFieldsToNewsfeed'));
        $eventManager->bind('feed.after_activity', array($service, 'feedAdded'));
        $eventManager->bind(IISEventManager::ON_FEED_ITEM_RENDERER, array($service, 'onFeedRender'));
        $eventManager->bind('newsfeed.generic_item_render', array($service, 'genericItemRender'));
        $eventManager->bind('feed.before_action_delete', array($service, 'deleteAction'));
        $eventManager->bind('feed.on_entity_action', array($service, 'onEntityAction'));
        $eventManager->bind('admin.add_auth_labels', array($service, 'onCollectAuthLabels'));
        $eventManager->bind('base.on.before.forward.status.create', array($service, 'onForward'));
    }
}
