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
class IISMOBILESUPPORT_BOL_WebServiceNewsfeed
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

    public function userProfilePosts($userId, $first = 0, $count = 11){
        if($userId == null){
            return array();
        }

        if(!$this->canUserSeeFeed(OW::getUser()->getId(), $userId)){
            return array();
        }

        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        if(isset($_GET['count'])){
            $count = $_GET['count'];
        }

        $params = array(
            "feedType" => "user",
            "feedId" => $userId,
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

    public function getPost(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $entityType = null;
        $entityId = null;
        if(isset($_GET['entityType'])){
            $entityType = $_GET['entityType'];
        }

        if(isset($_GET['entityId'])){
            $entityId = $_GET['entityId'];
        }

        if($entityId == null || $entityType == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        if(!$this->userCanSeeAction($entityType, $entityId)){
            return array('valid' => false, 'message' => 'authorization_error', 'entityId' => $entityId, 'entityType' => $entityType);
        }

        $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
        if($action == null){
            return array('valid' => false, 'message' => 'input_error', 'entityId' => $entityId, 'entityType' => $entityType);
        }
        $data = $this->preparedActionData($action);
        if($data == null){
            return array('valid' => false, 'message' => 'input_error', 'entityId' => $entityId, 'entityType' => $entityType);
        }

        return $data;
    }

    public function getDashboard(){
        if(!OW::getUser()->isAuthenticated()){
            return array();
        }

        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize() + 1;
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        $params = array(
            "feedType" => "my",
            "feedId" => OW::getUser()->getId(),
            "offset" => $first,
            "displayCount" => $count,
            "displayType" => "action",
            "checkMore" => true,
            "feedAutoId" => "feed1",
            "startTime" => time(),
            "formats" => null,
            "endTIme" => 0
        );
        return IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->getUserActionData($params);
    }

    public function like(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $entityType = null;
        $entityId = null;
        if(isset($_POST['entityType'])){
            $entityType = $_POST['entityType'];
        }

        if(isset($_POST['entityId'])){
            $entityId = $_POST['entityId'];
        }

        if($entityId == null || $entityType == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        if(!$this->userCanSeeAction($entityType, $entityId)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userId = OW::getUser()->getId();

        NEWSFEED_BOL_Service::getInstance()->addLike($userId, $entityType, $entityId);
        $likesArray = NEWSFEED_BOL_Service::getInstance()->findEntityLikes($entityType, $entityId);

        $likeInfo = array(
            'size' => sizeof($likesArray)
        );

        $feedId = '';
        $feedType = '';
        $feed = $this->findFeed($entityType, $entityId);
        if ($feed) {
            $feedId = (int) $feed->feedId;
            $feedType = $feed->feedType;
        }

        return array('valid' => true, 'message' => 'liked', 'info' => $likeInfo, "feedId" => $feedId, "feedType" => $feedType);
    }


    public function removeAction(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $entityType = null;
        $entityId = null;
        if(isset($_POST['entityType'])){
            $entityType = $_POST['entityType'];
        }

        if(isset($_POST['entityId'])){
            $entityId = $_POST['entityId'];
        }

        if($entityId == null || $entityType == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        if(!$this->userCanSeeAction($entityType, $entityId)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!$this->canRemoveFeedByAction($entityType, $entityId)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $feedId = '';
        $feedType = '';
        $feed = $this->findFeed($entityType, $entityId);
        if ($feed) {
            $feedId = (int) $feed->feedId;
            $feedType = $feed->feedType;
        }

        NEWSFEED_BOL_Service::getInstance()->removeAction($entityType, $entityId);
        $data = array("entityId" => (int) $entityId, "entityType" => $entityType, "feedId" => $feedId, "feedType" => $feedType);
        return array('valid' => true, 'message' => 'removed', 'data' => $data);
    }

    public function getCreatorActivityOfAction($entityType, $entityId){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return null;
        }

        $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
        if($action == null){
            return null;
        }
        $activities = NEWSFEED_BOL_ActivityDao::getInstance()->findIdListByActionIds(array($action->getId()));
        foreach($activities as $activityId){
            $activity = NEWSFEED_BOL_Service::getInstance()->findActivity($activityId)[0];
            if($activity->activityType=='create'){
                return $activity;
            }
        }
        return null;
    }

    public function findAllParticipatedUsersInAction($entityType, $entityId){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return null;
        }

        $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
        if($action == null){
            return null;
        }
        $userIds = array();
        $activities = NEWSFEED_BOL_ActivityDao::getInstance()->findIdListByActionIds(array($action->getId()));
        foreach($activities as $activityId){
            $activity = NEWSFEED_BOL_Service::getInstance()->findActivity($activityId)[0];
            if(!in_array($activity->userId, $userIds)){
                $userIds[] = $activity->userId;
            }
        }
        return $userIds;
    }

    public function getGroupId($entityType, $entityId){
        $activity = $this->getCreatorActivityOfAction($entityType, $entityId);
        if($activity == null){
            return false;
        }
        $feedIdFromActivities = NEWSFEED_BOL_ActionFeedDao::getInstance()->findByActivityIds(array($activity->id));
        $event = null;
        foreach ($feedIdFromActivities as $feedFromActivity){
            if($feedFromActivity->feedType=="groups"){
                return $feedFromActivity->feedId;
            }
        }
        return null;
    }

    public function findFeed($entityType, $entityId){
        $activity = $this->getCreatorActivityOfAction($entityType, $entityId);
        if($activity == null){
            return false;
        }
        $feedIdFromActivities = NEWSFEED_BOL_ActionFeedDao::getInstance()->findByActivityIds(array($activity->id));
        $event = null;
        foreach ($feedIdFromActivities as $feedFromActivity){
            return $feedFromActivity;
        }
        return null;
    }

    public function canUserSendPostOnFeed($userId, $feedId){
        if(OW::getUser()->isAdmin()){
            return true;
        }
        return $this->checkFeedPrivacy($userId, $feedId, 'who_post_on_newsfeed');
    }

    public function getDefaultPrivacyOfUsersPosts($user){
        if($user == null){
            return '';
        }
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissecurityessentials', true)) {
            return '';
        }
        $text = IISSECURITYESSENTIALS_BOL_Service::getInstance()->getActionValueOfPrivacy('other_post_on_feed_newsfeed', $user->id);
        return $text;
    }

    public function canUserSeeFeed($userId, $feedId){
        if(OW::getUser()->isAdmin()){
            return true;
        }

        $blocked = BOL_UserService::getInstance()->isBlocked($userId, $feedId);
        if ($blocked) {
            return false;
        }
        return $this->checkFeedPrivacy($userId, $feedId, 'base_view_profile');
    }

    public function checkFeedPrivacy($userId, $feedId, $key){
        if($userId == null && OW::getUser()->isAuthenticated()){
            $userId = OW::getUser()->isAuthenticated();
        }
        if($feedId == null) {
            return false;
        }

        if($userId == $feedId){
            return true;
        }

        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissecurityessentials', true)) {
            return true;
        }

        $profileOwnerPrivacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->getActionValueOfPrivacy($key, $feedId);
        $profileOwnerPrivacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->validatePrivacy($profileOwnerPrivacy);
        if(!OW::getUser()->isAuthenticated() && $profileOwnerPrivacy != 'everybody'){
            return false;
        }
        if ($profileOwnerPrivacy == 'friends_only') {
            $ownerFriendsId = OW::getEventManager()->call('plugin.friends.get_friend_list', array('userId' => $feedId));
            if(!in_array($userId,$ownerFriendsId)){
                return false;
            }
        } else if ($profileOwnerPrivacy == 'only_for_me') {
            return false;
        }

        return true;
    }

    public function getUserProfileDefaultPrivacy($userId){
        if($userId == null){
            return 'only_for_me';
        }
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissecurityessentials', true)){
            $profileOwnerPrivacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->getActionValueOfPrivacy('other_post_on_feed_newsfeed', $userId);
            $profileOwnerPrivacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->validatePrivacy($profileOwnerPrivacy);
            return $profileOwnerPrivacy;
        }
        return 'only_for_me';
    }

    public function sendPost(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $generalService = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance();
        $text = null;
        $feedType = null;
        $feedId = null;
        $privacy = null;

        if(isset($_POST['text'])){
            $text = $_POST['text'];
        }

        if(isset($_POST['feedId']) && !empty($_POST['feedId'])){
            $feedId = $_POST['feedId'];
        }

        if(isset($_POST['feedType'])){
            $feedType = $_POST['feedType'];
        }

        $userId = OW::getUser()->getId();
        if($feedId == null || empty($feedId) || $feedId == 'null'){
            $feedId = $userId;
        }
        if($text == null || $feedType == null || $feedId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!in_array($feedType, array('user', 'groups'))){
            return array('valid' => false, 'message' => 'feed_type_error');
        }

        if($feedType == 'user'){
            if(!$this->canUserSeeFeed($userId, $feedId) || !$this->canUserSendPostOnFeed($userId, $feedId)){
                return array('valid' => false, 'message' => 'authorization_error');
            }
            $profileOwnerPrivacy = $this->getUserProfileDefaultPrivacy($feedId);
            if($feedId == $userId){
                if(isset($_POST['privacy'])){
                    if (IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissecurityessentials', true)) {
                        $privacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->validatePrivacy($_POST['privacy']);
                        $_POST['privacy'] = $privacy;
                    }
                }else{
                    $privacy = $profileOwnerPrivacy;
                    $_POST['privacy'] = $profileOwnerPrivacy;
                }
            } else if($feedId != $userId){
                $privacy = $profileOwnerPrivacy;
                $_POST['privacy'] = $profileOwnerPrivacy;
            }
        }else{
            $privacy = 'everybody';
            $_POST['privacy'] = 'everybody';
        }

        $visibility = NEWSFEED_BOL_Service::VISIBILITY_FULL;

        if($feedType == 'groups') {
            if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true)){
                return array('valid' => false, 'message' => 'plugin_not_found');
            }
            $group = GROUPS_BOL_Service::getInstance()->findGroupById($feedId);
            if ( $group == null || !GROUPS_BOL_Service::getInstance()->isCurrentUserCanView($group) )
            {
                return array('valid' => false, 'message' => 'authorization_error');
            }
            $private = $group->whoCanView == GROUPS_BOL_Service::WCV_INVITE;
            $visibility = $private
                ? 14 // VISIBILITY_FOLLOW + VISIBILITY_AUTHOR + VISIBILITY_FEED
                : 15; // Visible for all (15)
        }

        $text = empty($text) ? '' : $generalService->stripString($text, false);

        /**
         * replace unicode emoji characters
         */
        $replaceUnicodeEmoji= new OW_Event('iis.replace.unicode.emoji', array('text' => $text));
        OW::getEventManager()->trigger($replaceUnicodeEmoji);
        if(isset($replaceUnicodeEmoji->getData()['correctedText'])) {
            $text = $replaceUnicodeEmoji->getData()['correctedText'];
        }

        /**
         * remove remaining utf8 unicode emoji characters
         */
        $removeUnicodeEmoji= new OW_Event('iis.remove.unicode.emoji', array('text' => $text));
        OW::getEventManager()->trigger($removeUnicodeEmoji);
        if(isset($removeUnicodeEmoji->getData()['correctedText'])) {
            $text = $removeUnicodeEmoji->getData()['correctedText'];
        }

        $text = UTIL_HtmlTag::autoLink($text);

        if($text == ""){
            return array('valid' => false, 'message' => 'empty_text');
        }
        $attachId = null;
        $dtoObject = null;
        $content = array();
        $fileIndex = 0;
        $virusDetectedFiles = array();
        $attachmentList = null;

        if (isset($_POST['attachId'])) {
            $attachmentList = BOL_AttachmentDao::getInstance()->findAttahcmentByBundle('iisnewsfeedplus', $_POST['attachId']);
            if (!empty($attachmentList) && $attachmentList != null) {
                $attachId = $_POST['attachId'];
                foreach ($attachmentList as $attachmentItem) {
                    if ($attachmentItem->status == 1 || $attachmentItem->userId != OW::getUser()->getId()) {
                        return array('valid' => false, 'message' => 'input_file_error');
                    }
                }
            }
        }

        if (isset($_FILES) && $attachId == null) {
            if (isset($_FILES['file'])) {
                $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
                if ($isFileClean) {
                    $dtoObject = $this->manageNewsfeedAttachment($userId, $_FILES['file']);
                    if (isset($dtoObject) || $dtoObject != null) {
                        $attachId = $dtoObject['bundle'];
                    }
                } else {
                    $virusDetectedFiles[] = $_FILES['file']['name'];
                }
            }
            while (isset($_FILES['file' . $fileIndex])) {
                $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file' . $fileIndex]['tmp_name']);
                if ($isFileClean) {
                    $dtoObject = $this->manageNewsfeedAttachment($userId, $_FILES['file' . $fileIndex], $attachId, $fileIndex);
                    if (isset($dtoObject) || $dtoObject != null) {
                        $attachId = $dtoObject['bundle'];
                    }
                } else {
                    $virusDetectedFiles[] = $_FILES['file' . $fileIndex]['name'];
                }
                $fileIndex++;
            }
        }

        if (isset($_POST['fileIds'])) {
            $fileIds = explode(',', $_POST['fileIds']);
            if (!empty($attachmentList) && $attachmentList != null) {
                $fileIndex = 1;
                foreach ($attachmentList as $attachmentItem) {
                    if ($attachmentItem->status == 0 && !in_array($attachmentItem->id, $fileIds)) {
                        BOL_AttachmentService::getInstance()->deleteAttachment(OW::getUser()->getId(), $attachmentItem->id);
                    } else {
                        if (!isset($_POST['attachment_feed_data'])) {
                            $_POST['attachment_feed_data'] = '';
                        }
                        $_POST['attachment_feed_data'] = $_POST['attachment_feed_data'] . $fileIndex . ':' . $attachmentItem->id . '-';
                        $fileIndex += 1;
                    }
                }
            }
        }

        $out = NEWSFEED_BOL_Service::getInstance()
            ->addStatus($userId, $feedType, $feedId, $visibility, $text, array(
                "content" => $content,
                "attachmentId" => $attachId
            ));

        if(!isset($out['entityType']) || !isset($out['entityId'])){
            return array('valid' => false, 'message' => 'error_save_data');
        }

        $action = NEWSFEED_BOL_Service::getInstance()->findAction($out['entityType'], $out['entityId']);
        if ($action == null ){
            return array('valid' => false, 'message' => 'output_error');
        }
        if($privacy != null) {
            $this->updateActionPrivacy($action->getId(), $privacy);
        }

        $result = $this->preparedActionsData(array($action));
        return array('valid' => true, 'message' => 'added', 'item' => $result, 'virus_files' => $virusDetectedFiles);
    }

    public function manageNewsfeedAttachment($userId, $file, $bundle = null, $index = 1){
        BOL_FileTemporaryService::getInstance()->deleteUserTemporaryFiles($userId);
        if ($bundle == null){
            $bundle = IISSecurityProvider::generateUniqueId();
        }
        $maxUploadSize = OW::getConfig()->getValue('base', 'attch_file_max_size_mb');
        $validFileExtensions = json_decode(OW::getConfig()->getValue('base', 'attch_ext_list'), true);
        $attUpload = BOL_AttachmentService::getInstance()->processUploadedFile('newsfeed', $file, $bundle, $validFileExtensions, $maxUploadSize);
        $attachmentId = $attUpload['dto']->id;
        $attachment = BOL_AttachmentDao::getInstance()->findById((int)$attachmentId);
        $attachmentPath = BOL_AttachmentService::getInstance()->getAttachmentsDir(). $attachment->fileName;
        $fileExt = UTIL_File::getExtension($attachment->fileName);
        $newAttachmentFileName =$attachment->origFileName;
        $item = array();
        $item['name'] = $newAttachmentFileName;
        $item['type'] = 'image/'.$fileExt;
        $item['error'] = 0;
        $item['size'] = UTIL_File::getFileSize($attachmentPath,false);
        $pluginKey = 'iisnewsfeedplus';
        $tempFileId = BOL_FileTemporaryService::getInstance()->addTemporaryFile($attachmentPath,$newAttachmentFileName,$userId);
        $item['tmp_name']=BOL_FileTemporaryService::getInstance()->getTemporaryFilePath($tempFileId);
        $dtoArr =BOL_AttachmentService::getInstance()->processUploadedFile($pluginKey, $item, $bundle);

        if (!isset($_POST['attachment_feed_data'])) {
            $_POST['attachment_feed_data'] = '';
        }
        $_POST['attachment_feed_data'] = $_POST['attachment_feed_data'] . $index . ':' . $dtoArr['dto']->id . '-';
        $preview = false;
        if(isset($_POST['preview']) && $_POST['preview'] == 'true'){
            $preview = true;
        } else if(isset($_POST['preview'.$index]) && $_POST['preview'.$index] == 'true'){
            $preview = true;
        }
        if ($preview) {
            if (!isset($_POST['attachment_preview_data'])) {
                $_POST['attachment_preview_data'] = '';
            }
            $_POST['attachment_preview_data'] = $_POST['attachment_preview_data'] . $dtoArr['dto']->id . '-';
        }
        return array('bundle' => $bundle, 'dto' => $dtoArr);
    }

    public function updateActionPrivacy($actionId, $privacy){
        if($actionId == null){
            return;
        }
        $activities = NEWSFEED_BOL_ActivityDao::getInstance()->findIdListByActionIds(array($actionId));

        foreach ($activities as $activityId) {
            $activity = NEWSFEED_BOL_Service::getInstance()->findActivity($activityId)[0];
            $activity->privacy = $privacy;
            NEWSFEED_BOL_Service::getInstance()->saveActivity($activity);
        }
    }

    public function editPost(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisnewsfeedplus', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $entityType = null;
        $entityId = null;
        $text = '';
        if(isset($_POST['entityType'])){
            $entityType = $_POST['entityType'];
        }

        if(isset($_POST['entityId'])){
            $entityId = $_POST['entityId'];
        }

        if(isset($_POST['text'])){
            $text = $_POST['text'];
        }

        if($text == '' || $entityId == null || $entityType == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $result = IISNEWSFEEDPLUS_BOL_Service::getInstance()->editPost($text, $entityId, $entityType);
        if(isset($result['actionId']) && $result['actionId'] != -1){
            $feedId = '';
            $feedType = '';
            $feed = $this->findFeed($entityType, $entityId);
            if ($feed) {
                $feedId = (int) $feed->feedId;
                $feedType = $feed->feedType;
            }

            if (isset($result['text'])) {
                $text = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->setMentionsOnText($result['text']);
            }

            return array('valid' => true, 'message' => 'post_edited', 'feedId' => $feedId, 'feedType' => $feedType, 'text' => $text);
        }
        return array('valid' => false, 'message' => 'authorization_error');
    }

    public function removeLike(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $entityType = null;
        $entityId = null;
        if(isset($_POST['entityType'])){
            $entityType = $_POST['entityType'];
        }

        if(isset($_POST['entityId'])){
            $entityId = $_POST['entityId'];
        }

        if($entityId == null || $entityType == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        if(!$this->userCanSeeAction($entityType, $entityId)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userId = OW::getUser()->getId();
        NEWSFEED_BOL_Service::getInstance()->removeLike($userId, $entityType, $entityId);

        $event = new OW_Event('feed.after_like_removed', array(
            'entityType' => $entityType,
            'entityId' => $entityId,
            'userId' => $userId
        ));

        OW::getEventManager()->trigger($event);

        $feedId = '';
        $feedType = '';
        $feed = $this->findFeed($entityType, $entityId);
        if ($feed) {
            $feedId = (int) $feed->feedId;
            $feedType = $feed->feedType;
        }

        return array('valid' => true, 'message' => 'removed', "feedId" => $feedId, "feedType" => $feedType);
    }

    public function userCanSeeAction($entityType, $entityId){
        if(!OW::getUser()->isAuthenticated()){
            return false;
        }

        if($entityId == null || $entityType == null){
            return false;
        }

        $action = NEWSFEED_BOL_Service::getInstance()->findAction($entityType, $entityId);
        if($action == null){
            return false;
        }

        $isModerator = false;
        if (OW::getUser()->isAuthenticated() && OW::getUser()->isAuthorized($action->pluginKey)) {
            $isModerator = true;
        }
        try{
            if(!$isModerator && !OW::getUser()->isAdmin()){
                OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_FEED_ITEM_RENDERER, array('actionId' => $action->getId())));
            }
        }catch (Exception $e){
            return false;
        }

        return true;
    }

    public function getUserActionData($params){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array();
        }
        $endTime = null;
        if(isset($params['endTime'])){
            $endTime = $params['endTime'];
        }
        $driver = new NEWSFEED_CLASS_FeedDriver();
        $actionList = NEWSFEED_BOL_ActionDao::getInstance()->findByUser($params['feedId'], array($params['offset'], $params['displayCount'], $params['checkMore']), $params['startTime'], $params['formats'], $driver, $endTime);
        return $this->preparedActionsData($actionList);
    }

    public function getActionData($params){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array();
        }

        $driver = new NEWSFEED_CLASS_FeedDriver();
        $endTime = null;
        if(isset($params['endTime'])){
            $endTime = $params['endTime'];
        }
        $actionList = NEWSFEED_BOL_ActionDao::getInstance()->findByFeed($params['feedType'], $params['feedId'], array($params['offset'], $params['displayCount'], $params['checkMore']), $params['startTime'], $params['formats'], $driver, $endTime);
        return $this->preparedActionsData($actionList);
    }

    public function getSiteActionData($first, $count){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array();
        }
        $driver = new NEWSFEED_CLASS_SiteDriver();
        $actionList = NEWSFEED_BOL_ActionDao::getInstance()->findSiteFeed(array($first, $count, false), time(), null, $driver, null);
        return $this->preparedActionsData($actionList);
    }

    public function findOrderedListByIdList($idList)
    {
        if (empty($idList)) {
            return array();
        }

        $unsortedDtoList = NEWSFEED_BOL_ActionDao::getInstance()->findByIdList($idList);
        $unsortedList = array();
        foreach ($unsortedDtoList as $dto) {
            $unsortedList[$dto->id] = $dto;
        }

        $sortedList = array();
        foreach ($idList as $id) {
            if (!empty($unsortedList[$id])) {
                $sortedList[] = $unsortedList[$id];
            }
        }

        return $sortedList;
    }


    public function preparedActionsData($actionList = array()){
        $data = array();
        foreach ($actionList as $action){
            $actionData = $this->preparedActionData($action);
            if($actionData != null){
                $data[] = $actionData;
            }
        }

        return $data;
    }

    private function preparedActionData($action, $params = array()){
        $data = array();
        $generalService = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance();
        $actionDataJson = null;
        $images = array();
        $sounds = array();
        $videos = array();
        $album = array();
        $entityTitle = "";
        $questionData = array();
        $onLocationTitle = "";
        $text = "";
        $forwardString = null;
        $forwardEntityType = null;
        $forwardEntityId = null;
        $entityImage = null;
        $time = "";
        $activityString = "";
        $userId = null;
        $objectId = null;
        $lastActivity = null;
        $entityDescription = null;
        $privacy = null;
        $forumGroupId = null;
        $privacyEditable = false;
        if(isset($action->data)){
            $actionDataJson = $action->data;
        }

        if($actionDataJson != null){
            $actionDataJson = json_decode($actionDataJson);
        }

        if($actionDataJson != null){
            $creatorActivity = $this->getCreatorActivityOfAction($action->entityType, $action->entityId);
            $feedObject = $this->findFeed($action->entityType, $action->entityId);
            if(isset($actionDataJson->ownerId)){
                $userId = $actionDataJson->ownerId;
            }

            if(isset($actionDataJson->data->userId)){
                $userId = $actionDataJson->data->userId;
            }

            if(isset($actionDataJson->string)) {
                if(!isset($actionDataJson->string->key)){
                    $activityString = $generalService->stripString($actionDataJson->string, false, true);
                }else {
                    $keys = explode('+', $actionDataJson->string->key);
                    $varsArray = array();
                    $vars = empty($actionDataJson->string->vars) ? array() : $actionDataJson->string->vars;
                    foreach ($vars as $key => $var) {
                        $varsArray[$key] = $var;
                    }
                    $string = OW::getLanguage()->text($keys[0], $keys[1], $varsArray);
                    if (!empty($string)) {
                        $activityString = $generalService->stripString($string, false, true);
                    }
                }
            }

            if($action->format == "image_content"){
                // This is prefetch image
                if(false && isset($actionDataJson->content->vars->image)) {
                    $images[] = array(
                        "url" => IISSecurityProvider::getInstance()->correctHomeUrlVariable($actionDataJson->content->vars->image),
                    );
                }

                if(isset($actionDataJson->status)){
                    $text = $generalService->stripString($actionDataJson->status, false);
                }
            }else if($action->format == "text" || $action->format == "content"){
                if(isset($actionDataJson->status)) {
                    $text = $generalService->stripString($actionDataJson->status, false);
                }
                if(isset($actionDataJson->data->userId)){
                    $userId = $actionDataJson->data->userId;
                }
            }else if($action->format == "image" &&
                $action->entityType == "photo_comments" &&
                isset($actionDataJson->content->format) &&
                $actionDataJson->content->format == 'image' &&
                isset($actionDataJson->content->vars->url->routeName) &&
                $actionDataJson->content->vars->url->routeName == 'view_photo' &&
                isset($actionDataJson->content->vars->url->vars->id)){

                $photoId = $actionDataJson->content->vars->url->vars->id;
                $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);
                $albumId = $photo->albumId;
                $albumObj = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId);
                if($albumObj != null){
                    $albumLabel = $albumObj->name;
                    $album = array("label" => $albumLabel, "id" => $albumId);
                    $userId = $albumObj->userId;
                }
                $url = PHOTO_BOL_PhotoService::getInstance()->getPhotoFullsizeUrl($photoId, $photo->hash);
                $image = array(
                    "url" => IISSecurityProvider::getInstance()->correctHomeUrlVariable($url),
                );

                $images[] = $image;
            }

            if(isset($actionDataJson->photoIdList)){
                if(isset($actionDataJson->content->vars->status)) {
                    $text = $generalService->stripString($actionDataJson->content->vars->status, false);
                }
                $photoIdList = $actionDataJson->photoIdList;
                $albumId = null;
                $images = array();
                foreach ($photoIdList as $photoId){
                    $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);
                    if($albumId == null) {
                        $albumId = $photo->albumId;
                    }
                    $url = PHOTO_BOL_PhotoService::getInstance()->getPhotoFullsizeUrl($photoId, $photo->hash);
                    $image = array(
                        "url" => IISSecurityProvider::getInstance()->correctHomeUrlVariable($url),
                    );

                    $images[] = $image;
                }

                if($albumId == null && isset($actionDataJson->content->vars->info->route->vars->album)){
                    $albumId = $actionDataJson->content->vars->info->route->vars->album;
                }

                $albumLabel = "";
                if(isset($actionDataJson->content->vars->info->route->label)){
                    $albumLabel = $actionDataJson->content->vars->info->route->label;
                }

                if($albumId != null && $albumLabel == ""){
                    $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId);
                    if($album != null){
                        $albumLabel = $album->name;
                    }
                }
                $album = array("label" => $albumLabel, "id" => $albumId);
            }

            $files = array();
            $videoThumbnailUrl = null;
            $videoUrl = null;
            $videoIframe = false;

            if( isset($actionDataJson->question_id) &&
                IISSecurityProvider::checkPluginActive('iisquestions', true)) {
                $questionId = $actionDataJson->question_id;
                $question= IISQUESTIONS_BOL_Service::getInstance()->findQuestion($questionId);
                if ($question != null) {
                    $multipleAnswer = $question->isMultiple != 0;
                    $questionData = array(
                        'privacy' => $question->privacy,
                        'multiple' => $multipleAnswer,
                        'id' => (int) $questionId,
                    );
                    $optionsArray = IISQUESTIONS_BOL_Service::getInstance()->findOptionList($questionId);
                    $options = array();
                    $answeredOneOptions=false;
                    foreach ($optionsArray as $option){
                        $prepareOptionData=IISMOBILESUPPORT_BOL_WebServiceQuestions::getInstance()->prepareOptionData($option);
                        $options[]=$prepareOptionData['questionData'];
                        if($prepareOptionData['answeredOneOptions']==true) {
                            $answeredOneOptions = true;
                        }
                    }
                    $canAnswerOptions = true;
                    if ($answeredOneOptions && !$multipleAnswer) {
                        $canAnswerOptions = false;
                    }
                    $questionData['add_answer'] = $canAnswerOptions;
                    $questionData['options'] = $options;

                    $userCanAddOption = IISQUESTIONS_BOL_Service::getInstance()->canCurrentUserAddOption($questionId);
                    $questionData['add_option'] = $userCanAddOption;
                }
            }

            if($action->entityType == "avatar-change"){
                if( $creatorActivity != null){
                    $userId = $creatorActivity->userId;
                }
            }

            if($action->entityType == "user_join"){
                if( $creatorActivity != null){
                    $userId = (int) $creatorActivity->userId;
                }
            }

            if($action->entityType == "groups-add-file" || $action->entityType == "event-add-file") {
                $entityId = $action->entityId;
                $fileId = null;
                if($action->entityType == "groups-add-file"){
                    if(IISSecurityProvider::checkPluginActive('groups', true)){
                        $file = IISGROUPSPLUS_BOL_GroupFilesDao::getInstance()->findById($entityId);
                        if($file != null){
                            $fileId = $file->attachmentId;
                        }
                    }
                }else if($action->entityType == "event-add-file"){
                    if(IISSecurityProvider::checkPluginActive('event', true)) {
                        $file = IISEVENTPLUS_BOL_EventFilesDao::getInstance()->findById($entityId);
                        if ($file != null) {
                            $fileId = $file->attachmentId;
                        }
                        if(isset($file->eventId)) {
                            $objectId = $file->eventId;
                        }
                    }
                }
                if($fileId != null) {
                    $attachment = BOL_AttachmentDao::getInstance()->findById($fileId);
                    if (isset($attachment) && $attachment->getId() > 0) {
                        $files[$attachment->getId()] = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->prepareFileInformation($attachment);
                        $userId = $attachment->userId;
                    }
                }else if($creatorActivity != null){
                    $userId = $creatorActivity->userId;
                }

            }else if(in_array($action->entityType, array("groups-join", "groups-leave")) && isset($actionDataJson->data->joinUsersId)) {
                $userId = $actionDataJson->data->joinUsersId;
            }else if($action->entityType == "friend_add") {
                $friendedUsers = $this->findAllParticipatedUsersInAction($action->entityType, $action->entityId);
                if($friendedUsers != null && sizeof($friendedUsers) > 1) {
                    $friendAddInfo = array();
                    $usernames = BOL_UserService::getInstance()->getDisplayNamesForList($friendedUsers);
                    $avatars = BOL_AvatarService::getInstance()->getAvatarsUrlList($friendedUsers);
                    $userIdRequested = $friendedUsers[0];
                    $userIdAccepted = $friendedUsers[1];
                    $userId = $userIdRequested;
                    $paramsText = array(
                        "user_url" => "",
                        "name" => $usernames[$userIdAccepted],
                    );
                    $activityString = OW::getLanguage()->text('friends', 'newsfeed_action_string', $paramsText);
                    $friendAddInfo[] = array(
                        'id' => $userIdRequested,
                        "name" => $usernames[$userIdRequested],
                        "avatarUrl" => $avatars[$userIdRequested]
                    );
                    $friendAddInfo[] = array(
                        'id' => $userIdAccepted,
                        "name" => $usernames[$userIdAccepted],
                        "avatarUrl" => $avatars[$userIdAccepted]
                    );
                    $data['friendAddInformation'] = $friendAddInfo;
                }else{
                    if( $creatorActivity != null){
                        $userId = $creatorActivity->userId;
                    }
                }
                $activityString = $generalService->stripString($activityString, true, true);
            }

            if(in_array($action->entityType, array("groups-join", "groups-leave", "groups-status", "groups-add-file"))) {
                $groupId = $this->getGroupId($action->entityType, $action->entityId);
                $groupEntity = null;
                if($groupId != null){
                    $groupEntity = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
                }
                if($groupEntity != null){
                    $entityTitle = $groupEntity->title;
                    $onLocationTitle = $groupEntity->title;
                    $time = $groupEntity->timeStamp;
                    $objectId = $groupEntity->id;
                    if(in_array($action->entityType, array("groups-join", "groups-leave")) && (empty($text) || $text == "")){
                        $text = $activityString;
                    }
                }
            }
            $previewIdList = array();
            if(isset($actionDataJson->previewIdList)){
                $previewIdList = $actionDataJson->previewIdList;
            }
            if(isset($actionDataJson->attachmentIdList)) {
                foreach ($actionDataJson->attachmentIdList as $fileId) {
                    $attachment = BOL_AttachmentDao::getInstance()->findById($fileId);
                    if (isset($attachment) && $attachment->getId() > 0 && !in_array($fileId, $previewIdList)) {
                        $files[$attachment->getId()] = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->prepareFileInformation($attachment);
                    }
                }
            }


            $imageExtensions=array("jpg","jpeg","png","gif","bmp");
            $videoExtensions=array("mp4", "3gp", "avi", "mov");
            $audioExtensions=array("mp3", "aac");

            foreach ($previewIdList as $previewId){
                $attachment = BOL_AttachmentDao::getInstance()->findById($previewId);
                if (isset($attachment) && $attachment->getId() > 0) {
                    $attInfo = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->prepareFileInformation($attachment);
                    if(isset($attInfo['fileUrl'])){
                        $extension = '';
                        if (isset(pathinfo($attachment->getOrigFileName())['extension'])) {
                            $extension = strtolower(pathinfo($attachment->getOrigFileName())['extension']);
                        }

                        $defaultThumbnailUrl = OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'video-no-video.png';
                        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisnewsfeedplus', true)){
                            $thumbnailObj = IISNEWSFEEDPLUS_BOL_ThumbnailDao::getInstance()->getThumbnailById($attInfo['id']);
                            if ($thumbnailObj == null) {
                                if (OW::getUser()->isAuthenticated() && $attachment->userId == OW::getUser()->getId()) {
                                    $defaultThumbnailUrl = null;
                                }
                            } else {
                                $defaultThumbnailUrl = IISNEWSFEEDPLUS_BOL_Service::getInstance()->getThumbnailFilePath($thumbnailObj->getName());
                            }
                        }

                        $attName = '';
                        if (isset($attInfo['fileName'])){
                            $attName = $attInfo['fileName'];
                        }

                        if(in_array($extension, $imageExtensions)) {
                            $images[] = array(
                                "url" => $attInfo['fileUrl'],
                                "id" => $attInfo['id'],
                                "name" => $attName,
                            );
                        } else if(in_array($extension, $audioExtensions)) {
                            $sounds[] = array(
                                "url" => $attInfo['fileUrl'],
                                "id" => $attInfo['id'],
                                "name" => $attName,
                            );
                        } else if(in_array($extension, $videoExtensions)) {
                            $videos[] = array(
                                "url" => $attInfo['fileUrl'],
                                "id" => $attInfo['id'],
                                "thumbnail" => $defaultThumbnailUrl,
                                "name" => $attName,
                            );
                        }
                    }
                }
            }

            if($action->entityType == "group" && IISSecurityProvider::checkPluginActive('groups', true)){
                $groupEntity = GROUPS_BOL_Service::getInstance()->findGroupById($action->entityId);
                if ($groupEntity != null) {
                    $entityTitle = $groupEntity->title;
                    $objectId = $groupEntity->id;
                    $onLocationTitle = $groupEntity->title;
                    $entityDescription = $groupEntity->description;
                    $entityImage = GROUPS_BOL_Service::getInstance()->getGroupImageUrl($groupEntity);
                    $time = $groupEntity->timeStamp;
                }
            }else if($action->entityType == "event" && IISSecurityProvider::checkPluginActive('event', true)){
                $eventEntity = EVENT_BOL_EventService::getInstance()->findEvent($action->entityId);
                if ($eventEntity != null) {
                    if ($eventEntity->getImage()) {
                        $entityImage = EVENT_BOL_EventService::getInstance()->generateImageUrl($eventEntity->getImage(), true);
                    } else {
                        $entityImage = EVENT_BOL_EventService::getInstance()->generateDefaultImageUrl();
                    }
                    $objectId = $eventEntity->id;
                    $entityTitle = $eventEntity->title;
                    $onLocationTitle = $eventEntity->title;
                    $entityDescription = $eventEntity->description;
                    $time = $eventEntity->createTimeStamp;
                }
            }else if($action->entityType == "news-entry" && IISSecurityProvider::checkPluginActive('iisnews', true)){
                $newsEntity = EntryService::getInstance()->findById($action->entityId);
                if ($newsEntity != null) {
                    $entityTitle = $generalService->stripString($newsEntity->title);
                    $entityDescription = $newsEntity->entry;
                    $objectId = $newsEntity->id;
                    if ($newsEntity->getImage()) {
                        $entityImage = EntryService::getInstance()->generateImageUrl($newsEntity->getImage(), true);
                    } else {
                        $entityImage = EntryService::getInstance()->generateDefaultImageUrl();
                    }
                    $time = $newsEntity->timestamp;
                }
            }else if($action->entityType == "forum-topic" && IISSecurityProvider::checkPluginActive('forum', true)){
                $topic = FORUM_BOL_ForumService::getInstance()->findTopicById($action->entityId);
                if ($topic) {
                    $postForum = FORUM_BOL_PostDao::getInstance()->findTopicPostList($topic->id, 0, 1);
                    $entityTitle = $topic->title;
                    $objectId = $topic->id;
                    if (isset($postForum) && $postForum != null && sizeof($postForum) > 0){
                        $entityDescription = $postForum[0]->text;
                    }
                    $forumService = FORUM_BOL_ForumService::getInstance();
                    $groupInfo = $forumService->getGroupInfo($topic->groupId);
                    if ( $groupInfo )
                    {
                        $forumSection = $forumService->findSectionById($groupInfo->sectionId);
                        if ( $forumSection && $forumSection->entity == 'groups' ){
                            $forumGroupId = (int) $groupInfo->entityId;
                        }
                    }
                }
            }else if($action->entityType == "video_comments" && IISSecurityProvider::checkPluginActive('iisvideoplus', true) && IISSecurityProvider::checkPluginActive('video', true)){
                $clip = VIDEO_BOL_ClipService::getInstance()->findClipById($action->entityId);
                if($clip != null){
                    $entityTitle = $clip->title;
                    $entityDescription = $clip->description;
                    $objectId = $clip->id;

                    if (substr($clip->code, 0, 7) == "<iframe") {
                        $videoUrl = $clip->code;
                        $videoIframe = true;
                    } else {
                        if (strpos($clip->code, 'https://www.aparat.com/video/video/embed/videohash/') !== false) {
                            $parts = explode('/', strstr($clip->code, 'https://www.aparat.com/video/video/embed/videohash/'));
                            $aparat_video_ID = $parts[7];
                            $videoUrl = '<iframe src="https://www.aparat.com/video/video/embed/videohash/'.$aparat_video_ID.'/vt/frame" allowfullscreen="true"></iframe>';
                            $videoIframe = true;
                        } else {
                            $videoUrl = IISVIDEOPLUS_BOL_Service::getInstance()->getVideoFilePath($clip->code);
                        }
                    }
                    if (!empty($clip->thumbUrl)) {
                        $videoThumbnailUrl = IISVIDEOPLUS_BOL_Service::getInstance()->getVideoFilePath($clip->thumbUrl);
                    }
                }else{
                    return null;
                }
            } else if($action->entityType == "blog-post" && isset($actionDataJson->content->vars->title)){
                $entityTitle = $generalService->stripString($actionDataJson->content->vars->title);
                $blogEntity = PostService::getInstance()->findById($action->entityId);
                $entityDescription = $blogEntity->post;
                $objectId = $blogEntity->id;
                $entityImage = OW::getPluginManager()->getPlugin('base')->getStaticUrl(). 'css/images/' . 'no-picture.png';
                $time = $blogEntity->timestamp;
            }

            if($creatorActivity != null && isset($creatorActivity->timeStamp)){
                $time = $creatorActivity->timeStamp;
            }else if(empty($time) && isset($actionDataJson->time)){
                $time = $actionDataJson->time;
            }

            $lastActivityObject = $this->getLastActivity($action->entityType, $action->entityId);
            if($lastActivityObject != null && isset($lastActivityObject['data']['string'])){
                $lastActivityString = $lastActivityObject['data']['string'];
                $lastActivityString = $this->getLocalizedText($lastActivityString);
                $assigns = array();
                if(isset($lastActivityObject['data']['assigns'])){
                    $assigns = $lastActivityObject['data']['assigns'];
                }
                $lastActivityString = $this->processAssigns($lastActivityString, $assigns);
                $lastActivityString = $generalService->stripString($lastActivityString, true, true);
                $lastActivity = array(
                    "timestamp" => $lastActivityObject['timeStamp'],
                    "user" => IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($lastActivityObject['userId']),
                    "text" => $lastActivityString
                );
            }

            $likesInformation = $this->getLikesInformation($action->entityType, $action->entityId);

            $features['likable'] = true;
            $features['commentable'] = true;
            if(isset($actionDataJson->features)){
                $features['likable'] = false;
                $features['commentable'] = false;
                if(in_array('likes', $actionDataJson->features)){
                    $features['likable'] = true;
                }
                if(in_array('comments', $actionDataJson->features)){
                    $features['commentable'] = true;
                }
            }

            $entityTypeBlackList = array('friend_add', 'groups-status', 'group', 'group-join', 'event', 'groups-add-file', 'forum-topic');
            $feedTypeWhiteList = array('user', 'my', 'site');
            if($creatorActivity != null &&
                $feedObject != null &&
                !in_array($action->entityType, $entityTypeBlackList) &&
                in_array($feedObject->feedType , $feedTypeWhiteList)){
                $privacy = $creatorActivity->privacy;
                if($feedObject->feedId == OW::getUser()->getId()){
                    $privacyEditable = true;
                }
            }

            if(isset($actionDataJson->sourceUser)){
                $doc = new DOMDocument();
                @$doc->loadHTML(mb_convert_encoding($actionDataJson->sourceUser, 'HTML-ENTITIES', 'UTF-8'));
                $doc->removeChild($doc->doctype);
                $link = $doc->getElementsByTagName('a');
                if (isset($link) && isset($link->item(0)->nodeValue) ) {
                    $forwardString = $generalService->stripString($link->item(0)->nodeValue);
                }
                if(isset($actionDataJson->contextFeedType) && $actionDataJson->contextFeedType == 'groups'){
                    $forwardEntityType = 'groups-status';
                    $forwardEntityId = $actionDataJson->contextFeedId;
                    $groupEntity = GROUPS_BOL_Service::getInstance()->findGroupById($forwardEntityId);
                    if ($groupEntity != null) {
                        $entityTitle = $groupEntity->title;
                        $onLocationTitle = $groupEntity->title;
                    }
                }
            }

            if($action->entityType == 'user-status' && $feedObject != null && $feedObject->feedType == 'user' && $feedObject->feedId != $userId){
                $onLocationTitle = BOL_UserService::getInstance()->getDisplayName($feedObject->feedId);
                $objectId = $feedObject->feedId;
            }

            if($action->entityType == 'birthday'){
                if (isset($actionDataJson->userData) && isset($actionDataJson->userData->userId)) {
                    $userId = $actionDataJson->userData->userId;
                    if (isset($actionDataJson->birthdate)){
                        $birthdayTime = $actionDataJson->birthdate;
                        $dateTime = new DateTime($birthdayTime);
                        $dateTime = $dateTime->getTimestamp();
                        $text = UTIL_DateTime::formatSimpleDate($dateTime, true);
                    }
                }
            }

            if($objectId == null){
                $objectId = $action->entityId;
            }

            if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisnewsfeedplus', true)){
                $canEditPost = IISNEWSFEEDPLUS_BOL_Service::getInstance()->canEditPost($action->entityId, $action->entityType);
                $data['editable'] = $canEditPost;
            }

            $text = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->setMentionsOnText($text);

            $forwardable = false;
            $eventForwardable = OW::getEventManager()->trigger(new OW_Event('newsfeed.can_forward_post',array('entityId' => (int) $action->entityId, 'entityType' => $action->entityType)));
            if(isset($eventForwardable->getData()['forwardable'])){
                $forwardable = $eventForwardable->getData()['forwardable'];
            }

            $data['objectId'] = (int) $objectId;
            $data['entityId'] = (int) $action->entityId;
            $data['entityType'] = $action->entityType;
            $data['text'] = $text;
            $data['forwardable'] = $forwardable;
            $data['actionId'] = (int) $action->id;
            $data['likable'] = $features['likable'];
            $data['removable'] = $this->canRemoveFeed($action, OW::getUser()->getId(), $creatorActivity);
            $data['commentable'] = $features['commentable'];
            $data['user'] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($userId);
            $data['likes'] = $likesInformation;
            $data['user_like'] = $this->getUserLikesValue($likesInformation);
            $data['comments_count'] = IISMOBILESUPPORT_BOL_WebServiceComment::getInstance()->getCommentsCount($action->entityType, $action->entityId);
            if (in_array('comments', $params)){
                $data['comments'] = IISMOBILESUPPORT_BOL_WebServiceComment::getInstance()->getCommentsInformation($action->entityType, $action->entityId, 1);
            }
            $data['video_iframe'] = $videoIframe;
            $data['privacy'] = $privacy;
            $data['privacyEditable'] = $privacyEditable;
            $data['time'] = $time;
            $data['flagAble'] =  true;

            // Temporary we should use here
            $data['onLocationTitle'] = $onLocationTitle;
            $data['activityString'] = $activityString;
            $data['entityTitle'] = $entityTitle;
            $data['album'] = $album;
            $data['files'] = $files;
            $data['question'] = $questionData;
            $data['images'] = $images;
            $data['sounds'] = $sounds;
            $data['videos'] = $videos;
            $data['video_url'] = $videoUrl;
            $data['entityDescription'] = $generalService->stripString($entityDescription);
            $data['forwardEntityId'] = $forwardEntityId;
            $data['forwardEntityType'] = $forwardEntityType;
            $data['forwardString'] = $forwardString;
            $data['entityImage'] = $entityImage;
            $data['video_thumbnail_url'] = $videoThumbnailUrl;
            $data['forumGroupId'] = $forumGroupId;
            $data['lastActivity'] = $lastActivity;

            // Set data if they are not empty
            if ($onLocationTitle != "") {
                $data['onLocationTitle'] = $onLocationTitle;
            }
            if ($activityString != "") {
                $data['activityString'] = $activityString;
            }
            if ($entityTitle != "") {
                $data['entityTitle'] = $entityTitle;
            }
            if (sizeof($album) > 0) {
                $data['album'] = $album;
            }
            if (sizeof($files) > 0) {
                $data['files'] = $files;
            }
            if (sizeof($questionData) > 0) {
                $data['question'] = $questionData;
            }
            if (sizeof($images) > 0) {
                $data['images'] = $images;
            }
            if (sizeof($sounds) > 0) {
                $data['sounds'] = $sounds;
            }
            if (sizeof($videos) > 0) {
                $data['videos'] = $videos;
            }
            if ($videoUrl != null) {
                $data['video_url'] = $videoUrl;
            }
            if ($entityDescription != null) {
                $data['entityDescription'] = $generalService->stripString($entityDescription);
            }
            if ($forwardEntityId != null) {
                $data['forwardEntityId'] = $forwardEntityId;
            }
            if ($forwardEntityType != null) {
                $data['forwardEntityType'] = $forwardEntityType;
            }
            if ($forwardString != null) {
                $data['forwardString'] = $forwardString;
            }
            if ($entityImage != null) {
                $data['entityImage'] = $entityImage;
            }
            if ($videoThumbnailUrl != null) {
                $data['video_thumbnail_url'] = $videoThumbnailUrl;
            }
            if ($forumGroupId != null) {
                $data['forumGroupId'] = $forumGroupId;
            }
            if ($lastActivity != null) {
                $data['lastActivity'] = $lastActivity;
            }
        }
        return $data;
    }

    public function setPostVideoThumbnail($videoId, $fileData) {
        $generalService = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance();
        if($videoId  == null || $fileData == null || !$generalService->checkPluginActive('iisnewsfeedplus', true)){
            return array('valid' => false, 'thumbnail' => '');
        }

        $attachment = BOL_AttachmentDao::getInstance()->findById($videoId);
        if ($attachment == null || $attachment->userId != OW::getUser()->getId()) {
            return array('valid' => false, 'thumbnail' => '');
        }

        $tmpVideoImageFile = IISNEWSFEEDPLUS_BOL_Service::getInstance()->getThumbnailFileDir($videoId . '.png');
        $filteredData = explode(',', $fileData);
        if (!isset($filteredData[1])) {
            return array('valid' => false, 'thumbnail' => '');
        }

        $valid = IISSecurityProvider::createFileFromRawData($tmpVideoImageFile, $filteredData[1]);
        if (!$valid) {
            return array('valid' => false, 'thumbnail' => '');
        }

        $thumbnailObj = IISNEWSFEEDPLUS_BOL_ThumbnailDao::getInstance()->addThumbnail($videoId, OW::getUser()->getId());
        if ($thumbnailObj == null) {
            return array('valid' => false, 'thumbnail' => '');
        }

        $thumbnail = IISNEWSFEEDPLUS_BOL_Service::getInstance()->getThumbnailFilePath($thumbnailObj->getName());
        return array('valid' => true, 'thumbnail' => $thumbnail);
    }

    public function changePrivacy(){
        $privacy = null;
        if (isset($_POST['privacy'])){
            $privacy = $_POST['privacy'];
        }
        $entityId = null;
        if (isset($_POST['entityId'])){
            $entityId = $_POST['entityId'];
        }
        $entityType = null;
        if (isset($_POST['entityType'])){
            $entityType = $_POST['entityType'];
        }

        if($privacy == null || $entityId == null || $entityType == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $objectId = $entityId;
        $actionType = 'user_status';
        $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);

        if(in_array($entityType, array('album'))){
            $actionType = $entityType;
        }else if($action == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if($action != null){
            $objectId = $action->getId();
        }

        $feedId = null;
        $feed = $this->findFeed($entityType, $entityId);
        if($feed != null && isset($feed->feedId)) {
            $feedId = $feed->feedId;
        }

        $res = IISSECURITYESSENTIALS_BOL_Service::getInstance()->editPrivacyProcess($privacy, $objectId, $actionType, $feedId);

        if(isset($res['result'])) {
            if($res['result']){
                return array('valid' => true, 'message' => 'changed.');
            }else{
                return array('valid' => false, 'message' => 'authorization_error');
            }
        }else{
            return array('valid' => false, 'message' => 'authorization_error');
        }
    }

    /***
     * @param $entityType
     * @param $entityId
     * @param $feedType
     * @return null
     */
    public function getLastActivity($entityType, $entityId, $feedType = null){
        $driver = new NEWSFEED_CLASS_UserDriver();
        $driver->setup(array('feedType' => 'my', 'feedId' => OW::getUser()->getId()));
        $action = $driver->getAction($entityType, $entityId);
        $lastActivity = null;
        if ($action != null) {
            foreach ($action->getActivityList() as $a) {
                /* @var $a NEWSFEED_BOL_Activity */
                $activity[$a->id] = array(
                    'activityType' => $a->activityType,
                    'activityId' => $a->activityId,
                    'id' => $a->id,
                    'data' => json_decode($a->data, true),
                    'timeStamp' => $a->timeStamp,
                    'privacy' => $a->privacy,
                    'userId' => $a->userId,
                    'visibility' => $a->visibility
                );

                if ($lastActivity === null && !in_array($activity[$a->id]['activityType'], NEWSFEED_BOL_Service::getInstance()->SYSTEM_ACTIVITIES)) {
                    $lastActivity = $activity[$a->id];
                }
            }
        }
        return $lastActivity;
    }

    protected function processAssigns( $content, $assigns )
    {
        $search = array();
        $values = array();

        foreach ( $assigns as $key => $item )
        {
            $search[] = '[ph:' . $key . ']';
            $values[] = $item;
        }

        $result = str_replace($search, $values, $content);
        $result = preg_replace('/\[ph\:\w+\]/', '', $result);

        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('string' => $result)));
        if(isset($stringRenderer->getData()['string'])){
            $result = $stringRenderer->getData()['string'];
        }
        return $result;
    }

    protected function getLocalizedText( $textData )
    {
        if ( !is_array($textData) )
        {
            return $textData;
        }

        $keyData = explode("+", $textData["key"]);
        $vars = empty($textData["vars"]) ? array() : $textData["vars"];

        return OW::getLanguage()->text($keyData[0], $keyData[1], $vars);
    }

    public function canRemoveFeedByAction($entityType, $entityId, $userId = null){
        $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
        if($action == null){
            return false;
        }

        return $this->canRemoveFeed($action, $userId);
    }

    public function canRemoveFeed($action, $userId = null, $creatorActivity = null){
        if($userId == null){
            $userId = OW::getUser()->getId();
        }
        if($userId == null){
            return false;
        }
        if($creatorActivity == null){
            $creatorActivity = $this->getCreatorActivityOfAction($action->entityType, $action->entityId);
        }
        if($creatorActivity != null){
            if($creatorActivity->userId == $userId) {
                return true;
            }
            $actionFeed = NEWSFEED_BOL_Service::getInstance()->findFeedListByActivityids(array($creatorActivity->id));
            if(isset($actionFeed[$creatorActivity->id]) && isset($actionFeed[$creatorActivity->id][0])){
                if($actionFeed[$creatorActivity->id][0]->feedId == $userId){
                    return true;
                }
            }
        }

        if(in_array($action->entityType, array("groups-join", "groups-leave", "groups-status", "groups-add-file"))) {
            $groupId = $this->getGroupId($action->entityType, $action->entityId);
            if($groupId != null){
                $groupEntity = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
                if (isset($groupEntity)) {
                    $isGroupOwner = $groupEntity->userId == OW::getUser()->getId();
                    $isGroupModerator = OW::getUser()->isAuthorized('groups');
                    $canRemoveGroupPost = $isGroupOwner || $isGroupModerator;
                    if ($canRemoveGroupPost) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function getUserLikesValue($likesInformation){
        if(!OW::getUser()->isAuthenticated() || $likesInformation == null || empty($likesInformation)){
            return false;
        }

        foreach ($likesInformation as $likeInformation){
            if($likeInformation['id'] == OW::getUser()->getId()){
                return true;
            }
        }

        return false;
    }

    private function getLikesInformation($entityType, $entityId){
        $data = array();
        $userIds = NEWSFEED_BOL_Service::getInstance()->findEntityLikeUserIds($entityType, $entityId);
        foreach ($userIds as $userId){
            $data[] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($userId);
        }

        return $data;
    }

    public function forwardAction(){

        $feedType=null;
        $privacy=null;
        $visibility=null;
        $newsfeedPlugin = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        if(!isset($newsfeedPlugin) || !$newsfeedPlugin->isActive()) {
            return array(false,'newsfeed_plugin_is_not_active');
        }
        if(!isset($_POST['actionId']))
        {
            return array('valid' => false, 'message' => 'actionId_is_null');
        }
        $actionId = $_POST['actionId'];
        $action=NEWSFEED_BOL_Service::getInstance()->findActionById($actionId);
        if(!isset($action))
        {
            return array('valid' => false, 'message' => 'action_is_null');
        }
        $activities = NEWSFEED_BOL_ActivityDao::getInstance()->findIdListByActionIds(array($action->getId()));
        foreach($activities as $activityId){
            $activity = NEWSFEED_BOL_Service::getInstance()->findActivity($activityId)[0];
            if($activity->activityType=='create'){
               $privacy=$activity->privacy;
               $visibility=$activity->visibility;
            }
        }
        switch ($action->entityType)
        {
            case 'user-status':
                $feedType='user';
                break;
            case 'groups-status':
                $feedType='groups';
                break;
            default:
                return array('valid' => false, 'message' => 'invalid_source_type');
        }

        $sourceId = null;

        $actionDataJson = null;
        if(isset($action->data)){
            $actionDataJson = $action->data;
        }

        if($actionDataJson != null){
            $actionDataJson = json_decode($actionDataJson);
        }

        if ($actionDataJson != null) {
            if (isset($actionDataJson->contextFeedId)) {
                $sourceId = $actionDataJson->contextFeedId;
            } else if(isset($actionDataJson->data) && isset($actionDataJson->data->userId)) {
                $sourceId = $actionDataJson->data->userId;
            }
        }

        if(!isset($_POST['entityType']) || !in_array($_POST['entityType'],array('user','groups')))
        {
            return array('valid' => false, 'message' => 'unknown_entityType');
        }
        $entityType=$_POST['entityType'];

        if(!isset($_POST['entityId']))
        {
            return array('valid' => false, 'message' => 'entityId_is_null');
        }
        if (!isset($_POST["forwardType"]) && $feedType === 'groups') {
            $_POST["forwardType"] = $feedType;
        }
        $entityId = $_POST['entityId'];
        $selectedIds=array($entityId);
        $errorMessage=null;
        list($isDataValid,$errorMessage)=$this->checkUserIsValidToForward($actionId, $sourceId, $selectedIds, $feedType,$entityType);
        if($isDataValid===false)
        {
            return array('valid' => false, 'message' => $errorMessage);
        }
        return IISSecurityProvider::forwardPost($actionId,$sourceId,$selectedIds,$privacy,$visibility,$feedType,$entityType,true);
    }

    private function checkUserIsValidToForward($actionId, $sourceId, $selectedIds, $feedType,$forwardType)
    {

        if($forwardType=='groups') {
            $groupPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
            if (!isset($groupPlugin) || !$groupPlugin->isActive()) {
                return array(false,'groups_plugin_is_not_active');
            }
        }
        if($feedType=='groups') {
            /*
             * check if user has access to source group
             */
            $sourceGroup = GROUPS_BOL_Service::getInstance()->findGroupById($sourceId);
            if (!isset($sourceGroup)) {
                return array(false,'source_group_not_found');
            }
            $isCurrentUserCanViewSourceGroup = GROUPS_BOL_Service::getInstance()->isCurrentUserCanView($sourceGroup);
            if (!$isCurrentUserCanViewSourceGroup) {
                return array(false,'access_denied_to_source_group');
            }
            /*
             * check if destination users allow current user to write on their walls.
             */
            $iisSecurityEssentialPlugin = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
            if(isset($iisSecurityEssentialPlugin) && $iisSecurityEssentialPlugin->isActive()) {
                if ($forwardType == 'user') {
                    foreach ($selectedIds as $selectedUserId) {
                        $whoCanPostPrivacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->getActionValueOfPrivacy('who_post_on_newsfeed', $selectedUserId);
                        if ($whoCanPostPrivacy == 'only_for_me') {
                            return array(false,'access_denied_to_user_feed');
                        }
                    }
                }
            }

            OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_FEED_ITEM_RENDERER, array('actionId' => $actionId, 'feedId' => $sourceId)));
        }

        /* check if user has access to selected group(s) */
        if($forwardType=='groups') {
            foreach ($selectedIds as $selectedGroupId) {
                $selectedGroup = GROUPS_BOL_Service::getInstance()->findGroupById($selectedGroupId);
                if (!isset($selectedGroup)) {
                    return array(false,'destination_group_not_found');
                }
                $isCurrentUserCanViewSelectedGroup = GROUPS_BOL_Service::getInstance()->isCurrentUserCanView($selectedGroup);
                if (!$isCurrentUserCanViewSelectedGroup) {
                    return array(false,'access_denied_to_destination_group');
                }
                else{
                    $event = OW::getEventManager()->trigger(new OW_Event('iisgroupsplus.on.channel.add.widget',array('groupId' => $selectedGroupId)));
                    if(isset($event->getData()['channelParticipant']) && $event->getData()['channelParticipant']==true) {
                        return array(false,'access_denied_to_write_destination_group_channel');
                    }
                }
            }
        }
        if($feedType=='user') {
            $activity=IISNEWSFEEDPLUS_BOL_Service::getInstance()->getCreatorActivityOfActionById($actionId);
            /*
             * check if current user is owner of the activity
             */
            if ($activity->userId == OW::getUser()->getId()) {
                return true;
            }
            /*
             * check if current user has access to this activity
             */
            $activityOwnerId = $activity->userId;
            $activityPrivacy = $activity->privacy;

            /*
             * activity is private
             */
            if ($activity->userId != OW::getUser()->getId())
            {
                switch ( $activityPrivacy)
                {
                    case 'only_for_me' :
                        return array(false,'access_denied_to_user_feed');
                        break;
                    case 'everybody' :
                        /*
                         * all users have access to a general status
                         */
                        return true;
                        break;
                    case 'friends_only' :
                        /*
                         * check if current user is a friend of owner of the activity
                         */
                        $friendsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('friends');
                        if (!isset($friendsPlugin) || !$friendsPlugin->isActive()) {
                            throw new Redirect404Exception();
                        }
                        $service = FRIENDS_BOL_Service::getInstance();
                        $isFriends = $service->findFriendship(Ow::getUser()->getId(), $activityOwnerId);
                        if (isset($isFriends) && $isFriends->status == 'active') {
                            return true;
                        }else {
                            return array(false,'friends_plugin_not_installed');
                        }
                        break;
                    default:
                        return array(false,'no_activity_privacy_found');
                }
            }
        }
    }
}