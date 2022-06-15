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
 * Groups Service
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.groups.bol
 * @since 1.0
 */
class GROUPS_BOL_Service
{
    const IMAGE_WIDTH_SMALL = 100;
    const IMAGE_WIDTH_BIG = 400;
    
    const IMAGE_SIZE_SMALL = 1;
    const IMAGE_SIZE_BIG = 2;
    
    const WIDGET_PANEL_NAME = 'group';

    const EVENT_ON_DELETE = 'groups_on_group_delete';
    const EVENT_DELETE_COMPLETE = 'groups_group_delete_complete';
    const EVENT_CREATE = 'groups_group_create_complete';
    const EVENT_BEFORE_CREATE = 'groups_group_before_create';
    const EVENT_EDIT = 'groups_group_edit_complete';
    const EVENT_USER_ADDED = 'groups_user_signed';
    const EVENT_USER_BEFORE_ADDED = 'groups_before_user_signed';
    const EVENT_USER_DELETED = 'groups_user_left';
    const EVENT_DELETE_FORUM = 'forum.delete_group';

    const EVENT_INVITE_ADDED = 'groups.invite_user';
    const EVENT_INVITE_DELETED = 'groups.invite_removed';

    const EVENT_UNINSTALL_IN_PROGRESS = 'groups.uninstall_in_progress';

    const WCV_ANYONE = 'anyone';
    const WCV_INVITE = 'invite';

    const WCI_CREATOR = 'creator';
    const WCI_PARTICIPANT = 'participant';

    const PRIVACY_EVERYBODY = 'everybody';
    const PRIVACY_ACTION_VIEW_MY_GROUPS = 'view_my_groups';

    const LIST_MOST_POPULAR = 'most_popular';
    const LIST_LATEST = 'latest';

    const LIST_ALL = 'all';

    const ENTITY_TYPE_WAL = 'groups_wal';
    const ENTITY_TYPE_GROUP = 'groups';
    const FEED_ENTITY_TYPE = 'group';
    const GROUP_FEED_ENTITY_TYPE = 'groups-status';

    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return GROUPS_BOL_Service
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     *
     * @var GROUPS_BOL_InviteDao
     */
    private $inviteDao;

    /**
     *
     * @var GROUPS_BOL_GroupDao
     */
    private $groupDao;

