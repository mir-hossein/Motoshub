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
 * Photo Service Class.  
 * 
 * @authors Egor Bulgakov <egor.bulgakov@gmail.com>, Kairat Bakitow <kainisoft@gmail.com>
 * @package ow.plugin.photo.bol
 * @since 1.0
 */
final class PHOTO_BOL_PhotoService
{
    CONST HASHTAG_PATTERN = '/#[^\s#]+/';
    
    CONST DIM_ORIGINAL_HEIGHT = 1080;
    CONST DIM_ORIGINAL_WIDTH = 1960;
    
    CONST DIM_FULLSCREEN_HEIGHT = 1080;
    CONST DIM_FULLSCREEN_WIDTH = 1960;
    
    CONST DIM_MAIN_HEIGHT = 640;
    CONST DIM_MAIN_WIDTH = 960;
    
    CONST DIM_PREVIEW_HEIGHT = 400;
    CONST DIM_PREVIEW_WIDTH = 400;  
    
    CONST DIM_SMALL_HEIGHT = 200;
    CONST DIM_SMALL_WIDTH = 200;
    
    CONST TYPE_ORIGINAL = 'original';
    CONST TYPE_FULLSCREEN = 'fullscreen';
    CONST TYPE_MAIN = 'main';
    CONST TYPE_PREVIEW = 'preview';
    CONST TYPE_SMALL = 'small'; 
    
    CONST ID_LIST_LIMIT = 50;
    CONST FORMAT_LIST_LIMIT = 4;

    const EVENT_UNINSTALL_IN_PROGRESS = 'photo.uninstall_in_progress';
    const EVENT_AFTER_ADD_SEARCH_DATA = 'photo.after_add_search_data';
    const EVENT_CONTENT_INDEXING_INCOMPLETE = 'photo.content_indexing_incomplete';

    /**
     * @var PHOTO_BOL_PhotoDao
     */
    private $photoDao;
    /**
     * @var PHOTO_BOL_PhotoFeaturedDao
     */
    private $photoFeaturedDao;
    /**
     * Class instance
     *
     * @var PHOTO_BOL_PhotoService
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    private function __construct()
    {
        $this->photoDao = PHOTO_BOL_PhotoDao::getInstance();
        $this->photoFeaturedDao = PHOTO_BOL_PhotoFeaturedDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return PHOTO_BOL_PhotoService
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    public function getPhotoTypes()
    {
        return array(
            self::TYPE_ORIGINAL,
            self::TYPE_FULLSCREEN,
            self::TYPE_MAIN,
            self::TYPE_PREVIEW,
            self::TYPE_SMALL
        );
    }

    /**
     * Find latest public photos authors ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findLatestPublicPhotosAuthorsIds($first, $count)
    {
        return $this->photoDao->findLatestPublicPhotosAuthorsIds($first, $count);
    }

    /**
     * Adds photo
     *
     * @param PHOTO_BOL_Photo $photo
     * @return int
     */
    public function addPhoto( PHOTO_BOL_Photo $photo )
    {
        $this->photoDao->save($photo);
        
        $this->cleanListCache();

        return $photo->id;
    }

    /**
     * Updates photo
     *
     * @param PHOTO_BOL_Photo $photo
     * @return int
     */
    public function updatePhoto( PHOTO_BOL_Photo $photo )
    {
        $this->photoDao->save($photo);
        
        $this->cleanListCache();


        $event = new OW_Event(PHOTO_CLASS_EventHandler::EVENT_ON_PHOTO_EDIT, array('id' => $photo->id));
        OW::getEventManager()->trigger($event);
        return $photo->id;

    }

    /**
     * Finds photo by id
     *
     * @param int $id
     * @return PHOTO_BOL_Photo
     */
    public function findPhotoById( $id )
    {
        if ( empty($id) )
        {
            return NULL;
        }
        
        return $this->photoDao->findById($id);
    }

    /**
     * Finds photo owner
     *
     * @param int $id
     * @return int
     */
    public function findPhotoOwner( $id )
    {
        return $this->photoDao->findOwner($id);
    }

    /**
     * Returns photo list
     *
     * @param string $type
     * @param int $page
     * @param int $limit
     * @param null $exclude
     * @return array of PHOTO_BOL_Photo
     */
    public function findPhotoList( $listType, $page, $limit, $exclude = null, $type = self::TYPE_PREVIEW )
    {
        $first = ( $page - 1 ) * $limit;
        
        if ( in_array($listType, array('toprated', 'most_discussed')) )
        {
            switch ( $listType )
            {
                case 'toprated':
                    $topRatedList = BOL_RateService::getInstance()->findMostRatedEntityList('photo_rates', $first, $limit, $exclude);

                    if ( !$topRatedList )
                    {
                        return array();
                    }

                    $photoArr = $this->photoDao->findPhotoInfoListByIdList(array_keys($topRatedList), $listType);

                    $photos = array();

                    foreach ( $photoArr as $key => $photo )
                    {
                        $photos[$key] = $photo;
                        $photos[$key]['score'] = $topRatedList[$photo['id']]['avgScore'];
                        $photos[$key]['rates'] = $topRatedList[$photo['id']]['ratesCount'];
                    }

                    usort($photos, array('PHOTO_BOL_PhotoService', 'sortArrayItemByDesc'));
                    break;
                case 'most_discussed':
                    $discussedList = BOL_CommentService::getInstance()->findMostCommentedEntityList('photo_comments', $first, $limit);

                    if ( empty($discussedList) )
                    {
                        return array();
                    }

                    $photoArr = $this->photoDao->findPhotoInfoListByIdList(array_keys($discussedList), $listType);
                    $photos = array();

                    foreach ( $photoArr as $key => $photo )
                    {
                        $photos[$key] = $photo;
                        $photos[$key]['commentCount'] = $discussedList[$photo['id']]['commentCount'];
                    }

                    usort($photos, array('PHOTO_BOL_PhotoService', 'sortArrayItemByCommentCount'));
                    break;
            }
        }
        else
        {
            $photos = $this->photoDao->getPhotoList($listType, $first, $limit, $exclude, FALSE);
        }
        
        if ( $photos )
        {
            if ( !in_array($type, $this->getPhotoTypes()) )
            {
                $type = self::TYPE_PREVIEW;
            }
            
            foreach ( $photos as $key => $photo )
            {
                $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $photo['description'])));
                if(isset($stringRenderer->getData()['string'])){
                    $photos[$key]['description'] = ($stringRenderer->getData()['string']);
                }

