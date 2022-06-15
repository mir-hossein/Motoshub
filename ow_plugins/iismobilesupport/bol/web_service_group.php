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
class IISMOBILESUPPORT_BOL_WebServiceGroup
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

    public function leave(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userId = OW::getUser()->getId();

        if (!isset($_GET['groupId']))
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $groupId = $_GET['groupId'];
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $userInvitedBefore = GROUPS_BOL_Service::getInstance()->findUser($groupId, $userId);
        if($userInvitedBefore != null) {
            if($group->userId == $userId){
                GROUPS_BOL_Service::getInstance()->deleteGroup($groupId);
            }else{
                $deleteUser = GROUPS_BOL_Service::getInstance()->deleteUser($groupId, $userId);
                if (!$deleteUser) {
                    return array('valid' => true, 'leavable' => false);
                }
            }
            $groupData = $this->getGroupInformation($group);
            return array('valid' => true, 'leavable' => true, 'group' => $groupData);
        }
        return array('valid' => false, 'message' => 'authorization_error');
    }

    public function deleteGroup(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if (!isset($_GET['groupId']))
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $groupId = $_GET['groupId'];
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $isOwner = OW::getUser()->getId() == $group->userId;
        $isModerator = OW::getUser()->isAuthorized('groups');

        $eventIisGroupsPlusManager = new OW_Event('iisgroupsplus.check.user.manager.status', array('groupId' => $group->id));
        OW::getEventManager()->trigger($eventIisGroupsPlusManager);
        if(isset($eventIisGroupsPlusManager->getData()['isUserManager'])){
            $isModerator=$eventIisGroupsPlusManager->getData()['isUserManager'];
        }
        if ( !$isOwner && !$isModerator )
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        GROUPS_BOL_Service::getInstance()->deleteGroup($group->id);

        return array('valid' => true, 'leavable' => true, 'groupId' => $group->id);
    }

    public function acceptInvite(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $accepterUserId = OW::getUser()->getId();

        if (!isset($_GET['groupId']))
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $groupId = $_GET['groupId'];
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $invite = GROUPS_BOL_Service::getInstance()->findInvite($groupId, $accepterUserId);
        if($invite != null ){
            GROUPS_BOL_Service::getInstance()->addUser($groupId, $accepterUserId);
        }
        return array('valid' => true, 'registration' => true, 'id' => (int) $group->id, 'group' => $this->getGroupInformation($group));
    }


    public function removeUser(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userId = null;
        $groupId = null;

        if (!isset($_GET['userId']) || !isset($_GET['groupId']))
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $userId = $_GET['userId'];
        $groupId = $_GET['groupId'];
        return $this->removeUserById($userId, $groupId);
    }

    public function removeUserById($userId, $groupId){
        if($userId == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        if($groupId == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if($userId == OW::getUser()->getId() || $group->userId == $userId){
            return array('valid' => false, 'message' => 'admin_delete_error');
        }

        $canEdit = GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($group);
        if($canEdit){
            $groupUser = GROUPS_BOL_Service::getInstance()->findUser($groupId, $userId);
            if($groupUser == null){
                return array('valid' => false, 'message' => 'authorization_error');
            }
            $result = GROUPS_BOL_Service::getInstance()->deleteUser($groupId, $userId);
            if ($result == false) {
                return array('valid' => false, 'message' => 'authorization_error', 'leavable' => false);
            }
            $groupInfo = $this->getGroupInformation($group);
            return array('valid' => true, 'message' => 'deleted', 'group' => $groupInfo);
        }
        return array('valid' => false, 'message' => 'authorization_error');
    }

    public function cancelInvite(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $accepterUserId = OW::getUser()->getId();

        if (!isset($_GET['groupId']))
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $groupId = $_GET['groupId'];
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $invite = GROUPS_BOL_Service::getInstance()->findInvite($groupId, $accepterUserId);
        if($invite != null ){
            GROUPS_BOL_Service::getInstance()->deleteInvite($groupId, $accepterUserId);
        }
        return array('valid' => true, 'registration' => false, 'id' => (int) $group->id );
    }

    public function inviteUser(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }


        $inviterUserId = OW::getUser()->getId();

        if ( !isset($_GET['userId']) || !isset($_GET['groupId']) )
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $userId = $_GET['userId'];
        $groupId = $_GET['groupId'];
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'input_error');
        }
        if(!OW::getUser()->isAdmin()){
            if(!GROUPS_BOL_Service::getInstance()->isCurrentUserInvite($group->id)){
                return array('valid' => false, 'message' => 'authorization_error');
            }
        }

        $eventIisGroupsPlusCheckCanSearchAll = new OW_Event('iisgroupsplus.check.can.invite.all',array('checkAccess'=>true));
        OW::getEventManager()->trigger($eventIisGroupsPlusCheckCanSearchAll);
        if(isset($eventIisGroupsPlusCheckCanSearchAll->getData()['hasAccess'])){
            $hasAccess=true;
        }
        if(isset($eventIisGroupsPlusCheckCanSearchAll->getData()['directInvite']) && $eventIisGroupsPlusCheckCanSearchAll->getData()['directInvite']==true){
            $eventIisGroupsPlusAddAutomatically = new OW_Event('iisgroupsplus.add.users.automatically',array('groupId'=>$groupId,'userIds'=>array($userId)));
            OW::getEventManager()->trigger($eventIisGroupsPlusAddAutomatically);
            return array('valid' => true, 'result_key' => 'add_automatically');
        }else {
            if (isset($hasAccess)) {
                GROUPS_BOL_Service::getInstance()->inviteUser($group->id, $userId, $inviterUserId);
                return array('valid' => true);
            }
            if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('friends', true)) {
                $isFriends = FRIENDS_BOL_Service::getInstance()->findFriendship($userId, $inviterUserId);
                if (isset($isFriends) && $isFriends->status == 'active') {
                    GROUPS_BOL_Service::getInstance()->inviteUser($group->id, $userId, $inviterUserId);
                    return array('valid' => true);
                }
            }
        }
        return array('valid' => false, 'message' => 'input_error');
    }

    public function getInvitableUsers(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $currentUserId = OW::getUser()->getId();

        if ( !isset($_GET['groupId']) )
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $groupId = $_GET['groupId'];
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'input_error');
        }
        if(!OW::getUser()->isAdmin()){
            if(!GROUPS_BOL_Service::getInstance()->isCurrentUserInvite($group->id)){
                return array('valid' => false, 'message' => 'authorization_error');
            }
        }

        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        $key = '';
        if(isset($_GET['key'])){
            $key = $_GET['key'];
        }

        $idList = GROUPS_BOL_Service::getInstance()->getInvitableUserIds($groupId, $currentUserId);
        $users = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->populateInvitableUserList($idList, $key, $first, $count);
        return $users;
    }

    public function getGroups($type){
        if($type != "latest"){
            return array();
        }
        $userId = null;
        if(isset($_GET['userId'])){
            $userId = $_GET['userId'];
        }else if(isset($_GET['username'])){
            $user = BOL_UserService::getInstance()->findByUsername($_GET['username']);
            if($user != null){
                $userId = $user->getId();
            }
        }

        if(OW::getUser()->isAuthenticated() && isset($_GET['my']) && $_GET['my']){
            $userId = OW::getUser()->getId();
        }

        $data = $this->getGroupsByUserId($userId, $type);
        return $data;
    }

    public function getGroupsByUserId($userId = null, $type = 'latest'){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if ( !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if($userId != null){
            $checkPrivacy = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($userId, 'view_my_groups', 'groups');
            if(!$checkPrivacy){
                return array();
            }
        }

        $data = array();
        $groups = array();
        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        if($userId == null){
            $groups = GROUPS_BOL_Service::getInstance()->findGroupList($type, $first, $count);
        }else{
            $groups = GROUPS_BOL_Service::getInstance()->findUserGroupList($userId, $first, $count);
        }

        foreach ($groups as $group){
            $data[] = $this->getGroupInformation($group, 0, 2, array());
        }

        return $data;
    }

    public function getGroup(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if ( !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $groupId = null;
        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        if(isset($_GET['groupId'])){
            $groupId = (int) $_GET['groupId'];
        }

        if($groupId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'authorization_error', 'id' => $groupId);
        }

        $data = $this->getGroupInformation($group, $first, $count);
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('forum', true)) {
            $data['topics'] = $this->getGroupTopics($group);
        }

        return $data;
    }

    public function getGroupTopics($group) {
        if ( $group == null)
        {
            return array();
        }
        if (!GROUPS_BOL_Service::getInstance()->isCurrentUserCanView($group))
        {
            return array();
        }

        $entity = 'groups';
        $entityId = $group->id;
        $forumService = FORUM_BOL_ForumService::getInstance();
        $forumGroup = $forumService->findGroupByEntityId($entity, $entityId);
        if ( empty($forumGroup) )
        {
            return array();
        }

        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }
        $page = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageNumber($first);

        $topicList = $forumService->getGroupTopicList($forumGroup->getId(), $page, $count);
        $topicsData = array();
        foreach ($topicList as $topic){
            $topicsData[] = IISMOBILESUPPORT_BOL_WebServiceForum::getInstance()->preparedTopic($topic);
        }

        return $topicsData;
    }

    public function canAddTopicToGroup($groupId){

        $params = array('entity' => 'groups', 'entityId' => (int) $groupId, 'action' => 'add_topic');
        $event = new OW_Event('forum.check_permissions', $params);
        OW::getEventManager()->trigger($event);

        $canAdd = $event->getData();

        $channelEvent = OW::getEventManager()->trigger(new OW_Event('iisgroupsplus.on.channel.add.widget',
            array('groupId'=>$groupId)));
        $isChannelParticipant = $channelEvent->getData()['channelParticipant'];
        if(isset($isChannelParticipant) && $isChannelParticipant){
            $canAdd=false;
        }

        $canCreateTopic = false;
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisgroupsplus', true)){
            $groupSetting = IISGROUPSPLUS_BOL_GroupSettingDao::getInstance()->findByGroupId($groupId);
            if (isset($groupSetting)){
                if($groupSetting->getWhoCanCreateTopic() == IISGROUPSPLUS_BOL_Service::WCU_MANAGERS)
                {
                    $canCreateTopic = true;
                }
            }
        }

        if ($canCreateTopic) {
            return true;
        }

        return $canAdd;
    }

    public function canUserCreateGroup(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true)){
            return false;
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('groups', 'create') ){
            return false;
        }

        return true;
    }

    public function canUserAccessWithEntity($entityType, $entityId){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true)){
            return false;
        }
        $activity = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->getCreatorActivityOfAction($entityType, $entityId);
        if($activity == null){
            return false;
        }
        $feedIdFromActivities = NEWSFEED_BOL_ActionFeedDao::getInstance()->findByActivityIds(array($activity->id));
        $group = null;
        foreach ($feedIdFromActivities as $feedFromActivity){
            if($feedFromActivity->feedType=="groups"){
                $group = GROUPS_BOL_Service::getInstance()->findGroupById($feedFromActivity->feedId);
            }
        }
        if($group == null){
            return false;
        }
        if ( !GROUPS_BOL_Service::getInstance()->isCurrentUserCanView($group) )
        {
            return false;
        }

        return true;
    }

    public function removeGroupManager(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisgroupsplus', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $managerId = null;
        $groupId = null;
        if (isset($_POST['userId'])){
            $managerId = (int) $_POST['userId'];
        }
        if (isset($_POST['groupId'])){
            $groupId = (int) $_POST['groupId'];
        }
        if($managerId == null || $groupId == null || !OW::getUser()->isAuthenticated()) {
            return array('valid' => false, 'message' => 'input_error');
        }
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $canManage = GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($group);
        if (!$canManage) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $groupUser = GROUPS_BOL_Service::getInstance()->findUser($groupId, $managerId);
        if($groupUser == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $isManager = IISGROUPSPLUS_BOL_GroupManagersDao::getInstance()->getGroupManagerByUidAndGid($groupId, $managerId);
        if ($isManager) {
            IISGROUPSPLUS_BOL_GroupManagersDao::getInstance()->deleteGroupManagerByUidAndGid($groupId, $managerId);
        }

        return array('valid' => true, 'userId' => (int) $managerId, 'groupId' => (int) $groupId);
    }

    public function addGroupManager(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisgroupsplus', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $managerId = null;
        $groupId = null;
        if (isset($_POST['userId'])){
            $managerId = (int) $_POST['userId'];
        }
        if (isset($_POST['groupId'])){
            $groupId = (int) $_POST['groupId'];
        }
        if($managerId == null || $groupId == null || !OW::getUser()->isAuthenticated()) {
            return array('valid' => false, 'message' => 'input_error');
        }
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $canManage = GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($group);
        if (!$canManage) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $groupUser = GROUPS_BOL_Service::getInstance()->findUser($groupId, $managerId);
        if($groupUser == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $isManager = IISGROUPSPLUS_BOL_GroupManagersDao::getInstance()->getGroupManagerByUidAndGid($groupId, $managerId);
        if (!$isManager) {
            IISGROUPSPLUS_BOL_GroupManagersDao::getInstance()->addUserAsManager($groupId, $managerId);
        }

        return array('valid' => true, 'userId' => (int) $managerId, 'groupId' => (int) $groupId);
    }

    public function addFile(){
        if ( !isset($_POST['groupId']) )
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $groupId = $_POST['groupId'];
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        if(!$this->canAddFile($group)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if (isset($_FILES['file']) && isset($_FILES['file']['tmp_name'])) {
            $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
            if (!$isFileClean) {
                return array('valid' => false, 'message' => 'virus_detected');
            }
        }

        $resultArr = IISGROUPSPLUS_BOL_Service::getInstance()->manageAddFile($groupId, $_FILES['file']);
        if(!isset($resultArr) || !$resultArr['result']){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $filesList = IISGROUPSPLUS_BOL_Service::getInstance()->findFileList($group->id, 0, 1);
        $filesInformation = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->preparedFileList($group, $filesList);

        return array('valid' => true, 'files' => $filesInformation);
    }

    public function deleteFile(){
        if ( !isset($_POST['groupId']) || !isset($_POST['id']) )
        {
            return false;
        }

        $groupId = $_POST['groupId'];
        $attachmentId = $_POST['id'];
        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $canDeleteGroupFile = $this->canDeleteFile($group);
        $attachment = BOL_AttachmentDao::getInstance()->findById($attachmentId);
        $canDeleteFile = true;
        if ($attachment->userId != OW::getUser()->getId()) {
            $canDeleteFile = false;
        }
        if(!$canDeleteFile && !$canDeleteGroupFile){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        try {
            IISGROUPSPLUS_BOL_Service::getInstance()->deleteFileForGroup($groupId, $attachmentId);
            return array('valid' => true, 'id' => (int) $attachmentId);
        }
        catch (Exception $e){
            return array('valid' => false, 'message' => 'authorization_error');
        }
    }

    public function canAddFile($group){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);
        if(!$pluginActive){
            return false;
        }

        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisgroupsplus', true);
        if(!$pluginActive){
            return false;
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return false;
        }

        $isUserInGroup = GROUPS_BOL_Service::getInstance()->findUser($group->id, OW::getUser()->getId());
        if(!$isUserInGroup){
            return false;
        }

        $canUploadFile = false;
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisgroupsplus', true)){
            $groupSetting = IISGROUPSPLUS_BOL_GroupSettingDao::getInstance()->findByGroupId($group->id);
            if (isset($groupSetting)){
                if($groupSetting->getWhoCanUploadFile() == IISGROUPSPLUS_BOL_Service::WCU_MANAGERS)
                {
                    $canUploadFile = true;
                }
            }
        }

        $isChannel = IISGROUPSPLUS_BOL_ChannelDao::getInstance()->findIsExistGroupId($group->id);
        $isManager = IISGROUPSPLUS_BOL_GroupManagersDao::getInstance()->getGroupManagerByUidAndGid($group->id, OW::getUser()->getId());
        $isCreator = $group->userId == OW::getUser()->getId() ? true : false;

        if (isset($isManager) || $isCreator) {
            return true;
        }

        if (!OW::getUser()->isAuthorized('groups')) {
            return false;
        }

        if ($canUploadFile) {
            return true;
        }

        if (isset($isChannel)){
            return false;
        }

        return true;
    }

    public function canDeleteFile($group){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);
        if(!$pluginActive){
            return false;
        }

        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisgroupsplus', true);
        if(!$pluginActive){
            return false;
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return false;
        }

        $isUserInGroup = GROUPS_BOL_Service::getInstance()->findUser($group->id, OW::getUser()->getId());
        if(!$isUserInGroup){
            return false;
        }

        $isManager = IISGROUPSPLUS_BOL_GroupManagersDao::getInstance()->getGroupManagerByUidAndGid($group->id, OW::getUser()->getId());
        $isCreator = $group->userId == OW::getUser()->getId() ? true : false;
        if (isset($isManager) || $isCreator){
            return true;
        } else {
            return false;
        }
    }

    public function isFollowable($groupId, $groupDto = null) {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return false;
        }

        if(!OW::getUser()->isAuthenticated()) {
            return false;
        }

        if ($groupId == null) {
            return false;
        }

        if ($groupDto == null) {
            $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        }

        if ( $groupDto === null )
        {
            return false;
        }

        if ($groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE) {
            $userDtoInGroup = GROUPS_BOL_Service::getInstance()->findUser($groupId, OW::getUser()->getId());
            if ($userDtoInGroup == null) {
                return false;
            }
        }

        return true;
    }

    public function isFollower($groupId) {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return false;
        }
        if (!OW::getUser()->isAuthenticated()) {
            return false;
        }
        return NEWSFEED_BOL_Service::getInstance()->isFollow(OW::getUser()->getId(), 'groups', $groupId);
    }

    public function follow() {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $groupId = null;
        if(isset($_GET['groupId'])){
            $groupId = (int) $_GET['groupId'];
        }

        if(!$this->isFollowable($groupId)) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $eventParams = array(
            'userId' => OW::getUser()->getId(),
            'feedType' => 'groups',
            'feedId' => $groupId
        );
        OW::getEventManager()->call('feed.add_follow', $eventParams);
        return array('valid' => true, 'follow' => true, 'groupId' => $groupId);
    }

    public function unFollow() {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $groupId = null;
        if(isset($_GET['groupId'])){
            $groupId = (int) $_GET['groupId'];
        }

        if(!$this->isFollowable($groupId)) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $eventParams = array(
            'userId' => OW::getUser()->getId(),
            'feedType' => 'groups',
            'feedId' => $groupId
        );
        OW::getEventManager()->call('feed.remove_follow', $eventParams);
        return array('valid' => true, 'follow' => false, 'groupId' => $groupId);
    }

    private function getGroupInformation($group, $first = 0, $count = 10, $params = array('users', 'files', 'posts')){
        $imageUrl = GROUPS_BOL_Service::getInstance()->getGroupImageUrl($group);
        $imageUrlPath = GROUPS_BOL_Service::getInstance()->getGroupImagePath($group, GROUPS_BOL_Service::IMAGE_SIZE_SMALL);
        $emptyImage = empty($imageUrlPath) ? true : false;
        $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($group->description, false);

        $groupOwnerInfo = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($group->userId);
        if (!GROUPS_BOL_Service::getInstance()->isCurrentUserCanView($group) )
        {
            $invite = GROUPS_BOL_Service::getInstance()->findInvite($group->id, OW::getUser()->getId());
            if ($invite != null){
                return array(
                    "id" => (int) $group->id,
                    "invite" => true,
                    "title" => $group->title,
                    "description" => $description,
                    "privacy" => $group->privacy,
                    "userId" => (int) $group->userId,
                    "user" => $groupOwnerInfo,
                    'emptyImage' => $emptyImage,
                    "timestamp" => $group->timeStamp,
                    "imageUrl" => $imageUrl,
                    "whoCanView" => $group->whoCanView,
                    "whoCanInvite" => $group->whoCanInvite,
                    "followable" => false,
                    "follower" => false,
                    "whoCanUploadFile" => 'manager',
                    "whoCanCreateTopic" => 'manager',
                );
            }
            return array();
        }

        $categoryValue = "";
        $registered = false;
        $whoCanCreateContent = 'group';
        $whoCanUploadFile = 'participant';
        $whoCanCreateTopic = 'participant';

        $filesInformation = array();
        $managerIds = array();
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisgroupsplus', true)){
            $categoryId = IISGROUPSPLUS_BOL_Service::getInstance()->getGroupCategoryByGroupId($group->id);
            if($categoryId!= null){
                $category = IISGROUPSPLUS_BOL_Service::getInstance()->getCategoryById($categoryId);
                if($category != null){
                    $categoryValue = $category->label;
                }
            }

            if (in_array('files', $params)) {
                $filesList = IISGROUPSPLUS_BOL_Service::getInstance()->findFileList($group->id, $first, $count);
                $filesInformation = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->preparedFileList($group, $filesList);
            }

            $channel = IISGROUPSPLUS_BOL_ChannelDao::getInstance()->findIsExistGroupId($group->id);
            if(isset($channel)){
                $whoCanCreateContent = IISGROUPSPLUS_BOL_Service::WCC_CHANNEL;
            }
            $managers = IISGROUPSPLUS_BOL_GroupManagersDao::getInstance()->getGroupManagersByGroupId($group->id);
            foreach ($managers as $manager) {
                $managerIds[] = (int) $manager->userId;
            }

            $groupSetting = IISGROUPSPLUS_BOL_GroupSettingDao::getInstance()->findByGroupId($group->id);
            if (isset($groupSetting)){
                $whoCanUploadFile = $groupSetting->getWhoCanUploadFile();
                $whoCanCreateTopic = $groupSetting->getWhoCanCreateTopic();
            }
        }

        $postsInformation = $this->getGroupPosts($group->id, $first, $count);

        $lastActivityString = OW::getLanguage()->text('groups','feed_create_string');
        $lastActivityUsername = $groupOwnerInfo['name'];
        $lastActivityTimestamp = $group->timeStamp;
        if($postsInformation != null && sizeof($postsInformation) > 0){
            $find = false;
            foreach ($postsInformation as $postInformation){
                if($find){
                    break;
                }
                $lastActivity = $postInformation;
                if(isset($lastActivity['text']) && !empty($lastActivity['text'])){
                    $find = true;
                    $lastActivityString = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($lastActivity['text'], true, true);
                    if(isset($lastActivity['user']['name'])){
                        $lastActivityUsername = $lastActivity['user']['name'];
                    }

                    if(isset($lastActivity['time'])){
                        $lastActivityTimestamp = $lastActivity['time'];
                    }
                } else if($lastActivity['entityType'] == 'groups-add-file'){
                    $find = true;
                    $lastActivityString = OW::getLanguage()->text('iismobilesupport', 'add_file_string');
                    if(isset($lastActivity['user']['name'])){
                        $lastActivityUsername = $lastActivity['user']['name'];
                    }

                    if(isset($lastActivity['time'])){
                        $lastActivityTimestamp = $lastActivity['time'];
                    }
                } else if(in_array($lastActivity['entityType'], array('groups-join', 'groups-leave')) && !empty($lastActivity['activityString'])){
                    $find = true;
                    $lastActivityString = $lastActivity['activityString'];
                    if(isset($lastActivity['user']['name'])){
                        $lastActivityUsername = $lastActivity['user']['name'];
                    }

                    if(isset($lastActivity['time'])){
                        $lastActivityTimestamp = $lastActivity['time'];
                    }
                } else if($lastActivity['entityType'] == 'forum-topic' && !empty($lastActivity['activityString'])){
                    $find = true;
                    $lastActivityString = $lastActivity['activityString'];
                    if(isset($lastActivity['user']['name'])){
                        $lastActivityUsername = $lastActivity['user']['name'];
                    }

                    if(isset($lastActivity['time'])){
                        $lastActivityTimestamp = $lastActivity['time'];
                    }
                }
            }
        }

        $users = array();
        if (in_array('users', $params)) {
            $idList = array();
            $groupUserList = GROUPS_BOL_GroupUserDao::getInstance()->findListByGroupId($group->id, $first, $count);
            foreach ($groupUserList as $groupUser) {
                $idList[] = $groupUser->userId;
            }
            $usersObject = BOL_UserService::getInstance()->findUserListByIdList($idList);
            $usernames = BOL_UserService::getInstance()->getDisplayNamesForList($idList);
            $avatars = BOL_AvatarService::getInstance()->getAvatarsUrlList($idList);
            foreach ($usersObject as $userObject) {
                $username = null;
                if (isset($usernames[$userObject->id])) {
                    $username = $usernames[$userObject->id];
                }

                $avatarUrl = null;
                if (isset($avatars[$userObject->id])) {
                    $avatarUrl = $avatars[$userObject->id];
                }
                $userData = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->populateUserData($userObject, $avatarUrl, $username);
                $userData['isManager'] = false;
                if (in_array($userObject->id, $managerIds)){
                    $userData['isManager'] = true;
                }
                $users[] = $userData;
            }
        }

        $canInviteUser = true;
        if (!GROUPS_BOL_Service::getInstance()->isCurrentUserInvite($group->id)) {
            $canInviteUser = false;
        }

        $canCreatePost = true;

        if(OW::getUser()->getId() && OW::getUser()->isAuthenticated()){
            $registeredUser = GROUPS_BOL_Service::getInstance()->findUser($group->id, OW::getUser()->getId());
            if($registeredUser != null){
                $registered = true;
            }

            $channelEvent = OW::getEventManager()->trigger(new OW_Event('iisgroupsplus.on.channel.add.widget',
                array('feedId'=> $group->id, 'feedType'=> 'groups' ) ));

            if(isset($channelEvent->getData()['channelParticipant'])){
                $channelEvent->getData()['channelParticipant'];
                $isChannelParticipant = $channelEvent->getData()['channelParticipant'];
                if($isChannelParticipant){
                    $canCreatePost = false;
                }
            }
        }

        $registrable = false;
        if(!$registered && ($group->whoCanView != 'invite' || OW::getUser()->isAdmin())) {
            $registrable = true;
        }

        $isAdmin = GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($group);
        $canAddFile = $this->canAddFile($group);
        $canDeleteFile = $this->canDeleteFile($group);
        $canAddTopic = $this->canAddTopicToGroup($group->id);

        $data = array(
            "id" => (int) $group->id,
            "title" => $group->title,
            "description" => $description,
            "privacy" => $group->privacy,
            "can_add_file" => $canAddFile,
            "can_add_topic" => $canAddTopic,
            "can_delete_file" => $canDeleteFile,
            "userId" => (int) $group->userId,
            "user" => $groupOwnerInfo,
            "timestamp" => $group->timeStamp,
            "imageUrl" => $imageUrl,
            'emptyImage' => $emptyImage,
            "categoryValue" => $categoryValue,
            "isAdmin" => $isAdmin,
            "registered" => $registered,
            "registrable" => $registrable,
            "files" => $filesInformation,
            "can_create_post" => $canCreatePost,
            "can_invite_user" => $canInviteUser,
            "whoCanView" => $group->whoCanView,
            "whoCanInvite" => $group->whoCanInvite,
            "whoCanCreateContent" => $whoCanCreateContent,
            "whoCanUploadFile" => $whoCanUploadFile,
            "whoCanCreateTopic" => $whoCanCreateTopic,
            "lastActivityString" => $lastActivityString,
            "lastActivityUsername" => $lastActivityUsername,
            "lastActivityTimestamp" => $lastActivityTimestamp,
            "users" => $users,
            "followable" => $this->isFollowable($group->id, $group),
            "follower" => $this->isFollower($group->id),
        );

        if (in_array('posts', $params)) {
            $data["posts"] = $postsInformation;
        }

        return $data;
    }


    private function getGroupPosts($groupId, $first = 0, $count = 11){
        if ($count != 11) {
            $count += 1;
        }
        $params = array(
            "feedType" => "groups",
            "feedId" => $groupId,
            "offset" => $first,
            "displayCount" => $count,
            "displayType" => "action",
            "checkMore" => true,
            "feedAutoId" => "feed1",
            "startTime" => time(),
            "formats" => null,
            "endTIme" => 0
        );
        return IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->getActionData($params);
    }

    public function getGroupFields(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $fields = array();
        $language = OW::getLanguage();
        $fields[] = array(
            'name' => 'title',
            'type' => 'text',
            'label' => $language->text('groups', 'create_field_title_label'),
            'presentation' => 'text',
            'values' => array()
        );

        $fields[] = array(
            'name' => 'description',
            'type' => 'text',
            'label' => $language->text('groups', 'create_field_description_label'),
            'presentation' => 'text',
            'values' => array()
        );

        $whoCanViewValues[$language->text('groups', 'form_who_can_view_anybody')] = GROUPS_BOL_Service::WCV_ANYONE;
        $whoCanViewValues[$language->text('groups', 'form_who_can_view_invite')] = GROUPS_BOL_Service::WCV_INVITE;
        $fields[] = array(
            'name' => 'whoCanView',
            'type' => 'select',
            'label' => $language->text('groups', 'form_who_can_view_label'),
            'presentation' => 'radio',
            'values' => $whoCanViewValues
        );

        $whoCanInviteValues[$language->text('groups', 'form_who_can_invite_participants')] = GROUPS_BOL_Service::WCI_PARTICIPANT;
        $whoCanInviteValues[$language->text('groups', 'form_who_can_invite_creator')] = GROUPS_BOL_Service::WCI_CREATOR;

        $fields[] = array(
            'name' => 'whoCanInvite',
            'type' => 'select',
            'label' => $language->text('groups', 'form_who_can_invite_label'),
            'presentation' => 'radio',
            'values' => $whoCanInviteValues
        );

        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisgroupsplus', true)) {
            $whoCanCreateContentValues[$language->text('iisgroupsplus', 'form_who_can_create_content_participants')] = IISGROUPSPLUS_BOL_Service::WCC_GROUP;
            $whoCanCreateContentValues[$language->text('iisgroupsplus', 'form_who_can_create_content_creators')] = IISGROUPSPLUS_BOL_Service::WCC_CHANNEL;

            $fields[] = array(
                'name' => 'whoCanCreateContent',
                'type' => 'select',
                'label' => $language->text('iisgroupsplus', 'who_can_create_content'),
                'presentation' => 'radio',
                'values' => $whoCanCreateContentValues,
                'required' => false
            );

            $categories = IISGROUPSPLUS_BOL_Service::getInstance()->getGroupCategoryList();
            if(sizeof($categories) > 0) {
                $values = array();
                $values[null] = OW::getLanguage()->text('iisgroupsplus', 'select_category');
                foreach ($categories as $category) {
                    $values[$category->label] = $category->id;
                }
                $fields[] = array(
                    'name' => 'categoryStatus',
                    'type' => 'select',
                    'label' => $language->text('iisgroupsplus', 'select_category'),
                    'presentation' => 'radio',
                    'values' => $values,
                    'required' => false
                );
            }
        }

        return $fields;
    }

    public function processCreateGroup(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('groups', 'create') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $valid = true;
        $questions = $this->getGroupFields();
        $formValidator = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkDataFormValid($questions);
        if($formValidator['valid'] == true){
            $result = $this->createGroup();
            if($result == null){
                $valid = false;
            }
            if($valid) {
                return array(
                    'valid' => true,
                    'message' => 'group_created',
                    'group' => array(
                        'id' => (int) $result->id,
                        'time' => $result->timeStamp,
                        'userId' => (int) $result->userId,
                        'title' => $result->title,
                        'description' => $result->description,
                        'whoCanInvite' => $result->whoCanInvite,
                        'whoCanView' => $result->whoCanView,
                    ));
            }
        }else{
            $valid = false;
        }

        if(!$valid){
            return array('valid' => false, 'message' => 'invalid_data');
        }
    }

    public function processEditGroup(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('groups', 'create') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $groupId = null;
        if(isset($_GET['groupId'])){
            $groupId = (int) $_GET['groupId'];
        }

        if($groupId == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $isAdmin = GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($group);
        if(!$isAdmin){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $valid = true;
        $questions = $this->getGroupFields();
        $formValidator = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkDataFormValid($questions);
        if($formValidator['valid'] == true){
            if(!isset($_POST['whoCanCreateContent'])){
                $_POST['whoCanCreateContent'] = 'group';
            }
            $data = $_POST;
            if (isset($_FILES['file'])){
                if ( !empty($_FILES['file']['name']) ){
                    if ( (int) $_FILES['file']['error'] !== 0 ||
                        !is_uploaded_file($_FILES['file']['tmp_name']) ||
                        !UTIL_File::validateImage($_FILES['file']['name']) ){
                        // Do nothing
                    }
                    else{
                        $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
                        if ($isFileClean) {
                            $data['image'] = $_FILES['file'];
                        }
                    }
                }
            }
            $result = GROUPS_BOL_Service::getInstance()->processGroupInfo($group, $data);
            if($result == null){
                $valid = false;
            }
            if($valid) {
                return array(
                    'valid' => true,
                    'message' => 'group_edited',
                    'group' => array(
                        'id' => (int) $result->id,
                        'time' => $result->timeStamp,
                        'userId' => (int) $result->userId,
                        'title' => $result->title,
                        'description' => $result->description,
                        'whoCanInvite' => $result->whoCanInvite,
                        'whoCanView' => $result->whoCanView,
                    ));
            }
        }else{
            $valid = false;
        }

        if(!$valid){
            return array('valid' => false, 'message' => 'invalid_data');
        }
    }

    public function joinGroup(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if ( !OW::getUser()->isAuthorized('groups', 'view') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $groupId = null;
        $userId = OW::getUser()->getId();
        if(isset($_GET['groupId'])){
            $groupId = (int) $_GET['groupId'];
        }

        if($groupId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $findUser = GROUPS_BOL_Service::getInstance()->findUser($groupId, $userId);
        if($findUser != null){
            return array('valid' => true, 'message' => 'add_before');
        }

        $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if($group == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $invite = GROUPS_BOL_Service::getInstance()->findInvite($groupId, $userId);

        if ( $invite !== null )
        {
            GROUPS_BOL_Service::getInstance()->markInviteAsViewed($groupId, $userId);
        }
        else if ( $group->whoCanView == GROUPS_BOL_Service::WCV_INVITE  && !OW::getUser()->isAdmin() && !OW::getUser()->isAuthorized('groups'))
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        GROUPS_BOL_Service::getInstance()->addUser($groupId, $userId);
        $groupData = $this->getGroupInformation($group);

        return array('valid' => true, 'message' => 'user_add', 'group' => $groupData);
    }

    public function createGroup(){
        if(!OW::getUser()->isAuthenticated()){
            return null;
        }

        if(!in_array($_POST['whoCanInvite'], array(GROUPS_BOL_Service::WCI_CREATOR, GROUPS_BOL_Service::WCI_PARTICIPANT))) {
            return null;
        }

        if(!in_array($_POST['whoCanView'], array(GROUPS_BOL_Service::WCV_ANYONE, GROUPS_BOL_Service::WCV_INVITE))) {
            return null;
        }

        $data = $_POST;
        if (isset($_FILES['file'])){
            if ( !empty($_FILES['file']['name']) ){
                if ( (int) $_FILES['file']['error'] !== 0 ||
                    !is_uploaded_file($_FILES['file']['tmp_name']) ||
                    !UTIL_File::validateImage($_FILES['file']['name']) ){
                    // Do nothing
                }
                else{
                    $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
                    if ($isFileClean) {
                        $data['image'] = $_FILES['file'];
                    }
                }
            }
        }

        $group = GROUPS_BOL_Service::getInstance()->createGroup(OW::getUser()->getId(), $data);
        return $group;
    }
}