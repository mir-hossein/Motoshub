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
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.mobile.classes
 * @since 1.0
 */
class NEWSFEED_MCLASS_EventHandler
{
    /**
     * Singleton instance.
     *
     * @var NEWSFEED_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return NEWSFEED_MCLASS_EventHandler
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
     * @var NEWSFEED_BOL_Service
     */
    private $service;

    private function __construct()
    {
        $this->service = NEWSFEED_BOL_Service::getInstance();
    }
    
    public function onCollectProfileActions( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();
        $userId = $params['userId'];

        if ( !OW::getUser()->isAuthenticated() || OW::getUser()->getId() == $userId )
        {
            return;
        }

        $urlParams = array(
            'userId' => $userId,
            'backUri' => OW::getRouter()->getUri()
        );

        $linkId = IISSecurityProvider::generateUniqueId('follow');
        $isFollowing = NEWSFEED_BOL_Service::getInstance()->isFollow(OW::getUser()->getId(), 'user', $userId);
        $followUrl = OW::getRouter()->urlFor('NEWSFEED_CTRL_Feed', 'follow');
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$userId,'isPermanent'=>true,'activityType'=>'followProfile_newsfeed')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $code = $iisSecuritymanagerEvent->getData()['code'];
            $urlParams['followCode']=$code;
        }
        $followUrl = OW::getRequest()->buildUrlQueryString($followUrl, $urlParams);
        $followLabel = OW::getLanguage()->text('newsfeed', 'follow_button');

        $unfollowUrl = OW::getRouter()->urlFor('NEWSFEED_CTRL_Feed', 'unFollow');
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$userId,'isPermanent'=>true,'activityType'=>'unFollowProfile_newsfeed')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $code = $iisSecuritymanagerEvent->getData()['code'];
            $urlParams['unFollowCode']=$code;
        }
        $unfollowUrl = OW::getRequest()->buildUrlQueryString($unfollowUrl, $urlParams);
        $unfollowLabel = OW::getLanguage()->text('newsfeed', 'unfollow_button');

        $script = UTIL_JsGenerator::composeJsString('
            var isFollowing = {$isFollowing};

            $("#' . $linkId . '").click(function()
            {
                if ( !isFollowing && {$isBlocked} )
                {
                    OWM.error({$blockError});
                    return;
                }

                $.getJSON(isFollowing ? {$unfollowUrl} : {$followUrl}, function( r ) {
                    OWM.info(r.message);
                });

                isFollowing = !isFollowing;
                $(this).text(isFollowing ? {$unfollowLabel} : {$followLabel})
            });
        ', array(
            'isFollowing' => $isFollowing,
            'unfollowUrl' => $unfollowUrl,
            'followUrl' => $followUrl,
            'followLabel' => $followLabel,
            'unfollowLabel' => $unfollowLabel,
            'isBlocked' => BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $userId),
            'blockError' => OW::getLanguage()->text('base', 'user_block_message')
        ));

        OW::getDocument()->addOnloadScript($script);

        $resultArray = array(
            "label" => $isFollowing ? $unfollowLabel : $followLabel,
            "href" => 'javascript://',
            "id" => $linkId,

        );

        if (OW::getPluginManager()->isPluginActive('friends'))
        {
            $resultArray["group"] = "addition";
        }

        $event->add($resultArray);
    }

    public function onProfileBottomContentCollect( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();
        
        $userId = $params["userId"];

        // privacy check
        $viewerId = OW::getUser()->getId();
        $ownerMode = $userId == $viewerId;
        $modPermissions = OW::getUser()->isAuthorized('newsfeed');

        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => NEWSFEED_BOL_Service::PRIVACY_ACTION_VIEW_MY_FEED, 'ownerId' => $userId, 'viewerId' => $viewerId);
            try {
                OW::getEventManager()->trigger(new OW_Event('privacy_check_permission', $privacyParams));
            }
            catch ( RedirectException $e )
            {
                return;
            }
        }
        
        $driver = OW::getClassInstance("NEWSFEED_CLASS_FeedDriver");
        $feed = OW::getClassInstance("NEWSFEED_MCMP_Feed", $driver, "user", $userId);

        $isBloacked = BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $userId);

        if ( OW::getUser()->isAuthorized('base', 'add_comment') )
        {
            if ( $isBloacked )
            {
                $feed->addStatusMessage(OW::getLanguage()->text("base", "user_block_message"));
            }
            else
            {
                $visibility = NEWSFEED_BOL_Service::VISIBILITY_FULL;
                $eventNewsfeed = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_UPDATE_STATUS_FORM_CREATE_IN_PROFILE, array('userId' => $userId)));
                $showUpdateStatusForm = true;
                if(isset($eventNewsfeed->getData()['showUpdateStatusForm'])) {
                    $showUpdateStatusForm = $eventNewsfeed->getData()['showUpdateStatusForm'];
                }
                if($showUpdateStatusForm) {
                    OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_FEED_RENDERED, array('userId' =>$userId )));
                    $feed->addStatusForm('user', $userId, $visibility);
                }
            }
        }
        else 
        {
            $actionStatus = BOL_AuthorizationService::getInstance()->getActionStatus('base', 'add_comment');
            
            if ( $actionStatus["status"] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $feed->addStatusMessage($actionStatus["msg"]);
            }
        }
        
        $feed->setDisplayType(NEWSFEED_CMP_Feed::DISPLAY_TYPE_ACTIVITY);
        
        $feed->setup(array(
            "displayCount" => 20,
            "customizeMode" => false,
            "viewMore" => true
        ));

        $event->add($feed->render());
    }

    public function onDashboardBottomContentCollect( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();

        $userId = $params["userId"];

        // privacy check
        $viewerId = OW::getUser()->getId();
        $ownerMode = $userId == $viewerId;
        $modPermissions = OW::getUser()->isAuthorized('newsfeed');

        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => NEWSFEED_BOL_Service::PRIVACY_ACTION_VIEW_MY_FEED, 'ownerId' => $userId, 'viewerId' => $viewerId);
            try {
                OW::getEventManager()->trigger(new OW_Event('privacy_check_permission', $privacyParams));
            }
            catch ( RedirectException $e )
            {
                return;
            }
        }

        $driver = OW::getClassInstance("NEWSFEED_CLASS_UserDriver");
        $feed = OW::getClassInstance("NEWSFEED_MCMP_Feed", $driver, "my", $userId);

        $isBloacked = BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $userId);

        if ( OW::getUser()->isAuthorized('base', 'add_comment') )
        {
            if ( $isBloacked )
            {
                $feed->addStatusMessage(OW::getLanguage()->text("base", "user_block_message"));
            }
            else
            {
                $visibility = NEWSFEED_BOL_Service::VISIBILITY_FULL;
                $feed->addStatusForm('user', $userId, $visibility);
            }
        }
        else
        {
            $actionStatus = BOL_AuthorizationService::getInstance()->getActionStatus('base', 'add_comment');

            if ( $actionStatus["status"] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $feed->addStatusMessage($actionStatus["msg"]);
            }
        }

        $feed->setDisplayType(NEWSFEED_CMP_Feed::DISPLAY_TYPE_ACTIVITY);

        $feed->setup(array(
            "displayCount" => 20,
            "customizeMode" => false,
            "viewMore" => true
        ));

        $event->add($feed->render());
    }

    public function feedItemRenderFlagBtn( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();
        if (in_array(OW::getLanguage()->text('base', 'flag'), array_column($data['contextMenu'],'label'))) {
            return;
        }

        $userId = OW::getUser()->getId();
        
        if ( empty($userId) || $params['action']['userId'] == $userId )
        {
            return;
        }
        
        $contentType = BOL_ContentService::getInstance()->getContentTypeByEntityType($params['action']['entityType']);
        $flagsAllowed = !empty($contentType) && in_array(BOL_ContentService::MODERATION_TOOL_FLAG, $contentType["moderation"]);
        
        if ( !$flagsAllowed )
        {
            return;
        }
        
        OW::getLanguage()->addKeyForJs("base", "flag_as");
        
        array_unshift($data['contextMenu'], array(
            'label' => OW::getLanguage()->text('base', 'flag'),
            'attributes' => array(
                'onclick' => 'OWM.flagContent($(this).data().etype, $(this).data().eid)',
                "data-etype" => $params['action']['entityType'],
                "data-eid" => $params['action']['entityId']
            )
        ));

        $e->setData($data);
    }
    
    public function onMobileTopMenuAddLink( BASE_CLASS_EventCollector $event )
    {
        if ( OW::getUser()->isAuthenticated() && OW::getUser()->isAuthorized('newsfeed', 'allow_status_update') ) {
            $event->add(array(
                'prefix' => 'newsfeed',
                'key' => 'newsfeed_feed',
                'url' => OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('newsfeed_view_feed'), array(
                    "write" => 1
                ))
            ));
        }
    }

    public function onNotificationRender( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['pluginKey'] == 'newsfeed' 
                && in_array($params['entityType'], array('user_status', 'status_comment', 'status_like','groups-status'))
                && !empty($params['data']["format"]) )
        {
            $data = $params['data'];
            $e->setData($data);
        }
    }
    public  function mobileItemRender( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        switch ($data["string"]["key"]){
              case "birthdays+feed_item_line":
                if (OW::getPluginManager()->isPluginActive('birthdays')){
                    if($params["lastActivity"]["activityType"]=="comment" && isset($data["respond"]["text"]))
                    {
                        $userName = BOL_UserService::getInstance()->getDisplayName($data["userData"]["userId"]);
                        $userUrl = BOL_UserService::getInstance()->getUserUrl($data["userData"]["userId"]);
                        $data["respond"]["text"] = OW::getLanguage()->text('birthdays','feed_activity_birthday_string',array(
                            'user' => '<a href="' . $userUrl . '">' . $userName . '</a>'
                        ));
                    }
                    elseif ($params["lastActivity"]["activityType"]=="like" && isset($data["respond"]["text"])){
                        $userName = BOL_UserService::getInstance()->getDisplayName($data["userData"]["userId"]);
                        $userUrl = BOL_UserService::getInstance()->getUserUrl($data["userData"]["userId"]);
                        $data["respond"]["text"] = OW::getLanguage()->text('birthdays','feed_activity_birthday_string_like',array(
                            'user' => '<a href="' . $userUrl . '">' . $userName . '</a>'
                        ));
                    }
                }
                break;
        }
        if( $params["action"]["entityType"]=="groups-status" && $data["content"]["format"]=="text" && $data["contextFeedType"]=="groups" && OW::getPluginManager()->isPluginActive('groups'))
        {
            $groupService = GROUPS_BOL_Service::getInstance();
            $group = $groupService->findGroupById($data["contextFeedId"]);
            $data["context"]["url"]= $groupService->getGroupUrl($group);
            $data["context"]["label"]=$group->title;
        }
        elseif( $params["action"]["entityType"]=="user-status" && $data["content"]["format"]=="text" && isset($data["ReceiverId"]) )
        {
            $userName = BOL_UserService::getInstance()->getDisplayName($data["ReceiverId"]);
            $userUrl = BOL_UserService::getInstance()->getUserUrl($data["ReceiverId"]);
            $data["context"]["label"] = $userName;
            $data["context"]["url"] = $userUrl;
        }
        elseif ( $params["action"]["entityType"]=="user-status" && $data["content"]["format"]=="text" && $params["lastActivity"]["activityType"]=="comment"){
            $userName = BOL_UserService::getInstance()->getDisplayName($data["data"]["userId"]);
            $userUrl = BOL_UserService::getInstance()->getUserUrl($data["data"]["userId"]);
            if(isset($data["respond"]) && isset($data["respond"]["text"])) {
                $data["respond"]["text"] = OW::getLanguage()->text('newsfeed', 'activity_string_status_comment', array(
                    'user' => '<a href="' . $userUrl . '">' . $userName . '</a>'
                ));
            }

        }

        $e->setData($data);
    }
}