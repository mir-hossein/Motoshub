<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisfriendsplus.bol
 * @since 1.0
 */
class IISFRIENDSPLUS_BOL_Service
{
    private static $classInstance;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
    }

    public function isRoleInSelected($roleId, $selectedRolesArray){
        if($selectedRolesArray == null){
            return false;
        }
        foreach ($selectedRolesArray as $selectedRole){
            if($selectedRole == $roleId){
                return true;
            }
        }

        return false;
    }

    public function onUserRegistered(OW_Event $event){
        $params = $event->getParams();
        if(isset($params['forEditProfile']) && $params['forEditProfile']==true){
            return;
        }
        if(!IISSecurityProvider::checkPluginActive('friends', true)){
            return;
        }
        if (isset($params["userId"])) {
            $this->manageByUserIdList(array($params["userId"]));
        }
    }

    public function manageByUserIdList($targetUserIds){
        $config =  OW::getConfig();
        $selectedRoles = $config->getValue('iisfriendsplus', 'selected_roles');
        if($selectedRoles != null){
            $selectedRoles = json_decode($selectedRoles);
        }

        $markUserIds = array();
        if($selectedRoles != null && sizeof($selectedRoles) > 0){
            $usersId = BOL_AuthorizationUserRoleDao::getInstance()->findUsersByRoleIds($selectedRoles);
            foreach ($usersId as $userId){
                if(!in_array($userId->userId, $markUserIds)) {
                    $markUserIds[] = $userId->userId;
                    foreach ($targetUserIds as $targetUserId){
                        if($targetUserId!=$userId->userId) {
                            $this->addFriendship($userId->userId, $targetUserId);
                        }
                    }
                }
            }
        }

        if(IISSecurityProvider::checkPluginActive('mailbox', true)){
            MAILBOX_BOL_ConversationService::getInstance()->resetAllUsersLastData();
        }
    }

    public function manageAllUsers(){
        $numberOfUsers = BOL_UserService::getInstance()->count(true);
        $allUsers = BOL_UserService::getInstance()->findList(0, $numberOfUsers, true);
        $userIds = array();
        foreach ($allUsers as $user){
            if(!in_array($user->id, $userIds)){
                $userIds[] = $user->id;
            }
        }
        $this->manageByUserIdList($userIds);
    }

    public function addFriendship($requesterId, $userId){
        if(!IISSecurityProvider::checkPluginActive('friends', true)){
            return;
        }
        $friendship = FRIENDS_BOL_FriendshipDao::getInstance()->findFriendship($requesterId, $userId);
        if($friendship != null){
            return;
        }
        $dto = new FRIENDS_BOL_Friendship();
        $dto->setUserId($requesterId);
        $dto->setFriendId($userId);
        $dto->setStatus(FRIENDS_BOL_Service::STATUS_ACTIVE);
        $dto->timeStamp = time();
        $dto->notificationSent = 1;
        $dto->viewed = 1;
        $dto->active = 1;
        FRIENDS_BOL_FriendshipDao::getInstance()->save($dto);
    }

    public function getAllUsersForm(){
        $formAllUsers = new Form('manage_all_users');
        $formAllUsers->setAction(OW::getRouter()->urlForRoute('iisfriendsplus_admin_config_all_users'));
        $submit = new Submit('submit');
        $submit->setValue(OW::getLanguage()->text('iisfriendsplus', 'all_users_label'));
        $formAllUsers->addElement($submit);
        return $formAllUsers;
    }
}