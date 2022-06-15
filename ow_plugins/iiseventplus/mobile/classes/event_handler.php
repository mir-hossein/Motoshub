<?php

/**
 * Copyright (c) 2016, Mohammad Agha Abbasloo
 * All rights reserved.
 */

/**
 * 
 *
 * @author Mohammad Agha Abbasloo <a.mohammad85@gmail.com>
 * @package ow_plugins.iiseventplus.bol
 * @since 1.0
 */
class IISEVENTPLUS_MCLASS_EventHandler
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
        $masterPlugin = BOL_PluginService::getInstance()->findPluginByKey('event');
        if( !isset($masterPlugin) || !$masterPlugin->isActive() ){
            return;
        }
        $service = IISEVENTPLUS_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(IISEventManager::ADD_LIST_TYPE_TO_EVENT, array($service, 'addListTypeToEvent'));
        $eventManager->bind(IISEventManager::GET_RESULT_FOR_LIST_ITEM_EVENT, array($service, 'getResultForListItemEvent'));
        $eventManager->bind(IISEventManager::SET_TITLE_HEADER_LIST_ITEM_EVENT, array($service, 'setTitleHeaderListItemEvent'));
        $eventManager->bind(IISEventManager::ADD_EVENT_FILTER_FORM, array($service, 'addEventFilterForm'));
        $eventManager->bind(IISEventManager::ADD_LEAVE_BUTTON, array($service, 'addLeaveButton'));
        $eventManager->bind(IISEventManager::ADD_CATEGORY_FILTER_ELEMENT, array($service, 'addCategoryFilterElement'));
        $eventManager->bind(IISEventManager::GET_EVENT_SELECTED_CATEGORY_ID, array($service, 'getEventSelectedCategoryId'));
        $eventManager->bind(IISEventManager::ADD_CATEGORY_TO_EVENT, array($service, 'addCategoryToEvent'));
        $eventManager->bind(IISEventManager::GET_EVENT_SELECTED_CATEGORY_LABEL, array($service, 'getEventSelectedCategoryLabel'));
        $eventManager->bind(IISEVENTPLUS_BOL_Service::ADD_FILTER_PARAMETERS_TO_PAGING, array($service, "addFilterParametersToPaging"));
        $eventManager->bind(IISEVENTPLUS_BOL_Service::CHECK_IF_EVENTPLUS_IS_ACTIVE , array($service, "checkIfEventPlusIsActive"));
        $eventManager->bind(IISEVENTPLUS_BOL_Service::DELETE_FILES, array($service, 'deleteFiles'));
        $eventManager->bind(IISEVENTPLUS_BOL_Service::ADD_FILE_WIDGET, array($service, 'addFileWidget'));
        OW::getEventManager()->bind('notifications.collect_actions', array($service, 'onCollectNotificationActions'));
        OW::getEventManager()->bind('mobile.notifications.on_item_render', array($this, 'onNotificationRender'));

        $eventManager->bind('event.invite_user',array($service,'onInviteUser'));
        OW::getEventManager()->bind('feed.on_item_render', array($service,'feedOnItemRender'));
    }

    public function onNotificationRender( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['pluginKey'] != 'event' ||  ($params['entityType'] != 'event-add-file' && $params['entityType'] != 'event_invitation'))
        {
            return;
        }
        if($params['entityType'] == 'event_invitation'){
            $data = $params['data'];
            $e->setData($data);
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

        //Notification on click logic is set here
        $event = new OW_Event('mobile.notification.data.received', array('pluginKey' => $params['pluginKey'],
            'entityType' => $params['entityType'],
            'data' => $data));
        OW::getEventManager()->trigger($event);
        if(isset($event->getData()['url'])){
            $data['url']=$event->getData()['url'];
        }

        $e->setData($data);
    }
}