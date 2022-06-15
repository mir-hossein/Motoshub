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
 * Clip Service Class.  
 * 
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.video.bol
 * @since 1.0
 */
final class VIDEO_BOL_ClipService
{
    const EVENT_AFTER_DELETE = 'video.after_delete';
    const EVENT_BEFORE_DELETE = 'video.before_delete';
    const EVENT_AFTER_EDIT = 'video.after_edit';
    const EVENT_AFTER_ADD = 'video.after_add';
    const EVENT_CACHE_THUMBNAILS_INCOMPLETE = 'video.cache_thumbnails_incomplete';
    
    const ENTITY_TYPE = 'video_comments';
    
    const TAGS_ENTITY_TYPE = "video";
    const RATES_ENTITY_TYPE = "video_rates";
    const FEED_ENTITY_TYPE = self::ENTITY_TYPE;

    /**
     * @var VIDEO_BOL_ClipDao
     */
    private $clipDao;
    /**
     * @var VIDEO_BOL_ClipFeaturedDao
     */
    private $clipFeaturedDao;
    /**
     * Class instance
     *
     * @var VIDEO_BOL_ClipService
     */
    private static $classInstance;

    /**
     * Class constructor
     *
     */
    private function __construct()
    {
        $this->clipDao = VIDEO_BOL_ClipDao::getInstance();
        $this->clipFeaturedDao = VIDEO_BOL_ClipFeaturedDao::getInstance();
    }

    /**
     * Returns class instance
     *
     * @return VIDEO_BOL_ClipService
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
     * Adds video clip
     *
     * @param VIDEO_BOL_Clip $clip
     * @return int
     */
    public function addClip( VIDEO_BOL_Clip $clip )
    {
        $this->clipDao->save($clip);
        
        $this->cleanListCache();

        return $clip->id;
    }
    
    public function saveClip( VIDEO_BOL_Clip $clip ) 
    {
        $this->clipDao->save($clip);
        $this->cleanListCache();
    }

    /**
     * Updates video clip
     *
     * @param VIDEO_BOL_Clip $clip
     * @return int
     */
    public function updateClip( VIDEO_BOL_Clip $clip )
    {
        $this->clipDao->save($clip);
        
        $this->cleanListCache();

        $event = new OW_Event('feed.action', array(
            'pluginKey' => 'video',
            'entityType' => self::FEED_ENTITY_TYPE,
            'entityId' => $clip->id,
            'userId' => $clip->userId
        ));
        OW::getEventManager()->trigger($event);

        $event = new OW_Event(self::EVENT_AFTER_EDIT, array('clipId' => $clip->id));
        OW::getEventManager()->trigger($event);
        
        return $clip->id;
    }

    /**
     * Finds clip by id
     *
     * @param int $id
     * @return VIDEO_BOL_Clip
     */
    public function findClipById( $id )
    {
        return $this->clipDao->findById($id);
    }
    
    /**
     * Finds clips by id list
     *
     * @param int $ids
     * @return array
     */
    public function findClipByIds( $ids )
    {
        return $this->clipDao->findByIdList($ids);
    }

    /**
     * Finds clip owner
     *
     * @param int $id
     * @return int
     */
    public function findClipOwner( $id )
    {
        $clip = $this->clipDao->findById($id);

        /* @var $clip VIDEO_BOL_Clip */

        return $clip ? $clip->getUserId() : null;
    }

