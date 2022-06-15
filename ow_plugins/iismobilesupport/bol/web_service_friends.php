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
class IISMOBILESUPPORT_BOL_WebServiceFriends
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

    public function getUserFriends($userId){
        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if(!IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->canUserSeeFeed(OW::getUser()->getId(), $userId)){
            return array();
        }

        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($userId, 'friends_view', 'friends')){
            return array();
        }

        $friendsData = array();
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('friends', true)){
            $first = 0;
            $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
            if(isset($_GET['first'])){
                $first = (int) $_GET['first'];
            }

            $friendsFetch = FRIENDS_BOL_Service::getInstance()->findFriendIdList($userId, $first, $count);
            $userIds = array();
            foreach ($friendsFetch as $friend){
                $userIds[] = $friend;
            }

            if(sizeof($userIds) == 0){
                return array();
            }

            $users = BOL_UserService::getInstance()->findUserListByIdList($userIds);
            $usernames = BOL_UserService::getInstance()->getDisplayNamesForList($userIds);
            $avatars = BOL_AvatarService::getInstance()->getAvatarsUrlList($userIds);
            foreach ($friendsFetch as $friend){
                $userFriendObject = null;
                foreach ($users as $user){
                    if($user->id == $friend){
                        $userFriendObject = $user;
                    }
                }

                if($userFriendObject != null) {
                    $username = null;
                    if(isset($usernames[$userFriendObject->id])){
                        $username = $usernames[$userFriendObject->id];
                    }

                    $avatarUrl = null;
                    if(isset($avatars[$userFriendObject->id])){
                        $avatarUrl = $avatars[$userFriendObject->id];
                    }
                    $friendsData[] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->populateUserData($userFriendObject, $avatarUrl, $username, false, true);
                }
            }
        }
        return $friendsData;
    }

    public function getUserFriendsCount($userId){
        $friendsData = 0;
        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return (int) $friendsData;
        }

        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('friends', true)){
            $friendsData = FRIENDS_BOL_Service::getInstance()->countFriends($userId);
        }
        return (int) $friendsData;
    }

    public function friendRequest()
    {
        if (!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('friends', true)) {
            return array("valid" => false);
        }

        $userRequesterId = null;

        if (isset($_GET['userId'])) {
            $userRequesterId = $_GET['userId'];
        }

        if ($userRequesterId == null || !OW::getUser()->isAuthenticated() || !is_numeric($userRequesterId)) {
            return array("valid" => false);
        }

        $userId = OW::getUser()->getId();

        if($userId == $userRequesterId){
            return array("valid" => false);
        }

        if ( BOL_UserService::getInstance()->isBlocked($userRequesterId, $userId) ||
            BOL_UserService::getInstance()->isBlocked($userId, $userRequesterId)){
            return array("valid" => false);
        }

        $request = FRIENDS_BOL_Service::getInstance()->findByRequesterIdAndUserId($userRequesterId, $userId);
        if($request != null && $request->friendId == $userRequesterId &&  $userId== $request->userId){
            $friendshipData = $this->getFriendshipInformation($userId, $userRequesterId);
            return array('valid' => true, 'message' => 'already_send_request', 'friendship' => $friendshipData);
        }

        FRIENDS_BOL_Service::getInstance()->request($userId, $userRequesterId);
        $friendshipData = $this->getFriendshipInformation($userId, $userRequesterId);
        return array("valid" => true, 'message' => 'send_request', 'friendship' => $friendshipData);
    }

    public function cancelRequest()
    {
        if (!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('friends', true)) {
            return array("valid" => false);
        }

        $userId = null;

        if (isset($_GET['userId'])) {
            $userId = $_GET['userId'];
        }

        if ($userId == null || !OW::getUser()->isAuthenticated()) {
            return array("valid" => false);
        }

        FRIENDS_BOL_Service::getInstance()->cancel(OW::getUser()->getId(), $userId);
        $friendshipData = $this->getFriendshipInformation(OW::getUser()->getId(), $userId);
        return array("valid" => true, 'id' => (int) $userId, 'friendship' => $friendshipData);
    }

    public function acceptFriendRequest(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('friends', true)){
            return array("valid" => false);
        }

        $userRequesterId = null;

        if(isset($_GET['requesterId'])){
            $userRequesterId = $_GET['requesterId'];
        }

        if($userRequesterId == null || !OW::getUser()->isAuthenticated()){
            return array("valid" => false);
        }

        $userId = OW::getUser()->getId();


        if ( BOL_UserService::getInstance()->isBlocked($userRequesterId, $userId) ||
            BOL_UserService::getInstance()->isBlocked($userId, $userRequesterId)){
            return array("valid" => false);
        }

        $request = FRIENDS_BOL_Service::getInstance()->findByRequesterIdAndUserId($userRequesterId, $userId);
        if($request != null && $request->friendId == $userId && $userRequesterId == $request->userId){
            FRIENDS_BOL_Service::getInstance()->accept($userId, $userRequesterId);
            $event = new OW_Event('friends.request-accepted', array(
                'senderId' => $userRequesterId,
                'recipientId' => $userId,
                'time' => time()
            ));
            OW::getEventManager()->trigger($event);
            $friendshipData = $this->getFriendshipInformation($userId, $userRequesterId);
            return array('valid' => true,'id' => (int) $userRequesterId, 'friendship' => $friendshipData);
        }

        return array('valid' => false);
    }


    public function isFriend($user1Id, $user2Id){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('friends', true)){
            return true;
        }

        $isFriends = FRIENDS_BOL_Service::getInstance()->findFriendship($user1Id, $user2Id);
        if (isset($isFriends) && $isFriends->status == 'active') {
            return true;
        }

        return false;
    }

    public function getFriendshipInformation($user1Id, $user2Id){

        /**
         * friendship
         * 0 (not load),
         * 1 (is friend),
         * 2 (is not friend),
         * 3 (send request from itself),
         * 4 (send request from the other side)
         */

        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('friends', true)){
            return 1;
        }

        $isFriends = FRIENDS_BOL_Service::getInstance()->findFriendship($user1Id, $user2Id);
        if (isset($isFriends) && $isFriends->status == 'active') {
            return 1;
        }

        $request = FRIENDS_BOL_Service::getInstance()->findByRequesterIdAndUserId($user2Id, $user1Id);
        if($request != null && $request->friendId == $user1Id && $user2Id == $request->userId){
            return 4;

        } else if($request != null && $request->friendId == $user2Id && $user1Id == $request->userId){
            return 3;

        }

        return 2;
    }

    public function removeFriend(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('friends', true)){
            return array("valid" => false);
        }

        $friendId = null;

        if(isset($_GET['friendId'])){
            $friendId = $_GET['friendId'];
        }

        if($friendId == null || !OW::getUser()->isAuthenticated()){
            return array("valid" => false);
        }

        $userId = OW::getUser()->getId();

        if($friendId == $userId){
            return array("valid" => false);
        }

        FRIENDS_BOL_Service::getInstance()->onCancelFriendshipRequest($friendId,$userId);

        $event = new OW_Event('friends.cancelled', array(
            'senderId' => $friendId,
            'recipientId' => $userId
        ));

        OW::getEventManager()->trigger($event);
        $friendshipData = $this->getFriendshipInformation($userId, $friendId);
        return array('valid' => true, 'friendship' => $friendshipData);
    }
}