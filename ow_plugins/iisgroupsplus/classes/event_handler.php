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
class IISGROUPSPLUS_CLASS_EventHandler
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
        $eventManager->bind('iisgroupsplus.add_widget', array($service, 'addWidgetToOthers'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::SET_USER_MANAGER_STATUS, array($service, 'setUserManagerStatus'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::CHECK_USER_MANAGER_STATUS, array($service, 'checkUserManagerStatus'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::DELETE_USER_AS_MANAGER, array($service, 'deleteUserAsManager'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::DELETE_FILES, array($service, 'deleteFiles'));
        $eventManager->bind('notifications.collect_actions', array($service, 'onCollectNotificationActions'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::ON_UPDATE_GROUP_STATUS, array($service, 'onUpdateGroupStatus'));
        $eventManager->bind('iisgroupsplus.delete_widget', array($service, 'deleteWidget'));
        $eventManager->bind(OW_EventManager::ON_BEFORE_PLUGIN_DEACTIVATE, array($service, 'pluginDeactivate'));
        $eventManager->bind(OW_EventManager::ON_BEFORE_PLUGIN_UNINSTALL, array($service, 'pluginUninstall'));
        $eventManager->bind('admin.add_auth_labels', array($this, "onCollectAuthLabels"));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::CHECK_CAN_INVITE_ALL, array($service, 'onCanInviteAll'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::ADD_USERS_AUTOMATICALLY, array($service, 'addUsersAutomatically'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::SET_CHANNEL_GROUP, array($service, 'setChannelGroup'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::SET_CHANNEL_FOR_GROUP, array($service, 'setChannelForGroup'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::ON_CHANNEL_ADD_WIDGET, array($service, 'onChannelAddWidget'));
        $eventManager->bind(IISGROUPSPLUS_BOL_Service::ON_CHANNEL_LOAD, array($service, 'onChannelLoad'));
        $eventManager->bind('groups.member_list_page_render', array($service, 'memberListPageRender'));

        $eventManager->bind('groups.invite_user',array($service,'onGroupUserInvitation'));
        $eventManager->bind('notifications.on_item_render', array($service, 'onNotificationRender'));
        $eventManager->bind('iisgroupsplus.is.group.channel', array($service, 'isGroupChannel'));
        $eventManager->bind('feed.on_item_render', array($service, 'feedOnItemRenderActivity'));
        $eventManager->bind('base.add_main_console_item', array($service, 'addConsoleItem'));

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

    public function onCollectAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'iisgroupsplus' => array(
                    'label' => $language->text('iisgroupsplus', 'auth_iisinvite_label'),
                    'actions' => array(
                        'all-search' => $language->text('iisgroupsplus', 'auth_action_label_all_search'),
                        'direct-add' => $language->text('iisgroupsplus', 'auth_action_label_direct_add'),
                        'add-forced-groups' => $language->text('iisgroupsplus', 'auth_action_label_add_forced_groups')
                    )
                )
            )
        );
    }
}