    /**
     * Find latest clips authors ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findLatestPublicClipsAuthorsIds($first, $count)
    {
        return $this->clipDao->findLatestPublicClipsAuthorsIds($first, $count);
    }

    /**
     * Finds video clips list of specified type 
     *
     * @param string $type
     * @param int $page
     * @param int $limit
     * @return array of VIDEO_BOL_Clip
     */
    public function findClipsList( $type, $page, $limit )
    {
        if ( $type == 'toprated' )
        {
            $first = ( $page - 1 ) * $limit;
            $topRatedList = BOL_RateService::getInstance()->findMostRatedEntityList(self::RATES_ENTITY_TYPE, $first, $limit);

            $clipArr = $this->clipDao->findByIdList(array_keys($topRatedList));

            $clips = array();

            foreach ( $clipArr as $key => $clip )
            {
                $clipArrItem = (array) $clip;
                $clips[$key] = $clipArrItem;
                $clips[$key]['score'] = $topRatedList[$clipArrItem['id']]['avgScore'];
                $clips[$key]['rates'] = $topRatedList[$clipArrItem['id']]['ratesCount'];
            }

            usort($clips, array('VIDEO_BOL_ClipService', 'sortArrayItemByDesc'));
        }
        else
        {
            $clips = $this->clipDao->getClipsList($type, $page, $limit);
        }

        $list = array();
        if ( is_array($clips) )
        {
            foreach ( $clips as $key => $clip )
            {
                $clip = (array) $clip;
                $list[$key] = $clip;
                $list[$key]['thumb'] = $this->getClipThumbUrl($clip['id'], $clip['code'], $clip['thumbUrl']);
            }
        }

        return $list;
    }

    /**
     * Deletes user all clips
     * 
     * @param int $userId
     * @return boolean
     */
    public function deleteUserClips( $userId )
    {
        if ( !$userId )
        {
            return false;
        }

        $clipsCount = $this->findUserClipsCount($userId);

        if ( !$clipsCount )
        {
            return true;
        }

        $clips = $this->findUserClipsList($userId, 1, $clipsCount);

        foreach ( $clips as $clip )
        {
            $event = new OW_Event('videplus.on.user.unregister', array('code'=>$clip['code']));
            OW::getEventManager()->trigger($event);
            $this->deleteClip($clip['id']);
        }

        return true;
    }

    public static function sortArrayItemByDesc( $el1, $el2 )
    {
        if ( $el1['score'] === $el2['score'] )
        {
            if ( $el1['rates'] === $el2['rates'] )
            {
                return 0;
            }
            
            return $el1['rates'] < $el2['rates'] ? 1 : -1;
        }

        return $el1['score'] < $el2['score'] ? 1 : -1;
    }

    /**
     * Finds user other video list
     *
     * @param $userId
     * @param $page
     * @param int $itemsNum
     * @param int $exclude
     * @return array of VIDEO_BOL_Clip
     */
    public function findUserClipsList( $userId, $page, $itemsNum, $exclude = null )
    {
        $clips = $this->clipDao->getUserClipsList($userId, $page, $itemsNum, $exclude);

        if ( is_array($clips) )
        {
            $list = array();
            foreach ( $clips as $key => $clip )
            {
                $clip = (array) $clip;
                $list[$key] = $clip;
                $list[$key]['thumb'] = $this->getClipThumbUrl($clip['id'], $clip['code'], $clip['thumbUrl']);
            }

            return $list;
        }

        return null;
    }

    /**
     * Finds list of tagged clips
     *
     * @param string $tag
     * @param int $page
     * @param int $limit
     * @return array of VIDEO_BOL_Clip
     */
    public function findTaggedClipsList( $tag, $page, $limit )
    {
        $first = ($page - 1 ) * $limit;

        $clipIdList = BOL_TagService::getInstance()->findEntityListByTag(self::TAGS_ENTITY_TYPE, $tag, $first, $limit);

        $clips = $this->clipDao->findByIdList($clipIdList);

        $list = array();
        if ( is_array($clips) )
        {
            foreach ( $clips as $key => $clip )
            {
                $clip = (array) $clip;
                $list[$key] = $clip;
                $list[$key]['thumb'] = $this->getClipThumbUrl($clip['id'], $clip['code'], $clip['thumbUrl']);
            }
        }

        return $list;
    }

    public function getThumbUrlWithoutId($thumbUrl = null){
        if($thumbUrl == null){
            return $this->getClipDefaultThumbUrl();
        }
        $event = new OW_Event('videplus.on.video.list.view.render', array('getThumb'=>true,'thumbUrl'=>$thumbUrl));
        OW::getEventManager()->trigger($event);
        if(isset($event->getData()['thumbUrl'])){
            return $event->getData()['thumbUrl'];
        }
        return $this->getClipDefaultThumbUrl();
    }

