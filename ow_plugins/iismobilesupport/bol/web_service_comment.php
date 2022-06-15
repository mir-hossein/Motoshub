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
class IISMOBILESUPPORT_BOL_WebServiceComment
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

    public function getCommentsInformationFromRequest(){
        $entityType = null;
        $entityId = null;
        if (isset($_GET['entityType']))
        {
            $entityType = $_GET['entityType'];
        }
        if (isset($_GET['entityId']))
        {
            $entityId = $_GET['entityId'];
        }

        if($entityId == null || $entityType == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            if(!IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->userCanSeeAction($entityType, $entityId)){
                return array();
            }
        }

        if($entityType == 'event'){
            $canUserAccessToGroup = IISMOBILESUPPORT_BOL_WebServiceEvent::getInstance()->canUserAccessWithEntity($entityType, $entityId, OW::getUser()->getId());
            if(!$canUserAccessToGroup){
                return array();
            }
        }

        return $this->getCommentsInformation($entityType, $entityId);
    }

    public function likeComment() {
        if(!IISSecurityProvider::checkPluginActive('iislike', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $id = null;
        if(isset($_GET['id'])){
            $id = $_GET['id'];
        }

        if ($id === null) {
            return array('valid' => false, 'message' => 'input_error');
        }

        $comment = BOL_CommentService::getInstance()->findComment($id);
        if (!isset($comment)) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $commentEntityTId = $comment->getCommentEntityId();
        $commentEntity = BOL_CommentService::getInstance()->findCommentEntityById($commentEntityTId);

        if (!isset($commentEntity)) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $entityType = $commentEntity->entityType;
        $entityId = $commentEntity->entityId;

        $access = $this->likable($entityType, $entityId, OW::getUser()->getId());
        if (!$access) {
            return array('valid' => false, 'message' => 'authorization_error', 'entityType' => $entityType, 'id' => $id);
        }
        IISLIKE_BOL_Service::getInstance()->setLike($id, $entityType, OW::getUser()->getId());
        return array('valid' => true, 'message' => 'liked', 'entityId' => $entityId, 'entityType' => $entityType, 'id' => $id);
    }

    public function unlikeComment() {
        if(!IISSecurityProvider::checkPluginActive('iislike', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $id = null;
        if(isset($_GET['id'])){
            $id = $_GET['id'];
        }

        if ($id === null) {
            return array('valid' => false, 'message' => 'input_error');
        }

        $comment = BOL_CommentService::getInstance()->findComment($id);
        if (!isset($comment)) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $commentEntityTId = $comment->getCommentEntityId();
        $commentEntity = BOL_CommentService::getInstance()->findCommentEntityById($commentEntityTId);

        if (!isset($commentEntity)) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $entityType = $commentEntity->entityType;
        $entityId = $commentEntity->entityId;

        $access = $this->likable($entityType, $entityId, OW::getUser()->getId());
        if (!$access) {
            return array('valid' => false, 'message' => 'authorization_error', 'entityType' => $entityType, 'id' => $id);
        }
        IISLIKE_BOL_Service::getInstance()->removeLike($id, $entityType, OW::getUser()->getId());
        return array('valid' => true, 'message' => 'unliked', 'entityId' => $entityId, 'entityType' => $entityType, 'id' => $id);
    }

    public function likable($entityType, $entityId, $userId) {
        if(!IISSecurityProvider::checkPluginActive('iislike', true)){
            return false;
        }
        $validEntityTypes = IISLIKE_BOL_Service::getInstance()->getValidEntityTypes();
        if(!in_array($entityType, $validEntityTypes))
        {
            return false;
        }
        if($entityType == 'groups-status' || $entityType == 'groups-join' || $entityType == 'group'){
            $canUserAccessToGroup = IISMOBILESUPPORT_BOL_WebServiceGroup::getInstance()->canUserAccessWithEntity($entityType, $entityId);
            if(!$canUserAccessToGroup){
                return false;
            }
            return true;
        }

        if($entityType == 'video_comments'){
            $canUserAccessToVideo = IISMOBILESUPPORT_BOL_WebServiceVideo::getInstance()->canUserSeeVideoOfUserId(OW::getUser()->getId(), $entityId);
            if(!$canUserAccessToVideo){
                return false;
            }
            return true;
        }

        if($entityType == 'photo_comments'){
            $canUserAccessToPhoto = IISMOBILESUPPORT_BOL_WebServicePhoto::getInstance()->canUserSeePhoto($entityId);
            if(!$canUserAccessToPhoto){
                return false;
            }
            return true;
        }

        if($entityType == 'event'){
            $canUserAccessToEvent = IISMOBILESUPPORT_BOL_WebServiceEvent::getInstance()->canUserAccessWithEntity($entityType, $entityId, $userId);
            if(!$canUserAccessToEvent){
                return false;
            }
            return true;
        }

        if($entityType == 'blog-post'){
            $canUserAccessToBlog = IISMOBILESUPPORT_BOL_WebServiceBlogs::getInstance()->canUserCommentBlog($entityId);
            if(!$canUserAccessToBlog){
                return false;
            }
            return true;
        }

        if(in_array($entityType, $this->getDashboardFeedEntityType())) {
            $access = $this->canAccessToComment($entityType, $entityId, $userId);
            if (!$access) {
                return false;
            }
            return true;
        }

        if($entityType == 'news-entry') {
            return true;
        }
        return false;
    }

    public function getCommentsInformation($entityType, $entityId, $fromPage = null){
        $data = array();

        $page = null;
        if($fromPage != null){
            $page = $fromPage;
        }
        if(isset($_GET['page'])){
            $page = $_GET['page'];
        }

        $first = null;
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        if($page == null && $first != null){
            $page = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageNumber($first);
        }

        if($page == null){
            $page= 1;
        }

        $commentEntity = BOL_CommentService::getInstance()->findCommentList($entityType, $entityId, $page);
        foreach ($commentEntity as $comment){
            $data[] = $this->prepareComment($comment);
        }

        return $data;
    }

    public function getCommentsCount($entityType, $entityId){
        $commentsCount = BOL_CommentService::getInstance()->findCommentCount($entityType, $entityId);
        return $commentsCount;
    }

    /***
     * @param BOL_Comment $comment
     * @return array
     */
    public function prepareComment($comment){
        if($comment == null) {
            return array();
        }
        $files = array();
        if(isset($comment->attachment) && $comment->attachment != null){
            $attachments = json_decode($comment->attachment);
            $tmpUrl = null;
            if(isset($attachments->url)){
                $tmpUrl = $attachments->url;
            }
            if($tmpUrl != null){
                $files[] = IISSecurityProvider::getInstance()->correctHomeUrlVariable($tmpUrl);
            }
        }

        $removable = true;
        $commentInfo = $this->getCommentInfoForDelete($comment->id);
        if($commentInfo == null){
            $removable = false;
        }
        $user = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($comment->userId);
        $text = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($comment->message, true, false, true);

        $regex_view = '((( |^|\n|\t|>|>|\(|\))@)(\w+))';
        preg_match_all('/'.$regex_view.'/', $text, $matches);
        if(isset($matches[4])){
            foreach($matches[4] as $match){
                $mentionedUser = BOL_UserService::getInstance()->findByUsername($match);
                if($mentionedUser){
                    $text = str_replace('@'.$match, '@'.$match.':'.$mentionedUser->getId(), $text);
                }
            }
        }

        $commentEntityTId = $comment->getCommentEntityId();
        $commentEntity = BOL_CommentService::getInstance()->findCommentEntityById($commentEntityTId);

        $likeInfo = array(
            'enable' => false,
            'liked' => false,
            'disliked' => false,
            'sum' => 0,
            'disliked_users' => array(),
            'liked_users' => array()
        );
        if (OW::getUser()->isAuthenticated() && $this->likable($commentEntity->entityType, $commentEntity->entityId, OW::getUser()->getId())) {
            $likeInfo['enable'] = true;
            list($commentLikeInfo, $userVoteInfo) = IISLIKE_BOL_Service::getInstance()->getLikeInfoForList(array($comment->id), 'iislike-' . $commentEntity->entityType);
            if (isset($commentLikeInfo) && isset($commentLikeInfo[$comment->id])) {
                $commentLikeInfo = $commentLikeInfo[$comment->id];
            } else {
                $commentLikeInfo = null;
            }
            if ($commentLikeInfo != null) {
                if (!empty($commentLikeInfo['sum'])) {
                    $likeInfo['sum'] = (int)$commentLikeInfo['sum'];
                }
                if (!empty($commentLikeInfo['upUserId'])) {
                    $upUserIds = $commentLikeInfo['upUserId'];
                    foreach ($upUserIds as $upUserId){
                        if ($upUserId == OW::getUser()->getId()) {
                            $likeInfo['liked'] = true;
                        }
                    }
                    $likeInfo['liked_users'] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUsersInfoByIdList($upUserIds);
                }
                if (!empty($commentLikeInfo['downUserId'])) {
                    $downUserIds = $commentLikeInfo['downUserId'];
                    foreach ($downUserIds as $downUserId){
                        if ($downUserId == OW::getUser()->getId()) {
                            $likeInfo['disliked'] = true;
                        }
                    }
                    $likeInfo['disliked_users'] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUsersInfoByIdList($downUserIds);
                }
            }
        }

        return array(
            "userId" => (int) $comment->userId,
            "user" => $user,
            "text" => $text,
            "id" => (int) $comment->id,
            "time" => $comment->createStamp,
            "removable" => $removable,
            "entityType" => "comment",
            "likeInfo" => $likeInfo,
            "entityId" => (int) $comment->id,
            "flagAble" => true,
            "files" => $files
        );
    }

    public function addComment(){
        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $text = null;
        $entityType = null;
        $entityId = null;
        $pluginKey = null;

        if(isset($_POST['text'])){
            $text = $_POST['text'];
        }

        if(isset($_POST['pluginKey'])){
            $pluginKey = $_POST['pluginKey'];
        }

        if(isset($_POST['entityId'])){
            $entityId = $_POST['entityId'];
        }

        if(isset($_POST['entityType'])){
            $entityType = $_POST['entityType'];
        }

        $userId = OW::getUser()->getId();
        $access = false;

        if($text == null || $entityType == null || $entityId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!in_array($pluginKey, array('event', 'newsfeed', 'iisnews', 'video', 'photo','blogs'))){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!in_array($entityType, array('event', 'groups-status', 'groups-join', 'group', 'user_join', 'news-entry', 'video_comments', 'photo_comments', 'blog-post')) && !in_array($entityType, $this->getDashboardFeedEntityType())){
            return array('valid' => false, 'message' => 'authorization_error', 'entityType' => $entityType, 'entityId' => $entityId);
        }

        if($entityType == 'groups-status' || $entityType == 'groups-join' || $entityType == 'group'){
            $canUserAccessToGroup = IISMOBILESUPPORT_BOL_WebServiceGroup::getInstance()->canUserAccessWithEntity($entityType, $entityId);
            if(!$canUserAccessToGroup){
                return array('valid' => false, 'message' => 'authorization_error');
            }
            $access = true;
        }

        if($entityType == 'video_comments'){
            $canUserAccessToVideo = IISMOBILESUPPORT_BOL_WebServiceVideo::getInstance()->canUserSeeVideoOfUserId(OW::getUser()->getId(), $entityId);
            if(!$canUserAccessToVideo){
                return array('valid' => false, 'message' => 'authorization_error');
            }
            $access = true;
        }

        if($entityType == 'photo_comments'){
            $canUserAccessToPhoto = IISMOBILESUPPORT_BOL_WebServicePhoto::getInstance()->canUserSeePhoto($entityId);
            if(!$canUserAccessToPhoto){
                return array('valid' => false, 'message' => 'authorization_error');
            }
            $access = true;
        }

        if($entityType == 'event'){
            $canUserAccessToEvent = IISMOBILESUPPORT_BOL_WebServiceEvent::getInstance()->canUserAccessWithEntity($entityType, $entityId, $userId);
            if(!$canUserAccessToEvent){
                return array('valid' => false, 'message' => 'authorization_error');
            }
            $access = true;
        }

        if($entityType == 'blog-post'){
            $canUserAccessToBlog = IISMOBILESUPPORT_BOL_WebServiceBlogs::getInstance()->canUserCommentBlog($entityId);
            if(!$canUserAccessToBlog){
                return array('valid' => false, 'message' => 'authorization_error');
            }
            $access = true;
        }

        if(!$access && in_array($entityType, $this->getDashboardFeedEntityType())) {
            $access = $this->canAccessToComment($entityType, $entityId, $userId);
            if (!$access) {
                return array('valid' => false, 'message' => 'authorization_error', 'entityType' => $entityType, 'entityId' => $entityId);
            }
        }

        $text = empty($text) ? '' : trim($text);
        if(empty($text)){
            return array('valid' => false, 'message' => 'empty_text');
        }

        $attachment = null;
        $virusDetectedFiles = array();
        $fileValid = true;

        if ( isset($_FILES) && !empty($_FILES['file']['name']) ){
            if ( (int) $_FILES['file']['error'] !== 0 ||
                !is_uploaded_file($_FILES['file']['tmp_name']) ||
                !UTIL_File::validateImage($_FILES['file']['name']) ){
                $fileValid = false;
            }
            else {
                $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
                if ($isFileClean) {
                    $tempArr = array();

                    $tempArr['url'] = '';
                    $tempArr['uid'] = IISSecurityProvider::generateUniqueId();;
                    $tempArr['pluginKey'] = $pluginKey;
                    $item = $_FILES['file'];

                    try
                    {
                        $dtoArr = BOL_AttachmentService::getInstance()->processUploadedFile($tempArr['pluginKey'], $item, $tempArr['uid'], array('jpg', 'jpeg', 'png', 'gif'));
                        $tempArr['url'] = $dtoArr['url'];
                    }
                    catch ( Exception $e )
                    {
                        $fileValid = false;
                    }

                    if ($fileValid || !empty($tempArr['url'])) {
                        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE, array('string' => $tempArr['url'])));
                        if (isset($stringRenderer->getData()['string'])) {
                            $tempArr['url'] = $stringRenderer->getData()['string'];
                        }
                        OW::getEventManager()->call('base.attachment_save_image', array('uid' => $tempArr['uid'], 'pluginKey' => $tempArr['pluginKey']));

                        $tempArr['href'] = $tempArr['url'];
                        $tempArr['type'] = 'photo';
                        $attachment = json_encode($tempArr);
                    }
                } else {
                    $virusDetectedFiles[] = $_FILES['file']['name'];
                }
            }
        }

        $comment = BOL_CommentService::getInstance()->addComment($entityType, $entityId, $pluginKey, $userId, $text, $attachment);
        $comment->message = urldecode($comment->message);
        BOL_AuthorizationService::getInstance()->trackAction($pluginKey, 'add_comment');
        $commentItem = $this->prepareComment($comment);
        return array('valid' => true, 'message' => 'added', 'item' => $commentItem, 'virus_files' => $virusDetectedFiles, 'fileValid' => $fileValid);
    }

    public function getDashboardFeedEntityType(){
        return array('multiple_photo_upload', 'user-status', 'photo_comments', 'group');
    }

    public function canAccessToComment($entityType, $entityId, $userId){
        if($entityType == null ||
            $entityId == null ||
            $entityType == 'event' ||
            $entityType == 'groups-status'){
            return false;
        }

        if(in_array($entityType, $this->getDashboardFeedEntityType())){
            $activity = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->getCreatorActivityOfAction($entityType, $entityId);
            if($activity == null){
                return false;
            }

            $ownerId = $activity->userId;
            $privacy = $activity->privacy;

            if($ownerId == $userId){
                return true;
            }

            if(BOL_UserService::getInstance()->isBlocked($userId, $ownerId) ||
                BOL_UserService::getInstance()->isBlocked($ownerId, $userId)){
                return false;
            }

            if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->userAccessUsingPrivacy($privacy, $userId, $ownerId)){
               return true;
            }

        }

        return false;
    }

    public function removeComment(){
        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $cid = null;
        if(isset($_POST['id'])){
            $cid = $_POST['id'];
        }

        if($cid == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $commentInfo = $this->getCommentInfoForDelete($cid);
        if($commentInfo == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        BOL_CommentService::getInstance()->deleteComment($cid);
        return array('valid' => true, 'message' => 'deleted', 'id' => (int) $cid);
    }

    public function getCommentInfoForDelete($cid)
    {
        if($cid == null){
            return null;
        }
        $comment = BOL_CommentService::getInstance()->findComment($cid);
        if($comment == null){
            return null;
        }

        $userId = OW::getUser()->getId();

        /* @var $comment BOL_Comment */
        $comment = BOL_CommentService::getInstance()->findComment($cid);
        if($comment == null){
            return null;
        }
        /* @var $commentEntity BOL_CommentEntity */
        $commentEntity = BOL_CommentService::getInstance()->findCommentEntityById($comment->getCommentEntityId());

        if ($commentEntity === null )
        {
            return null;
        }

        $isModerator = OW::getUser()->isAuthorized($commentEntity->pluginKey) || OW::getUser()->isAdmin();
        $commentOwner = $userId == $comment->getUserId();

        if ( !$isModerator && !$commentOwner )
        {
            return null;
        }

        return array('comment' => $comment, 'commentEntity' => $commentEntity);
    }
}