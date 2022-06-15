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
class IISMOBILESUPPORT_BOL_WebServiceSuggest
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


    public function getUserSuggest($userId){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissuggestfriend', true)){
            return array();
        }

        if($userId == null){
            return array();
        }

        if(!OW::getUser()->isAuthenticated() || OW::getUser()->getId() != $userId){
            return array();
        }
        $users = array();
        $usersId = IISSUGGESTFRIEND_CLASS_Suggest::getInstance()->getSuggestedFriends($userId);
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