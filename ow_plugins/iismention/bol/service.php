<?php
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismention
 * @since 1.0
 */
class IISMENTION_BOL_Service
{
    private static $classInstance;
    private $regex_view = '((( |^|\n|\t|>|:|\(|\))@)(\w+))';
    private $notifications_action = 'mentioned';

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
    }

    /***
     * @param BASE_CLASS_EventCollector $event
     */
    public function privacyAddAction( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $privacyValueEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PRIVACY_ITEM_ADD,
            array('key' => 'iismention_search_my_username')));
        $defaultValue = 'everybody';
        if(isset($privacyValueEvent->getData()['value'])){
            $defaultValue = $privacyValueEvent->getData()['value'];
        }
        $action = array(
            'key' => 'iismention_search_my_username',
            'pluginKey' => 'iismention',
            'label' => $language->text('iismention', 'privacy_action_search_my_username'),
            'description' => '',
            'defaultValue' => $defaultValue
        );

        $event->add($action);
    }

    /***
     * @param $authorId
     * @param $mentionedUserId
     * @param $textLink
     * @param $textDicKey
     * @param $entityType
     * @param $entityId
     */
    public function addToNotificationList($authorId, $mentionedUserId, $textLink, $textDicKey, $entityType, $entityId)
    {
        //add new notif
        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($authorId));
        $authorName = BOL_UserService::getInstance()->getDisplayName($authorId);
        $authorUrl = BOL_UserService::getInstance()->getUserUrl($authorId);
        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $textLink)));
        if(isset($stringRenderer->getData()['string'])){
            $textLink = $stringRenderer->getData()['string'];
        }

        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $authorUrl)));
        if(isset($stringRenderer->getData()['string'])){
            $authorUrl = $stringRenderer->getData()['string'];
        }

        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $avatars[$authorId]['src'])));
        if(isset($stringRenderer->getData()['string'])){
            $avatars[$authorId]['src'] = $stringRenderer->getData()['string'];
        }

        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $avatars[$authorId]['url'])));
        if(isset($stringRenderer->getData()['string'])){
            $avatars[$authorId]['url'] = $stringRenderer->getData()['string'];
        }
        $event = new OW_Event('notifications.add', array(
            'pluginKey' => 'iismention',
            'entityType' => $entityType,
            'entityId' => $entityId,
            'action' => $this->notifications_action,
            'userId' => $mentionedUserId,
            'time' => time()
        ), array(
            'avatar' => $avatars[$authorId],
            'string' => array(
                'key' => $textDicKey,
                'vars' => array(
                    'userName' => $authorName,
                    'userUrl' => $authorUrl,
                    'textLink'=>$textLink
                )
            ),
            'url' => $textLink
        ));
        OW::getEventManager()->trigger($event);
    }

    /***
     * @param $content
     * @return mixed|string
     */
    public function fixMentionPaste($content)
    {
        $content=str_replace("@&zwnj;","@",$content);
        $content=utf8_encode($content);
        $content=str_replace("@â","@",$content);
        $content=utf8_decode($content);
        $content=str_replace("&nbsp;"," ",$content);
        return $content;
    }

    /***
     * @param $entityType
     * @param $entityId
     */
    public function deleteAllNotificationsByEntity($entityType, $entityId)
    {
        NOTIFICATIONS_BOL_Service::getInstance()->deleteNotificationByEntityAndAction($entityType, $entityId, $this->notifications_action);
    }

    /***
     * @param $content
     * @param $entityId
     * @param $entityType
     * @param $authorId
     * @param $textLink
     * @param $isComment
     */
    private function findAndNotifyFromContent($content, $entityId, $entityType, $authorId, $textLink, $isComment){
        $textDicKey = 'iismention+console_notification_newsfeed';
        if($isComment)
            $textDicKey = 'iismention+console_notification_comment';
        $content=$this->fixMentionPaste($content);
        preg_match_all('/'.$this->regex_view.'/', $content, $matches);
        if(isset($matches[4])){
            foreach($matches[4] as $match){
                $user = BOL_UserService::getInstance()->findByUsername($match);
                if($user){
                    if($user->getId() != $authorId) {
                        $this->addToNotificationList($authorId, $user->getId(), $textLink, $textDicKey, $entityType, $entityId);
                    }
                }
            }
        }
    }

    /***
     * @param OW_Event $e
     */
    public function onAddComment( OW_Event $e )
    {
        $params = $e->getParams();
        $comment = BOL_CommentService::getInstance()->findComment($params['commentId']);
        $content = $comment->getMessage();
        $entityId = $params['entityId'];
        $entityType = $params['entityType'];
        if(isset($params['pluginKey']) && $params['pluginKey'] == 'groups') {
            if (isset($params['entityType']) && $params['entityType'] == 'groups-join') {
                $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction('groups-join', $entityId);
                if($action == null) {
                    $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction('user-status', $entityId);
                }
                if($action == null) {
                    $entityId = $this->findGroupIdByEntityId($params['entityId']);
                }else{
                    $entityId = $this->findGroupIdByActionId($action->id, $params['entityId'], 'groups');
                }
                if($entityId == null){
                    return;
                }
                $g = GROUPS_BOL_Service::getInstance()->findGroupById($entityId);
                $textLink = GROUPS_BOL_Service::getInstance()->getGroupUrl($g);
            }

            $entityType = 'groups-status';
            if (isset($action)) {
                $textLink = NEWSFEED_BOL_Service::getInstance()->getActionPermalink($action->getId());
            }
        }
        else if(isset($params['pluginKey']) && $params['pluginKey'] == 'newsfeed') {
            if (isset($params['entityType']) && $params['entityType'] == 'groups-status') {
                $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($params['entityType'], $entityId);
                if($action == null){
                    $entityId = $this->findGroupIdByEntityId($params['entityId']);
                }else {
                    $entityId = $this->findGroupIdByActionId($action->id, $params['entityId'], 'groups');
                }
                if($entityId == null){
                    return;
                }
                $g = GROUPS_BOL_Service::getInstance()->findGroupById($entityId);
                $textLink = GROUPS_BOL_Service::getInstance()->getGroupUrl($g);
                if (isset($action)) {
                    $textLink = NEWSFEED_BOL_Service::getInstance()->getActionPermalink($action->getId());
                }
            }
        }else if($entityType=="photo_comments") {
            $textLink = OW::getRouter()->urlForRoute('view_photo', array('id' => $entityId));
        }else if($entityType=="groups-wal") {
            $g = GROUPS_BOL_Service::getInstance()->findGroupById($entityId);
            $textLink = GROUPS_BOL_Service::getInstance()->getGroupUrl($g);
        }else if ($entityType == 'event') {
            $textLink = EVENT_BOL_EventService::getInstance()->getEventUrl($entityId);
        }else if($entityType=="blog-post") {
            $blog = PostService::getInstance()->findById($entityId);
            $textLink = PostService::getInstance()->getPostUrl($blog);
        }

        //find author and text link
        if(!isset($textLink)){
            $action = NEWSFEED_BOL_Service::getInstance()->findAction($entityType, $entityId);
            if(!isset($action))
                return;
            $textLink = NEWSFEED_BOL_Service::getInstance()->getActionPermalink($action->getId());
        }

        //add to notifications from new comment
        $authorId = OW::getUser()->getId();
        $this->findAndNotifyFromContent($content, $entityId, $entityType, $authorId, $textLink, true);
    }

    /***
     * @param $content
     * @return mixed
     */
    private function findAndReplaceUsernamesFromView($content){
        $content=$this->fixMentionPaste($content);
        $replace1 = preg_replace_callback('/'.$this->regex_view.'/', function($matches) {
            $matches1 = $matches[4];
            $url = BOL_UserService::getInstance()->getUserUrlForUsername($matches1);
            return $matches[3] . '<a class="iismention_person" href="'.$url.'">@&#8235;'.$matches1.'</a>';
        },  $content);

        return $replace1;
    }

    /***
     * @param $event
     * @param $key
     * @return mixed
     */
    private function findAndProcessKeyFromEvent($event, $key){
        $data = $event->getData();
        if(isset($data[$key])) {
            $string = $data[$key];
        }else{
            $params = $event->getParams();
            if (isset($params[$key])) {
                $string = $params[$key];
            }
        }

        if(isset($string)){
            $string = $this->findAndReplaceUsernamesFromView($string);
            $data[$key] = $string;
        }
        return $data;
    }

    /***
     * @param OW_Event $event
     * @return mixed
     */
    public function renderNewsfeed( OW_Event $event )
    {
        $data = $this->findAndProcessKeyFromEvent($event, 'content');
        $event->setData($data);
        return $data;
    }

    /***
     * @param BASE_CLASS_EventProcessCommentItem $event
     */
    public function renderComments( BASE_CLASS_EventProcessCommentItem $event )
    {
        $string = $event->getDataProp('content');

        $string = $this->findAndReplaceUsernamesFromView($string);

        $event->setDataProp('content', $string);
    }

    /***
     * @param OW_Event $event
     * @return mixed
     */
    public function renderString( OW_Event $event )
    {
        $data = $this->findAndProcessKeyFromEvent($event, 'string');
        $event->setData($data);
        return $data;
    }

    /**
     * @param OW_Event $event
     */
    public function onCommentDelete(OW_Event $event){
        $params = $event->getParams();
        /** @var BOL_Comment $comment */
        $comment = $params['comment'];
        if(isset($comment)){
            preg_match_all('/'.$this->regex_view.'/u', $comment->getMessage(), $commentMatches);
            if(!isset($commentMatches))
                return;
            $this->onEntityUpdate(new OW_Event('', array('entityType'=>$params['entityType'],'entityId'=>$params['entityId'],'pluginKey'=>$params['pluginKey'])));
        }
    }

    /***
     * @param BASE_CLASS_EventCollector $e
     */
    public function onNotifyActions( BASE_CLASS_EventCollector $e )
    {
        //register notif to be shown
        $e->add(array(
            'section' => 'iismention',
            'action' => $this->notifications_action,
            'sectionIcon' => 'ow_ic_calendar',
            'sectionLabel' => OW::getLanguage()->text('iismention', 'title'),
            'description' => OW::getLanguage()->text('iismention', 'you_are_mentioned'),
            'selected' => true
        ));
    }

    /***
     * @param OW_Event $e
     */
    public function onNotificationRender( OW_Event $e )
    {
        //how to show
        $params = $e->getParams();
        if ( $params['pluginKey'] != 'iismention')
        {
            return;
        }
        $data = $params['data'];

        if ( !isset($data['avatar']['urlInfo']['vars']['username']) )
        {
            return;
        }

        $userService = BOL_UserService::getInstance();
        $user = $userService->findByUsername($data['avatar']['urlInfo']['vars']['username']);
        if ( !$user )
        {
            return;
        }
        $e->setData($data);
    }

    /***
     * @param $actionId
     * @param $entityId
     * @param $type
     * @return null
     */
    public function findGroupIdByActionId($actionId, $entityId, $type){
        $activityId = null;
        $activities = NEWSFEED_BOL_ActivityDao::getInstance()->findByActionIds(array($actionId));
        foreach($activities as $activity){
            if($activity->activityType=='create'){
                $activityId = $activity->id;
            }
        }
        if($activityId!=null){
            $feedList = NEWSFEED_BOL_Service::getInstance()->findFeedListByActivityids(array($activityId));
            $feedList = $feedList[$activityId];
            foreach ($feedList as $feed) {
                if ($feed->feedType == $type) {
                    return $feed->feedId;
                }
            }
        }else {
            $groupId = $this->findGroupIdByEntityId($entityId);
            if($groupId == null){
                return null;
            }else{
                return $groupId;
            }
        }
        return null;
    }

    /***
     * @param $entityId
     * @return null
     */
    public function findGroupIdByEntityId($entityId){
        if($entityId == null){
            return null;
        }
        $groupStatus = NEWSFEED_BOL_StatusDao::getInstance()->findById($entityId);
        if($groupStatus == null || $groupStatus->feedType != 'groups'){
            return null;
        }else if($groupStatus != null && $groupStatus->feedType == 'groups'){
            return $groupStatus->feedId;
        }
        return null;
    }

    /***
     * @param OW_Event $e
     */
    public function onEntityUpdate(OW_Event $e )
    {
        $params = $e->getParams();
        $entityId = $params['entityId'];
        $entityType = $params['entityType'];

        $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        if(!isset($pluginNewsfeed) || !$pluginNewsfeed->isActive()){
            return;
        }
        $pluginGroups = BOL_PluginDao::getInstance()->findPluginByKey('groups');
        $textLink = null;
        $content = isset($params['newContent'])?$params['newContent']:'';
        if($entityType=='news-entry') {
            $entry = EntryService::getInstance()->findById($entityId);
            if (isset($entry) && !$entry->isDraft()) {
                $content = $entry->entry;
                $textLink = EntryService::getInstance()->getEntryUrl($entry);
            }
        }elseif ($entityType=='blog-post'){
            $entry = PostService::getInstance()->findById($entityId);
            if (isset($entry) && !$entry->isDraft()) {
                $content = $entry->post;
                $textLink = PostService::getInstance()->getPostUrl($entry);
            }
        }else if(isset($pluginGroups) && $pluginGroups->isActive() && $entityType==GROUPS_BOL_Service::FEED_ENTITY_TYPE) {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if (isset($action)) {
                $jsonTmp = json_decode($action->data, true);
                $content = $jsonTmp["content"]["vars"]["description"];
            }
            $entityType = "groups-status";
        }else if($entityType=='groups-status') {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if (isset($action)) {
                if (strcmp($e->getName(), 'hashtag.edit_newsfeed') == 0) {
                    $content = nl2br($params['text']);
                } else {
                    $jsonTmp = json_decode($action->data, true);
                    $content = nl2br($jsonTmp["status"]);
                }
                $textLink = NEWSFEED_BOL_Service::getInstance()->getActionPermalink($action->getId());
                $entityId = $action->id;
            }else if (isset($params['actionId'])){
                $entityId = $params['actionId'];
            }
            $entityType = "groups-status";
        }else if($entityType=='event') {
            $event = EVENT_BOL_EventService::getInstance()->findByIdList([$entityId]);
            if(count($event)==1) {
                $event = $event[0];
                $content = $event->description;
                $textLink = EVENT_BOL_EventService::getInstance()->getEventUrl($entityId);
            }
        }else if($entityType=='video_comments') {
            $clip = VIDEO_BOL_ClipService::getInstance()->findClipById($entityId);
            if(isset($clip)) {
                $content = $clip->description;
                $textLink = VIDEO_BOL_ClipService::getInstance()->getVideoUrl($clip);
            }
        }else if($entityType=='forum-post'){
            $post = FORUM_BOL_ForumService::getInstance()->findPostById($entityId);
            if (isset($post)) {
                $content = strip_tags(UTIL_HtmlTag::stripTags($post->text));
                $textLink = FORUM_BOL_ForumService::getInstance()->getPostUrl($post->topicId,$post->id);
            }
        }else if($entityType=='user-status') {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if (isset($action)) {
                $jsonTmp = json_decode($action->data, true);
                $content = nl2br($jsonTmp["status"]);
            }
        }else if($entityType=='photo_comments') {
            $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($entityId);
            if (isset($photo)) {
                $content = strip_tags(UTIL_HtmlTag::stripTags($photo->description));
                $textLink = OW::getRouter()->urlForRoute('view_photo', array('id' => $photo->id));
            }
        }else{
            return;
        }
        $this->deleteAllNotificationsByEntity($entityType, $entityId);

        if($e->getName()!='feed.delete_item') {
            //send notification
            if(!isset($textLink)){
                $action = NEWSFEED_BOL_Service::getInstance()->findAction($entityType, $entityId);
                if(!isset($action))
                    return;
                $textLink = NEWSFEED_BOL_Service::getInstance()->getActionPermalink($action->getId());
            }

            $authorId = OW::getUser()->getId();
            $this->findAndNotifyFromContent($content, $entityId, $entityType, $authorId, $textLink, false);
            $comments = BOL_CommentService::getInstance()->findFullCommentList($params['entityType'], $params['entityId']);
            foreach ($comments as $comment) {
                $this->findAndNotifyFromContent($comment->message, $entityId, $entityType, $comment->userId, $textLink, true);
            }
        }
    }

}