    public function getClipThumbUrl( $clipId, $code = null, $thumbUrl = null )
    {
        $event = new OW_Event('videplus.on.video.list.view.render', array('getThumb'=>true,'clipId'=>$clipId));
        OW::getEventManager()->trigger($event);
        if(isset($event->getData()['thumbUrl'])){
            return $event->getData()['thumbUrl'];
        }else {
            if (mb_strlen($thumbUrl)) {
                return $thumbUrl;
            }

            if ($code == null) {
                $clip = $this->findClipById($clipId);
                if ($clip) {
                    if (mb_strlen($clip->thumbUrl)) {
                        return $clip->thumbUrl;
                    }
                    $code = $clip->code;
                }
            }

            $providers = new VideoProviders($code);

            return $providers->getProviderThumbUrl();
        }
    }
    
    public function getClipDefaultThumbUrl()
    {
        return OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'video-no-video.png';
    }
    

    /**
     * Counts clips
     *
     * @param string $type
     * @return int
     */
    public function findClipsCount( $type )
    {
        if ( $type == 'toprated' )
        {
            return BOL_RateService::getInstance()->findMostRatedEntityCount(self::RATES_ENTITY_TYPE);
        }

        return $this->clipDao->countClips($type);
    }

    /**
     * Counts user added clips
     *
     * @param int $userId
     * @return int
     */
    public function findUserClipsCount( $userId )
    {
        return $this->clipDao->countUserClips($userId);
    }

    /**
     * Counts clips with specified tag
     *
     * @param string $tag
     * @return array of VIDEO_BOL_Clip
     */
    public function findTaggedClipsCount( $tag )
    {
        return BOL_TagService::getInstance()->findEntityCountByTag(self::TAGS_ENTITY_TYPE, $tag);
    }

    /**
     * Gets number of clips to display per page
     *
     * @return int
     */
    public function getClipPerPageConfig()
    {
        return (int) OW::getConfig()->getValue('video', 'videos_per_page');
    }

    /**
     * Gets user clips quota
     *
     * @return int
     */
    public function getUserQuotaConfig()
    {
        return (int) OW::getConfig()->getValue('video', 'user_quota');
    }

    /**
     * Updates the 'status' field of the clip object 
     *
     * @param int $id
     * @param string $status
     * @return boolean
     */
    public function updateClipStatus( $id, $status )
    {
        /** @var $clip VIDEO_BOL_Clip */
        $clip = $this->clipDao->findById($id);

        $newStatus = $status == 'approve' ? VIDEO_BOL_ClipDao::STATUS_APPROVED : VIDEO_BOL_ClipDao::STATUS_BLOCKED;

        $clip->status = $newStatus;

        $this->updateClip($clip);

        return $clip->id ? true : false;
    }

    /**
     * Changes clip's 'featured' status
     *
     * @param int $id
     * @param string $status
     * @return boolean
     */
    public function updateClipFeaturedStatus( $id, $status )
    {
        $clip = $this->clipDao->findById($id);

        if ( $clip )
        {
            $clipFeaturedService = VIDEO_BOL_ClipFeaturedService::getInstance();

            if ( $status == 'mark_featured' )
            {
                return $clipFeaturedService->markFeatured($id);
            }
            else
            {
                return $clipFeaturedService->markUnfeatured($id);
            }
        }

        return false;
    }