                $photos[$key]['url'] = $this->getPhotoUrlByPhotoInfo($photo['id'], $type, $photo['hash'], !empty($photo['dimension']) ? $photo['dimension'] : FALSE);
            }
        }

        return $photos;
    }

    /**
     * Find last public photos
     *
     * @param integer $offset
     * @param integer $limit
     * @return array
     */
    public function findLastPublicPhotos($offset, $limit)
    {
        return $this->photoDao->getPhotoList('latest', $offset, $limit, null, false);
    }

    public function findAlbumPhotoList( $albumId, $listType, $offset, $limit, $privacy = null )
    {
        if ( empty($albumId) )
        {
            return array();
        }

        if ( empty($listType) || !in_array($listType, array('latest', 'toprated', 'featured')) )
        {
            return array();
        }

        return $this->photoDao->findAlbumPhotoList($albumId, $listType, $offset, $limit, $privacy);
    }

    public static function sortArrayItemByDesc( $el1, $el2 )
    {
        if ( $el1['score'] === $el2['score'] )
        {
            if ( $el1['rates'] === $el2['rates'] )
            {
                return $el1['id'] < $el2['id'] ? 1 : -1;
            }

            return $el1['rates'] < $el2['rates'] ? 1 : -1;
        }

        return $el1['score'] < $el2['score'] ? 1 : -1;
    }

    public static function sortArrayItemByCommentCount( $el1, $el2 )
    {
        return $el1['commentCount'] < $el2['commentCount'] ? 1 : -1;
    }

    public function countAlbumPhotos( $id, $exclude=null )
    {
        return $this->photoDao->countAlbumPhotos($id, $exclude);
    }

    /**
     * Counts photos
     *
     * @param string $type
     * @param bool $checkPrivacy
     * @param null $exclude
     * @return int
     */
    public function countPhotos( $type, $checkPrivacy = true, $exclude = null )
    {
        if ( $type == 'toprated' )
        {
            return BOL_RateService::getInstance()->findMostRatedEntityCount('photo_rates', $exclude);
        }

        return $this->photoDao->countPhotos($type, $checkPrivacy, $exclude);
    }

    public function countFullsizePhotos()
    {
        return (int) $this->photoDao->countFullsizePhotos();
    }

    /**
     * Counts all user uploaded photos
     *
     * @param int $userId
     * @return int
     */
    public function countUserPhotos( $userId )
    {
        return $this->photoDao->countUserPhotos($userId);
    }

    /**
     * Counts photos with tag
     *
     * @param string $tag
     * @return int
     */
    public function countTaggedPhotos( $tag )
    {
        return BOL_TagService::getInstance()->findEntityCountByTag('photo', $tag);
    }

    public function countPhotosByListType( $listType )
    {
        switch ( $listType )
        {
            case 'latest':
            default:
                return (int)$this->photoDao->countAll();
        }
    }

    /**
     * @param $id
     * @param $type
     * @param array $photoInfo
     * @param $returnPath
     * @return string
     */
    public function getPhotoUrlByPhotoInfo($id, $type, $photoInfo = array(), $returnPath = false )
    {
        if ( empty($photoInfo) || !is_array($photoInfo) || empty($photoInfo['hash'])
            || !isset($photoInfo['dimension']) || empty($photoInfo['albumId']) )
        {
            $photo = $this->photoDao->findById($id);

            if ( empty($photo) )
            {
                return null;
            }

            $photoInfo = get_object_vars($photo);
        }

        $hash = $photoInfo['hash'];
        $dimension = $photoInfo['dimension'];

        $url = $this->photoDao->getPhotoUrlByType($id, $type, $hash, $dimension, $returnPath);
        $event = OW::getEventManager()->trigger(new OW_Event('photo.getPhotoUrl', array(
            'id' => $id,
            'type' => $type,
            'hash' => $hash,
            'dimension' => $dimension,
            'photoInfo' => $photoInfo
        ), $url));

        return $event->getData();
    }

    /**
     * @param $id
     * @param $type
     * @param null $hash
     * @param null $dimension
     * @return string
     */

    public function getPhotoUrlByType( $id, $type, $hash = null, $dimension = null )
    {
        $photo = $this->photoDao->findById($id);

        if ( empty($photo) )
        {
            return null;
        }

        $photoInfo = get_object_vars($photo);

        if ( $hash )
        {
            $photoInfo['hash'] = $hash;
        }

        if ( $dimension )
        {
            $photoInfo['dimension'] = $hash;
        }

        return $this->getPhotoUrlByPhotoInfo($id, $type, $photoInfo);
    }

    /**
     * @deprecated
     * @param $id
     * @param bool $preview
     * @param null $hash
     * @param null $dimension
     * @return string
     */
    public function getPhotoUrl( $id, $preview = false, $hash = null, $dimension = null )
    {
        $photo = $this->photoDao->findById($id);

        if ( empty($photo) )
        {
            return null;
        }

        $photoInfo = get_object_vars($photo);

        if ( $hash )
        {
            $photoInfo['hash'] = $hash;
        }

        if ( $dimension )
        {
            $photoInfo['dimension'] = $hash;
        }

        return $this->getPhotoUrlByPhotoInfo($id, $preview ? self::TYPE_PREVIEW : self::TYPE_MAIN, $photoInfo);
    }
    
    /**
     * Returns photo preview URL
     *
     * @param int $id
     * @param $hash
     * @return string
     */
    public function getPhotoPreviewUrl( $id, $hash )
    {
        return $this->getPhotoUrl($id, true, $hash);
    }

    public function getPhotoFullsizeUrl( $id, $hash )
    {
        return $this->photoDao->getPhotoFullsizeUrl($id, $hash);
    }

    /**
     * Get directory where 'photo' plugin images are uploaded
     *
     * @return string
     */
    public function getPhotoUploadDir()
    {
        return $this->photoDao->getPhotoUploadDir();
    }

    /**
     * Get path to photo in file system
     *
     * @param int $photoId
     * @param $hash
     * @param string $type
     * @return string
     */
    public function getPhotoPath( $photoId, $hash, $type = '' )
    {
        return $this->photoDao->getPhotoPath($photoId, $hash, $type);
    }

    public function getPhotoPluginFilesPath( $photoId, $type = '' )
    {
        return $this->photoDao->getPhotoPluginFilesPath($photoId, $type);
    }

    /***
     * @param $album
     * @param $page
     * @param $limit
     * @param null $exclude
     * @param string $status
     * @return array
     */
    public function getAlbumPhotos( $album, $page, $limit, $exclude = null, $status = PHOTO_BOL_PhotoDao::STATUS_APPROVED )
    {
        $photos = $this->photoDao->getAlbumPhotos($album, $page, $limit, $exclude, $status);

        $list = array();

        if ( $photos )
        {
            $commentService = BOL_CommentService::getInstance();

            foreach ( $photos as $key => $photo )
            {
                $list[$key]['id'] = $photo->id;
                $list[$key]['dto'] = $photo;
                $list[$key]['comments_count'] = $commentService->findCommentCount('photo', $photo->id);
                $list[$key]['url'] = $this->getPhotoUrl($photo->id, TRUE, $photo->hash);
            }
        }

        return $list;
    }

    /**
     * Updates the 'status' field of the photo object 
     *
     * @param int $id
     * @param string $status
     * @return boolean
     */
    public function updatePhotoStatus( $id, $status )
    {
        /** @var $photo PHOTO_BOL_Photo */
        $photo = $this->photoDao->findById($id);

        $newStatus = $status == 'approve' ? 'approved' : 'blocked';

        $photo->status = $newStatus;

        $this->updatePhoto($photo);

        return $photo->id ? true : false;
    }

    /**
     * Changes photo's 'featured' status
     *
     * @param int $id
     * @param string $status
     * @return boolean
     */
    public function updatePhotoFeaturedStatus( $id, $status )
    {
        $photo = $this->photoDao->findById($id);

        if ( $photo )
        {
            $photoFeaturedService = PHOTO_BOL_PhotoFeaturedService::getInstance();

            if ( $status == 'mark_featured' )
            {
                return $photoFeaturedService->markFeatured($id);
            }
            else
            {
                return $photoFeaturedService->markUnfeatured($id);
            }
        }

        return false;
    }
    
    public function getFirstPhotoIdList( $listType, $photoId )
    {
        if ( in_array($listType, array('albumPhotos', 'userPhotos')) )
        {
            $ownerId = $this->findPhotoOwner($photoId);
            $checkPrivacy = $this->isCheckPrivacy($ownerId);
        }
        else
        {
            $checkPrivacy = FALSE;
        }

        if ( in_array($listType, array('toprated', 'most_discussed')) )
        {
            switch ( $listType )
            {
                case 'toprated': return $this->getTopratedPhotoIdList();
                case 'most_discussed': return $this->getMostDiscussedPhotoIdList();
            }
        }
        
        return $this->photoDao->getFirstPhotoIdList($listType, $checkPrivacy, $photoId);
    }
    
    public function getLastPhotoIdList( $listType, $photoId )
    {
        if ( in_array($listType, array('albumPhotos', 'userPhotos')) )
        {
            $ownerId = $this->findPhotoOwner($photoId);
            $checkPrivacy = $this->isCheckPrivacy($ownerId);
        }
        else
        {
            $checkPrivacy = FALSE;
        }

        if ( in_array($listType, array('toprated', 'most_discussed')) )
        {
            switch ( $listType )
            {
                case 'toprated': return $this->getTopratedPhotoIdList();
                case 'most_discussed': return $this->getMostDiscussedPhotoIdList();
            }
        }
        
        return $this->photoDao->getLastPhotoIdList($listType, $checkPrivacy, $photoId);
    }

    public function getPrevPhotoIdList( $listType, $photoId )
    {
        if ( in_array($listType, array('albumPhotos', 'userPhotos')) )
        {
            $ownerId = $this->findPhotoOwner($photoId);
            $checkPrivacy = $this->isCheckPrivacy($ownerId);
        }
        else
        {
            $checkPrivacy = FALSE;
        }
        
        if ( in_array($listType, array('toprated', 'most_discussed')) )
        {
            switch ( $listType )
            {
                case 'toprated': return $this->getTopratedPhotoIdList();
                case 'most_discussed': return $this->getMostDiscussedPhotoIdList();
            }
        }
        
        return $this->photoDao->getPrevPhotoIdList($listType, $photoId, $checkPrivacy);
    }
    
    public function getNextPhotoIdList( $listType, $photoId )
    {
        if ( in_array($listType, array('albumPhotos', 'userPhotos')) )
        {
            $ownerId = $this->findPhotoOwner($photoId);
            $checkPrivacy = $this->isCheckPrivacy($ownerId);
        }
        else
        {
            $checkPrivacy = FALSE;
        }

        if ( in_array($listType, array('toprated', 'most_discussed')) )
        {
            switch ( $listType )
            {
                case 'toprated': return $this->getTopratedPhotoIdList();
                case 'most_discussed': return $this->getMostDiscussedPhotoIdList();
            }
        }
        
        return $this->photoDao->getNextPhotoIdList($listType, $photoId, $checkPrivacy);
    }
    
    public function getTopratedPhotoIdList()
    {
        static $list = array();
        
        if ( empty($list) )
        {
            $count = BOL_RateService::getInstance()->findMostRatedEntityCount('photo_rates');
            $topRatedList = BOL_RateService::getInstance()->findMostRatedEntityList('photo_rates', 0, $count);

            if ( !$topRatedList )
            {
                return array();
            }

            $photoArr = $this->photoDao->findPhotoInfoListByIdList(array_keys($topRatedList), 'toprated');
            $photos = array();

            foreach ( $photoArr as $key => $photo )
            {
                $photos[$key] = $photo;
                $photos[$key]['score'] = $topRatedList[$photo['id']]['avgScore'];
                $photos[$key]['rates'] = $topRatedList[$photo['id']]['ratesCount'];
            }

            usort($photos, array('PHOTO_BOL_PhotoService', 'sortArrayItemByDesc'));

            foreach ( $photos as $photo )
            {
                $list[] = $photo['id'];
            }
        }
        
        return $list;
    }

    public function getMostDiscussedPhotoIdList()
    {
        static $list = array();

        if ( empty($list) )
        {
            $count = BOL_CommentService::getInstance()->findCommentedEntityCount('photo_comments');
            $mostDiscussedList = BOL_CommentService::getInstance()->findMostCommentedEntityList('photo_comments', 0, $count);

            if ( empty($mostDiscussedList) )
            {
                return array();
            }

            $photoArr = $this->photoDao->findPhotoInfoListByIdList(array_keys($mostDiscussedList), 'most_discussed');
            $photos = array();

            foreach ( $photoArr as $key => $photo )
            {
                $photos[$key] = $photo;
                $photos[$key]['commentCount'] = $mostDiscussedList[$photo['id']]['commentCount'];
            }

            usort($photos, array('PHOTO_BOL_PhotoService', 'sortArrayItemByCommentCount'));

            foreach ( $photos as $photo )
            {
                $list[] = $photo['id'];
            }
        }

        return $list;
    }
    
    public function getPreviousPhotoId( $albumId, $id )
    {
        $prev = $this->photoDao->getPreviousPhoto($albumId, $id);
                
        return $prev ? $prev->id : null;
    }

    public function getNextPhotoId( $albumId, $id )
    {
        $next = $this->photoDao->getNextPhoto($albumId, $id);
                
        return $next ? $next->id : null;
    }
    
    /**
     * Returns current photo index in album
     *
     * @param int $albumId
     * @param int $id
     * @return int
     */
    public function getPhotoIndex( $albumId, $id )
    {
        return $this->photoDao->getPhotoIndex($albumId, $id);
    }

    /**
     * Deletes photo
     *
     * @param $id
     * @param bool $totalAlbum
     * @return bool
     */
    public function deletePhoto( $id, $totalAlbum = FALSE )
    {
        if ( !$id || !$photo = $this->photoDao->findById($id) )
        {
            return false;
        }

        if ( $totalAlbum === FALSE )
        {
            $event = new OW_Event(PHOTO_CLASS_EventHandler::EVENT_BEFORE_PHOTO_DELETE, array('id' => $id));
            OW::getEventManager()->trigger($event);
        }

        if ( $this->photoDao->deleteById($id) )
        {
            if( !$totalAlbum ){
                OW::getLogger()->writeLog(OW_Log::INFO, 'delete_photo', ['actionType'=>OW_Log::DELETE, 'enType'=>'photo', 'enId'=>$id]);
            }
            OW::getEventManager()->call('notifications.remove', array(
                'entityType' => 'photo-add_rate',
                'entityId' => $id
            ));
            BOL_CommentService::getInstance()->deleteEntityComments('photo_comments', $id);
            BOL_RateService::getInstance()->deleteEntityRates($id, 'photo_rates');
            BOL_TagService::getInstance()->deleteEntityTags($id, 'photo');

            $this->photoDao->removePhotoFile($id, $photo->hash, self::TYPE_SMALL);
            $this->photoDao->removePhotoFile($id, $photo->hash, self::TYPE_PREVIEW);
            $this->photoDao->removePhotoFile($id, $photo->hash, self::TYPE_MAIN);
            $this->photoDao->removePhotoFile($id, $photo->hash, self::TYPE_FULLSCREEN);

            $path = $this->photoDao->getPhotoPath($photo->id, $photo->hash, self::TYPE_ORIGINAL);

            if ( OW::getStorage()->fileExists($path) )
            {
                $hash=IISSecurityProvider::generateUniqueId();
                $photo->hash=$hash;
                $this->photoDao->save($photo);
                $newpath = $this->photoDao->getPhotoPath($photo->id, $photo->hash, self::TYPE_ORIGINAL);
                $string=explode(DS ,$newpath);
                $fileName=$string[count($string)-1];
                $fileNewName = 'deleted_' . $fileName;
                $newpath=$this->photoDao->getPhotoUploadDir($photo->id, $photo->hash, self::TYPE_ORIGINAL);
                OW::getStorage()->renameFile($path, $newpath.$fileNewName);
            }

            $this->photoFeaturedDao->markUnfeatured($id);

            BOL_FlagService::getInstance()->deleteByTypeAndEntityId(PHOTO_CLASS_ContentProvider::ENTITY_TYPE, $id);
            BOL_TagService::getInstance()->deleteEntityTags($id, PHOTO_BOL_PhotoDao::PHOTO_ENTITY_TYPE);

            $this->cleanListCache();

            OW::getEventManager()->trigger(new OW_Event(PHOTO_CLASS_EventHandler::EVENT_ON_PHOTO_DELETE, array(
                'id' => $id
            )));
            
            return TRUE;
        }

        return FALSE;
    }
    
    public function deleteFullsizePhotos()
    {
        $this->photoDao->deleteFullsizePhotos();
    }
    
    public function setMaintenanceMode( $mode = true )
    {
        $config = OW::getConfig();
        
        if ( $mode )
        {
            $state = (int) $config->getValue('base', 'maintenance');
            $config->saveConfig('photo', 'maintenance_mode_state', $state);
            OW::getApplication()->setMaintenanceMode($mode);
        }
        else 
        {
            $state = (int) $config->getValue('photo', 'maintenance_mode_state');
            $config->saveConfig('base', 'maintenance', $state);
        }
    }
    
    public function cleanListCache()
    {
        OW::getCacheManager()->clean(array(PHOTO_BOL_PhotoDao::CACHE_TAG_PHOTO_LIST));
    }

    public function triggerNewsfeedEventOnSinglePhotoAdd( PHOTO_BOL_PhotoAlbum $album, PHOTO_BOL_Photo $photo, $isAdd = TRUE )
    {
        $lastPhoto = $this->photoDao->getLastPhoto($album->id, array($photo->id));

        if ( $isAdd && $lastPhoto && (time() - $lastPhoto->addDatetime < 60 * 15) && $lastPhoto->uploadKey )
        {
            $this->feedDeleteItem('photo_comments', $lastPhoto->id);
            $this->feedDeleteItem('multiple_photo_upload', $lastPhoto->uploadKey);
            
            $photoIdList = $this->photoDao->findPhotoIdListByUploadKey($lastPhoto->uploadKey);
            sort($photoIdList, SORT_NUMERIC);
            $count = count($photoIdList);
            $albumUrl = OW::getRouter()->urlForRoute('photo_user_album', array(
                'user' => BOL_UserService::getInstance()->getUserName($album->userId),
                'album' => $album->id
            ));

            $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $albumUrl)));
            if(isset($stringRenderer->getData()['string'])){
                $albumUrl = $stringRenderer->getData()['string'];
            }
            if ( $count === 1 )
            {
                $entityType = 'photo_comments';
                $entityId = $photoIdList[0];
                $key = 'photo+feed_single_description';
            }
            else
            {
                $entityType = 'multiple_photo_upload';
                $entityId = $lastPhoto->uploadKey;
                $key = 'photo+feed_multiple_descriptions';
            }
            
            $event = new OW_Event('feed.action', array(
                'pluginKey' => 'photo',
                'entityType' => $entityType,
                'entityId' => $entityId,
                'userId' => $album->userId
            ), array(
                'photoIdList' => array_reverse($photoIdList),
                'time'=>time(),
                'string' => array(
                    'key' => $key,
                    'vars' => array(
                        'number' => $count,
                        'albumUrl' => $albumUrl,
                        'albumName' => $album->name
                    )
                ),
                'ownerId' => $album->userId,
                'features' => array('likes'),
                'content' => '',
                'view' => array('iconClass' => 'ow_ic_picture')
            ));

            OW::getEventManager()->trigger($event);
        }
        else
        {
            $this->feedDeleteItem('photo_comments', $photo->id);
            $this->feedDeleteItem('multiple_photo_upload', $photo->uploadKey);
            
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
                'time'=>time(),
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

        OW::getEventManager()->trigger(new OW_Event('photo.after_add_feed', array(
            'album' => $album,
            'photos' => array($photo)
        )));

        return TRUE;
    }

    public function triggerNewsfeedEventOnMultiplePhotosAdd( PHOTO_BOL_PhotoAlbum $album, array $photos, $isAdd = TRUE )
    {
        $lastPhoto = $this->photoDao->getLastPhoto($album->id);
        $photoIdList = array();

        if ( $isAdd && $lastPhoto && (time() - $lastPhoto->addDatetime < 60 * 15) && $lastPhoto->uploadKey )
        {
            $this->feedDeleteItem('photo_comments', $lastPhoto->id);
            $this->feedDeleteItem('multiple_photo_upload', $lastPhoto->uploadKey);
            
            $photoIdList = $this->photoDao->findPhotoIdListByAlbumId($album->id);
            sort($photoIdList, SORT_NUMERIC);
            $albumUrl = OW::getRouter()->urlForRoute('photo_user_album', array(
                'user' => BOL_UserService::getInstance()->getUserName($album->userId),
                'album' => $album->id
            ));

            $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $albumUrl)));
            if(isset($stringRenderer->getData()['string'])){
                $albumUrl = $stringRenderer->getData()['string'];
            }
            $event = new OW_Event('feed.action', array(
                'pluginKey' => 'photo',
                'entityType' => 'multiple_photo_upload',
                'entityId' => $lastPhoto->uploadKey,
                'userId' => $album->userId
            ), array(
                'photoIdList' => array_reverse($photoIdList),
                'string' => array(
                    'key' => 'photo+feed_multiple_descriptions',
                    'vars' => array(
                        'number' => count($photoIdList),
                        'albumUrl' => $albumUrl,
                        'albumName' => $album->name
                    )
                ),
                'ownerId' => $album->userId,
                'features' => array('likes'),
                'content' => '',
                'view' => array('iconClass' => 'ow_ic_picture')
            ));

            OW::getEventManager()->trigger($event);
        }
        else
        {
            $this->feedDeleteItem('photo_comments', $lastPhoto->id);
            $this->feedDeleteItem('multiple_photo_upload', $lastPhoto->uploadKey);
            
            $albumUrl = OW::getRouter()->urlForRoute('photo_user_album', array(
                'user' => BOL_UserService::getInstance()->getUserName($album->userId),
                'album' => $album->id
            ));
            
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $albumUrl)));
            if(isset($stringRenderer->getData()['string'])){
                $albumUrl = $stringRenderer->getData()['string'];
            }
            foreach ( $photos as $photo )
            {
                $photoIdList[] = $photo->id;
            }
            
            sort($photoIdList, SORT_NUMERIC);
            
            $event = new OW_Event('feed.action', array(
                'pluginKey' => 'photo',
                'entityType' => 'multiple_photo_upload',
                'entityId' => $photos[0]->uploadKey,
                'userId' => $album->userId,
                'time' => $photos[0]->addDatetime
            ), array(
                'photoIdList' => array_reverse($photoIdList),
                'string' => array(
                    'key' => 'photo+feed_multiple_descriptions',
                    'vars' => array(
                        'number' => count($photos),
                        'albumUrl' => $albumUrl,
                        'albumName' => $album->name
                    )
                ),
                'ownerId' => $album->userId,
                'features' => array('likes'),
                'content' => '',
                'view' => array('iconClass' => 'ow_ic_picture')
            ));

            OW::getEventManager()->trigger($event);
        }

        OW::getEventManager()->trigger(new OW_Event('photo.after_add_feed', array(
            'album' => $album,
            'photos' => $photos
        )));

        return TRUE;
    }

    public function getPhotoUploadKey( $albumId )
    {
        $photo = $this->photoDao->getLastPhoto($albumId);

        if ( $photo && (time() - $photo->addDatetime < 60 * 15) && $photo->uploadKey )
        {
                return $photo->uploadKey;
        }
        
        return (int)$albumId + time();
    }

    public function getPhotoListByUploadKey( $uploadKey, array $exclude = null, $status = null )
    {
        return $this->photoDao->findPhotoListByUploadKey($uploadKey, $exclude, $status);
    }
    
    public function findEntityPhotoList( $entityType, $entityId, $first, $count, $status = "approved", $privacy = null )
    {
        return $this->photoDao->findEntityPhotoList($entityType, $entityId, $first, $count, $status, $privacy);
    }
    
    public function countEntityPhotos( $entityType, $entityId, $status = "approved", $privacy = null )
    {
        return $this->photoDao->countEntityPhotos($entityType, $entityId, $status, $privacy);
    }

    public function getMaxUploadFileSize( $convert = true )
    {
        $postMaxSize = trim(ini_get('post_max_size'));
        $uploadMaxSize = trim(ini_get('upload_max_filesize'));

        $lastPost = strtolower($postMaxSize[strlen($postMaxSize) - 1]);
        $lastUpload = strtolower($uploadMaxSize[strlen($uploadMaxSize) - 1]);

        $intPostMaxSize = (int)$postMaxSize;
        $intUploadMaxSize = (int)$uploadMaxSize;

        switch ( $lastPost )
        {
            case 'g': $intPostMaxSize *= 1024;
            case 'm': $intPostMaxSize *= 1024;
            case 'k': $intPostMaxSize *= 1024;
        }

        switch ( $lastUpload )
        {
            case 'g': $intUploadMaxSize *= 1024;
            case 'm': $intUploadMaxSize *= 1024;
            case 'k': $intUploadMaxSize *= 1024;
        }

        $possibleSize = array($postMaxSize => $intPostMaxSize, $uploadMaxSize => $intUploadMaxSize);
        $maxSize = min($possibleSize);

        if ( $convert )
        {
            $accepted = (float)(OW::getConfig()->getValue('photo', 'accepted_filesize') * 1024 * 1024);

            return $accepted >= $maxSize ? $maxSize : $accepted;
        }

        return array_search($maxSize, $possibleSize);
    }
    
    public function descToHashtag( $desc )
    {
        if ( empty($desc) )
        {
            return array();
        }
        
        $match = NULL;
        
        preg_match_all(self::HASHTAG_PATTERN, $desc, $match);
        
        if ( !empty($match[0]) )
        {
            foreach ( $match[0] as $key => $tag )
            {
                $match[0][$key] = ltrim($tag, '#');
            }
            
            return $match[0];
        }
        
        return array();
    }
    
    public function hashtagToDesc( $desc )
    {
        return preg_replace_callback(self::HASHTAG_PATTERN, 'PHOTO_BOL_PhotoService::tagReplace', $desc);
    }

    private static function tagReplace( $tag )
    {
        return '<a href="' . OW::getRouter()->urlForRoute('view_tagged_photo_list', array('tag' => $tag[0])) . '">' . $tag[0] . '</a>';
    }
    
    public function findPhotoListByAlbumId( $albumId, $page, $limit, array $exclude = array() )
    {
        if ( !$albumId || ($album = PHOTO_BOL_PhotoAlbumDao::getInstance()->findById($albumId)) === NULL )
        {
            return array();
        }

        $first = ($page - 1) * $limit;
        $photos = $this->photoDao->getAlbumPhotoList($albumId, $first, $limit, $this->isCheckPrivacy($album->userId), $exclude);
        
        if ( $photos )
        {
            foreach ( $photos as $key => $photo )
            {
                $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $photo['description'])));
                if(isset($stringRenderer->getData()['string'])){
                    $photos[$key]['description'] = ($stringRenderer->getData()['string']);
                }

                $photos[$key]['url'] = $this->getPhotoUrlByPhotoInfo($photo['id'], self::TYPE_PREVIEW, $photo);
            }
        }

        return $photos;
    }
    
    public function movePhotosToAlbum( $photoIdList, $albumId, $newAlbum = FALSE )
    {
        return $this->photoDao->movePhotosToAlbum($photoIdList, $albumId, $newAlbum);
    }
    
    public function findTaggedPhotosByTagId( $tagId, $page, $limit )
    {
        $first = ($page - 1 ) * $limit;

        $photos = $this->photoDao->findTaggedPhotosByTagId($tagId, $first, $limit, FALSE);
        
        if ( $photos )
        {
            foreach ( $photos as $key => $photo )
            {
                $photos[$key]['url'] = $this->getPhotoUrlByPhotoInfo($photo['id'], self::TYPE_PREVIEW, $photo['hash'], $photo);
            }
        }

        return $photos;
    }
    
    public function findPhotoListByUserId( $userId, $page, $limit, array $exclude = array(), $status = PHOTO_BOL_PhotoDao::STATUS_APPROVED )
    {
        if ( empty($userId) )
        {
            return array();
        }
        
        $first = ($page - 1) * $limit;
        $photos = $this->photoDao->findPhotoListByUserId($userId, $first, $limit, $this->isCheckPrivacy($userId), $exclude, $status);
        
        if ( $photos )
        {
            foreach ( $photos as $key => $photo )
            {
                $photos[$key]['url'] = $this->getPhotoUrlByPhotoInfo($photo['id'], self::TYPE_PREVIEW, $photo);
            }
        }

        return $photos;
    }

    public function findPhotoListByUserIdListCount(array $userIdList,array $exclude = array()){
        if ( count($userIdList) === 0 )
        {
            return array();
        }
        $count = $this->photoDao->findPhotoListByUserIdListCount($userIdList,null,$exclude);
        return $count;
    }
    public function findPhotoListByUserIdList( array $userIdList, $page, $limit,array $exclude = array())
    {
        if ( count($userIdList) === 0 )
        {
            return array();
        }
        
        $first = ($page - 1) * $limit;
        $photos = $this->photoDao->findPhotoListByUserIdList($userIdList, $first, $limit,null,$exclude);
        
        if ( $photos )
        {
            foreach ( $photos as $key => $photo )
            {
                $photos[$key]['url'] = $this->getPhotoUrl($photo['id'], FALSE, $photo['hash']);
            }
        }

        return $photos;
    }
    
    public function findPhotoListByDesc( $searchVal, $id, $page, $limit )
    {
        if ( empty($searchVal) )
        {
            return array();
        }
        
        $first = ($page - 1) * $limit;
        $photoCache = PHOTO_BOL_PhotoCacheDao::getInstance();
        
        if ( ($cach = $photoCache->findCacheByKey($photoCache->getKey($searchVal))) !== NULL )
        {
            $data = json_decode($cach->data, TRUE);
            $photos = $this->photoDao->findPhotoListByIdList(explode(',', $data['list'][$id]['ids']), $first, $limit);
        }
        else
        {
            $photos = $this->photoDao->findPhotoListByDescription($searchVal, $id, $first, $limit);
        }
        
        if ( $photos )
        {
            foreach ( $photos as $key => $photo )
            {
                $photos[$key]['url'] = $this->getPhotoUrlByPhotoInfo($photo['id'], self::TYPE_PREVIEW, $photo);
            }
        }

        return $photos;
    }
    
    public function findPhotoListByIdList(array $idList, $page, $limit, $status = PHOTO_BOL_PhotoDao::STATUS_APPROVED )
    {
        if ( count($idList) === 0 )
        {
            return array();
        }
        
        $first = ($page - 1) * $limit;
        $photos = $this->photoDao->findPhotoListByIdList($idList, $first, $limit, $status);
        
        if ( $photos )
        {
            foreach ( $photos as $key => $photo )
            {
                $photos[$key]['url'] = $this->getPhotoUrl($photo['id'], FALSE, $photo['hash']);
            }
        }

        return $photos;
    }
    
    public function createAlbumCover( $albumId, array $photos )
    {
        if ( empty($albumId) || count($photos) === 0 || PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->isAlbumCoverExist($albumId) )
        {
            return FALSE;
        }
        
        foreach ( $photos as $photo )
        {
            $path = $this->getPhotoPath($photo->id, $photo->hash, 'main');
            $storage = OW::getStorage();
            
            if ( !$storage->fileExists($path) )
            {
                continue;
            }
            
            $tmpPathCrop = OW::getPluginManager()->getPlugin('photo')->getPluginFilesDir() . IISSecurityProvider::generateUniqueId(IISSecurityProvider::generateUniqueId(), TRUE) . '.jpg';
            $tmpPathOrig = OW::getPluginManager()->getPlugin('photo')->getPluginFilesDir() . IISSecurityProvider::generateUniqueId(IISSecurityProvider::generateUniqueId(), TRUE) . '.jpg';

            if ( !$storage->copyFileToLocalFS($path, $tmpPathOrig) )
            {
                continue;
            }
            $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN, array('source' => $path, 'destination' => $tmpPathOrig)));
            if(isset($checkAnotherExtensionEvent->getData()['destination'])){
                $tmpPathOrig = $checkAnotherExtensionEvent->getData()['destination'];
            }
            $info = getimagesize($tmpPathOrig);

            if ( $info['0'] < 330 || $info['1'] < 330 )
            {
                OW::getStorage()->removeFile($tmpPathOrig, true);
                
                continue;
            }

            $coverDto = new PHOTO_BOL_PhotoAlbumCover();
            $coverDto->albumId = $albumId;
            $coverDto->hash = IISSecurityProvider::generateUniqueId();
            PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->save($coverDto);

            $image = new UTIL_Image($tmpPathOrig);
            $left = $image->getWidth() / 2 - 165;
            $top = $image->getHeight() / 2 - 165;
            $image->cropImage($left, $top, 330, 330);
            $image->saveImage($tmpPathCrop);
            $image->destroy();

            $storage->copyFile($tmpPathCrop, PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->getAlbumCoverPathForCoverEntity($coverDto));
            $storage->copyFile($tmpPathOrig, PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->getAlbumCoverOrigPathForCoverEntity($coverDto));

            OW::getStorage()->removeFile($tmpPathCrop, true);
            OW::getStorage()->removeFile($tmpPathOrig, true);
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    public function isCheckPrivacy( $userId )
    {
        if ( empty($userId) )
        {
            return FALSE;
        }
            
        static $privacy = array();
        
        if ( !array_key_exists($userId, $privacy) )
        {
            if ( $userId == OW::getUser()->getId() || OW::getUser()->isAuthorized('photo') )
            {
                $privacy[$userId] = NULL;
            }
            else
            {
                $privacy[$userId] = ($friendDto = OW::getEventManager()->call('plugin.friends.check_friendship', array('userId' => $userId, 'friendId' => OW::getUser()->getId()))) !== null && $friendDto->status == 'active';
            }
        }
        
        return $privacy[$userId];
    }
    
    public function findDistinctPhotoUploadKeyByAlbumId( $albumId )
    {
        return $this->photoDao->findDistinctPhotoUploadKeyByAlbumId($albumId);
    }

    public function feedDeleteItem( $entityType, $entityId )
    {
        if ( empty($entityType) || empty($entityId) )
        {
            return false;
        }

        try
        {
            OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array(
                'entityType' => $entityType,
                'entityId' => $entityId
            )));
        }
        catch ( Exception $e )
        {

        }
    }

    public function getQueryCondition( $listType, $aliases, array $params = array() )
    {
        $event = new BASE_CLASS_QueryBuilderEvent('photo.getPhotoList', array(
            'listType' => $listType,
            'aliases' => $aliases,
            'params' => $params
        ));
        OW::getEventManager()->trigger($event);

        $queryParts = BOL_ContentService::getInstance()->getQueryFilter(array(
            BASE_CLASS_QueryBuilderEvent::TABLE_USER => $aliases['album'],
            BASE_CLASS_QueryBuilderEvent::TABLE_CONTENT => $aliases['album']
        ), array(
            BASE_CLASS_QueryBuilderEvent::FIELD_USER_ID => 'userId',
            BASE_CLASS_QueryBuilderEvent::FIELD_CONTENT_ID => 'id'
        ), array(
            BASE_CLASS_QueryBuilderEvent::OPTION_METHOD => __METHOD__,
            BASE_CLASS_QueryBuilderEvent::OPTION_TYPE => $listType
        ));

        $event->addJoin($queryParts['join']);
        $event->addWhere($queryParts['where']);
        $event->addOrder($queryParts['order']);

        return array(
            'join' => $event->getJoin(),
            'where' => $event->getWhere(),
            'order' => $event->getOrder(),
            'params' => $event->getQueryParams()
        );
    }

    // Content Provider

    // Newsfeed Update
    public function updateFeedEntity( $photoId )
    {
        if ( ($photo = $this->findPhotoById($photoId)) === null || $photo->status != PHOTO_BOL_PhotoDao::STATUS_APPROVED )
        {
            return;
        }

        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($photo->albumId);

        if ( PHOTO_BOL_PhotoAlbumService::getInstance()->isNewsfeedAlbum($album) )
        {
            return;
        }

        $this->feedDeleteItem('multiple_photo_upload', $photo->uploadKey);

        $photos = array();

        foreach ( $this->getPhotoListByUploadKey($photo->uploadKey, null) as $_photo )
        {
            if ( $_photo->status == PHOTO_BOL_PhotoDao::STATUS_APPROVED )
            {
                $this->feedDeleteItem('photo_comments', $_photo->id);
                $photos[] = $_photo;
            }
        }

        if ( count($photos) > 1 )
        {
            $this->triggerNewsfeedEventOnMultiplePhotosAdd($album, $photos, false);
        }
        else
        {
            $this->triggerNewsfeedEventOnSinglePhotoAdd($album, $photo, false);
        }
    }

    public function findPhotosInAlbum( $albumId, array $photos )
    {
        $self = $this;

        return array_map(function( $photo ) use( $self )
        {
            $photo['url'] = $self->getPhotoUrlByPhotoInfo($photo['id'], PHOTO_BOL_PhotoService::TYPE_PREVIEW, $photo);

            return $photo;
        }, $this->photoDao->findPhotosInAlbum($albumId, $photos));
    }

    public function deleteComment( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['entityType']) || $params['entityType'] !== 'photo_comments' )
            return;

        $commentId = (int) $params['commentId'];
        $entityId = $params['entityId'];
        OW::getEventManager()->call("feed.delete_activity", array(
            'activityType' => 'comment',
            'activityId' => $commentId,
            'entityId' => $entityId,
            'userId' => OW::getUser()->getId(),
            'entityType' => 'event'
        ));

        $this->checkIfPhotoFeedMustDeleted($params['entityType'], $params['entityId']);
        OW::getEventManager()->call('notifications.remove', array(
            'entityType' => 'photo-add_comment',
            'entityId' => $commentId
        ));
    }

    /**
     * @param $entityType
     * @param $entityId
     * delete action feed for photo_comments which are created by comment or like on multiple photo feed
     */
    public function checkIfPhotoFeedMustDeleted($entityType,$entityId)
    {
        $newsfeedPlugin = BOL_PluginService::getInstance()->findPluginByKey('newsfeed');
        if( !isset($newsfeedPlugin) || !$newsfeedPlugin->isActive() )
        {
            $action = NEWSFEED_BOL_Service::getInstance()->findAction($entityType, $entityId);
            $activities = NEWSFEED_BOL_ActivityDao::getInstance()->findIdListByActionIds(array($action->getId()));
            /*
             * The feed must not be delete there's another activity on this feed
             */
            foreach ($activities as $activityId)
            {
                $activity = NEWSFEED_BOL_Service::getInstance()->findActivity($activityId)[0];
                if ($activity->activityType != 'create' && $activity->activityType != 'subscribe') {
                    return;
                }
            }
            $data = json_decode($action->data);
            if (sizeof($data->photoIdList) == 0)
            {
                OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array(
                    'entityType' => $entityType,
                    'entityId' => $entityId
                )));
            }
        }

    }

    public function findIdListBySearch( $q, $first, $count )
    {
        $ex = new OW_Example();
        $ex->andFieldLike('description', '%'.$q.'%');
        $ex->setOrder('addDatetime desc')->setLimitClause($first, $count);
        $list = $this->photoDao->findIdListByExample($ex);

        return array_unique($list);
    }

    public function userIsAuthorized(){
        $userId = null;
        $showAll= false;
        $user = OW::getUser();
        if(isset($user)) {
            $userId = $user->getId();
            if($user->isAdmin() || $user->isAuthorized('photo'))
                $showAll = true;
        }
        return array($userId,$showAll);
    }

    public function findListByIdListAndUser( $list )
    {
        list($userId,$showAll) = $this->userIsAuthorized();
        $result = $this->photoDao->findListByIdListAndUser($list,$userId,$showAll);
        $output = array();
        foreach ($list as $id){
            foreach ($result as $photoArray){
                if($photoArray['id'] == $id) {
                    $photo = new PHOTO_BOL_Photo();
                    $photo->id = $photoArray['id'];
                    $photo->albumId = $photoArray['albumId'];
                    $photo->description = $photoArray['description'];
                    $photo->addDatetime = $photoArray['addDatetime'];
                    $photo->status = $photoArray['status'];
                    $photo->hasFullsize = $photoArray['hasFullsize'];
                    $photo->privacy = $photoArray['privacy'];
                    $photo->hash = $photoArray['hash'];
                    $photo->uploadKey = $photoArray['uploadKey'];
                    $photo->dimension = $photoArray['dimension'];
                    $output[] = $photo;
                    break;
                }
            }
        }
        return $output;
    }

    public function onCollectSearchItems(OW_Event $event){
        if (!OW::getUser()->isAdmin() && !OW::getUser()->isAuthorized('photo', 'view'))
        {
            return;
        }
        $searchValue = '';
        $params = $event->getParams();
        if ( !empty($params['q']) )
        {
            $searchValue = $params['q'];
        }
        $maxCount = empty($params['maxCount'])?10:$params['maxCount'];
        $first= empty($params['first'])?0:$params['first'];
        $first=(int)$first;
        $count=empty($params['count'])?$first+$maxCount:$params['count'];
        $count=(int)$count;

        $idList = $this->findIdListBySearch(strip_tags(UTIL_HtmlTag::stripTags($searchValue)), $first, $count);

        $resultData = $this->findListByIdListAndUser($idList);
        $albumIdList = array();
        foreach ($resultData as $item){
            $albumIdList[] = $item->albumId;

        }
        $authorList = PHOTO_BOL_PhotoAlbumDao::getInstance()->findAlbumsAuthorIds($albumIdList);
        $result = array();
        $count = 0;
        foreach($resultData as $item){
            $itemInformation = array();
            $itemInformation['title'] = $item->description;
            $itemInformation['id'] = $item->id;
            $itemInformation['createdDate'] = $item->addDatetime;
            $itemInformation['userId'] = $authorList[$item->albumId];
            $itemInformation['link'] = OW::getRouter()->urlForRoute('view_photo', array('id'=>$item->id));;
            $itemInformation['label'] = OW::getLanguage()->text('iisadvancesearch', 'photos_label');
            $itemInformation['image'] = $this->getPhotoUrl($item->id,true,$item->hash);
            $result[] = $itemInformation;
            $count++;
            if($count == $maxCount){
                break;
            }
        }

        $data = $event->getData();
        $data['photo'] = array('label' => OW::getLanguage()->text('iisadvancesearch', 'photos_label'), 'data' => $result);
        $event->setData($data);
    }
}
