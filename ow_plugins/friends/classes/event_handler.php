<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow.ow_plugins.friends.classes
 * @since 1.6.0
 */
class FRIENDS_CLASS_EventHandler
{
    /**
     * Singleton instance.
     *
     * @var FRIENDS_CLASS_EventHandler
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return FRIENDS_CLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     *
     * @var FRIENDS_BOL_Service
     */
    private $service;

    private function __construct()
    {
        $this->service = FRIENDS_BOL_Service::getInstance();
    }

    public function genericInit()
    {
        OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER,        array($this,'onUnregisterUser'));
        OW::getEventManager()->bind(OW_EventManager::ON_USER_BLOCK,             array($this,'onBlockUser'));

        OW::getEventManager()->bind('notifications.collect_actions',            array($this,'onCollectNotificationActions'));
        OW::getEventManager()->bind('plugin.friends',                           array($this,'onPluginIsActive'));
        OW::getEventManager()->bind('plugin.friends.get_friend_list',           array($this,'getFriendList'));
        OW::getEventManager()->bind('plugin.friends.get_friend_list_by_display_name', array($this,'getFriendListByDisplayName'));
        OW::getEventManager()->bind('plugin.friends.check_friendship',          array($this,'findFriendship'));
        OW::getEventManager()->bind('plugin.friends.count_friends',             array($this,'findCountOfUserFriendsInList'));
        OW::getEventManager()->bind('plugin.friends.find_all_active_friendships', array($this,'findAllActiveFriendships'));
        OW::getEventManager()->bind('plugin.friends.find_active_friendships',   array($this,'findActiveFriendships'));
        OW::getEventManager()->bind('feed.collect_follow',                      array($this,'onCollectFeedFollow'));
        OW::getEventManager()->bind('admin.add_auth_labels',                    array($this,'onCollectAuthLabels'));
        OW::getEventManager()->bind('plugin.privacy.get_action_list',           array($this,'onCollectPrivacyActionList'));
        OW::getEventManager()->bind('plugin.privacy.get_privacy_list',          array($this,'onCollectPrivacyList'));
        OW::getEventManager()->bind('plugin.privacy.check_permission',          array($this,'onCollectPrivacyPermissions'));
        OW::getEventManager()->bind('friends.request-accepted',                 array($this,'onAcceptRequest'));

//        OW::getEventManager()->bind('friends.request-sent',                     array($this,'onSentRequest'));

        OW::getEventManager()->bind('friends.cancelled',                        array($this,'onCancelRequest'));
        OW::getEventManager()->bind('feed.collect_follow_permissions',          array($this,'onCollectFollowPermissions'));
        OW::getEventManager()->bind('feed.collect_privacy',                     array($this,'onCollectFeedPrivacyActions'));
        OW::getEventManager()->bind('feed.collect_configurable_activity',       array($this,'onCollectFeedConfigurableActivity'));
        OW::getEventManager()->bind('feed.after_comment_add',                   array($this,'onFeedAddComment'));
        OW::getEventManager()->bind('feed.after_like_added',                    array($this,'onFeedAddLike'));
        //OW::getEventManager()->bind('feed.action',                              array($this,'onFeedActionChangeAvatar'));
        OW::getEventManager()->bind('notifications.send_list',                  array($this,'onCollectNotificationSendList'));
        OW::getEventManager()->bind('friends.add_friend',                       array($this,'addFriend'));
        OW::getEventManager()->bind('friends.send_friend_request',              array($this, 'sendFriendRequest'));

        OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER,  array($this, 'onUserEventClearQueryCache'));
        OW::getEventManager()->bind(OW_EventManager::ON_USER_SUSPEND,     array($this, 'onUserEventClearQueryCache'));
        OW::getEventManager()->bind(OW_EventManager::ON_USER_UNSUSPEND,   array($this, 'onUserEventClearQueryCache'));
        OW::getEventManager()->bind(OW_EventManager::ON_USER_APPROVE,     array($this, 'onUserEventClearQueryCache'));
        OW::getEventManager()->bind(OW_EventManager::ON_USER_DISAPPROVE,  array($this, 'onUserEventClearQueryCache'));

        $credits = new FRIENDS_CLASS_Credits();
        OW::getEventManager()->bind('usercredits.on_action_collect',      array($credits, 'bindCreditActionsCollect'));

        OW::getEventManager()->bind('notifications.on_item_render', array($this->service, 'onNotificationRender'));
        OW::getEventManager()->bind('feed.on_item_render', array($this,'onNewsfeedItemRender'));
    }

    /**
     * Prepeare actions for tool on the profile view page
     *
     * @param BASE_CLASS_EventCollector $event
     */
    public function onCollectProfileActionTools( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();

        if ( empty($params['userId']) )
        {
            return;
        }

        $userId = (int) $params['userId'];

        if ( !OW::getUser()->isAuthenticated() || OW::getUser()->getId() == $userId )
        {
            return;
        }

        $language = OW::getLanguage();
        $router = OW::getRouter();
        $dto = $this->service->findFriendship($userId, OW::getUser()->getId());
        $linkId = 'friendship' . rand(10, 1000000);
        $extra_label = null;
        if ( $dto === null )
        {
            if( !OW::getUser()->isAuthorized('friends', 'add_friend') )
            {
                $status = BOL_AuthorizationService::getInstance()->getActionStatus('friends', 'add_friend');
            
                if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
                {
                    $href = 'javascript://';
                    $script = '$({$link}).click(function(){
                        window.OW.error({$message});
                    });';

                    $script = UTIL_JsGenerator::composeJsString($script, array('link' => '#'.$linkId, 'message' => $status['msg'] ));
                    OW::getDocument()->addOnloadScript($script);
                }
                else if ( $status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE )
                {
                    return;
                }
            }
            else if ( BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $userId) )
            {
                $href = 'javascript://';
                $script = '$({$link}).click(function(){
                    window.OW.error({$message});
                });';
                
                $script = UTIL_JsGenerator::composeJsString($script, array('link' => '#'.$linkId, 'message' => OW::getLanguage()->text('base', 'user_block_message') ));
                OW::getDocument()->addOnloadScript($script);
            }
            else
            {
                $href = $router->urlFor('FRIENDS_CTRL_Action', 'request', array('id' => $userId));
                $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                    array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$userId,'isPermanent'=>true,'activityType'=>'request_friends')));
                if(isset($iisSecuritymanagerEvent->getData()['code'])){
                    $code = $iisSecuritymanagerEvent->getData()['code'];
                    $href = $router->urlFor('FRIENDS_CTRL_Action', 'request', array('id' => $userId,'code'=>$code));
                }
            }

            $label = OW::getLanguage()->text('friends', 'add_to_friends');
        }
        elseif ( $dto === null )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('friends', 'add_friend');
            
            if($status['status'] == BOL_AuthorizationService::STATUS_PROMOTED)
            {
                $href = $router->urlFor('FRIENDS_CTRL_Action', 'request', array('id' => $userId));
                $label = OW::getLanguage()->text('friends', 'add_to_friends');
            }
            else
            {
                return;
            }            
            
        }
        else
        {
            switch ( $dto->getStatus() )
            {
                case FRIENDS_BOL_Service::STATUS_ACTIVE:
                    $href = $router->urlFor('FRIENDS_CTRL_Action', 'cancel', array('id' => $userId, 'redirect'=>true));
                    $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                        array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$userId,'isPermanent'=>true,'activityType'=>'cancel_friends')));
                    if(isset($iisSecuritymanagerEvent->getData()['code'])){
                        $code = $iisSecuritymanagerEvent->getData()['code'];
                        $href = $router->urlFor('FRIENDS_CTRL_Action', 'cancel', array('id' => $userId, 'redirect'=>true,'code'=>$code));
                    }
                    $label = $language->text('friends', 'remove_from_friends');
                    break;

                case FRIENDS_BOL_Service::STATUS_PENDING:

                    if ( $dto->getUserId() == OW::getUser()->getId() )
                    {
                        $label = $language->text('friends', 'friend_request_was_sent') . ' ('.$language->text('friends', 'cancel_request').')';
                        $href = $router->urlFor('FRIENDS_CTRL_Action', 'cancel', array('id' => $userId, 'redirect'=>true));
                        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$userId,'isPermanent'=>true,'activityType'=>'cancel_friends')));
                        if(isset($iisSecuritymanagerEvent->getData()['code'])){
                            $code = $iisSecuritymanagerEvent->getData()['code'];
                            $href = $router->urlFor('FRIENDS_CTRL_Action', 'cancel', array('id' => $userId, 'redirect'=>true,'code'=>$code));
                        }
