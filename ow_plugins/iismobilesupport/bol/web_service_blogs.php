<?php

/**
 * Copyright (c) 2018, Milad Heshmati
 * All rights reserved.
 */

/**
 *
 *
 * @author Milad Heshmati <milad.heshmati@gmail.com>
 * @package ow_plugins.iismobilesupport.bol
 * @since 1.0
 */
class IISMOBILESUPPORT_BOL_WebServiceBlogs
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

    public function getUserblogs()
    {
        if (!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('blogs', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if (!$guestAccess) {
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        $userId = null;

        if (isset($_GET['userId'])) {
            $userId = $_GET['userId'];
        }else if(OW::getUser()->isAuthorized()){
            $userId = OW::getUser()->getId();
        }
        if($userId == null){
            return array('valid' => false, 'message' => 'authentication_error');
        }
        return $this->getUserBlogsWithId($userId);
    }

    public function getUserBlogsWithId($userId){
        $blogsData=array();
        if (!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('blogs', true)){
            return $blogsData;
        }

        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if (isset($_GET['first'])) {
            $first = (int)$_GET['first'];
        }

        $canView = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($userId, 'blogs_view_blog_posts', 'blogs');
        if (!$canView) {
            return array('valid' => false, 'message' => 'authentication_error');
        }
        $blogs = PostService::getInstance()->findUserPostList($userId, $first, $count);

        foreach ( $blogs as $blog ){
            $blogsData[] = $this->prepareBlogInfo($blog);
        }

        return $blogsData;
    }

    public function prepareBlogInfo($blog) {

        if($blog == null) {
            return array();
        }
        $commentable = $this->canUserCommentBlog($blog->id);
        $removable = $this->canUserEditBlog($blog->id);

        $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($blog->post);
        $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->setMentionsOnText($description);

        return array(
            'id' => (int) $blog->id,
            'title' => IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($blog->title),
            'userId' => (int) $blog->authorId,
            'user' => IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($blog->authorId),
            'commentable' => $commentable,
            'removable' => $removable,
            'entityId' => (int) $blog->id,
            'entityType' => 'blogs',
            'description' => $description,
            'timestamp' => $blog->timestamp,
            'flagAble' => true,
            'tags' => '',
        );
    }

    public function getBlog(){
        $blogId = null;
        if(isset($_GET['id'])){
            $blogId  = $_GET['id'];
        }

        if(isset($_POST['id'])){
            $blogId  = $_POST['id'];
        }

        if($blogId == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('blogs', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }
        $first=0;
        if(isset($_GET['first'])){
            $first=$_GET['first'];
        }

        $blog=PostService::getInstance()->findById($blogId);
        if ($blog == null){
            return array('valid' => false, 'message' => 'authorization_error', 'id' => $blogId);
        }

        $canView = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($blog->authorId, 'blogs_view_blog_posts', 'blogs');
        if (!$canView) {
            return array('valid' => false, 'message' => 'authentication_error', 'id' => $blogId);
        }

        $page=IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageNumber($first);
        $comments = IISMOBILESUPPORT_BOL_WebServiceComment::getInstance()->getCommentsInformation('blog-post', $blogId, $page);

        $blogData = $this->prepareBlogInfo($blog);
        $blogData['comments'] = $comments;

        return $blogData;
    }

    public function getLatestBlogs(){

        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('blogs', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if (isset($_GET['first'])) {
            $first = (int)$_GET['first'];
        }

        $data = array();
        $blogs = PostService::getInstance()->findListByUser($first, $count);

        foreach ( $blogs as $blog ){
            $data[] = $this->prepareBlogInfo($blog);
        }

        return $data;
    }

    public function addBlog()
    {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('blogs', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }
        if (!OW::getUser()->isAuthorized('blogs', 'add')){
            return array('valid' => false, 'message' => 'user_is_not_allowed_to_add_blog');
        }
        if(!isset($userId)){
            $userId=OW::getUser()->getId();
        }
        if (!isset($_POST['title']) || !isset($_POST['description'])) {
            return array('valid' => false, 'message' => 'input_error');
        }

        $post = new Post();
        $post->title = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($_POST['title'], true, true);
        $post->post = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($_POST['description'], true, false);
        $post->authorId = $userId;
        $post->timestamp = time();
        $post->isDraft = false;
        $post->privacy = 'everybody';

        PostService::getInstance()->save($post);
        $newBlog=PostService::getInstance()->findUserLastPost($userId);
        $event = new OW_Event('feed.action', array(
            'pluginKey' => 'blogs',
            'entityType' => 'blog-post',
            'entityId' => (int) $newBlog->id,
            'userId' => (int) $newBlog->authorId,
        ));
        OW::getEventManager()->trigger(new OW_Event(PostService::EVENT_AFTER_ADD, array(
            'postId' => $newBlog->id
        )));
        OW::getEventManager()->trigger($event);
        return array('valid' => true, 'blog' => $this->prepareBlogInfo($newBlog));
    }


    public function canUserCommentBlog($blogId){
        $blog = PostService::getInstance()->findById($blogId);
        if($blog == null){
            return false;
        }
        if(!OW::getUser()->isAuthorized('blogs', 'add_comment') ){
            return false;
        }
        $checkPrivacy = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($blog->authorId, 'blogs_comment_blog_posts', 'blogs');
        if (!$checkPrivacy){
            return false;
        }
        return true;
    }

    public function canUserEditBlog($blogId){

        $blog = PostService::getInstance()->findById($blogId);
        if($blog == null){
            return false;
        }
        if(!(OW::getUser()->isAdmin() || ( OW::getUser()->getId() == $blog->getAuthorId() || OW::getUser()->isAuthorized('blogs'))) ){
            return false;
        }
        return true;
    }

    public function canUserCreateBlog(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('blogs', true);

        if(!$pluginActive){
            return false;
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('blogs', 'add') )
        {
            return false;
        }

        return true;
    }

    public function removeBlog()
    {
        if(!isset($_GET['blogId'])){
            return array('valid' => false, 'message' => 'blog_id_not_set');
        }
        $blogId=$_GET['blogId'];
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('blogs', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }
        if ( $this->canUserEditBlog($blogId) ) {
            PostService::getInstance()->deletePost($blogId);
            return array('valid' => true, 'id' => (int) $blogId);
        }else{
            return array('valid' => false, 'message' => 'blog_was_not_deleted');
        }
    }


}