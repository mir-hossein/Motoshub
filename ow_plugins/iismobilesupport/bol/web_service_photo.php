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
class IISMOBILESUPPORT_BOL_WebServicePhoto
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


    public function getUserAlbums(){
        $userId = null;
        if(isset($_GET['userId'])){
            $userId = $_GET['userId'];
        }

        return $this->getUserAlbumsByUserId($userId);
    }

    public function getUserAlbumsByUserId($userId){
        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if($userId == null){
            $userId = OW::getUser()->getId();
        }

        if(!OW::getUser()->isAuthenticated() || OW::getUser()->getId() != $userId){
            $canView = $this->checkUserPhotosPrivacy($userId);
            if(!$canView){
                return array();
            }
        }

        $albumData = array();
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('photo', true)){
            $first = 0;
            $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
            if(isset($_GET['first'])){
                $first = (int) $_GET['first'];
            }
            $page = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageNumber($first);

            if(OW::getUser()->getId() == $userId){
                $albums = PHOTO_BOL_PhotoAlbumService::getInstance()->findUserAlbums($userId, $first, $count);
                $albumIdList = array();
                foreach ($albums as $album) {
                    $albumIdList[] = $album->id;
                }
                $photoCounters = PHOTO_BOL_PhotoAlbumService::getInstance()->countAlbumPhotosForList($albumIdList);
                foreach ($albums as $album){
                    $photoCounts = 0;
                    if(isset($photoCounters[$album->id]) && !empty($photoCounters[$album->id])){
                        $photoCounts = $photoCounters[$album->id];
                    }
                    $albumItemData = array(
                        'name' => $album->name,
                        'userId' => $album->userId,
                        'createDatetime' => $album->createDatetime,
                        'description' => $album->description,
                        'id' => $album->id,
                        'count' => $photoCounts
                    );
                    $albumData[] = $this->getAlbumInformation($albumItemData);
                }
            }else{
                $albums = PHOTO_BOL_PhotoAlbumService::getInstance()->getUserAlbumList($userId, $page, $count);
                $albumIdList = array();
                foreach ($albums as $album) {
                    $albumIdList[] = $album['id'];
                }
                $photoCounters = PHOTO_BOL_PhotoAlbumService::getInstance()->countAlbumPhotosForList($albumIdList);
                foreach ($albums as $album){
                    $photoCounts = 0;
                    if(isset($photoCounters[$album['id']]) && !empty($photoCounters[$album['id']])){
                        $photoCounts = $photoCounters[$album['id']];
                    }
                    $album['count'] = $photoCounts;
                    $data = $this->getAlbumInformation($album);
                    $albumData[] = $data;
                }
            }
        }
        return $albumData;
    }

    public function getPhoto(){
        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('photo', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $photoId = null;
        if(isset($_GET['id'])){
            $photoId = $_GET['id'];
        }

        if($photoId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);

        if($photo == null){
            return array('valid' => false, 'message' => 'authorization_error', 'id' => $photoId);
        }

        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($photo->albumId);
        if($album == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!OW::getUser()->isAuthenticated() || OW::getUser()->getId() != $album->userId){
            $canView = $this->checkUserPhotosPrivacy($album->userId);
            if(!$canView){
                return array('valid' => false, 'message' => 'authorization_error');
            }
        }

        if(!$this->canUserSeePhotoOfUserId(OW::getUser()->getId(), $album->id, $album)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $first = 0;
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }
        $page = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageNumber($first);
        $url = PHOTO_BOL_PhotoService::getInstance()->getPhotoUrlByPhotoInfo($photo->id, PHOTO_BOL_PhotoService::TYPE_PREVIEW, $photo);
        $photoData = array(
            "description" => $photo->description,
            "id" => $photo->id,
            "addDatetime" => $photo->addDatetime,
            "url" => $url,

        );
        $photoData = $this->getPhotoInformation($photoData);
        $comments = IISMOBILESUPPORT_BOL_WebServiceComment::getInstance()->getCommentsInformation('photo_comments', $photo->id, $page);
        $photoData['comments'] = $comments;
        $albumData = array(
            'name' => $album->name,
            'userId' => $album->userId,
            'createDatetime' => $album->createDatetime,
            'description' => $album->description,
            'id' => $album->id,
        );
        $albumData = $this->getAlbumInformation($albumData);
        return array('valid' => true, 'photo' => $photoData, 'album' => $albumData);
    }

    public function getAlbumPhotos(){
        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('photo', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $albumId = null;
        if(isset($_GET['id'])){
            $albumId = $_GET['id'];
        }

        if($albumId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId);

        if($album == null){
            return array('valid' => false, 'message' => 'authorization_error', 'id' => $albumId);
        }

        if(!OW::getUser()->isAuthenticated() || OW::getUser()->getId() != $album->userId){
            $canView = $this->checkUserPhotosPrivacy($album->userId);
            if(!$canView){
                return array('valid' => false, 'message' => 'authorization_error');
            }
        }

        if(!$this->canUserSeePhotoOfUserId(OW::getUser()->getId(), $album->id, $album)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $photosData = array();
        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }
        $page = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageNumber($first);
        $photos = PHOTO_BOL_PhotoService::getInstance()->findPhotoListByAlbumId($albumId, $page, $count);
        foreach ($photos as $photo){
            $photosData[] = $this->getPhotoInformation($photo);
        }

        $albumData = array(
            'name' => $album->name,
            'userId' => $album->userId,
            "user" => IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($album->userId),
            'createDatetime' => $album->createDatetime,
            'description' => $album->description,
            'id' => $album->id,
        );
        $albumData = $this->getAlbumInformation($albumData);
        return array('valid' => true, 'photos' => $photosData, 'album' => $albumData);
    }

    public function removePhoto(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('photo', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $photoId = null;

        if(isset($_POST['id'])){
            $photoId = $_POST['id'];
        }

        if($photoId == null || !OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!$this->isPhotoRemovable($photoId)){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        PHOTO_BOL_PhotoService::getInstance()->deletePhoto($photoId);
        return array('valid' => true, 'id' => (int) $photoId);
    }

    public function isPhotoRemovable($photoId){
        if($photoId == null){
            return false;
        }
        $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);

        if($photo == null){
            return false;
        }

        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($photo->albumId);
        if($album == null){
            return false;
        }

        if(!OW::getUser()->isAdmin() && OW::getUser()->getId() != $album->userId){
            return false;
        }

        return true;
    }

    public function canUserSeePhoto($photoId){
        $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);
        if($photo == null){
            return false;
        }
        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($photo->albumId);
        if($album == null){
            return false;
        }
        $canView = $this->checkUserPhotosPrivacy($album->userId);
        if(!$canView){
            return false;
        }
        return true;
    }

    public function removeAlbum(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('photo', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $albumId = null;

        if(isset($_POST['id'])){
            $albumId = $_POST['id'];
        }

        if(!$this->isAlbumRemovable($albumId)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        PHOTO_BOL_PhotoAlbumService::getInstance()->deleteAlbum($albumId);
        return array('valid' => true, 'id' => (int) $albumId);
    }

    public function isAlbumRemovable($albumId){
        if($albumId == null || !OW::getUser()->isAuthenticated()){
            return false;
        }

        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId);
        if($album == null){
            return false;
        }

        if(!OW::getUser()->isAdmin() && OW::getUser()->getId() != $album->userId){
            return false;
        }

        return true;
    }

    public function canUserCreatePhoto(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('photo', true);

        if(!$pluginActive){
            return false;
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            return false;
        }

        return true;
    }

    public function createAlbum(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('photo', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if ( !$this->canUserCreatePhoto()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userId = OW::getUser()->getId();
        $albumName = '';
        if(isset($_POST['title'])){
            $albumName = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($_POST['title'], true);
        }

        $albumNameObj = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumByName($albumName, $userId);
        if($albumNameObj != null){
            $albumNameEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_ALBUM_CREATE_FOR_STATUS_UPDATE, array('albumName' => $albumName)));
            if(isset($albumNameEvent->getData()['albumName'])){
                $albumName = $albumNameEvent->getData()['albumName'];
            }
        }

        $event = new OW_Event('photo.createUserAlbum', array('userId' => $userId, 'name' => $albumName));
        OW::getEventManager()->trigger($event);

        $p = $event->getData();

        if ( empty($p['ablumId']) )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($p['ablumId']);
        $albumData = array(
            'name' => $album->name,
            'userId' => $album->userId,
            'createDatetime' => $album->createDatetime,
            'description' => $album->description,
            'id' => $album->id,
            'count' => 0,
        );

        return array('valid' => true, 'album' => $this->getAlbumInformation($albumData));
    }

    public function createPhoto(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('photo', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if ( !$this->canUserCreatePhoto()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $description = '';
        $fileName = '';
        if(isset($_POST['description'])){
            $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($_POST['description'], true);
        }

        if(isset($_FILES['file']) && isset($_FILES['file']['tmp_name'])){
            $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
            if (!$isFileClean) {
                return array('valid' => false, 'message' => 'virus_detected');
            }
            $fileName = $_FILES['file']['tmp_name'];
        }

        if($fileName == ''){
            return array('valid' => false, 'message' => 'input_file_error');
        }

        if(!$this->isValidFile($_FILES)){
            return array('valid' => false, 'message' => 'input_file_error');
        }

        $albumId = null;
        $album = null;
        if(isset($_POST['albumId'])){
            $albumId = $_POST['albumId'];
        }

        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId);
        if($album == null || $album->userId != OW::getUser()->getId()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $tmpPhotoService = PHOTO_BOL_PhotoTemporaryService::getInstance();
        $tmpId = PHOTO_BOL_PhotoTemporaryService::getInstance()->addTemporaryPhoto($fileName, OW::getUser()->getId());
        $photo = $tmpPhotoService->moveTemporaryPhoto($tmpId, $album->id, $description);
        if ( $photo )
        {
            if ($album->userId)
            {
                $albumUrl = OW::getRouter()->urlForRoute('photo_user_album', array(
                    'user' => BOL_UserService::getInstance()->getUserName($album->userId),
                    'album' => $album->id
                ));

                $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $albumUrl)));
                if(isset($stringRenderer->getData()['string'])){
                    $albumUrl = $stringRenderer->getData()['string'];
                }
                $data = array(
                    'photoIdList' => array($photo->id),
                    'string' => array(
                        'key' => 'photo+feed_single_description',
                        'vars' => array(
                            'number' => 1,
                            'albumUrl' => $albumUrl,
                            'albumName' => $album->name
                        )
                    ),
                    'ownerId' => $album->userId
                );

                if ( !empty($photo->description) )
                {
                    $data['status'] = $photo->description;
                }

                $event = new OW_Event('feed.action', array(
                    'pluginKey' => 'photo',
                    'entityType' => 'photo_comments',
                    'entityId' => $photo->id,
                    'userId' => $album->userId,
                    'time' => $photo->addDatetime
                ), $data);
                OW::getEventManager()->trigger($event);
            }

            PHOTO_BOL_PhotoService::getInstance()->createAlbumCover($album->id, array($photo));
            PHOTO_BOL_PhotoTemporaryService::getInstance()->deleteUserTemporaryPhotos($album->userId);

            $movedArray[] = array(
                'addTimestamp' => time(),
                'photoId' => $photo->id,
                'description' => $photo->description,
                "status" => $photo->status,
                "silent" => true
            );

            $event = new OW_Event(PHOTO_CLASS_EventHandler::EVENT_ON_PHOTO_ADD, $movedArray);
            OW::getEventManager()->trigger($event);
        }
        $url = PHOTO_BOL_PhotoService::getInstance()->getPhotoUrlByPhotoInfo($photo->id, PHOTO_BOL_PhotoService::TYPE_PREVIEW, $photo);
        $photoData = array(
            "description" => $photo->description,
            "id" => $photo->id,
            "addDatetime" => $photo->addDatetime,
            "url" => $url,

        );
        return array('valid' => true, 'photo' => $this->getPhotoInformation($photoData));
    }

    public function isValidFile( $file )
    {
        return !empty($file['file']) &&
            $file['file']['error'] === UPLOAD_ERR_OK &&
            in_array($file['file']['type'], array('image/jpeg', 'image/png', 'image/gif'), true) &&
            $_FILES['file']['size'] <= PHOTO_BOL_PhotoService::getInstance()->getMaxUploadFileSize() &&
            is_uploaded_file($file['file']['tmp_name']);
    }

    public function getPhotoInformation($photo){
        $description = '';
        if(!empty($photo['description'])){
            $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($photo['description'], true);
            $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->setMentionsOnText($description);
        }

        $removable = $this->isPhotoRemovable($photo['id']);

        return array(
            "description" => $description,
            "time" => $photo['addDatetime'],
            "id" => (int) $photo['id'],
            "removable" => $removable,
            'flagAble' => true,
            "entityId" => (int) $photo['id'],
            'entityType' => 'photo_comments',
            "url" => $photo['url'],
        );
    }

    public function getAlbumInformation($album){
        $description = '';
        if(!empty($album['description'])){
            $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($album['description'], true);
        }

        if(!isset($album['count'])){
            $album['count'] = PHOTO_BOL_PhotoAlbumService::getInstance()->countAlbumPhotos($album['id']);
        }

        $photosCountPreview = 3;
        $removable = $this->isAlbumRemovable($album['id']);
        if(!isset($album['photosUrl'])){
            $album['photosUrl'] = array();
            $photos = PHOTO_BOL_PhotoService::getInstance()->findPhotoListByAlbumId($album['id'], 1, $photosCountPreview);
            foreach ($photos as $photo){
                $album['photosUrl'][] = $photo['url'];
            }
        }
        for( $i = sizeof($album['photosUrl']); $i < $photosCountPreview; $i++ )
        {
            $album['photosUrl'][] = OW::getPluginManager()->getPlugin('base')->getStaticUrl(). 'css/images/' . 'no-picture.png';
        }

        $title = '';
        if(!empty($album['name'])){
            $title = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($album['name'], true);
        }

        $privacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->getPrivacyOfAlbum($album['id']);
        if($privacy == null){
            $eventParams = array(
                'action' => 'photo_view_album',
                'ownerId' => $album['userId']
            );
            $modulePrivacy = OW::getEventManager()->getInstance()->call('plugin.privacy.get_privacy', $eventParams);
            if($modulePrivacy != null && !empty($modulePrivacy)){
                $privacy = $modulePrivacy;
            }else{
                $privacy = 'everybody';
            }
        }

        $changeablePrivacy = $album['count'] == 0 ? false : OW::getUser()->getId() == $album['userId'];

        return array(
            "title" => $title,
            "userId" => (int) $album['userId'],
            "user" => IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($album['userId']),
            "description" => $description,
            "time" => $album['createDatetime'],
            "privacy" => $privacy,
            "privacyEditable" => $changeablePrivacy,
            "count" => $album['count'],
            "photosUrl" => $album['photosUrl'],
            'removable' => $removable,
            "id" => (int) $album['id'],
        );
    }

    public function checkPhotoPrivacy($ownerId, $privacy){
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

    public function checkUserPhotosPrivacy($ownerId){
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

            $userPrivacy = PRIVACY_BOL_ActionService::getInstance()->getActionValueListByUserIdList(array("photo_view_album"), array($ownerId));
            if(isset($userPrivacy[$ownerId]['photo_view_album'])){
                $userPrivacy = $userPrivacy[$ownerId]['photo_view_album'];
            }else{
                return true;
            }

            if($userPrivacy == "everybody" || $viewerId == $ownerId){
                return true;
            }

            if($userPrivacy == "friends_only"){
                if($isFriend){
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }
        return true;
    }

    public function canUserSeePhotoOfUserId($viewerId, $albumId, $album = null){
        if(OW::getUser()->isAdmin()){
            return true;
        }
        if($album == null){
            $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId);
        }

        if($album == null){
            return false;
        }

        $ownerId = $album->userId;
        if($viewerId == $ownerId){
            return true;
        }

        $photoPrivacy = 'everybody';
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissecurityessentials', true)) {
            $photoPrivacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->getPrivacyOfAlbum($albumId);
        }

        if(!$this->checkPhotoPrivacy($ownerId, $photoPrivacy)){
            return false;
        }

        return true;
    }
}