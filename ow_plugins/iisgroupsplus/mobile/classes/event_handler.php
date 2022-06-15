<?php

/**
 * Copyright (c) 2016, Mohammad Agha Abbasloo
 * All rights reserved.
 */

/**
 * 
 *
 * @author Mohammad Agha Abbasloo <a.mohammad85@gmail.com>
 * @package ow_plugins.iisgroupsplus.bol
 * @since 1.0
 */
class IISGROUPSPLUS_MCLASS_EventHandler
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
        $masterPlugin = BOL_PluginService::getInstance()->findPluginByKey('groups');
        if( !isset($masterPlugin) || !$masterPlugin->isActive() ){
            return;
        }
        $service = IISGROUPSPLUS_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(IISEventManager::GET_RESULT_FOR_LIST_ITEM_GROUP, array($service, 'getResultForListItemGroup'));
        $eventManager->bind(IISEventManager::ADD_GROUP_FILTER_FORM, array($service, 'addGroupFilterForm'));
        $eventManager->bind(IISEventManager::ADD_GROUP_CATEGORY_FILTER_ELEMENT, array($service, 'addGroupCategoryFilterElement'));
        $eventManager->bind(IISEventManager::GET_GROUP_SELECTED_CATEGORY_ID, array($service, 'getGroupSelectedCategoryId'));
        $eventManager->bind(IISEventManager::ADD_CATEGORY_TO_GROUP, array($service, 'addCategoryToGroup'));
        $eventManager->bind(IISEventManager::GET_GROUP_SELECTED_CATEGORY_LABEL, array($service, 'getGroupSelectedCategoryLabel'));
        $eventManager->bind('groups.on_toolbar_collect', array($service, "onGroupToolbarCollect"));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::SET_MOBILE_USER_MANAGER_STATUS, array($service, 'setMobileUserManagerStatus'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::CHECK_USER_MANAGER_STATUS, array($service, 'checkUserManagerStatus'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::DELETE_USER_AS_MANAGER, array($service, 'deleteUserAsManager'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::DELETE_FILES, array($service, 'deleteFiles'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::ADD_FILE_WIDGET, array($service, 'addFileWidget'));
        $eventManager->bind('notifications.collect_actions', array($service, 'onCollectNotificationActions'));
        $eventManager->bind('mobile.notifications.on_item_render', array($this, 'onNotificationRender'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::ON_UPDATE_GROUP_STATUS, array($service, 'onUpdateGroupStatus'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::CHECK_CAN_INVITE_ALL, array($service, 'onCanInviteAll'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::ADD_USERS_AUTOMATICALLY, array($service, 'addUsersAutomatically'));
        $eventManager->bind('groups.member_list_page_render', array($service, 'memberListPageRender'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::SET_CHANNEL_GROUP, array($service, 'setChannelGroup'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::SET_CHANNEL_FOR_GROUP, array($service, 'setChannelForGroup'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::ON_CHANNEL_ADD_WIDGET, array($service, 'onChannelAddWidget'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::ON_CHANNEL_LOAD, array($service, 'onChannelLoad'));
        $eventManager->bind('iisgroupsplus.is.group.channel', array($service, 'isGroupChannel'));
        $eventManager->bind('groups.invite_user',array($service,'onGroupUserInvitation'));
        $eventManager->bind('feed.on_item_render', array($service, 'feedOnItemRenderActivity'));
        $eventManager->bind(OW_EventManager::ON_USER_REGISTER, array($service, 'onUserRegistered'));
        $eventManager->bind('groups.before.user.leave', array($service, 'onBeforeUserLeave'));
        $eventManager->bind('base_add_comment', array($service, 'onCommentNotification'));
        $eventManager->bind('base_delete_comment', array($service, 'deleteComment'));
        $eventManager->bind(OW_EventManager::ON_USER_UNREGISTER, array($service, 'onUnregisterUser'));
        $eventManager->bind('add.group.setting.elements', array($service, 'addGroupSettingElements'));
        $eventManager->bind('set.group.setting', array($service, 'setGroupSetting'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::DELETE_FILES, array($service, 'deleteGroupSetting'));
        $eventManager->bind('can.create.topic', array($service, 'canCreateTopic'));
        $eventManager->bind('can.upload.in.file.widget', array($service, 'canUploadInFileWidget'));
    }


    public function onNotificationRender( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['pluginKey'] != 'groups'|| ($params['entityType'] != 'groups-add-file' && $params['entityType'] != 'groups-status' && $params['entityType']!='user_invitation' && $params['entityType']!='groups-join' && $params['entityType'] != 'photo_comments' && $params['entityType'] != 'multiple_photo_upload' && $params['entityType'] != 'groups-update-status'))
        {
            return;
        }

        if($params['entityType'] == 'user_invitation'){
            $data = $params['data'];
            $e->setData($data);
            return;
        }

        $data = $params['data'];

        if ( !isset($data['avatar']['urlInfo']['vars']['username']) )
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
        
        $userService = BOL_UserService::getInstance();
        $user = $userService->findByUsername($data['avatar']['urlInfo']['vars']['username']);
        if ( !$user )
        {
            return;
        }
        $e->setData($data);
    }
}