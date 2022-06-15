<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.event.mobile.classes
 * @since 1.6.0
 */
class GROUPS_MCLASS_EventHandler
{
    /**
     * Class instance
     *
     * @var GROUPS_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return GROUPS_MCLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function genericInit()
    {
        $eventHandler = $this;
        OW::getEventManager()->bind('groups.on_toolbar_collect', array($eventHandler, "onGroupToolbarCollect"));
        OW::getEventManager()->bind('base.mobile_top_menu_add_options', array($this, 'onMobileTopMenuAddLink'));
        OW::getEventManager()->bind('forum.check_permissions', array($eventHandler, "onForumCheckPermissions"));
        OW::getEventManager()->bind('forum.can_view', array($eventHandler, 'onForumCanView'));
        OW::getEventManager()->bind('groups.groups.status.flag.changer', array(GROUPS_BOL_Service::getInstance(),'groupStatusFlagRenderer'));
        OW::getEventManager()->bind('feed.after_like_added', array(GROUPS_BOL_Service::getInstance(), 'onLikeNotification'));
        OW::getEventManager()->bind('groups.set_mobile_user_as_owner', array(GROUPS_BOL_Service::getInstance(), 'setMobileUserAsOwner'));
        OW::getEventManager()->bind('iisgroupsplus.on_delete_user', array(GROUPS_BOL_Service::getInstance(), "onGroupUserLeave"));
    }

    public function onForumCheckPermissions( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !isset($params['entityId']) || !isset($params['entity']) )
        {
            return;
        }

        if ( $params['entity'] == 'groups' )
        {
            $groupService = GROUPS_BOL_Service::getInstance();
            $group = $groupService->findGroupById($params['entityId']);
            if($group == null) {
                $event->setData(false);
            }else {

                if ($params['action'] == 'edit_topic') {
                    if ($group->userId == OW::getUser()->getId() || OW::getUser()->isAuthorized($params['entity']) || $groupService->isCurrentUserCanEdit($group)) {
                        $event->setData(true);
                    }
                } else if ($params['action'] == 'add_topic') {
                    if (OW::getUser()->isAuthorized($params['entity'], 'add_topic') && $groupService->findUser($params['entityId'], OW::getUser()->getId()) || $groupService->isCurrentUserCanEdit($group)) {
                        $event->setData(true);
                    } else {

                        if ($groupService->findUser($params['entityId'], OW::getUser()->getId())) {
                            $status = BOL_AuthorizationService::getInstance()->getActionStatus($params['entity'], 'add_topic');
                            if ($status['status'] == BOL_AuthorizationService::STATUS_PROMOTED) {
                                $event->setData(true);
                                return;
                            }
                        }

                        $event->setData(false);
                    }
                } else if ($groupService->findUser($params['entityId'], OW::getUser()->getId())) {
                    $event->setData(true);
                } else {
                    $event->setData(false);
                }
            }
        }
    }

    public function onForumCanView( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !isset($params['entityId']) || !isset($params['entity']) )
        {
            return;
        }

        if ( $params['entity'] != 'groups' )
        {
            return;
        }


        $groupId = $params['entityId'];
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);

        if ( empty($group) )
        {
            return;
        }

        $canView = GROUPS_BOL_Service::getInstance()->isCurrentUserCanView($group, false);

        if ( $group->whoCanView != GROUPS_BOL_Service::WCV_INVITE )
        {
            $event->setData($canView);

            return;
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new Redirect404Exception();
        }

        $isUser = GROUPS_BOL_Service::getInstance()->findUser($group->id, OW::getUser()->getId()) !== null;

        if ( !$isUser && !OW::getUser()->isAuthorized('groups') )
        {
            throw new Redirect404Exception();
        }
    }

    public function onInvitationsItemRender( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] == 'group-join' )
        {
            $data = $params['data'];
            $data['string']['vars']['group'] = strip_tags($data['string']['vars']['group']);
            $data['acceptCommand'] = 'groups.accept';
            $data['declineCommand'] = 'groups.ignore';
            $event->setData($data);
        }
    }
    
    public function onFeedItemRenderDisableActions( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( !in_array($params["action"]["entityType"], array( GROUPS_BOL_Service::FEED_ENTITY_TYPE, "groups-join", "groups-status" )) )
        {
            return;
        }
        $groupActions = array(
            'groups-status'
        );
        $data = $event->getData();
        if ( in_array($params['action']['entityType'], $groupActions) && $params['feedType'] == 'groups' )
        {
            $data['context'] = null;
        }
        else if(isset($data['contextFeedId'])) {
            $service = GROUPS_BOL_Service::getInstance();
            $group = $service->findGroupById($data['contextFeedId']);
            if ($group) {
                $url = $service->getGroupUrl($group);
                $title = UTIL_String::truncate(strip_tags($group->title), 100, '...');
                $data['context'] = array(
                    'label' => $title,
                    'url' => $url
                );
            }
        }
        $data["disabled"] = false;
        
        $event->setData($data);
    }

    public function onFeedWidgetConstruct( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['feedType'] != 'groups' )
        {
            return;
        }

        $data = $e->getData();

        if ( !OW::getUser()->isAuthorized('groups', 'add_comment') )
        {
            $data['statusForm'] = false;
            $actionStatus = BOL_AuthorizationService::getInstance()->getActionStatus('groups', 'add_comment');

            if ( $actionStatus["status"] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $data["statusMessage"] = $actionStatus["msg"];
            }

            $e->setData($data);

            return;
        }

        $groupId = (int) $params['feedId'];
        $userId = OW::getUser()->getId();

        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        $userDto = GROUPS_BOL_Service::getInstance()->findUser($groupId, $userId);

        $data['statusForm'] = $userDto !== null && $group->status == GROUPS_BOL_Group::STATUS_ACTIVE;

        $e->setData($data);
    }

    public function onFeedItemRender( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        $actionUserId = $userId = (int) $data['action']['userId'];
        if ( OW::getUser()->isAuthenticated() && in_array($params['feedType'], array('groups')) )
        {
            $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($params['feedId']);
            $isGroupOwner = $groupDto->userId == OW::getUser()->getId();
            $isGroupModerator = OW::getUser()->isAuthorized('groups');

            if ( $actionUserId != OW::getUser()->getId() && ($isGroupOwner || $isGroupModerator) )
            {
                $groupUserDto = GROUPS_BOL_Service::getInstance()->findUser($groupDto->id, $actionUserId);
                if ( $groupUserDto !== null )
                {
                    $data['contextMenu'] = empty($data['contextMenu']) ? array() : $data['contextMenu'];


                    if ( $groupDto->userId == $userId )
                    {
                        array_unshift($data['contextMenu'], array(
                            'label' => OW::getLanguage()->text('groups', 'delete_group_user_label'),
                            'url' => 'javascript://',
                            'attributes' => array(
                                'data-message' => OW::getLanguage()->text('groups', 'group_owner_delete_error'),
                                'onclick' => 'OWM.error($(this).data().message); return false;'
                            )
                        ));
                    }
                    else
                    {
                        $callbackUri = OW::getRequest()->getRequestUri();
                        $urlParams = array(
                            'redirectUri' => urlencode($callbackUri)
                        );
                        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$userId,'isPermanent'=>true,'activityType'=>'deleteUser_group')));
                        if(isset($iisSecuritymanagerEvent->getData()['code'])){
                            $urlParams['code'] = $iisSecuritymanagerEvent->getData()['code'];

                        }
                        $deleteUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('GROUPS_MCTRL_Groups', 'deleteUser', array(
                            'groupId' => $groupDto->id,
                            'userId' => $userId
                        )),$urlParams );

                        array_unshift($data['contextMenu'], array(
                            'label' => OW::getLanguage()->text('groups', 'delete_user_from_group'),
                            'attributes' => array(
                                'onclick' => UTIL_JsGenerator::composeJsString('return confirm_redirect($(this).data(\'confirm-msg\'), \''.$deleteUrl.'\');',
                                    array(
                                        'groupId' => $groupDto->id,
                                        'userId' => $userId
                                    )
                                ),
                                "data-confirm-msg" => OW::getLanguage()->text('groups', 'delete_group_user_confirmation')
                            ),
                            "class" => "owm_red_btn"
                        ));
                    }
                }
            }

            $canRemove = $isGroupOwner || $params['action']['userId'] == OW::getUser()->getId() || $isGroupModerator;

            if ( $canRemove )
            {
                array_unshift($data['contextMenu'], array(
                    'label' => OW::getLanguage()->text('groups', 'delete_feed_item_label'),
                    'class' => 'newsfeed_remove_btn',
                    'attributes' => array(
                        'data-confirm-msg' => OW::getLanguage()->text('groups', 'delete_feed_item_confirmation')
                    )
                ));
            }
            if (!in_array(OW::getLanguage()->text('base', 'flag'), array_column($data['contextMenu'],'label'))) {
                array_unshift($data['contextMenu'], array(
                'label' => OW::getLanguage()->text('base', 'flag'),
                'attributes' => array(
                    'onclick' => 'OW.flagContent($(this).data().etype, $(this).data().eid)',
                    "data-etype" => $params['action']['entityType'],
                    "data-eid" => $params['action']['entityId']
                )
            ));}
            /*
             * delete a user from here is not recommended
            $canRemove = $isGroupOwner || $params['action']['userId'] == OW::getUser()->getId() || $isGroupModerator;

            if ( $canRemove )
            {
                $callbackUrl = OW_URL_HOME . OW::getRequest()->getRequestUri();
                array_unshift($data['contextMenu'], array(
                    'label' => OW::getLanguage()->text('newsfeed', 'delete_feed_item_user_label'),
                    'attributes' => array(
                        'onclick' => UTIL_JsGenerator::composeJsString('if ( confirm($(this).data(\'confirm-msg\')) ) OW.Users.deleteUser({$userId}, \'' . $callbackUrl . '\', true);', array(
                            'userId' => $actionUserId
                        )),
                        "data-confirm-msg" => OW::getLanguage()->text('base', 'are_you_sure')
                    ),
                    "class" => "owm_red_btn"
                ));
            }*/
        }

        $event->setData($data);
    }

    public function onFeedItemRenderContext( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        $groupActions = array(
            'groups-status'
        );

        if ( in_array($params['action']['entityType'], $groupActions) && $params['feedType'] == 'groups' )
        {
            $data['context'] = null;
        }

        if ( $params['action']['entityType'] == 'forum-topic' && isset($data['contextFeedType'])
            && $data['contextFeedType'] == 'groups' && $data['contextFeedType'] != $params['feedType'] )
        {
            $service = GROUPS_BOL_Service::getInstance();
            $group = $service->findGroupById($data['contextFeedId']);
            if($group) {
                $url = $service->getGroupUrl($group);
                $title = UTIL_String::truncate(strip_tags($group->title), 100, '...');

                $data['context'] = array(
                    'label' => $title,
                    'url' => $url
                );
            }
        }

        $event->setData($data);
    }

    /*public function onFeedItemRenderContext( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        if ( empty($data['contextFeedType']) )
        {
            return;
        }

        if ( $data['contextFeedType'] != "groups" )
        {
            return;
        }

        if ( $params['feedType'] == "groups" )
        {
            $data["context"] = null;
            $event->setData($data);

            return;
        }

        $service = GROUPS_BOL_Service::getInstance();
        $group = $service->findGroupById($data['contextFeedId']);
        $url = $service->getGroupUrl($group);
        $title = UTIL_String::truncate(strip_tags($group->title), 100, '...');

        $data['context'] = array(
            'label' => $title,
            'url' => $url
        );

        $event->setData($data);
    }*/

    public function onFeedItemRenderActivity( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        if ( $params['action']['entityType'] != GROUPS_BOL_Service::FEED_ENTITY_TYPE || $params['feedType'] == 'groups')
        {
            return;
        }

        $groupId = $params['action']['entityId'];
        $usersCount = GROUPS_BOL_Service::getInstance()->findUserListCount($groupId);

        if ( $usersCount == 1 )
        {
            return;
        }

        $users = GROUPS_BOL_Service::getInstance()->findGroupUserIdList($groupId, GROUPS_BOL_Service::PRIVACY_EVERYBODY);
        $activityUserIds = array();

        foreach ( $params['activity'] as $activity )
        {
            if ( $activity['activityType'] == 'groups-join')
            {
                $activityUserIds[] = $activity['data']['userId'];
            }
        }

        $lastUserId = reset($activityUserIds);
        $follows = array_intersect($activityUserIds, $users);
        $notFollows = array_diff($users, $activityUserIds);
        $idlist = array_merge($follows, $notFollows);

        $viewMoreUrl = null;

        if ( count($idlist) > 5 )
        {
            $viewMoreUrl = array("routeName" => "groups-user-list", "vars" => array(
                "groupId" => $groupId
            ));
        }

        if ( is_array($data["content"])  )
        {
            $data["content"]["vars"]["userList"] = array(
                "label" => array(
                    "key" => "groups+feed_activity_users",
                    "vars" => array(
                        "usersCount" => $usersCount
                    )
                ),
                "viewAllUrl" => $viewMoreUrl,
                "ids" => array_slice($idlist, 0, 5)
            );
        }
        else // Backward compatibility
        {
            $avatarList = new BASE_CMP_MiniAvatarUserList( array_slice($idlist, 0, 5) );
            $avatarList->setEmptyListNoRender(true);

            if ( count($idlist) > 5 )
            {
                $avatarList->setViewMoreUrl(OW::getRouter()->urlForRoute($viewMoreUrl["routeName"], $viewMoreUrl["vars"]));
            }

            $language = OW::getLanguage();
            $content = $avatarList->render();

            if ( $lastUserId )
            {
                $userName = BOL_UserService::getInstance()->getDisplayName($lastUserId);
                $userUrl = BOL_UserService::getInstance()->getUserUrl($lastUserId);
                $content .= $language->text('groups', 'feed_activity_joined', array('user' => '<a href="' . $userUrl . '">' . $userName . '</a>'));
            }

            $data['assign']['activity'] = array('template' => 'activity', 'vars' => array(
                'title' => $language->text('groups', 'feed_activity_users', array('usersCount' => $usersCount)),
                'content' => $content
            ));
        }

        $event->setData($data);
    }

    public function onGroupToolbarCollect( BASE_CLASS_EventCollector $e )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            return;
        }

        $params = $e->getParams();
        $backUri = OW::getRequest()->getRequestUri();

        $followCode = '';
        $unFollowCode='';
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$params['groupId'],'isPermanent'=>true,'activityType'=>'follow_group')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $followCode = $iisSecuritymanagerEvent->getData()['code'];
        }
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$params['groupId'],'isPermanent'=>true,'activityType'=>'unFollow_group')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $unFollowCode = $iisSecuritymanagerEvent->getData()['code'];
        }

        if ( OW::getEventManager()->call('feed.is_inited') )
        {
            $url = OW::getRouter()->urlFor('GROUPS_MCTRL_Groups', 'follow');

            $eventParams = array(
                'userId' => OW::getUser()->getId(),
                'feedType' => GROUPS_BOL_Service::ENTITY_TYPE_GROUP,
                'feedId' => $params['groupId']
            );

            if ( !OW::getEventManager()->call('feed.is_follow', $eventParams) )
            {
                $e->add(array(
                    'label' => OW::getLanguage()->text('groups', 'feed_group_follow'),
                    'href' => OW::getRequest()->buildUrlQueryString($url, array(
                        'backUri' => $backUri,
                        'groupId' => $params['groupId'],
                        'command' => 'follow',
                        'followCode' =>$followCode))
                ));
            }
            else
            {
                $e->add(array(
                    'label' => OW::getLanguage()->text('groups', 'feed_group_unfollow'),
                    'href' => OW::getRequest()->buildUrlQueryString($url, array(
                        'backUri' => $backUri,
                        'groupId' => $params['groupId'],
                        'command' => 'unfollow',
                        'unFollowCode' =>$unFollowCode))
                ));
            }

            $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($params['groupId']);
            if ( OW::getUser()->isAuthenticated() && !($groupDto->userId == OW::getUser()->getId())
                && (GROUPS_BOL_Service::getInstance()->findUser($groupDto->getId(), OW::getUser()->getId()) !== null) ) {
                $actionUrl = OW::getRouter()->urlForRoute('groups-leave', array('groupId' => $groupDto->getId()));
                $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                    array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$groupDto->getId(),'isPermanent'=>true,'activityType'=>'leave_group')));
                if(isset($iisSecuritymanagerEvent->getData()['code'])){
                    $code = $iisSecuritymanagerEvent->getData()['code'];
                    $actionUrl = OW::getRequest()->buildUrlQueryString($actionUrl, array('code' => $code));
                }

                $e->add(array(
                    'label' => OW::getLanguage()->text('groups', 'widget_leave_button'),
                    'href' => $actionUrl,
                    'click' => "return confirm_redirect('" . OW::getLanguage()->text('base', 'are_you_sure') . "', '$actionUrl');"
                ));
            }
        }
    }

    public function onMobileTopMenuAddLink( BASE_CLASS_EventCollector $event )
    {
        if ( OW::getUser()->isAuthenticated() && (OW::getUser()->isAuthorized('groups', 'create'))) {
            $id = IISSecurityProvider::generateUniqueId('group_add');
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('groups', 'create');
            OW::getDocument()->addScriptDeclaration(
                UTIL_JsGenerator::composeJsString(
                    ';$("#" + {$btn}).on("click", function()
                    {
                        OWM.showContent();
                        OWM.authorizationLimitedFloatbox({$msg});
                    });',
                    array(
                        'btn' => $id,
                        'msg' => $status['msg'],
                    )
                )
            );
            $event->add(array(
                'prefix' => 'groups',
                'key' => 'mobile_main_menu_list',
                'id' => $id,
                'url' => OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('groups-create'))
            ));
        }
    }
}