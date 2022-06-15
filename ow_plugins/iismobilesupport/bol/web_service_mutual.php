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
class IISMOBILESUPPORT_BOL_WebServiceMutual
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


    public function getUserMutual($currentUserId, $userId){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iismutual', true)){
            return array();
        }

        if($userId == null || $currentUserId == null){
            return array();
        }

        if(!OW::getUser()->isAuthenticated() || $currentUserId == $userId){
            return array();
        }

        $usersInfo = IISMUTUAL_CLASS_Mutual::getInstance()->getMutualFriends($userId, $currentUserId);
        $users = array();
        $usersId = array();
        if(isset($usersInfo['mutualFriensdId'])){
            $usersId = $usersInfo['mutualFriensdId'];
        }
        $usersId = array_slice($usersId,0,100);
        $usersObject = BOL_UserService::getInstance()->findUserListByIdList($usersId);
        $usernames = BOL_UserService::getInstance()->getDisplayNamesForList($usersId);
        $avatars = BOL_AvatarService::getInstance()->getAvatarsUrlList($usersId);
        foreach ($usersObject as $userObject){
            $users[] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->populateUserData($userObject, $avatars[$userObject->id], $usernames[$userObject->id]);
        }
        return $users;
    }
}