//                        $extra_label = $language->text('friends', 'cancel_request');
                    }
                    else
                    {
                        if ( !OW::getUser()->isAuthorized('friends', 'add_friend') )
                        {
                            return;
                        }
                        $label = $language->text('friends', 'accept_friendship_request');
                        $href = $router->urlFor('FRIENDS_CTRL_Action', 'accept', array('id' => $userId));
                        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$userId,'isPermanent'=>true,'activityType'=>'accept_friends')));
                        if(isset($iisSecuritymanagerEvent->getData()['code'])){
                            $code = $iisSecuritymanagerEvent->getData()['code'];
                            $href = $router->urlFor('FRIENDS_CTRL_Action', 'accept', array('id' => $userId, 'redirect'=>true,'code'=>$code));
                        }
                    }
                    break;

                case FRIENDS_BOL_Service::STATUS_IGNORED:

                    if ( $dto->getUserId() == OW::getUser()->getId() )
                    {
                        $label = $language->text('friends', 'remove_friendship_request');
                        $href = $router->urlFor('FRIENDS_CTRL_Action', 'cancel', array('id' => $userId));
                        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$userId,'isPermanent'=>true,'activityType'=>'cancel_friends')));
                        if(isset($iisSecuritymanagerEvent->getData()['code'])){
                            $code = $iisSecuritymanagerEvent->getData()['code'];
                            $href = $router->urlFor('FRIENDS_CTRL_Action', 'cancel', array('id' => $userId,'code'=>$code));
                        }
                    }
                    else
                    {
                        if ( !OW::getUser()->isAuthorized('friends', 'add_friend') )
                        {
                            return;
                        }
                        $label = $language->text('friends', 'add_to_friends');
                        $href = $router->urlFor('FRIENDS_CTRL_Action', 'activate', array('id' => $userId));

                    }
            }
        }

        $resultArray = array(
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LABEL => $label,
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_HREF => 'javascript://',
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_ID => $linkId,
            BASE_CMP_ProfileActionToolbar::DATA_KEY_ITEM_KEY => 'friends.action',
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_ORDER => 1,
            BASE_CMP_ProfileActionToolbar::DATA_KEY_EXTRA_LABEL => $extra_label,
            BASE_CMP_ProfileActionToolbar::DATA_KEY_LINK_ATTRIBUTES => [
                'onclick' => "confirm_redirect('".OW::getLanguage()->text('admin','are_you_sure')."', '".$href."')"
            ]
        );

        $event->add($resultArray);
    }

    public function onUnregisterUser( OW_Event $event )
    {
        $params = $event->getParams();

        $userId = $params['userId'];

        $this->service->deleteUserFriendships($userId);
    }

    public function onCollectNotificationActions( BASE_CLASS_EventCollector $e )
    {
        $sectionLabel = OW::getLanguage()->text('friends', 'notification_section_label');

        $e->add(array(
            'section' => 'friends',
            'action' => 'friends-request',
            'description' => OW::getLanguage()->text('friends', 'email_notifications_setting_request'),
            'selected' => true,
            'sectionLabel' => $sectionLabel,
            'sectionIcon' => 'ow_ic_write'
        ));

        $e->add(array(
            'section' => 'friends',
            'action' => 'friends-accept',
            'description' => OW::getLanguage()->text('friends', 'email_notifications_setting_accept'),
            'selected' => true,
            'sectionLabel' => $sectionLabel,
            'sectionIcon' => 'ow_ic_write'
        ));
    }

    public function onPluginIsActive()
    {
        return true;
    }

    public function getFriendList( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !empty($params['userId']) )
        {
            $userId = (int) $params['userId'];
        }
        else
        {
            return null;
        }

        $first = 0;

        if ( !empty($params['first']) )
        {
            $first = (int) $params['first'];
        }

        $count = 1000;

        if ( !empty($params['count']) )
        {
            $count = (int) $params['count'];
        }

        $paramsUserIdList = null;

        if ( !empty($params['idList']) && is_array($params['idList']) )
        {
            $paramsUserIdList = $params['idList'];
        }

        return FRIENDS_BOL_Service::getInstance()->findUserFriendsInList($userId, $first, $count, $paramsUserIdList);
    }

    public function getFriendListByDisplayName( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['userId']) )
        {
            return null;
        }

        $service = FRIENDS_BOL_Service::getInstance();
        $userId = $params['userId'];
        $search = $params['search'];
        $first = !empty($params['first']) ? (int)$params['first'] : 0;
        $count = !empty($params['count']) ? (int)$params['count'] : $service->countFriends($userId);
        $userIdList = !empty($params['userIdList']) ? (array)$params['userIdList'] : array();
        $event->setData($service->findFriendIdListByDisplayName($userId, $search, $first, $count, $userIdList));

        return $event->getData();
    }

    public function findFriendship( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['userId']) || empty($params['friendId']) )
        {
            return null;
        }

        return FRIENDS_BOL_Service::getInstance()->findFriendship((int) $params['userId'], (int) $params['friendId']);
    }

    public function findCountOfUserFriendsInList( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !empty($params['userId']) )
        {
            $userId = (int) $params['userId'];
        }
        else
        {
            return null;
        }

        $paramsUserIdList = null;

        if ( !empty($params['idList']) && is_array($params['idList']) )
        {
            $paramsUserIdList = $params['idList'];
        }

        return FRIENDS_BOL_Service::getInstance()->findCountOfUserFriendsInList($userId, $paramsUserIdList);
    }

    public function findAllActiveFriendships( OW_Event $event )
    {
        $params = $event->getParams();

        return FRIENDS_BOL_Service::getInstance()->findAllActiveFriendships();
    }

    public function findActiveFriendships( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !isset($params['first']) || !isset($params['count']) )
        {
            return null;
        }

        return FRIENDS_BOL_Service::getInstance()->findActiveFriendships((int) $params['first'], (int) $params['count']);
    }

    public function onCollectFeedFollow( BASE_CLASS_EventCollector $e )
    {
        $friends = FRIENDS_BOL_Service::getInstance()->findAllActiveFriendships();
        foreach ( $friends as $item )
        {
            $e->add(array(
                'feedType' => 'user',
                'feedId' => $item->getUserId(),
                'userId' => $item->getFriendId(),
                'permission' => 'friends_only'
            ));

            $e->add(array(
                'feedType' => 'user',
                'feedId' => $item->getFriendId(),
                'userId' => $item->getUserId(),
                'permission' => 'friends_only'
            ));
        }
    }

    public function onCollectAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'friends' => array(
                    'label' => $language->text('friends', 'auth_group_label'),
                    'actions' => array(
                        'add_friend' => $language->text('friends', 'auth_action_label_add_friend')
                    )
                )
            )
        );
    }

    public function onCollectPrivacyList( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $params = $event->getParams();

        $event->add(array(
            'key' => 'friends_only',
            'label' => $language->text('friends', 'privacy_friends_only'),
            'sortOrder' => 2
        ));
    }

    public function onCollectPrivacyPermissions( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();

        if ( !empty($params['privacy']) && $params['privacy'] == 'friends_only' )
        {
            $ownerId = (int) $params['ownerId'];
            if ( !empty($ownerId) )
            {
                $viewerId = (int) $params['viewerId'];

                $privacy = array(
                    'friends_only' => array(
                        'blocked' => true,
                    ));

                $friendship = FRIENDS_BOL_Service::getInstance()->findFriendship($ownerId, $viewerId);

                if ( $ownerId > 0 && $viewerId > 0 && ( (!empty($friendship) && $friendship->getStatus() == 'active' ) || $ownerId === $viewerId ) )
                {
                    $privacy = array(
                        'friends_only' => array(
                            'blocked' => false
                        ));
                }

                $event->add($privacy);
            }
        }

        if ( !empty($params['userPrivacyList']) )
        {

            $viewerId = (int) $params['viewerId'];
            $list = $params['userPrivacyList'];

            $resultList = array();
            $ownerIdLIst = array();

            foreach ( $list as $ownerId => $privacy )
            {
                if ( $privacy == 'friends_only' )
                {
                    $ownerIdLIst[] = $ownerId;
                }
            }

            $friendList = FRIENDS_BOL_Service::getInstance()->findUserFriendsInList($viewerId, 0, 10000, $ownerIdLIst);

            foreach ( $list as $ownerId => $privacy )
            {
                if ( $privacy == 'friends_only' )
                {
                    $privacy = array(
                        'privacy' => $privacy,
                        'blocked' => true,
                        'userId' => $ownerId
                    );

                    if ( $ownerId > 0 && $viewerId > 0 && !empty($friendList) && is_array($friendList) && in_array($ownerId, $friendList) || $ownerId === $viewerId )
                    {
                        $privacy = array(
                            'privacy' => $privacy,
                            'blocked' => false,
                            'userId' => $ownerId
                        );
                    }

                    $event->add($privacy);
                }
            }
            $event->add($resultList);
        }
    }

    public function onAcceptRequest( OW_Event $e )
    {
        $params = $e->getParams();
        $recipientId = $params['recipientId'];
        $senderId = $params['senderId'];

        $eventParams = array(
            'userId' => $recipientId,
            'feedType' => 'user',
            'feedId' => $senderId
        );
        OW::getEventManager()->trigger(new OW_Event('feed.add_follow', $eventParams));

        $eventParams = array(
            'userId' => $senderId,
            'feedType' => 'user',
            'feedId' => $recipientId
        );
        OW::getEventManager()->trigger(new OW_Event('feed.add_follow', $eventParams));
    }

