<?php

/**
 * Copyright (c) 2017, Mohammad Agha Abbasloo
 * All rights reserved.
 */


class IISNEWSFEEDPLUS_MCLASS_EventHandler
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
        $masterPlugin = BOL_PluginService::getInstance()->findPluginByKey('newsfeed');
        if( !isset($masterPlugin) || !$masterPlugin->isActive() ){
            return;
        }
        $service = IISNEWSFEEDPLUS_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(IISEventManager::ON_AFTER_UPDATE_STATUS_FORM_RENDERER, array($service, 'addAttachmentInputFieldsToNewsfeed'));
        $eventManager->bind('feed.on_entity_action', array($service,'saveAttachments'));
        $eventManager->bind(IISEventManager::ON_FEED_ITEM_RENDERER, array($service, 'appendAttachmentsToFeed'));
        $eventManager->bind('feed.before_action_delete', array($service, "onBeforeActionDelete"));
        $eventManager->bind(IISEventManager::ON_BEFORE_UPDATE_STATUS_FORM_RENDERER, array($service, 'onBeforeUpdateStatusFormRenderer'));
        $eventManager->bind('newsfeed.generic_item_render', array($service, 'genericItemRender'));
        $eventManager->bind('attachment.add.parameters',array($service,'attachmentAddParameters'));
        $eventManager->bind('newsfeed.after_status_component_addition', array($service, 'afterStatusComponentAddition'));
        $eventManager->bind('change.newsfeed.action.query', array($service, 'changeNewsfeedActionQuery'));
        $eventManager->bind('newsfeed.can_forward_post', array($service, 'canForwardPostEvent'));
    }

}