    /**
     *
     * @var GROUPS_BOL_GroupUserDao
     */
    private $groupUserDao;

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        $this->groupDao = GROUPS_BOL_GroupDao::getInstance();
        $this->groupUserDao = GROUPS_BOL_GroupUserDao::getInstance();
        $this->inviteDao = GROUPS_BOL_InviteDao::getInstance();
    }

    public function saveGroup( GROUPS_BOL_Group $groupDto )
    {
        $this->groupDao->save($groupDto);
        OW::getLogger()->writeLog(OW_Log::INFO, 'edit_group', ['actionType'=>OW_Log::UPDATE, 'enType'=>'group', 'enId'=>$groupDto->getId()]);
    }

    public function saveImages( $postFile, GROUPS_BOL_Group $group )
    {
        $service = GROUPS_BOL_Service::getInstance();

        $smallFile = $service->getGroupImagePath($group, GROUPS_BOL_Service::IMAGE_SIZE_SMALL);
        $bigFile = $service->getGroupImagePath($group, GROUPS_BOL_Service::IMAGE_SIZE_BIG);

        $tmpDir = OW::getPluginManager()->getPlugin('groups')->getPluginFilesDir();
        $smallTmpFile = $tmpDir . IISSecurityProvider::generateUniqueId('small_') . '.jpg';
        $bigTmpFile = $tmpDir . IISSecurityProvider::generateUniqueId('big_') . '.jpg';
        $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN, array('source' => $postFile['tmp_name'], 'destination' => $smallTmpFile)));
        if(isset($checkAnotherExtensionEvent->getData()['destination'])){
            $smallTmpFile = $checkAnotherExtensionEvent->getData()['destination'];
        }

        $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN, array('source' => $postFile['tmp_name'], 'destination' => $bigTmpFile)));
        if(isset($checkAnotherExtensionEvent->getData()['destination'])){
            $bigTmpFile = $checkAnotherExtensionEvent->getData()['destination'];
        }
        $image = new UTIL_Image($postFile['tmp_name']);
        $image->resizeImage(GROUPS_BOL_Service::IMAGE_WIDTH_BIG, null)
            ->saveImage($bigTmpFile)
            ->resizeImage(GROUPS_BOL_Service::IMAGE_WIDTH_SMALL, GROUPS_BOL_Service::IMAGE_WIDTH_SMALL, true)
            ->saveImage($smallTmpFile);

        try
        {
            OW::getStorage()->copyFile($smallTmpFile, $smallFile);
            OW::getStorage()->copyFile($bigTmpFile, $bigFile);
        }
        catch ( Exception $e ) {}

        OW::getStorage()->removeFile($smallTmpFile);
        OW::getStorage()->removeFile($bigTmpFile);
    }

    public function processGroupInfo($group, $values){
        $service = GROUPS_BOL_Service::getInstance();

        if(isset($values['deleteGroupImage']) && $values['deleteGroupImage']==1 && empty($values['image']))
        {
            if ( !empty($group->imageHash) )
            {
                OW::getStorage()->removeFile($service->getGroupImagePath($group));
                OW::getStorage()->removeFile($service->getGroupImagePath($group, GROUPS_BOL_Service::IMAGE_SIZE_BIG));
                $group->imageHash=null;
            }
        }
        if ( !empty($values['image']) )
        {
            if ( !empty($group->imageHash) )
            {
                OW::getStorage()->removeFile($service->getGroupImagePath($group));
                OW::getStorage()->removeFile($service->getGroupImagePath($group, GROUPS_BOL_Service::IMAGE_SIZE_BIG));
            }

            $group->imageHash = IISSecurityProvider::generateUniqueId();
        }

        $group->title = strip_tags($values['title']);

        $values['description'] = UTIL_HtmlTag::stripTagsAndJs($values['description'], array('frame'), array(), true);

        $group->description = $values['description'];
        $group->whoCanInvite = $values['whoCanInvite'];
        $group->whoCanView = $values['whoCanView'];
        $group->lastActivityTimeStamp = time();
        $service->saveGroup($group);
        $categoryStatus = null;
        $reportEnableStatus = false;
        if(isset($values['categoryStatus'])){
            $categoryStatus = $values['categoryStatus'];
        }
        if(isset($values['reportEnableStatus'])){
            $reportEnableStatus = $values['reportEnableStatus'];
        }
        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ADD_CATEGORY_TO_GROUP, array('groupId' => $group->getId(), 'categoryId' => $categoryStatus, 'reportEnableStatus'=> $reportEnableStatus)));
        if(isset($values['whoCanCreateContent'])){
            OW::getEventManager()->trigger(new OW_Event('iisgroupsplus.set.channel.for.group', array('groupId' => $group->getId(),'isChannel' => $values['whoCanCreateContent'])));
        }

        OW::getEventManager()->trigger(new OW_Event('set.group.setting',
            array('values' => $values,'groupId' => $group->getId())));

        if ( !empty($values['image']) )
        {
            $service->saveImages($values['image'], $group);
        }
        OW::getEventManager()->trigger(new OW_Event('update.group.feeds.privacy', array('groupPrivacy' => $values['whoCanView'])));
        return $group;
    }

    public function createGroup($userId, $values)
    {
        $group = new GROUPS_BOL_Group();
        $group->timeStamp = time();
        $group->userId = $userId;

        $data = array();
        foreach ( $group as $key => $value )
        {
            $data[$key] = $value;
        }

        $event = new OW_Event(GROUPS_BOL_Service::EVENT_BEFORE_CREATE, array('groupId' => $group->id), $data);
        OW::getEventManager()->trigger($event);
        $data = $event->getData();

        foreach ( $data as $k => $v )
        {
            $group->$k = $v;
        }

        $service = GROUPS_BOL_Service::getInstance();

        if ( isset($values['image']) && $values['image'] )
        {
            if ( !empty($group->imageHash) )
            {
                OW::getStorage()->removeFile($service->getGroupImagePath($group));
                OW::getStorage()->removeFile($service->getGroupImagePath($group, GROUPS_BOL_Service::IMAGE_SIZE_BIG));
            }

            $group->imageHash = IISSecurityProvider::generateUniqueId();
        }

        $group->title = strip_tags($values['title']);

        $values['description'] = UTIL_HtmlTag::stripTagsAndJs($values['description'], array('frame'), array(), true);

        $group->description = $values['description'];
        $group->whoCanInvite = $values['whoCanInvite'];
        $group->whoCanView = $values['whoCanView'];
        $group->lastActivityTimeStamp = time();
        $service->saveGroup($group);
        $categoryStatus = null;
        $reportEnableStatus = null;
        if(isset($values['categoryStatus'])){
            $categoryStatus = $values['categoryStatus'];
        }
        if(isset($values['reportEnableStatus'])){
            $reportEnableStatus = $values['reportEnableStatus'];
        }
        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ADD_CATEGORY_TO_GROUP, array('groupId' => $group->getId(), 'categoryId' => $categoryStatus, 'reportEnableStatus'=> $reportEnableStatus)));

        $whoCanCreateContent = null;
        if(isset($values['whoCanCreateContent'])){
            $whoCanCreateContent = $values['whoCanCreateContent'];
        }
        OW::getEventManager()->trigger(new OW_Event('iisgroupsplus.set.channel.for.group', array('groupId' => $group->getId(),'isChannel' => $whoCanCreateContent)));


        OW::getEventManager()->trigger(new OW_Event('set.group.setting',
            array('values' => $values,'groupId'=>$group->getId())));

        if ( isset($values['image']) && !empty($values['image']) )
        {
            $this->saveImages($values['image'], $group);
        }

        $is_forum_connected = OW::getConfig()->getValue('groups', 'is_forum_connected');
        // Add forum group
        if ( $is_forum_connected )
        {
            $event = new OW_Event('forum.create_group', array('entity' => 'groups', 'name' => $group->title, 'description' => $group->description, 'entityId' => $group->getId()));
            OW::getEventManager()->trigger($event);
        }

        if ( $group )
        {
            $event = new OW_Event(GROUPS_BOL_Service::EVENT_CREATE, array('groupId' => $group->id));
            OW::getEventManager()->trigger($event);
        }

        $group = GROUPS_BOL_Service::getInstance()->findGroupById($group->id);

        if ( $group->status == GROUPS_BOL_Group::STATUS_ACTIVE )
        {
            BOL_AuthorizationService::getInstance()->trackAction('groups', 'create');
        }

        if($group != null){
            $this->addUser($group->id, $userId);
        }

        return $group;
    }

    public function deleteGroup( $groupId )
    {
        $event = new OW_Event(self::EVENT_ON_DELETE, array('groupId' => $groupId));

        OW::getEventManager()->trigger($event);

        $this->groupDao->deleteById($groupId);

        //$this->groupUserDao->deleteByGroupId($groupId);
        $groupUsers = $this->groupUserDao->findByGroupId($groupId);
        foreach ( $groupUsers as $groupUser )
        {
            $this->deleteUser($groupId, $groupUser->userId, false);
        }

        $eventIisGroupsPlusFiles = new OW_Event('iisgroupsplus.delete.files', array('groupId'=>$groupId));
        OW::getEventManager()->trigger($eventIisGroupsPlusFiles);

        OW::getEventManager()->call('notifications.remove', array(
            'entityType' => self::GROUP_FEED_ENTITY_TYPE,
            'entityId' => $groupId
        ));

        $this->inviteDao->deleteByGroupId($groupId);

        $is_forum_connected = OW::getConfig()->getValue('groups', 'is_forum_connected');
        // Delete forum group
        if ( $is_forum_connected )
        {
            $event = new OW_Event(self::EVENT_DELETE_FORUM, array('entityId' => $groupId, 'entity' => 'groups'));
            OW::getEventManager()->trigger($event);
        }

        $event = new OW_Event(self::EVENT_DELETE_COMPLETE, array('groupId' => $groupId));

        OW::getEventManager()->trigger($event);
        $this->deleteGroupInformation($groupId);
        OW::getLogger()->writeLog(OW_Log::INFO, 'delete_group', ['actionType'=>OW_Log::DELETE, 'enType'=>'group', 'enId'=>$groupId]);
    }

    private  function deleteGroupInformation($groupId){

        if(class_exists('NEWSFEED_BOL_Service')) {
            $actionFeedList = NEWSFEED_BOL_ActionFeedDao::getInstance()->findByFeed('groups',$groupId);
            $actionIds = array();
            foreach($actionFeedList as $actionFeed){
                $newsfeedActivity=NEWSFEED_BOL_Service::getInstance()->findActivity($actionFeed->activityId)[0];
                if($newsfeedActivity!=null) {
                    $actionIds[] = $newsfeedActivity->actionId;
                }
            }
            foreach ($actionIds as $actionId) {
                $action = NEWSFEED_BOL_ActionDao::getInstance()->findById($actionId);
                if($action!=null) {
                    $entityId = $action->entityId;
                    OW::getEventManager()->call('notifications.remove', array(
                        'entityType' => 'status_comment',
                        'entityId' => $entityId
                    ));

                    BOL_CommentService::getInstance()->deleteEntityComments(self::GROUP_FEED_ENTITY_TYPE, $entityId);
                    OW::getEventManager()->call('notifications.remove', array(
                        'entityType' => 'base_profile_wall',
                        'entityId' => $entityId
                    ));

                    OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array(
                        'entityType' => self::GROUP_FEED_ENTITY_TYPE,
                        'entityId' => $entityId,
                        'actionId' => $action->id
                    )));
                }
            }

            NEWSFEED_BOL_Service::getInstance()->removeStatus('groups', $groupId);
        }
    }
    public function deleteUser( $groupId, $userId, $canCancel = true )
    {
        $groupUserDto = $this->groupUserDao->findGroupUser($groupId, $userId);
        
        $event = new OW_Event('groups.before.user.leave', array(
            'groupId' => $groupId,
            'userId' => $userId,
            'groupUserId' => $groupUserDto->id
        ));
        $eventResult = OW::getEventManager()->trigger($event);
        if($canCancel && isset($eventResult->getData()['cancel']) && $eventResult->getData()['cancel']==true) {
           return false;
        }

        $eventIisGroupsPlusManager = new OW_Event('iisgroupsplus.delete.user.as.manager', array('groupId'=>$groupId,'userId'=>$userId));
        OW::getEventManager()->trigger($eventIisGroupsPlusManager);

        $event = new OW_Event('iisgroupsplus.on_delete_user', array(
            'groupId' => $groupId,
            'userId' => $userId,
            'groupUserId' => $groupUserDto->id
        ));
        OW::getEventManager()->trigger($event);

        $this->groupUserDao->delete($groupUserDto);

        $event = new OW_Event(self::EVENT_USER_DELETED, array(
            'groupId' => $groupId,
            'userId' => $userId,
            'groupUserId' => $groupUserDto->id
        ));
        OW::getEventManager()->trigger($event);
        if($canCancel){
            OW::getLogger()->writeLog(OW_Log::INFO, 'delete_group_user', ['actionType'=>OW_Log::DELETE, 'enType'=>'group', 'enId'=>$groupId, 'id'=>$userId]);
        }
        return true;
    }

    public function onUserUnregister( $userId, $withContent )
    {
        if ( $withContent )
        {
            $groups = $this->groupDao->findAllUserGroups($userId);

            foreach ( $groups as $group )
            {
                GROUPS_BOL_Service::getInstance()->deleteGroup($group->id);
            }
        }

        $this->inviteDao->deleteByUserId($userId);
        $this->groupUserDao->deleteByUserId($userId);
    }

    public function findUserGroupList( $userId, $first = null, $count = null, $orderWithLastActivity = true )
    {
        return $this->groupDao->findByUserId($userId, $first, $count, null, null, $orderWithLastActivity);
    }



    public function findUserGroupListCount( $userId )
    {
        return $this->groupDao->findCountByUserId($userId);
    }

    /**
     *
     * @param $groupId
     * @return GROUPS_BOL_Group
     */
    public function findGroupById( $groupId )
    {
        return $this->groupDao->findById((int) $groupId);
    }
    
    public function findGroupListByIds( $groupIds )
    {
        return $this->groupDao->findByIdList($groupIds);
    }

    /**
     * Find latest public group list ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findLatestPublicGroupListIds($first, $count)
    {
        return $this->groupDao->findLatestPublicGroupListIds($first, $count);
    }

    public function findGroupList( $listType, $first=null, $count=null )
    {
        switch ( $listType )
        {
            case self::LIST_MOST_POPULAR:
                return $this->groupDao->findMostPupularList($first, $count);

            case self::LIST_LATEST:
                return $this->groupDao->findOrderedList($first, $count);

            case self::LIST_ALL:
                return $this->groupDao->findAllLimited( $first, $count );
        }

        throw new InvalidArgumentException('Undefined list type');
    }

    public function findGroupListCount( $listType )
    {
        switch ( $listType )
        {
            case self::LIST_MOST_POPULAR:
            case self::LIST_LATEST:
                return $this->groupDao->findAllCount();
        }

        throw new InvalidArgumentException('Undefined list type');
    }

    public function findInvitedGroups( $userId, $first=null, $count=null )
    {
        return $this->groupDao->findUserInvitedGroups($userId, $first, $count);
    }

    public function findInvitedGroupsCount( $userId )
    {
        return $this->groupDao->findUserInvitedGroupsCount($userId);
    }

    public function findMyGroups( $userId, $first=null, $count=null )
    {
        return $this->groupDao->findMyGroups($userId, $first, $count);
    }

    public function findGroupsWithIds( $ids, $first=null, $count=null )
    {
        return $this->groupDao->findGroupsWithIds($ids, $first, $count);
    }

    public function findMyGroupsCount( $userId )
    {
        return $this->groupDao->findMyGroupsCount($userId);
    }

    /*
     * @author Mohammad Agha Abbasloo
     * get all groups with filtering
     */
    public function findGroupsByFiltering($popular=false,$onlyActive=true,$latest=null,$first=null, $count=null,$userId, $groupIds=array(),$searchTitle)
    {
        return $this->groupDao->findGroupsByFiltering($popular,$onlyActive,$latest,$first, $count,$userId, $groupIds,$searchTitle);
    }
    /*
     * @author Mohammad Agha Abbasloo
     * get count of all groups with filtering
     */
    public function findGroupsByFilteringCount($popular=false,$onlyActive=true,$latest=null,$userId, $groupIds=array(),$searchTitle)
    {
        return $this->groupDao->findGroupsByFilteringCount($popular,$onlyActive,$latest,$userId, $groupIds,$searchTitle);
    }

    public function findAllGroupCount()
    {
        return $this->groupDao->findAll();
    }

    public function findByTitle( $title )
    {
        return $this->groupDao->findByTitle($title);
    }

    public function findLimitedList( $count )
    {
        return $this->groupDao->findLimitedList($count);
    }

    public function findUserListCount( $groupId )
    {
        return $this->groupUserDao->findCountByGroupId($groupId);
    }

    public function findUserCountForList( $groupIdList )
    {
        return $this->groupUserDao->findCountByGroupIdList($groupIdList);
    }

    public function findUserList( $groupId, $first, $count )
    {
        $groupUserList = $this->groupUserDao->findListByGroupId($groupId, $first, $count);
        $idList = array();
        foreach ( $groupUserList as $groupUser )
        {
            $idList[] = $groupUser->userId;
        }

        return BOL_UserService::getInstance()->findUserListByIdList($idList);
    }

    public function findGroupUserIdList( $groupId, $privacy = null )
    {
        $groupUserList = $this->groupUserDao->findByGroupId($groupId, $privacy);
        $idList = array();
        foreach ( $groupUserList as $groupUser )
        {
            $idList[] = $groupUser->userId;
        }

        return $idList;
    }

    public function addUser( $groupId, $userId )
    {
        $dto = $this->findUser($groupId, $userId);
        if ( $dto !== null )
        {
            return true;
        }

        $dto = new GROUPS_BOL_GroupUser();
        $dto->timeStamp = time();

        $dto->groupId = $groupId;
        $dto->userId = $userId;

        $data = array();
        foreach ( $dto as $key => $value )
        {
            $data[$key] = $value;
        }

        $event = new OW_Event(self::EVENT_USER_BEFORE_ADDED, array(
            'groupId' => $groupId,
            'userId' => $userId
        ), $data);

        OW::getEventManager()->trigger($event);
        $data = $event->getData();

        foreach ( $data as $k => $v )
        {
            $dto->$k = $v;
        }

        $this->groupUserDao->save($dto);

        $this->deleteInvite($groupId, $userId);

        $event = new OW_Event(self::EVENT_USER_ADDED, array(
                'groupId' => $groupId,
                'userId' => $userId,
                'groupUserId' => $dto->id
            ));

        OW::getEventManager()->trigger($event);
    }

    public function findUser( $groupId, $userId )
    {
        return $this->groupUserDao->findGroupUser($groupId, $userId);
    }

    public function getGroupImageFileName( GROUPS_BOL_Group $group = null, $size = self::IMAGE_SIZE_SMALL )
    {
        if ( $group == null || empty($group->imageHash) )
        {
            return null;
        }

        $suffix = $size == self::IMAGE_SIZE_BIG ? "big-" : "";
        $ext = '.jpg';
        $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN, array('fullPath' => OW::getPluginManager()->getPlugin('groups')->getUserFilesDir() . 'group-' . $group->id . '-'  . $suffix . $group->imageHash)));
        if(isset($checkAnotherExtensionEvent->getData()['ext'])){
            $ext = $checkAnotherExtensionEvent->getData()['ext'];
        }
        return 'group-' . $group->id . '-'  . $suffix . $group->imageHash . $ext;
    }

    public function getGroupImageUrl( GROUPS_BOL_Group $group = null, $size = self::IMAGE_SIZE_SMALL, $returnPath = false )
    {
        $noPictureUrl = OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'group_default.png';
        $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
        if(isset($mobileEvent->getData()['isMobileVersion'])&& $mobileEvent->getData()['isMobileVersion']==true) {
            $noPictureUrl =  OW::getPluginManager()->getPlugin('base')->getStaticUrl() . 'css/images_mobile/group_default_mobile.png';
        }
        if($group == null){
            return $noPictureUrl;
        }
        $path = $this->getGroupImagePath($group, $size);

        return (empty($path) || !OW::getStorage()->fileExists($path)) ? $noPictureUrl : OW::getStorage()->getFileUrl($path, $returnPath);
    }

    public function getGroupImagePath( GROUPS_BOL_Group $group = null, $size = self::IMAGE_SIZE_SMALL )
    {
        if($group == null){
            return null;
        }
        $fileName = $this->getGroupImageFileName($group, $size);

        return empty($fileName) ? null : OW::getPluginManager()->getPlugin('groups')->getUserFilesDir() . $fileName;
    }

    public function getGroupUrl( GROUPS_BOL_Group $group )
    {
        return OW::getRouter()->urlForRoute('groups-view', array('groupId' => $group->id));
    }

    public function isCurrentUserCanEdit( GROUPS_BOL_Group $group )
    {
        $isManager=false;
        $eventIisGroupsPlusManager = new OW_Event('iisgroupsplus.check.user.manager.status', array('groupId'=>$group->getId()));
        OW::getEventManager()->trigger($eventIisGroupsPlusManager);
        if(isset($eventIisGroupsPlusManager->getData()['isUserManager'])){
            $isManager=$eventIisGroupsPlusManager->getData()['isUserManager'];
        }
        return $group->userId == OW::getUser()->getId() || OW::getUser()->isAuthorized('groups') || $isManager==true;
    }

    public function isCurrentUserCanCreate()
    {
        return OW::getUser()->isAuthorized('groups', 'create') || OW::getUser()->isAdmin() || OW::getUser()->isAuthorized('groups');
    }

    public function isCurrentUserCanView( GROUPS_BOL_Group $group , $redirectOnInvite = false)
    {
        if ( $group->userId == OW::getUser()->getId() )
        {
            return true;
        }
        
        if ( OW::getUser()->isAuthorized('groups') )
        {
            return true;
        }

        $canView =  $group->status == GROUPS_BOL_Group::STATUS_ACTIVE && OW::getUser()->isAuthorized('groups', 'view');
        $isMember = GROUPS_BOL_Service::getInstance()->findUser($group->id, OW::getUser()->getId()) !== null;
        if ($canView && !$isMember && !$this->isCurrentUserCanEdit($group) && !OW::getUser()->isAdmin() && $group->whoCanView == GROUPS_BOL_Service::WCV_INVITE ){
            if($redirectOnInvite && GROUPS_BOL_Service::getInstance()->findInvite($group->getId(), OW::getUser()->getId()))
            {
                $invitations = OW::getRouter()->urlForRoute('groups-invite-list');
                OW::getApplication()->redirect($invitations);
                exit();
            }
            return false;
        }
        return $canView;

    }

    public function isCurrentUserCanViewList()
    {
        return OW::getUser()->isAuthorized('groups', 'view');
    }

    public function updateLastTimeStampOfGroup($groupId){
        if($groupId != null) {
            $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
            if ($group != null) {
                $group->lastActivityTimeStamp = time();
                GROUPS_BOL_Service::getInstance()->saveGroup($group);
            }
        }
    }

    public function getInvitableUserIds($groupId, $userId){
        $users = null;

        if ( OW::getEventManager()->call('plugin.friends') )
        {
            $users = OW::getEventManager()->call('plugin.friends.get_friend_list', array(
                'userId' => $userId,
                'count' => 1000
            ));
        }

        if ( $users === null )
        {
            $users = array();
            $userDtos = BOL_UserService::getInstance()->findRecentlyActiveList(0, 1000);

            foreach ( $userDtos as $u )
            {
                if ( $u->id != $userId )
                {
                    $users[] = $u->id;
                }
            }
        }
        $eventIisGroupsPlusCheckCanSearchAll = new OW_Event('iisgroupsplus.check.can.invite.all');
        OW::getEventManager()->trigger($eventIisGroupsPlusCheckCanSearchAll);
        if(isset($eventIisGroupsPlusCheckCanSearchAll->getData()['userIds'])){
            $users=$eventIisGroupsPlusCheckCanSearchAll->getData()['userIds'];
        }
        $idList = array();

        if ( !empty($users) )
        {
            $groupUsers = $this->findGroupUserIdList($groupId);
            $invitedList = $this->findInvitedUserIdList($groupId, $userId);

            foreach ( $users as $uid )
            {
                if ( in_array($uid, $groupUsers) || in_array($uid, $invitedList) )
                {
                    continue;
                }

                $idList[] = $uid;
            }
        }
        return $idList;
    }

    public function isCurrentUserInvite( $groupId )
    {
        $userId = OW::getUser()->getId();

        if ( empty($userId) )
        {
            return false;
        }

        $group = $this->findGroupById($groupId);
        if($group == null) {
            return false;
        }

        if ( $group->status != GROUPS_BOL_Group::STATUS_ACTIVE )
        {
            return false;
        }
        $eventIisGroupsPlusManager = new OW_Event('iisgroupsplus.check.user.manager.status', array('groupId'=>$group->getId()));
        OW::getEventManager()->trigger($eventIisGroupsPlusManager);
        if(isset($eventIisGroupsPlusManager->getData()['isUserManager'])){
            $isManager=$eventIisGroupsPlusManager->getData()['isUserManager'];
            if($isManager){
                return true;
            }
        }
        if ( $group->whoCanInvite == self::WCI_CREATOR )
        {
            return $group->userId == $userId;
        }

        if ( $group->whoCanInvite == self::WCI_PARTICIPANT  )
        {
            return $this->findUser($groupId, $userId) !== null;
        }

        return false;
    }

    public function inviteUser( $groupId, $userId, $inviterId )
    {
        $invite = $this->inviteDao->findInvite($groupId, $userId, $inviterId);

        if ( $invite !== null  )
        {
            return;
        }

        $invite = new GROUPS_BOL_Invite();
        $invite->userId = $userId;
        $invite->groupId = $groupId;
        $invite->inviterId = $inviterId;
        $invite->timeStamp = time();
        $invite->viewed = false;

        $this->inviteDao->save($invite);

        $event = new OW_Event(self::EVENT_INVITE_ADDED, array(
            'groupId' => $groupId,
            'userId' => $userId,
            'inviterId' => $inviterId,
            'inviteId' => $invite->id
        ));

        OW::getEventManager()->trigger($event);
    }

    public function deleteInvite( $groupId, $userId )
    {
        $this->inviteDao->deleteByUserIdAndGroupId($groupId, $userId);

        $event = new OW_Event(self::EVENT_INVITE_DELETED, array(
            'groupId' => $groupId,
            'userId' => $userId
        ));

        OW::getEventManager()->trigger($event);
        OW::getLogger()->writeLog(OW_Log::INFO, 'delete_group_user_invite', ['actionType'=>OW_Log::DELETE, 'enType'=>'group', 'enId'=>$groupId, 'id'=>$userId]);
    }

    public function findInvite( $groupId, $userId, $inviterId = null )
    {
        return $this->inviteDao->findInvite($groupId, $userId, $inviterId);
    }

    public function markInviteAsViewed( $groupId, $userId, $inviterId = null )
    {
        $invite = $this->inviteDao->findInvite($groupId, $userId, $inviterId);

        if ( empty($invite) )
        {
            return false;
        }

        $invite->viewed = true;
        $this->inviteDao->save($invite);

        return true;
    }

    public function markAllInvitesAsViewed( $userId )
    {
        $list = $this->inviteDao->findInviteListByUserId($userId);

        foreach ( $list as $item )
        {
            $item->viewed = true;

            $this->inviteDao->save($item);
        }
    }

    public function findAllInviteList( $groupId )
    {
        return $this->inviteDao->findInviteList($groupId);
    }

    public function findInvitedUserIdList( $groupId, $inviterId )
    {
        $list = $this->inviteDao->findListByGroupIdAndInviterId($groupId, $inviterId);
        $out = array();
        foreach ( $list as $item )
        {
            $out[] = $item->userId;
        }

        return $out;
    }

    public function findUserInvitedGroupsCount( $userId, $newOnly = false )
    {
        return $this->groupDao->findUserInvitedGroupsCount($userId, $newOnly);
    }

    /**
     * Find latest group authors ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findLatestGroupAuthorsIds($first, $count)
    {
        return $this->groupDao->findLatestGroupAuthorsIds($first, $count);
    }

    public function findAllGroupsUserList()
    {
        $users = $this->groupUserDao->findAll();

        $out = array();
        foreach ( $users as $user )
        {
            /* @var $user GROUPS_BOL_GroupUser */
            $out[$user->groupId][] = $user->userId;
        }

        return $out;
    }

    public function setGroupsPrivacy( $ownerId, $privacy )
    {
        $this->groupDao->setPrivacy($ownerId, $privacy);
    }

    public function setGroupUserPrivacy( $userId, $privacy )
    {
        $this->groupUserDao->setPrivacy($userId, $privacy);
    }

    public function clearListingCache()
    {
        OW::getCacheManager()->clean(array( GROUPS_BOL_GroupDao::LIST_CACHE_TAG ));
    }

    public function removeFeedsOfPrivateGroupsByGroupId($groupId){
        $forumPlugin = BOL_PluginService::getInstance()->findPluginByKey('forum');
        if ($forumPlugin != null && $forumPlugin->isActive()) {
            $groupForum = FORUM_BOL_ForumService::getInstance()->findGroupByEntityId('groups', $groupId);
            if ($groupForum != null) {
                $topics = FORUM_BOL_TopicDao::getInstance()->findIdListByGroupIds(array($groupForum->id));
                if ($topics != null) {
                    foreach ($topics as $topic) {
                        $actionGroupForumTopic = NEWSFEED_BOL_ActionDao::getInstance()->findAction('forum-topic', $topic);
                        if ($actionGroupForumTopic != null) {
                            $activities = NEWSFEED_BOL_ActivityDao::getInstance()->findByActionIds(array($actionGroupForumTopic->getId()));
                            $activityIds = array();
                            foreach ($activities as $activity) {
                                $activityIds[] = $activity->id;
                            }
                            $feedList = NEWSFEED_BOL_Service::getInstance()->findFeedListByActivityids($activityIds);
                            foreach ($activityIds as $activityId) {
                                if(isset($feedList[$activityId])) {
                                    foreach ($feedList[$activityId] as $feed) {
                                        if ($feed->feedType == 'user') {
                                            NEWSFEED_BOL_ActionFeedDao::getInstance()->deleteByFeedAndActivityId('user', $feed->feedId, $activityId);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function removeFeedsOfPrivateGroups(){
        $groups = GROUPS_BOL_GroupDao::getInstance()->findAll();
        foreach ($groups as $group) {
            if($group->whoCanView == GROUPS_BOL_Service::WCV_INVITE) {
                $this->removeFeedsOfPrivateGroupsByGroupId($group->getId());
            }
        }
    }

    public function onGroupDeletePostRemoveNotificationHandler(OW_Event $event){
        $params=$event->getParams();
        if(isset($params['entityType']) && $params['entityType']==self::GROUP_FEED_ENTITY_TYPE){
            OW::getEventManager()->call('notifications.remove', array(
                'entityType' => $params['entityType'],
                'entityId' => $params['entityId']
            ));
        }
    }

    public function groupStatusFlagRenderer(OW_Event $event){
        $params = $event->getParams();
        if(isset($params['label']) & isset($params['entityType'])){
            if($params['entityType'] == self::GROUP_FEED_ENTITY_TYPE){
                $event->setData(array('label'=>OW::getLanguage()->text('base','ow_ic_script')));
            }
        }
    }

    public function onLikeNotification( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        if ( $params['entityType'] != 'groups-status' )
        {
            return;
        }

        $userId = $params['userId'];
        $userService = BOL_UserService::getInstance();

        $action = NEWSFEED_BOL_Service::getInstance()->findAction($params['entityType'], $params['entityId']);

        if ( empty($action) )
        {
            return;
        }

        $actionData = json_decode($action->data, true);
        $status = empty($actionData['data']['status'])
            ? $actionData['string']
            : empty($actionData['data']['status']) ? null : $actionData['data']['status'];

        $contentImage = empty($actionData['contentImage']) ? null : $actionData['contentImage'];

        if ( empty($actionData['data']['userId']) )
        {
            $cActivities = $this->service->findActivity( NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE . ':' . $action->id);
            $cActivity = reset($cActivities);

            if ( empty($cActivity) )
            {
                return;
            }

            $ownerId = $cActivity->userId;
        }
        else
        {
            $ownerId = $actionData['data']['userId'];
        }

        $url = OW::getRouter()->urlForRoute('newsfeed_view_item', array('actionId' => $action->id));

        if ( $ownerId != $userId )
        {
            $avatar = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId), true, true, true, false);

            $stringKey = empty($status)
                ? 'newsfeed+email_notifications_empty_status_like'
                : 'newsfeed+email_notifications_status_like';

            $event = new OW_Event('notifications.add', array(
                'pluginKey' => 'newsfeed',
                'action' => 'newsfeed-status_like',
                'entityType' => 'status_like',
                'entityId' => $data['likeId'],
                'userId' => $ownerId
            ), array(
                'format' => "text",
                'avatar' => $avatar[$userId],
                'string' => array(
                    'key' => $stringKey,
                    'vars' => array(
                        'userName' => $userService->getDisplayName($userId),
                        'userUrl' => $userService->getUserUrl($userId),
                        'url' => $url
                    )
                ),
                'url' => $url
            ));

            OW::getEventManager()->trigger($event);
        }
    }

    public function setUserAsOwner( OW_Event $event )
    {
        $params = $event->getParams();


        if(isset($params['contextParentActionKey']) && isset($params['userId']) &&
            isset($params['groupOwnerId'])&& isset($params['groupId']) && isset($params['contextActionMenu'])){
            if ($params['userId'] != $params['groupOwnerId']) {
                $contextAction = new BASE_ContextAction();
                $contextAction->setParentKey($params['contextParentActionKey']);
                if ($params['groupOwnerId'] != $params['userId']) {
                    $isManager=false;

                    $eventIisGroupsPlusManager = new OW_Event('iisgroupsplus.check.user.manager.status', array('groupId'=>$params['groupId'], 'userId' => $params['userId']));
                    OW::getEventManager()->trigger($eventIisGroupsPlusManager);
                    if(isset($eventIisGroupsPlusManager->getData()['isUserManager'])){
                        $isManager = $eventIisGroupsPlusManager->getData()['isUserManager'];
                    }
                    if($isManager){
                        $contextAction->setKey('set_user_as_owner');
                        $contextAction->setLabel(OW::getLanguage()->text('groups', 'set_user_as_owner_label'));
                        $callbackUri = OW::getRequest()->getRequestUri();
                        $setOwnerUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('GROUPS_CTRL_Groups', 'setUserAsOwner', array(
                            'groupId' => $params['groupId'],
                            'userId' => $params['userId']
                        )), array(
                            'redirectUri' => urlencode($callbackUri)
                        ));

                        $contextAction->setUrl('javascript://');
                        $contextAction->addAttribute('data-message', OW::getLanguage()->text('groups', 'set_user_as_owner_confirmation'));
                        $contextAction->addAttribute('onclick', "return confirm_redirect($(this).data().message, '$setOwnerUrl')");
                        $params['contextActionMenu']->addAction($contextAction);
                    }
                }
            }
        }

    }

    public function setMobileUserAsOwner(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['contextMenu']) && isset($params['userId']) &&
            isset($params['groupOwnerId'])&& isset($params['groupId'])){
            if ($params['userId'] != $params['groupOwnerId']) {
                if ($params['groupOwnerId'] != $params['userId']) {
                    $isManager=false;

                    $eventIisGroupsPlusManager = new OW_Event('iisgroupsplus.check.user.manager.status', array('groupId'=>$params['groupId'], 'userId' => $params['userId']));
                    OW::getEventManager()->trigger($eventIisGroupsPlusManager);
                    if(isset($eventIisGroupsPlusManager->getData()['isUserManager'])){
                        $isManager = $eventIisGroupsPlusManager->getData()['isUserManager'];
                    }
                    if( $isManager ){
                        $callbackUri = OW::getRequest()->getRequestUri();
                        $setOwnerUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('GROUPS_MCTRL_Groups', 'setUserAsOwner', array(
                            'groupId' => $params['groupId'],
                            'userId' => $params['userId']
                        )), array(
                            'redirectUri' => urlencode($callbackUri)
                        ));
                        array_unshift($params['contextMenu'], array(
                            'label' => OW::getLanguage()->text('groups', 'set_user_as_owner_label'),
                            'attributes' => array(
                                'onclick' => 'return confirm_redirect($(this).data(\'confirm-msg\'), \''.$setOwnerUrl.'\');',
                                "data-confirm-msg" => OW::getLanguage()->text('groups', 'set_user_as_owner_confirmation')
                            ),
                            "class" => "owm_red_btn",
                            "order" => "3"
                        ));
                        $event->setData(array('contextMenu'=>$params['contextMenu']));

                    }
                }
            }
        }
    }

    public function selectGroup($isRequired = false)
    {
        $groupSelector = new Selectbox('gId');
        $grouplist = $this->findGroupList(GROUPS_BOL_Service::LIST_ALL);
        $option = array();
        $groupsNumber = count($grouplist);
        for ($i=0 ; $i < $groupsNumber ; $i++) {
            $option[$grouplist [$i]->id] = $grouplist [$i]-> title;// $group->title;
        }
        if($isRequired)
            $groupSelector->setRequired();
        $groupSelector ->setOptions($option);
        return  $groupSelector;
    }

    public function setGroupOwner($groupId, $userId){
    if(!isset($groupId) || !isset($userId) ){
        return;
    }
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if ($group != null) {
            $previousOwner = $group->userId;
            $group->userId = $userId;
            GROUPS_BOL_Service::getInstance()->saveGroup($group);

            $eventIisGroupsPlusManager = new OW_Event('iisgroupsplus.check.user.manager.status', array('groupId'=>$groupId, 'userId' => $previousOwner));
            OW::getEventManager()->trigger($eventIisGroupsPlusManager);
            if( isset($eventIisGroupsPlusManager->getData()['isUserManager']) && !($eventIisGroupsPlusManager->getData()['isUserManager']) ){
                IISGROUPSPLUS_BOL_Service::getInstance()->addUserAsManager($groupId, $previousOwner);
            }

        }
}
    public function onGroupUserLeave( OW_Event $event )
    {
        $params = $event->getParams();
        $groupUserId = (int)$params['groupUserId'];
        $leaveFeedString = true;
        if (OW::getConfig()->configExists('iisgroupsplus', 'groupFileAndJoinAndLeaveFeed')) {
            $fileUploadFeedValue = json_decode(OW::getConfig()->getValue('iisgroupsplus', 'groupFileAndJoinAndLeaveFeed'));
            if (!in_array('leaveFeed', $fileUploadFeedValue)) {
                $leaveFeedString = false;
            }
        }
        $groupService = GROUPS_BOL_Service::getInstance();
        $groupService->updateLastTimeStampOfGroup($params["groupId"]);
        $group = $groupService->findGroupById($params["groupId"]);
        //in delete group: first group is deleted then the user deleted
        if (isset($group)) {
            $url = $groupService->getGroupUrl($group);
            $title = UTIL_String::truncate(strip_tags($group->title), 100, '...');

            if ($leaveFeedString) {
                $data = array(
                    'time' => time(),
                    'string' => array(
                        "key" => 'iisgroupsplus+feed_leave_string',
                        "vars" => array(
                            'groupTitle' => $title,
                            'groupUrl' => $url
                        )
                    ),
                    'view' => array(
                        'iconClass' => 'ow_ic_add'
                    ),
                    'data' => array(
                        'joinUsersId' => $params["userId"]
                    )
                );

                $event = new OW_Event('feed.action', array(
                    'feedType' => 'groups',
                    'feedId' => $group->id,
                    'entityType' => 'groups-leave',
                    'entityId' => $groupUserId,
                    'pluginKey' => 'groups',
                    'userId' => $params["userId"],
                    'visibility' => 8,
                ), $data);

                OW::getEventManager()->trigger($event);
            }
        }
    }
}