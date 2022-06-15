<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Mohammad Aghaabbasloo
 * @package ow_plugins.iisvideoplus.bol
 * @since 1.0
 */
class IISVIDEOPLUS_MCLASS_EventHandler
{
    /**
     * @var IISVIDEOPLUS_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * @return IISVIDEOPLUS_MCLASS_EventHandler
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
        $masterPlugin = BOL_PluginService::getInstance()->findPluginByKey('video');
        if( !isset($masterPlugin) || !$masterPlugin->isActive() ){
            return;
        }
        $service = IISVIDEOPLUS_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(IISEventManager::ADD_LIST_TYPE_TO_VIDEO, array($service, 'addListTypeToVideo'));
        $eventManager->bind(IISEventManager::GET_RESULT_FOR_LIST_ITEM_VIDEO, array($service, 'getResultForListItemVideo'));
        $eventManager->bind(IISEventManager::GET_RESULT_FOR_COUNT_ITEM_VIDEO, array($service, 'getResultForCountItemVideo'));
        $eventManager->bind(IISEventManager::SET_TILE_HEADER_LIST_ITEM_VIDEO, array($service, 'setTtileHeaderListItemVideo'));
        $eventManager->bind(IISEventManager::ON_BEFORE_VIDEO_UPLOAD_FORM_RENDERER, array($service, 'onBeforeVideoUploadFormRenderer'));
        $eventManager->bind(IISEventManager::ON_BEFORE_VIDEO_UPLOAD_COMPONENT_RENDERER, array($service, 'onBeforeVideoUploadComponentRenderer'));
        OW::getEventManager()->bind(IISVIDEOPLUS_BOL_Service::EVENT_AFTER_ADD, array($service, "onAfterEntryAdd"));
        OW::getEventManager()->bind(IISVIDEOPLUS_BOL_Service::ON_VIDEO_VIEW_RENDER, array($service, "onVideoViewRender"));
        $eventManager->bind(IISVIDEOPLUS_BOL_Service::ON_BEFORE_VIDEO_ADD, array($service, 'onBeforeVideoAdded'));
        OW::getEventManager()->bind(IISVIDEOPLUS_BOL_Service::ON_VIDEO_LIST_VIEW_RENDER, array($service, "onVideoListViewRender"));
        OW::getEventManager()->bind(IISVIDEOPLUS_BOL_Service::ADD_VIDEO_DOWNLOAD_LINK, array($service, "addVideoDownloadLink"));
        OW::getEventManager()->bind('get.video.thumbnail', array($service, "getVideoThumbnail"));
    }

}