    /**
     * Deletes video clip
     *
     * @param int $id
     * @return int
     */
    public function deleteClip( $id )
    {
        $clip = $this->findClipById($id);
        if(isset($clip)) {
            $event = new OW_Event('videplus.on.user.unregister', array('code' => $clip->code));
            OW::getEventManager()->trigger($event);
            $path = OW::getPluginManager()->getPlugin('video')->getUserFilesDir();
            if ( OW::getStorage()->fileExists($path. $clip->code) )
            {
                $hash=IISSecurityProvider::generateUniqueId();
                $pastName=$clip->code;
                $string=explode(".",$clip->code);
                $videoExtention=$string[count($string)-1];
                $clip->code="deleted_".($clip->userId)."_".$hash.".".$videoExtention;
                $this->clipDao->save($clip);
                $newpath=$path.$clip->code;
                OW::getStorage()->renameFile($path.$pastName, $newpath);
            }
        }

        $event = new OW_Event(self::EVENT_BEFORE_DELETE, array('clipId' => $id));
        OW::getEventManager()->trigger($event);
        
        $this->clipDao->deleteById($id);
        OW::getLogger()->writeLog(OW_Log::INFO, 'delete_video', ['actionType'=>OW_Log::DELETE, 'enType'=>'video', 'enId'=>$id]);
        OW::getEventManager()->call('notifications.remove', array(
            'entityType' => 'video-add_rate',
            'entityId' => $id
        ));

        BOL_CommentService::getInstance()->deleteEntityComments(self::ENTITY_TYPE, $id);
        BOL_RateService::getInstance()->deleteEntityRates($id, self::RATES_ENTITY_TYPE);
        BOL_TagService::getInstance()->deleteEntityTags($id, self::TAGS_ENTITY_TYPE);

        $this->clipFeaturedDao->markUnfeatured($id);

        BOL_FlagService::getInstance()->deleteByTypeAndEntityId(VIDEO_CLASS_ContentProvider::ENTITY_TYPE, $id);
        
        OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array(
            'entityType' => self::FEED_ENTITY_TYPE,
            'entityId' => $id
        )));
        
        $this->cleanListCache();

        $event = new OW_Event(self::EVENT_AFTER_DELETE, array('clipId' => $id));
        OW::getEventManager()->trigger($event);

        return true;
    }
    
    public function cleanupPluginContent( )
    {
        BOL_CommentService::getInstance()->deleteEntityTypeComments(self::ENTITY_TYPE);
        BOL_RateService::getInstance()->deleteEntityTypeRates(self::RATES_ENTITY_TYPE);
        BOL_TagService::getInstance()->deleteEntityTypeTags(self::TAGS_ENTITY_TYPE);
        
        BOL_FlagService::getInstance()->deleteFlagList(self::ENTITY_TYPE);
    }

    /**
     * Adjust clip width and height
     *
     * @param string $code
     * @param int $width
     * @param int $height
     * @return string
     */
    public function formatClipDimensions( $code, $width, $height )
    {
        if ( !strlen($code) )
            return '';

        // remove %
        $code = preg_replace("/width=(\"|')?[\d]+(%)?(\"|')?/i", 'width=${1}' . $width . '${3}', $code);
        $code = preg_replace("/height=(\"|')?[\d]+(%)?(\"|')?/i", 'height=${1}' . $height . '${3}', $code);

        // adjust width and height
        $code = preg_replace("/width=(\"|')?[\d]+(px)?(\"|')?/i", 'width=${1}' . $width . '${3}', $code);
        $code = preg_replace("/height=(\"|')?[\d]+(px)?(\"|')?/i", 'height=${1}' . $height . '${3}', $code);

        $code = preg_replace("/width:( )?[\d]+(px)?/i", 'width:' . $width . 'px', $code);
        $code = preg_replace("/height:( )?[\d]+(px)?/i", 'height:' . $height . 'px', $code);

        return $code;
    }

    /**
     * Validate clip code integrity
     *
     * @param string $code
     * @param null $provider
     * @return string
     */
    public function validateClipCode( $code, $provider = null )
    {
        $textService = BOL_TextFormatService::getInstance();

        $code = UTIL_HtmlTag::stripTagsAndJs($code, $textService->getVideoParamList('tags'), $textService->getVideoParamList('attrs'));

        $objStart = '<object';
        $objEnd = '</object>';
        $objEndS = '/>';

        $posObjStart = stripos($code, $objStart);
        $posObjEnd = stripos($code, $objEnd);

        $posObjEnd = $posObjEnd ? $posObjEnd : stripos($code, $objEndS);

        if ( $posObjStart !== false && $posObjEnd !== false )
        {
            $posObjEnd += strlen($objEnd);
            return substr($code, $posObjStart, $posObjEnd - $posObjStart);
        }
        else
        {
            $embStart = '<embed';
            $embEnd = '</embed>';
            $embEndS = '/>';

            $posEmbStart = stripos($code, $embStart);
            $posEmbEnd = stripos($code, $embEnd) ? stripos($code, $embEnd) : stripos($code, $embEndS);

            if ( $posEmbStart !== false && $posEmbEnd !== false )
            {
                $posEmbEnd += strlen($embEnd);
                return substr($code, $posEmbStart, $posEmbEnd - $posEmbStart);
            }
            else
            {
                $frmStart = '<iframe ';
                $frmEnd = '</iframe>';
                $posFrmStart = stripos($code, $frmStart);
                $posFrmEnd = stripos($code, $frmEnd);
                if ( $posFrmStart !== false && $posFrmEnd !== false )
                {
                    $posFrmEnd += strlen($frmEnd);
                    $code = substr($code, $posFrmStart, $posFrmEnd - $posFrmStart);

                    preg_match('/src=(["\'])(.*?)\1/', $code, $match);
                    if ( !empty($match[2]) )
                    {
                        $src = $match[2];
                        if ( mb_substr($src, 0, 2) == '//' )
                        {
                            $src = 'http:' . $src;
                        }

                        $urlArr = parse_url($src);
                        $parts = explode('.', $urlArr['host']);

                        if ( count($parts) < 2 )
                        {
                            return '';
                        }

                        $d1 = array_pop($parts);
                        $d2 = array_pop($parts);
                        $host = $d2 . '.' . $d1;

                        $resourceList = BOL_TextFormatService::getInstance()->getMediaResourceList();

                        if ( !in_array($host, $resourceList) && !in_array($urlArr['host'], $resourceList) )
                        {
                            return '';
                        }
                    }

                    return $code;
                }
                else
                {
                    return '';
                }
            }
        }
    }

    /**
     * Adds parameter to embed code 
     *
     * @param string $code
     * @param string $name
     * @param string $value
     * @return string
     */
    public function addCodeParam( $code, $name = 'wmode', $value = 'transparent' )
    {
        $repl = $code;

        if ( preg_match("/<object/i", $code) )
        {
            $searchPattern = '<param';
            $pos = stripos($code, $searchPattern);
            if ( $pos )
            {
                $addParam = '<param name="' . $name . '" value="' . $value . '"></param><param';
                $repl = substr_replace($code, $addParam, $pos, strlen($searchPattern));
            }
        }

        if ( preg_match("/<embed/i", isset($repl) ? $repl : $code) )
        {
            $repl = preg_replace("/<embed/i", '<embed ' . $name . '="' . $value . '"', isset($repl) ? $repl : $code);
        }

        return $repl;
    }
    
    public function updateUserClipsPrivacy( $userId, $privacy )
    {
        if ( !$userId || !mb_strlen($privacy) )
        {
            return false;
        }
        
        $clips = $this->clipDao->findByUserId($userId);
        
        if ( !$clips )
        {
            return true;
        }
        
        $this->clipDao->updatePrivacyByUserId($userId, $privacy);
        
        $this->cleanListCache();

        $status = $privacy == 'everybody';
        $event = new OW_Event(
            'base.update_entity_items_status', 
            array('entityType' => 'video_rates', 'entityIds' => $clips, 'status' => $status)
        );
        OW::getEventManager()->trigger($event);
        
        return true;
    }
    
    public function cleanListCache()
    {
        OW::getCacheManager()->clean(array(VIDEO_BOL_ClipDao::CACHE_TAG_VIDEO_LIST));
    }

    public function cacheThumbnails( $limit )
    {
        $clips = $this->clipDao->getUncachedThumbsClipsList($limit);

        if ( !$clips )
        {
            return true;
        }

        foreach ( $clips as $clip )
        {
            $prov = new VideoProviders($clip->code);
            if ( !$clip->provider )
            {
                $clip->provider = $prov->detectProvider();
            }
            $thumbUrl = $prov->getProviderThumbUrl($clip->provider);
            if ( $thumbUrl != VideoProviders::PROVIDER_UNDEFINED )
            {
                $clip->thumbUrl = $thumbUrl;
            }
            $clip->thumbCheckStamp = time();
            $this->clipDao->save($clip);
        }

        if (count($clips) == $limit) {
            OW::getEventManager()->trigger(new OW_Event(self::EVENT_CACHE_THUMBNAILS_INCOMPLETE));
        }

        return true;
    }

    public function deleteComment( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['entityType']) || $params['entityType'] !== 'video_comments' )
            return;

        $commentId = (int) $params['commentId'];
        OW::getEventManager()->call('notifications.remove', array(
            'entityType' => 'video-add_comment',
            'entityId' => $commentId
        ));
    }

    public function findIdListBySearch( $q, $first, $count )
    {
        $ex = new OW_Example();
        $ex->andFieldLike('title', '%'.$q.'%');
        $ex->setOrder('addDatetime desc')->setLimitClause(0, $first+ $count);
        $list1 = $this->clipDao->findIdListByExample($ex);

        $ex = new OW_Example();
        $ex->andFieldLike('description', '%'.$q.'%');
        $ex->setOrder('addDatetime desc')->setLimitClause(0, $first+ $count);
        $list2 = $this->clipDao->findIdListByExample($ex);

        $list = array_unique(array_merge($list1, $list2));
        return array_splice($list, $first, $count );
    }

    public function userIsAuthorized(){
        $userId = null;
        $showAll= false;
        $user = OW::getUser();
        if(isset($user)) {
            $userId = $user->getId();
            if($user->isAdmin() || $user->isAuthorized('video'))
                $showAll = true;
        }
        return array($userId,$showAll);
    }



    public function findListByIdListAndUser( $list )
    {
        list($userId,$showAll) = $this->userIsAuthorized();
        $result = $this->clipDao->findListByIdListAndUser($list,$userId,$showAll);
        $output = array();
        foreach ($list as $id){
            foreach ($result as $clipArray){
                if($clipArray['id'] == $id) {
                    $clip = new VIDEO_BOL_Clip();
                    $clip->id = $clipArray['id'];
                    $clip->userId = $clipArray['userId'];
                    $clip->code = $clipArray['code'];
                    $clip->title = $clipArray['title'];
                    $clip->description = $clipArray['description'];
                    $clip->addDatetime = $clipArray['addDatetime'];
                    $clip->provider = $clipArray['provider'];
                    $clip->status = $clipArray['status'];
                    $clip->privacy = $clipArray['privacy'];
                    $clip->thumbUrl = $clipArray['thumbUrl'];
                    $clip->thumbCheckStamp = $clipArray['thumbCheckStamp'];
                    $output[] = $clip;
                    break;
                }
            }
        }
        return $output;
    }

    public function onCollectSearchItems(OW_Event $event){
        if (!OW::getUser()->isAdmin() && !OW::getUser()->isAuthorized('video', 'view'))
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

        $result = array();
        $count = 0;
        foreach($resultData as $item){
            $itemInformation = array();
            $itemInformation['title'] = $item->title;
            $itemInformation['id'] = $item->id;
            $itemInformation['createdDate'] = $item->addDatetime;
            $itemInformation['userId'] = $item->getUserId();
            $itemInformation['link'] = $this->getVideoUrl($item);
            $itemInformation['image'] = $this->getThumbUrlWithoutId($item->thumbUrl);
            $itemInformation['label'] = OW::getLanguage()->text('iisadvancesearch', 'videos_label');
            $result[] = $itemInformation;
            $count++;
            if($count == $maxCount){
                break;
            }
        }

        $data = $event->getData();
        $data['video'] = array('label' => OW::getLanguage()->text('iisadvancesearch', 'videos_label'), 'data' => $result);
        $event->setData($data);
    }

    public function getVideoUrl($clip)
    {
        return OW::getRouter()->urlForRoute('view_clip', array('id'=>$clip->getId()));
    }
}