//    public function onSentRequest( OW_Event $e )
//    {
//        $params = $e->getParams();
//        $recipientId = $params['recipientId'];
//        $senderId = $params['senderId'];
//
//        $eventParams = array(
//            'userId' => $senderId,
//            'feedType' => 'user',
//            'feedId' => $recipientId
//        );
//        OW::getEventManager()->trigger(new OW_Event('feed.add_follow', $eventParams));
//    }

    public function onCancelRequest( OW_Event $e )
    {        
        $params = $e->getParams();
        $recipientId = $params['recipientId'];
        $senderId = $params['senderId'];

        $dto = $this->service->findByRequesterIdAndUserId($recipientId, $senderId);

        if ( $dto )
        {
            $this->service->cancel($recipientId, $senderId);

            $event = new OW_Event('feed.delete_item', array('entityType' => 'friend_add', 'entityId' => $dto->id));
            OW::getEventManager()->trigger($event);

            $eventParams = array(
                'userId' => $recipientId,
                'feedType' => 'user',
                'feedId' => $senderId
            );
            OW::getEventManager()->trigger(new OW_Event('feed.remove_follow', $eventParams));

            $eventParams = array(
                'userId' => $senderId,
                'feedType' => 'user',
                'feedId' => $recipientId
            );
            OW::getEventManager()->trigger(new OW_Event('feed.remove_follow', $eventParams));
        }
    }

    public function onBlockUser( OW_Event $e )
    {
        $params = $e->getParams();

        $event = new OW_Event('friends.cancelled', array(
            'senderId' => $params['userId'],
            'recipientId' => $params['blockedUserId']
        ));

        OW::getEventManager()->trigger($event);
    }

    public function onCollectFollowPermissions( BASE_CLASS_EventCollector $e )
    {
        $params = $e->getParams();

        if ( $params['feedType'] != 'user' )
        {
            return;
        }

        $dto = FRIENDS_BOL_Service::getInstance()->findFriendship($params['feedId'], $params['userId']);
        if ( $dto === null || $dto->status != 'active' )
        {
            return;
        }

        $e->add('friends_only');
    }

    public function onCollectPrivacyActionList( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $privacyValueEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PRIVACY_ITEM_ADD, array('key' => 'friends_view')));
        $defaultValue = 'everybody';
        if(isset($privacyValueEvent->getData()['value'])){
            $defaultValue = $privacyValueEvent->getData()['value'];
        }
        $action = array(
            'key' => 'friends_view',
            'pluginKey' => 'friends',
            'label' => $language->text('friends', 'privacy_action_view_friends'),
            'description' => $language->text('friends', 'privacy_action_view_friends_description'),
            'defaultValue' => $defaultValue
        );

        $event->add($action);
    }

    public function onCollectFeedPrivacyActions( BASE_CLASS_EventCollector $event )
    {
        $event->add(array('create:friend_add', 'friends_view'));
    }

    public function onCollectFeedConfigurableActivity( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(array(
            'label' => $language->text('friends', 'feed_content_label'),
            'activity' => '*:friend_add'
        ));
    }

    public function onCollectQuickLinks( BASE_CLASS_EventCollector $event )
    {
        $userId = OW::getUser()->getId();

        $count = (int) $this->service->countFriends($userId);
        $activeCount = (int) $this->service->count(null, $userId, FRIENDS_BOL_Service::STATUS_PENDING);

        if ($count == 0 && $activeCount == 0)
        {
            return;
        }

        $event->add(array(
            BASE_CMP_QuickLinksWidget::DATA_KEY_LABEL => OW::getLanguage()->text('friends', 'widget_title'),
            BASE_CMP_QuickLinksWidget::DATA_KEY_URL => OW::getRouter()->urlForRoute('friends_list'),
            BASE_CMP_QuickLinksWidget::DATA_KEY_COUNT => $count,
            BASE_CMP_QuickLinksWidget::DATA_KEY_COUNT_URL => OW::getRouter()->urlForRoute('friends_list'),
            BASE_CMP_QuickLinksWidget::DATA_KEY_ACTIVE_COUNT => $activeCount,
            BASE_CMP_QuickLinksWidget::DATA_KEY_ACTIVE_COUNT_URL => OW::getRouter()->urlForRoute('friends_lists', array('list' => 'got-requests'))
        ));
    }

    public function onFeedAddComment( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != 'friend_add' )
        {
            return;
        }

        $friendship = FRIENDS_BOL_Service::getInstance()->findFriendshipById($params['entityId']);

        if ( empty($friendship) )
        {
            return;
        }

        $userName1 = BOL_UserService::getInstance()->getDisplayName($friendship->userId);
        $userUrl1 = BOL_UserService::getInstance()->getUserUrl($friendship->userId);
        $userEmbed1 = '<a href="' . $userUrl1 . '">' . $userName1 . '</a>';

        $userName2 = BOL_UserService::getInstance()->getDisplayName($friendship->friendId);
        $userUrl2 = BOL_UserService::getInstance()->getUserUrl($friendship->friendId);
        $userEmbed2 = '<a href="' . $userUrl2 . '">' . $userName2 . '</a>';

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
            'activityType' => 'comment',
            'activityId' => $params['commentId'],
            'entityId' => $params['entityId'],
            'entityType' => 'friend_add',
            'userId' => $params['userId'],
            'pluginKey' => 'friends'
        ), array(
            'string' => array('key'=>'friends+comment_activity_string', 'vars'=>array('user1' => $userEmbed1, 'user2' => $userEmbed2)),
            'line' => ''
        )));
    }

    public function onFeedAddLike( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != 'friend_add' )
        {
            return;
        }

        $friendship = FRIENDS_BOL_Service::getInstance()->findFriendshipById($params['entityId']);

        if ( empty($friendship) )
        {
            return;
        }

        $userName1 = BOL_UserService::getInstance()->getDisplayName($friendship->userId);
        $userUrl1 = BOL_UserService::getInstance()->getUserUrl($friendship->userId);
        $userEmbed1 = '<a href="' . $userUrl1 . '">' . $userName1 . '</a>';

        $userName2 = BOL_UserService::getInstance()->getDisplayName($friendship->friendId);
        $userUrl2 = BOL_UserService::getInstance()->getUserUrl($friendship->friendId);
        $userEmbed2 = '<a href="' . $userUrl2 . '">' . $userName2 . '</a>';

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
            'activityType' => 'like',
            'activityId' => $params['userId'],
            'entityId' => $params['entityId'],
            'entityType' => 'friend_add',
            'userId' => $params['userId'],
            'pluginKey' => 'friends'
        ), array(
            'string' => array('key' => 'friends+like_activity_string', 'vars'=>array('user1' => $userEmbed1, 'user2' => $userEmbed2)),
            'line' => ''
        )));
    }

    /*
    public function onFeedActionChangeAvatar( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != 'avatar-change' )
        {
            return;
        }

        $list = FRIENDS_BOL_Service::getInstance()->findFriendshipListByUserId($params['userId']);

        foreach ( $list as $frendshipDto )
        {
            $getPermalinkEvent = new OW_Event('feed.get_item_permalink', array('entityType' => 'friend_add', 'entityId' => $frendshipDto->id));
            OW::getEventManager()->trigger($getPermalinkEvent);

            if ( $getPermalinkEvent->getData() == null )
            {
                continue;
            }

            $names = BOL_UserService::getInstance()->getDisplayNamesForList(array($frendshipDto->userId, $frendshipDto->friendId));
            $unames = BOL_UserService::getInstance()->getUserNamesForList(array($frendshipDto->userId, $frendshipDto->friendId));
            $avatars = BOL_AvatarService::getInstance()->getAvatarsUrlList(array($frendshipDto->userId, $frendshipDto->friendId));
            $uUrls = BOL_UserService::getInstance()->getUserUrlsForList(array($frendshipDto->userId, $frendshipDto->friendId));

            //Add Newsfeed activity action
            $event = new OW_Event('feed.action', array(
                'pluginKey' => 'friends',
                'entityType' => 'friend_add',
                'entityId' => $frendshipDto->id,
                'userId' => array($frendshipDto->friendId, $frendshipDto->userId),
                'feedType' => 'user',
                'feedId' => $frendshipDto->userId
            ), array(
                'string' => array("key" => 'friends+newsfeed_action_string', "vars" => array(
                    'user_url' => $uUrls[$frendshipDto->friendId],
                    'name' => $names[$frendshipDto->friendId],
                    'requester_url' => $uUrls[$frendshipDto->userId],
                    'requester_name' => $names[$frendshipDto->userId]
                ))
            ));
            OW::getEventManager()->trigger($event);
        }
    }
    */

    public function addFriend( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['requesterId']) || empty($params['userId']) )
        {
            return;
        }

        $requesterId = $params['requesterId'];
        $userId = $params['userId'];

        $frendshipDto = $this->service->findFriendship($requesterId, $userId);

        if ( !empty($frendshipDto) )
        {
            return;
        }

        $this->service->request($requesterId, $userId);

        $event = new OW_Event('friends.request-sent', array(
            'senderId' => $requesterId,
            'recipientId' => $userId,
            'time' => time()
        ));

        OW::getEventManager()->trigger($event);

        $frendshipDto = $this->service->accept($userId, $requesterId);

        if ( empty($frendshipDto) )
        {
            return;
        }
        //Because the friendship feed was sent only to two person who were friends, this feed was deleted.

       /* $se = BOL_UserService::getInstance();

        $names = $se->getDisplayNamesForList(array($requesterId, $userId));
        $uUrls = $se->getUserUrlsForList(array($requesterId, $userId));

        //Add Newsfeed activity action
        $event = new OW_Event('feed.action', array(
            'pluginKey' => 'friends',
            'entityType' => 'friend_add',
            'entityId' => $frendshipDto->id,
            'userId' => array($requesterId, $userId),
            'feedType' => 'user',
            'feedId' => $requesterId
        ), array(
            'string' => array("key" => 'friends+newsfeed_action_string', "vars" => array(
                'user_url' => $uUrls[$userId],
                'name' => $names[$userId],
                'requester_url' => $uUrls[$requesterId],
                'requester_name' => $names[$requesterId]
            ))
        ));
        OW::getEventManager()->trigger($event);*/

        $event = new OW_Event('friends.request-accepted', array(
            'senderId' => $requesterId,
            'recipientId' => OW::getUser()->getId(),
            'time' => time()
        ));

        OW::getEventManager()->trigger($event);
    }

    public function onUserEventClearQueryCache( OW_Event $event )
    {
        OW::getCacheManager()->clean( array( FRIENDS_BOL_FriendshipDao::CACHE_TAG_FRIENDS_COUNT, FRIENDS_BOL_FriendshipDao::CACHE_TAG_FRIEND_ID_LIST ));
    }

    public function onCollectNotificationSendList( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();
        $userIdList = $params['userIdList'];

        $unreadFriendRequests = FRIENDS_BOL_Service::getInstance()->getUnreadFriendRequestsForUserIdList($userIdList);

        /**
         * @var FRIENDS_BOL_Friendship $friendship
         */
        foreach ( $unreadFriendRequests as $id => $friendship )
        {
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array( $friendship->userId ) );
            $avatar = $avatars[$friendship->userId];

            $event->add(array(
                'pluginKey' => 'friends',
                'entityType' => 'friends-request',
                'entityId' => $friendship->id,
                'userId' => $friendship->friendId,
                'action' => 'friends-request',
                'time' => $friendship->timeStamp,

                'data' => array(
                    'avatar' => $avatar,
                    'string' => OW::getLanguage()->text('friends', 'notify_request_string', array(
                            'displayName' => BOL_UserService::getInstance()->getDisplayName($friendship->userId),
                            'userUrl' => BOL_UserService::getInstance()->getUserUrl($friendship->userId),
                            'url' => OW::getRouter()->urlForRoute('friends_lists', array('list'=>'got-requests'))
                        )))
            ));

            $unreadFriendRequests[$id]->notificationSent = 1;
            FRIENDS_BOL_Service::getInstance()->saveFriendship($unreadFriendRequests[$id]);
        }
    }

    public function sendFriendRequest( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['requesterId']) || empty($params['userId']) )
        {
            return;
        }

        $requesterId = $params['requesterId'];
        $userId = $params['userId'];

        $frendshipDto = $this->service->findFriendship($requesterId, $userId);

        if ( !empty($frendshipDto) )
        {
            return;
        }

        $this->service->request($requesterId, $userId);

        $event = new OW_Event('friends.request-sent', array(
            'senderId' => $requesterId,
            'recipientId' => $userId,
            'time' => time()
        ));

        BOL_AuthorizationService::getInstance()->trackAction('friends', 'add_friend', $requesterId);

        OW::getEventManager()->trigger($event);
    }
    public function onNewsfeedItemRender( OW_Event $event )
    {
        $data = $event->getData();
        if (!isset($data["string"]["key"]) || $data["string"]["key"] != "friends+newsfeed_action_string")
            return;
        else {
            $users = BOL_UserService::getInstance();
            $requesterId = $data["action"]["userIds"][0];
            $RequestedId = $data["action"]["userIds"][1];

            $names = $users->getDisplayNamesForList(array($requesterId, $RequestedId));
            $userUrls = $users->getUserUrlsForList(array($requesterId, $RequestedId));
            $data["string"]["vars"]["requester_url"] = $userUrls[$requesterId];
            $data["string"]["vars"]["requester_name"] = $names[$requesterId];
            $data["string"]["vars"]["user_url"] = $userUrls[$RequestedId];
            $data["string"]["vars"]["name"] = $names[$RequestedId];
            $event->setData($data);
        }
    }
}
