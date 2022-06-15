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
class IISMOBILESUPPORT_BOL_WebServiceVideo
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


    public function getUserVideos(){
        $userId = null;
        if(isset($_GET['userId'])){
            $userId = $_GET['userId'];
        }

        return $this->getUserVideosById($userId);
    }

    public function getUserVideosById($userId){
        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if($userId == null){
            $userId = OW::getUser()->getId();
        }

        if(!OW::getUser()->isAuthenticated() || OW::getUser()->getId() != $userId){
            $canView = $this->checkUserVideoPrivacy($userId);
            if(!$canView){
                return array();
            }
        }

        $videoData = array();
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisvideoplus', true)){
            $first = 0;
            $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
            if(isset($_GET['first'])){
                $first = (int) $_GET['first'];
            }
            $page = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageNumber($first);
            $clips = VIDEO_BOL_ClipDao::getInstance()->getUserClipsList($userId, $page, $count, array());
            foreach ($clips as $clip){
                if(!isset($clip->code)){
                    continue;
                }
                $videoData[] = $this->getVideoInformation($clip);
            }
        }
        return $videoData;
    }

    public function removeVideo(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('video', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $clipId = null;

        if(isset($_POST['id'])){
            $clipId = $_POST['id'];
        }

        if(!$this->isVideoRemovable($clipId)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        VIDEO_BOL_ClipService::getInstance()->deleteClip($clipId);
        return array('valid' => true, 'id' => (int) $clipId);
    }

    public function isVideoRemovable($clipId){
        if($clipId == null || !OW::getUser()->isAuthenticated()){
            return false;
        }

        $clip = VIDEO_BOL_ClipService::getInstance()->findClipById($clipId);

        if($clip == null){
            return false;
        }

        if(!OW::getUser()->isAdmin() && OW::getUser()->getId() != $clip->userId){
            return false;
        }

        return true;
    }

    public function getVideoInformation($clip){
        $description = '';
        $videoIframe = false;
        if(!empty($clip->description)){
            $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($clip->description, true);
            $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->setMentionsOnText($description);
        }

        $title = '';
        if(!empty($clip->title)){
            $title = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($clip->title, true);
        }
        $changeablePrivacy = OW::getUser()->getId() == $clip->userId;
        if (substr($clip->code, 0, 7) == "<iframe") {
            $videoUrl = UTIL_HtmlTag::escapeHtml($clip->code);
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
        $emptyImage = true;
        if ($clip->thumbUrl != null) {
            $emptyImage = false;
        }
        $videoThumbnailUrl = VIDEO_BOL_ClipService::getInstance()->getThumbUrlWithoutId($clip->thumbUrl);
        $removable = $this->isVideoRemovable($clip->id);
        return array(
            "title" => $title,
            "userId" => (int) $clip->userId,
            "user" => IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($clip->userId),
            "description" => $description,
            "time" => $clip->addDatetime,
            "privacy" => $clip->privacy,
            "image" => $videoThumbnailUrl,
            'emptyImage' => $emptyImage,
            'videoIframe' => $videoIframe,
            "id" => (int) $clip->id,
            'removable' => $removable,
            "privacyEditable" => $changeablePrivacy,
            "entityId" => (int) $clip->id,
            'entityType' => 'video_comments',
            'flagAble' => true,
            "url" => $videoUrl
        );
    }

    public function canUserCreateVideo(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisvideoplus', true);

        if(!$pluginActive){
            return false;
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('video', 'add') )
        {
            return false;
        }

        return true;
    }

    public function setThumbnail($videoId, $fileData) {
        $generalService = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance();
        if($videoId  == null || $fileData == null || !$generalService->checkPluginActive('iisvideoplus', true) || !$generalService->checkPluginActive('video', true)){
            return array('valid' => false, 'thumbnail' => '');
        }

        $videoPlusService = IISVIDEOPLUS_BOL_Service::getInstance();
        $videoService = VIDEO_BOL_ClipService::getInstance();
        $video = $videoService->findClipById($videoId);
        if ($video === null || $video->userId != OW::getUser()->getId()) {
            return array('valid' => false, 'thumbnail' => '');
        }

        $videoNameParts = explode('.', $video->code);
        $imageName = "";
        foreach ($videoNameParts as $videoNamePart) {
            if ($videoNamePart != end($videoNameParts)) {
                $imageName = $imageName . $videoNamePart;
            }
        }
        $imageName = $imageName . '.png';

        $tmpVideoImageFile = OW::getPluginManager()->getPlugin('video')->getPluginFilesDir() . $imageName;

        $filteredData = explode(',', $fileData);
        if (!isset($filteredData[1])) {
            return array('valid' => false, 'thumbnail' => '');
        }

        $valid = IISSecurityProvider::createFileFromRawData($tmpVideoImageFile, $filteredData[1]);
        if (!$valid) {
            return array('valid' => false, 'thumbnail' => '');
        }

        $imageFile = $videoPlusService->getVideoFileDir($imageName);

        try {
            OW::getStorage()->copyFile($tmpVideoImageFile, $imageFile);
            $video->thumbUrl = $imageName;
            $videoService->saveClip($video);
        } catch (Exception $e) {
            return array('valid' => false, 'thumbnail' => '');
        }
        OW::getStorage()->removeFile($tmpVideoImageFile);

        $thumbnail = $videoPlusService->getVideoFilePath($imageName);
        return array('valid' => true, 'thumbnail' => $thumbnail);
    }

    public function createVideo(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisvideoplus', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if (!$this->canUserCreateVideo()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userId = OW::getUser()->getId();

        $title = '';
        if(isset($_POST['title'])){
            $title = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($_POST['title'], true);
        }

        if($title == ''){
            return array('valid' => false, 'message' => 'input_error');
        }

        $description = '';
        if(isset($_POST['description'])){
            $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($_POST['description'], true);
        }

        if(!isset($_FILES['file']) || !isset($_FILES['file']['name'])){
            return array('valid' => false, 'message' => 'input_file_error');
        }

        $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
        if (!$isFileClean) {
            return array('valid' => false, 'message' => 'virus_detected');
        }

        $videoUpload = $_FILES['file'];

        if(!$this->isValidFile($_FILES['file']['name'])){
            return array('valid' => false, 'message' => 'input_size_error');
        }

        $addClipParams = array(
            'userId' => (int) $userId,
            'title' => $title,
            'description' => $description,
            'code' => 'videoUpload',
            'videoUpload' => $videoUpload,
            'tags' => array()
        );

        $event = new OW_Event(VIDEO_CLASS_EventHandler::EVENT_VIDEO_ADD, $addClipParams);
        OW::getEventManager()->trigger($event);

        $addClipData = $event->getData();
        $clip = null;
        if ( !empty($addClipData['id']) )
        {
            $event = new OW_Event('videoplus.after_add', array(
                'videoUpload'=>$videoUpload,
                'videoId'=>$addClipData['id'] ));
            OW::getEventManager()->trigger($event);
            $clip = VIDEO_BOL_ClipService::getInstance()->findClipById($addClipData['id']);
        }
        if($clip == null){
            return array('valid' => false, 'message' => 'not_saved');
        }

        if(isset($_POST['privacy']) && in_array($_POST['privacy'], array('everybody', 'friends_only', 'only_for_me'))){
            $clip->privacy = $_POST['privacy'];
            VIDEO_BOL_ClipDao::getInstance()->save($clip);
        }

        return array('valid' => true, 'video' => $this->getVideoInformation($clip));
    }

    public function isValidFile($realName){
        $validFileExtensions = array('ogv','mp4','webm');
        if ( !empty($validFileExtensions) && !in_array(UTIL_File::getExtension($realName), $validFileExtensions) )
        {
            return false;
        }
        $maxUploadVideoSize = (int) OW::getConfig()->getValue('base', 'attch_file_max_size_mb');
        if(isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH']>0) {
            $bundleSize = floor($_SERVER['CONTENT_LENGTH'] / 1024);
            if ($maxUploadVideoSize > 0 && $bundleSize > ($maxUploadVideoSize * 1024)) {
                return false;
            }
        }
        return true;
    }

    public function getVideo(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisvideoplus', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        $first = 0;
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }
        $page = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageNumber($first);
        $clipId = null;

        if(isset($_GET['id'])){
            $clipId = $_GET['id'];
        }

        if($clipId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $clip = VIDEO_BOL_ClipService::getInstance()->findClipById($clipId);

        if($clip == null){
            return array('valid' => false, 'message' => 'authorization_error', 'id' => $clipId);
        }

        if(!OW::getUser()->isAuthenticated() || OW::getUser()->getId() != $clip->userId){
            $canView = $this->checkUserVideoPrivacy($clip->userId);
            if(!$canView){
                return array('valid' => false, 'message' => 'authorization_error');
            }
        }

        if(!isset($clip->code) || !$this->canUserSeeVideoOfUserId(OW::getUser()->getId(), $clip->id, $clip)){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $data = $this->getVideoInformation($clip);
        $comments = IISMOBILESUPPORT_BOL_WebServiceComment::getInstance()->getCommentsInformation('video_comments', $clip->id, $page);
        $data['comments'] = $comments;
        $data['valid'] = true;
        return $data;
    }

    public function checkVideoPrivacy($ownerId, $privacy){
        if(OW::getUser()->isAdmin()){
            return true;
        }
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissecurityessentials', true)){
            $canView = IISSECURITYESSENTIALS_BOL_Service::getInstance()->checkPrivacyOfObject($privacy, $ownerId, null, false);
            if(!$canView){
                return false;
            }
        }

        return true;
    }

    public function checkUserVideoPrivacy($ownerId){
        if(OW::getUser()->isAdmin()){
            return true;
        }
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('privacy', true)){
            $isFriend = true;
            $viewerId = null;
            if(OW::getUser()->isAuthenticated()){
                $viewerId = OW::getUser()->getId();
                $isFriend = IISMOBILESUPPORT_BOL_WebServiceFriends::getInstance()->isFriend($ownerId, $viewerId);
            }

            $userPrivacy = PRIVACY_BOL_ActionService::getInstance()->getActionValueListByUserIdList(array("video_view_video"), array($ownerId));
            if(isset($userPrivacy[$ownerId]['video_view_video'])){
                $userPrivacy = $userPrivacy[$ownerId]['video_view_video'];
            }else{
                return true;
            }

            if($userPrivacy == "everybody"){
                return true;
            }else if($viewerId == null || ($userPrivacy == "friends_only" && !$isFriend)){
                return false;
            }else if($ownerId != $viewerId){
                return false;
            }
        }
        return true;
    }

    public function canUserSeeVideoOfUserId($viewerId, $clipId, $clip = null){
        if(OW::getUser()->isAdmin()){
            return true;
        }
        if($clip == null){
            $clip = VIDEO_BOL_ClipService::getInstance()->findClipById($clipId);
        }

        if($clip == null){
            return false;
        }

        $ownerId = $clip->userId;
        if($viewerId == $ownerId){
            return true;
        }

        if(!$this->checkVideoPrivacy($ownerId, $clip->privacy)){
            return false;
        }

        $modPermissions = OW::getUser()->isAuthorized('video');

        if ( $clip->status != VIDEO_BOL_ClipDao::STATUS_APPROVED && !$modPermissions)
        {
            return false;
        }

        return true;
    }
}