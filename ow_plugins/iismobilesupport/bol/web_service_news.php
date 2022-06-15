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
class IISMOBILESUPPORT_BOL_WebServiceNews
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

    public function getNews(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisnews', true)){
            return array();
        }

        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        $newsIdList = array();
        $newsData = array();
        $arr = EntryService::getInstance()->findList($first, $count);

        foreach ( $arr as $item )
        {
            $newsIdList[] = $item->id;
        }

        if(sizeof($newsIdList) > 0) {
            $tags = BOL_TagService::getInstance()->findTagListByEntityIdList('news-entry', $newsIdList);
        }

        foreach ( $arr as $item )
        {
            $tag = $tags[$item->id];
            $newsData[] = $this->prepareNewsItem($item, $tag);
        }

        return $newsData;
    }

    public function prepareNewsItem($news, $tag = array(), $params = array()){
        if ($news == null) {
            return array();
        }
        $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($news->entry);
        $imageUrl = OW::getPluginManager()->getPlugin('base')->getStaticUrl(). 'css/images/' . 'default_news.png';
        $emptyImage = true;
        if(isset($news->image) && !empty($news->image)){
            $imageUrl = EntryService::getInstance()->generateImageUrl($news->image);
            $emptyImage = false;
        }

        $removable = $this->canUserManageNews();
        $editable = $removable;

        $result = array(
            'id' => (int) $news->id,
            'title' => $news->title,
            'entityId' => (int) $news->id,
            'entityType' => 'news-entry',
            'description' => $description,
            'description_raw' => $news->entry,
            'timestamp' => $news->timestamp,
            'imageUrl' => $imageUrl,
            'tags' => $tag,
            'emptyImage' => $emptyImage,
            'removable' => $removable,
            'editable' => $editable,
            "flagAble" => true,
        );

        $page = 1;
        if (isset($_GET['comment_page'])){
            $page = (int) $_GET['comment_page'];
        }

        if (in_array('comments', $params)){
            $comments = IISMOBILESUPPORT_BOL_WebServiceComment::getInstance()->getCommentsInformation('news-entry', $news->id, $page);
            $result['comments'] = $comments;
        }
        return $result;
    }

    public function getNewsItem(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisnews', true)){
            return array();
        }

        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        $newsId = null;
        if(isset($_GET['newsId'])){
            $newsId = (int) $_GET['newsId'];
        }

        if($newsId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $tags = BOL_TagService::getInstance()->findTagListByEntityIdList('news-entry', array($newsId));
        $newsItem = EntryService::getInstance()->findById($newsId);

        if($newsItem == null){
            return array('valid' => false, 'message' => 'authorization_error', 'id' => $newsId);
        }

        $tag = $tags[$newsItem->id];
        return $this->prepareNewsItem($newsItem, $tag, array('comments'));
    }

    public function addNews()
    {
        if(!$this->canUserManageNews()){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        if (!isset($_POST['title']) || !isset($_POST['entry'])) {
            return array('valid' => false, 'message' => 'input_error');
        }

        $userId=OW::getUser()->getId();

        $news = new Entry();
        $news->title = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($_POST['title'], true, true);
        $news->entry = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($_POST['entry'], true, false);
        $news->authorId = $userId;
        $news->timestamp = time();
        $news->isDraft = false;
        $news->privacy = 'everybody';

        EntryService::getInstance()->save($news);

        $newsData = $this->prepareNewsItem($news, array());
        return array('valid' => true, 'news' => $newsData );
    }

    public function removeNews()
    {
        if(!$this->canUserManageNews()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if (!isset($_GET['postId'])) {
            return array('valid' => false, 'message' => 'input_error');
        }
        $postId = $_GET['postId'];

        EntryService::getInstance()->deleteEntry($postId);

        return array('valid' => true, 'postId' => (int) $postId );
    }

    public function canUserManageNews(){
        if (!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisnews', true)) {
            return false;
        }
        if (!OW::getUser()->isAuthenticated()) {
            return false;
        }
        if (!OW::getUser()->isAuthorized('iisnews', 'add') && !OW::getUser()->isAdmin()) {
            return false;
        }
        return true;
    }
}