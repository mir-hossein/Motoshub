<?php

class NOTIFICATIONS_CLASS_ConsoleBridge
{
    /**
     * Class instance
     *
     * @var NOTIFICATIONS_CLASS_ConsoleBridge
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return NOTIFICATIONS_CLASS_ConsoleBridge
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    const CONSOLE_ITEM_KEY = 'notification';

    /**
     *
     * @var NOTIFICATIONS_BOL_Service
     */
    private $service;

    private function __construct()
    {
        $this->service = NOTIFICATIONS_BOL_Service::getInstance();
    }

    public function collectItems( BASE_CLASS_ConsoleItemCollector $event )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            return;
        }

        $item = new NOTIFICATIONS_CMP_ConsoleItem();
        $event->addItem($item, 3);
        $item->setViewAll(OW::getLanguage()->text('notifications','view_all'), OW::getRouter()->urlForRoute('base.notifications'));
    }

    public function addNotification( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        if ( empty($params['entityType']) || empty($params['entityId']) || empty($params['userId']) || empty($params['pluginKey']) )
        {
            throw new InvalidArgumentException('`entityType`, `entityId`, `userId`, `pluginKey` are required');
        }

        if ( !$this->service->isNotificationPermited($params['userId'], $params['action']) )
        {
            return;
        }

        $notification = $this->service->findNotification($params['entityType'], $params['entityId'], $params['userId']);

        if ( $notification === null )
        {
            $notification = new NOTIFICATIONS_BOL_Notification();
            $notification->entityType = $params['entityType'];
            $notification->entityId = $params['entityId'];
            $notification->userId = $params['userId'];
            $notification->pluginKey = $params['pluginKey'];
            $notification->action = $params['action'];
        }
        else
        {
            $notification->viewed = 0;

            $dublicateParams = array(
                'originalEvent' => $event,
                'notificationDto' => $notification,
                'oldData' => $notification->getData()
            );

            $dublicateParams = array_merge($params, $dublicateParams);

            $dublicateEvent = new OW_Event('notifications.on_dublicate', $dublicateParams, $data);
            OW::getEventManager()->trigger($dublicateEvent);

            $data = $dublicateEvent->getData();
        }

        $notification->timeStamp = empty($params['time']) ? time() : $params['time'];
        $notification->active = isset($params['active']) ? (bool)$params['active'] : true;
        $notification->setData($data);

        $this->service->saveNotification($notification);

        //new event after notification add, with id
        $notification = $this->service->findNotification($params['entityType'], $params['entityId'], $params['userId']);
        if ( $notification !== null )
        {
            $data['notification_id'] = $notification->id;
            $event = new OW_Event('notifications.on_add', $params, $data);
            OW::getEventManager()->trigger($event);
        }
    }

    public function removeNotification( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['entityType']) || empty($params['entityId']) )
        {
            throw new InvalidArgumentException('`entityType` and `entityId` params are required');
        }

        $userId = empty($params['userId']) ? null : $params['userId'];
        $entityType = $params['entityType'];
        $entityId = $params['entityId'];

        if ( $userId !== null )
        {
            $this->service->deleteNotification($entityType, $entityId, $userId);
        }
        else
        {
            $this->service->deleteNotificationByEntity($entityType, $entityId);
        }
    }

    public function changeBirthdayLikeNotification( OW_Event $event ){
        $params = $event->getParams();

        if ( empty($params['entityType']) || empty($params['entityId']) )
        {
            throw new InvalidArgumentException('`entityType` and `entityId` params are required');
        }

        $entityType = $params['entityType'];
        $entityId = $params['entityId'];
        $userId = $params['entityId'];
        $notification = $this->service->findNotification( $entityType, $entityId, $userId );
        if(isset($notification)){
            $event = OW::getEventManager()->trigger(new OW_Event('birthdays.like.notification.update', array('notification' => $notification)));
            if (isset($event->getData()['notification'])  && !isset($event->getData()['remove']) ) {
                $notification = $event->getData()['notification'];
                $this->service->saveNotification($notification);
            }
            else if ( isset($event->getData()['remove']) && $event->getData()['remove'] ){
                $this->service->deleteNotification($entityType, $entityId, $userId );
            }
        }
    }
    /* Console list */

    public function ping( BASE_CLASS_ConsoleDataEvent $event )
    {
        $userId = OW::getUser()->getId();
        $data = $event->getItemData(self::CONSOLE_ITEM_KEY);

        $newNotificationCount = $this->service->findNotificationCount($userId, false);
        $allNotificationCount = $this->service->findNotificationCount($userId);

        $data['counter'] = array(
            'all' => $allNotificationCount,
            'new' => $newNotificationCount
        );

        $event->setItemData(self::CONSOLE_ITEM_KEY, $data);
    }

    public function getEditedData($pluginKey,$entityId,$entityType,$notificationData)
    {
        switch ($pluginKey) {
            case'groups':
                $groupsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
                if(isset($groupsPlugin) && $groupsPlugin->isActive()) {
                    $groupService=GROUPS_BOL_Service::getInstance();
                    //user_invitation
                    if ($entityType == 'user_invitation') {
                        $group = GROUPS_BOL_InviteDao::getInstance()->findById($entityId);
                        if (isset($group)) {
                            $notificationData["string"]["vars"]["groupTitle"] = $groupService->findGroupById($group->groupId)->title;
                        }
                        else
                            $notificationData=null;
                    } elseif ($entityType == 'groups-status') {
                        $newsfeedPlugin = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
                        if(isset($newsfeedPlugin) && $newsfeedPlugin->isActive()) {
                            $action = NEWSFEED_BOL_Service::getInstance()->findAction($entityType, $entityId);
                            if(isset($action)){
                                $data = json_decode($action->data, true);
                                $groupId = $data['contextFeedId'];
                                $notificationData["string"]["vars"]["groupTitle"] = UTIL_String::truncate( $groupService->findGroupById($groupId)->title, 60, '...' );
                                $notificationData["string"]["vars"]["status"] = UTIL_String::truncate($data['status'], 120, '...');
                            }
                        }
                    } elseif ($entityType == 'groups-add-file') {
                        $iisgroupsplusPlugin = BOL_PluginDao::getInstance()->findPluginByKey('iisgroupsplus');
                        if(isset($iisgroupsplusPlugin) && $iisgroupsplusPlugin->isActive()) {
                            $groupFile=IISGROUPSPLUS_BOL_GroupFilesDao::getInstance()->findById($entityId);
                            if(isset($groupFile)) {
                                $groupId = $groupFile->groupId;
                                $group=$groupService->findGroupById($groupId);
                                if(isset($group)) {
                                    $notificationData["string"]["vars"]["groupTitle"] = UTIL_String::truncate( $group->title, 60, '...' );
                                    $notificationData["string"]["vars"]["fileName"] = UTIL_String::truncate( $notificationData["string"]["vars"]["fileName"], 120, '...' );
                                }
                            }
                        }
                    } elseif ($entityType == 'groups_wal')
                    {
                        $group=$groupService->findGroupById($entityId);
                        if(isset($group)) {
                            $notificationData["string"]["vars"]["groupTitle"] = UTIL_String::truncate( $group->title, 60, '...' );
                            $notificationData["string"]["vars"]["comment"] = UTIL_String::truncate( $notificationData["content"], 120, '...' );
                        }
                    }
                }
                break;
            case 'newsfeed':
                if ($entityType == 'status_comment')
                {
                    $newsfeedPlugin = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
                    if(isset($newsfeedPlugin) && $newsfeedPlugin->isActive())
                    {
                        $commentService = BOL_CommentService::getInstance();
                        $comment = $commentService->findComment($entityId);
                        if (isset($comment))
                        {
                            $commentEntityId = $comment->commentEntityId;
                            $commentEntity = $commentService->findCommentEntityById($commentEntityId);
                            if (isset($commentEntity))
                            {
                                $action = NEWSFEED_BOL_Service::getInstance()->findAction($commentEntity->entityType, $commentEntity->entityId);
                                if (isset($action)) {
                                    $actionData = json_decode($action->data, true);
                                    if(isset($actionData['status'])) {
                                        $notificationData["string"]["vars"]['status'] = UTIL_String::truncate( $actionData['status'], 60, '...' );
                                    }
                                }
                            }
                            $notificationData["string"]["vars"]["comment"] = UTIL_String::truncate($comment->getMessage(), 120, '...');
                        }
                    }
                }
                elseif ($entityType == 'user_status'){
                    $action = NEWSFEED_BOL_Service::getInstance()->findAction('user-status', $entityId);
                    if (isset($action)) {
                        $actionData = json_decode($action->data, true);
                        $notificationData["string"]["vars"]["status"] =  UTIL_String::truncate( $actionData['status'], 120, '...' );
                    }
                }
                break;
            case 'iisquestions':
                $iisquestionsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('iisquestions');
                $newsfeedPlugin = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
                if (isset($iisquestionsPlugin) && $iisquestionsPlugin->isActive() &&
                    isset($newsfeedPlugin) && $newsfeedPlugin->isActive()) {
                    if ($entityType == 'question_answer') {
                        $answer = IISQUESTIONS_BOL_AnswerDao::getInstance()->findById($entityId);
                        if (isset($answer)) {
                            $option = IISQUESTIONS_BOL_Service::getInstance()->findOption($answer->optionId);
                            $question = IISQUESTIONS_BOL_QuestionDao::getInstance()->findById($answer->questionId);
                            $action = NEWSFEED_BOL_Service::getInstance()->findAction($question->entityType, $question->entityId);
                            if(isset($action)) {
                                $data = json_decode($action->data, true);
                                $notificationData["string"]["vars"]["questionText"] = UTIL_String::truncate($data['status'], 60, '...');
                                $notificationData["string"]["vars"]["option"] = UTIL_String::truncate($option->text, 120, '...');
                                $notificationData["string"]["vars"]["questionUrl"] = OW::getRouter()->urlForRoute('newsfeed_view_item', array('actionId' => $action->getId()));
                            }
                        }
                    } elseif ($entityType == 'question_option') {
                        $option = IISQUESTIONS_BOL_Service::getInstance()->findOption($entityId);
                        if (isset($option)) {
                            $question = IISQUESTIONS_BOL_QuestionDao::getInstance()->findById($option->questionId);
                            $action = NEWSFEED_BOL_Service::getInstance()->findAction($question->entityType, $question->entityId);
                            if(isset($action)) {
                                $data = json_decode($action->data, true);
                                $notificationData["string"]["vars"]["questionText"] = UTIL_String::truncate($data['status'], 60, '...');
                                $notificationData["string"]["vars"]["option"] = UTIL_String::truncate($option->text, 120, '...');
                                $notificationData["string"]["vars"]["questionUrl"] = OW::getRouter()->urlForRoute('newsfeed_view_item', array('actionId' => $action->getId()));
                            }
                        }
                    }
                }
                break;
            case 'event':
                $eventPlugin = BOL_PluginDao::getInstance()->findPluginByKey('event');
                if(isset($eventPlugin) && $eventPlugin->isActive()) {
                    //invite
                    if ($entityType == 'event_invitation') {
                        $event = EVENT_BOL_EventService::getInstance()->findEvent($entityId);
                        if(isset($event)) {
                            $notificationData["string"]["vars"]["eventTitle"] = $event->title;
                        }
                        else
                            $notificationData=null;
                    } //wall
                    elseif ($entityType == 'event') {
                        $comment = BOL_CommentService::getInstance()->findComment($entityId);
                        if (isset($comment)) {

                            $commEntity = BOL_CommentService::getInstance()->findCommentEntityById($comment->commentEntityId);
                            if (isset($commEntity)) {
                                $event = EVENT_BOL_EventService::getInstance()->findEvent($commEntity->entityId);
                                if(isset($notificationData["string"]["vars"]["eventTitle"]))
                                    $notificationData["string"]["vars"]["eventTitle"] = UTIL_String::truncate( $event->title, 60, '...' );
                                elseif (isset($notificationData["string"]["vars"]["title"]))
                                    $notificationData["string"]["vars"]["title"] = UTIL_String::truncate( $event->title, 60, '...' );
                            }
                            $notificationData["string"]["vars"]["comment"] = UTIL_String::truncate( $comment->getMessage(), 120, '...' );
                        }
                    }//file
                    elseif ($entityType == 'event-add-file') {
                        $iiseventplusPlugin = BOL_PluginDao::getInstance()->findPluginByKey('iiseventplus');
                        if(isset($iiseventplusPlugin) && $iiseventplusPlugin->isActive()) {
                            $eventplusFile=IISEVENTPLUS_BOL_EventFilesDao::getInstance()->findById($entityId);
                            if(isset($eventplusFile)) {
                                $eventId = $eventplusFile->eventId;
                                $event= EVENT_BOL_EventService::getInstance()->findEvent($eventId);
                                if(isset($event)) {
                                    $notificationData["string"]["vars"]["eventTitle"] = UTIL_String::truncate( $event->title, 60, '...' );
                                    $notificationData["string"]["vars"]["fileName"] = UTIL_String::truncate( $notificationData["string"]["vars"]["fileName"], 120, '...' );
                                }
                            }
                        }
                    }
                }
                break;
            case 'iisnews':
                $iisnewsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('iisnews');
                if(isset($iisnewsPlugin) && $iisnewsPlugin->isActive()) {
                    if ($entityType == 'news-add_comment') {
                        $comment=BOL_CommentService::getInstance()->findComment($entityId);
                        if(isset($comment)) {
                            $commentEntityId = $comment->commentEntityId;
                            $commentEntity=BOL_CommentService::getInstance()->findCommentEntityById($commentEntityId);
                            if(isset($commentEntity)) {
                                $entry=EntryService::getInstance()->findById($commentEntity->entityId);
                                if(isset($entry)) {
                                    $notificationData["string"]["vars"]["title"] = UTIL_String::truncate( $entry->title, 60, '...' );
                                }
                            }
                            $notificationData["string"]["vars"]["comment"] = UTIL_String::truncate( $comment->getMessage(), 120, '...' );
                        }
                    } //publish news
                    elseif ($entityType == 'news-add_news') {
                        $entry=EntryService::getInstance()->findById($entityId);
                        if(isset($entry)) {
                            $notificationData["string"]["vars"]["title"] = $entry->title;
                            if(!empty($entry->image))
                                $notificationData["contentImage"]["src"] =EntryService::getInstance()->generateImageUrl($entry->image, true, true);
                            else
                                $notificationData["contentImage"] =null;



                        }
                    }
                }
                break;
            case 'blogs':
                $blogsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('blogs');
                if(isset($blogsPlugin) && $blogsPlugin->isActive()) {
                    if ($entityType == 'blogs-add_comment') {
                        $comment=BOL_CommentService::getInstance()->findComment($entityId);
                        if(isset($comment)) {
                            $commentEntityId = $comment->commentEntityId;
                            $entity=BOL_CommentService::getInstance()->findCommentEntityById($commentEntityId);
                            if(isset($entity)) {
                                $commEntityId = $entity->entityId;
                                $post=PostService::getInstance()->findById($commEntityId);
                                if(isset($post) && isset($notificationData["string"]["vars"]["title"])) {
                                    $notificationData["string"]["vars"]["title"] = UTIL_String::truncate( $post->title, 60, '...' );
                                }
                            }
                            $notificationData["string"]["vars"]["comment"] = UTIL_String::truncate( $comment->getMessage(), 120, '...' );
                        }
                    }
                }
                break;
            case 'video':
                $videoPlugin = BOL_PluginDao::getInstance()->findPluginByKey('video');
                if(isset($videoPlugin) && $videoPlugin->isActive()) {
                    if ($entityType == 'video-add_comment') {
                        $comment = BOL_CommentService::getInstance()->findComment($entityId);
                        if(isset($comment)) {
                            $commentEntityId = $comment->commentEntityId;
                            $entity=BOL_CommentService::getInstance()->findCommentEntityById($commentEntityId);
                            if(isset($entity)) {
                                $commEntityId = $entity->entityId;
                                $video = VIDEO_BOL_ClipService::getInstance()->findClipById($commEntityId);
                                if(isset($video)) {
                                    $notificationData["string"]["vars"]["videoTitle"] = UTIL_String::truncate($video->title, 60, '...' );
                                }
                            }
                            $notificationData["string"]["vars"]["comment"] = UTIL_String::truncate( $comment->getMessage(), 120, '...' );
                        }
                    } elseif ($entityType == 'video-add_rate') {
                        $video = VIDEO_BOL_ClipService::getInstance()->findClipById($entityId);
                        if(isset($video)) {
                            $notificationData["string"]["vars"]["videoTitle"] = $video->title;
                        }
                    }
                }
                break;
            case 'forum':
                $forumPlugin = BOL_PluginDao::getInstance()->findPluginByKey('forum');
                if(isset($forumPlugin) && $forumPlugin->isActive()) {
                    if ($entityType == 'forum_topic_reply') {
                        $post=FORUM_BOL_ForumService::getInstance()->findPostById($entityId);
                        if(isset($post)) {
                            $topicId = $post->topicId;
                            $topic=FORUM_BOL_ForumService::getInstance()->findTopicById($topicId);
                            if(isset($topic)) {
                                $notificationData["string"]["vars"]["title"] = $topic->title;
                            }
                        }
                    }
                }
                break;
            case 'iisforumplus':
                if ($entityType == 'group-topic-add') {
                    $forumPlugin = BOL_PluginDao::getInstance()->findPluginByKey('forum');
                    if (isset($forumPlugin) && $forumPlugin->isActive()) {
                        $forumTopic = FORUM_BOL_ForumService::getInstance()->findTopicById($entityId);
                        if(isset($forumTopic)) {
                            $notificationData["string"]["vars"]["topicTitle"] = $forumTopic->title;
                            $groupId = $forumTopic->groupId;
                            $group=FORUM_BOL_ForumService::getInstance()->findGroupById($groupId);
                            if(isset($group)) {
                                $notificationData["string"]["vars"]["groupTitle"] = $group->name;
                            }
                        }
                    }
                }
                break;
            case 'iiscompetition':
                $iiscompetitionPlugin = BOL_PluginDao::getInstance()->findPluginByKey('iiscompetition');
                if (isset($iiscompetitionPlugin) && $iiscompetitionPlugin->isActive()) {
                    if ($entityType == 'competition-add_user_point' || $entityType == 'competition-add_competition') {
                        $competition= IISCOMPETITION_BOL_CompetitionDao::getInstance()->findById($entityId);
                        if(isset($competition)) {
                            $notificationData["string"]["vars"]["competitionTitle"] = $competition->title;
                        }
                    } elseif ($entityType == 'competition-add_group_point') {
                        $competition= IISCOMPETITION_BOL_CompetitionDao::getInstance()->findById($entityId);
                        if(isset($competition)) {
                            $notificationData["string"]["vars"]["competitionTitle"] = $competition->title;
                            $groupsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
                            if(isset($groupsPlugin) && $groupsPlugin->isActive()) {
                                $groupUrlArray = explode('/', $notificationData["string"]["vars"]["groupUrl"]);
                                $groupId = end($groupUrlArray);
                                $group=GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
                                if(isset($group)) {
                                    $notificationData['string']["vars"]["groupTitle"] = $group->title;
                                }
                            }
                        }
                    }
                }
                break;
            case 'base':
                if($entityType == 'base_profile_wall'){
                    $commentService = BOL_CommentService::getInstance();
                    $comment = $commentService->findComment($entityId);
                    if (isset($comment))
                    {
                        $notificationData["string"]["vars"]["comment"] = UTIL_String::truncate( $comment->getMessage(), 120, '...' );
                    }
                }
                break;
            case 'photo':
                if ($entityType == 'photo-add_comment') {
                    $comment = BOL_CommentService::getInstance()->findComment($entityId);
                    if (isset($comment)) {
                        $notificationData["string"]["vars"]["comment"] = UTIL_String::truncate($comment->getMessage(), 120, '...');
                    }
                }
                break;
        }
        return $notificationData;
    }

    public function loadList( BASE_CLASS_ConsoleListEvent $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        $userId = OW::getUser()->getId();

        if ( $params['target'] != self::CONSOLE_ITEM_KEY )
        {
            return;
        }

        $loadItemsCount = 10;
        $notifications = $this->service->findNotificationList($userId, $params['console']['time'], $params['ids'], $loadItemsCount);
        $notificationIds = array();

        $data['listFull'] = count($notifications) < $loadItemsCount;

        foreach ( $notifications as $notification )
        {
            $notificationData=$this->getEditedData($notification->pluginKey,$notification->entityId,$notification->entityType, $notification->getData());
            if(isset($notificationData["string"]["vars"]["status"]))
                $notificationData["string"]["vars"]["status"]=UTIL_String::truncate(UTIL_HtmlTag::stripTags($notificationData["string"]["vars"]["status"]), 200, '...');
                $itemEvent = new OW_Event('notifications.on_item_render', array(
                'key' => 'notification_' . $notification->id,
                'entityType' => $notification->entityType,
                'entityId' => $notification->entityId,
                'pluginKey' => $notification->pluginKey,
                'userId' => $notification->userId,
                'timestamp' => $notification->timeStamp,
                'viewed' => (bool) $notification->viewed,
                'data' => $notificationData
            ), $notificationData);

            OW::getEventManager()->trigger($itemEvent);

            $item = $itemEvent->getData();

            if ( empty($item) )
            {
                continue;
            }
            
            $notificationIds[] = $notification->id;

            $event->addItem($item, $notification->id);
        }

        $event->setData($data);
        $this->service->markNotificationsViewedByIds($notificationIds);
    }

    private function processDataInterface( $params, $data )
    {
        if ( empty($data['avatar']) )
        {
            return array();
        }

        $questionName = OW::getConfig()->getValue('base', 'display_name_question');
        foreach ( array('string', 'conten') as $langProperty )
        {
            if ( !empty($data[$langProperty]) && is_array($data[$langProperty]) )
            {
                if($questionName == "username"){
                    $userName=BOL_UserService::getInstance()->getUserName($data['avatar']['userId']);
                }else{
                    $userName = BOL_UserService::getInstance()->getDisplayName($data['avatar']['userId']);
                }
                if ( $userName ){
                    $data['string']['vars']['userName'] = $userName;
                }
                $key = explode('+', $data[$langProperty]['key']);
                $vars = empty($data[$langProperty]['vars']) ? array() : $data[$langProperty]['vars'];
                $data[$langProperty] = OW::getLanguage()->text($key[0], $key[1], $vars);
            }
        }

        if ( empty($data['string']) )
        {
            return array();
        }

        if ( !empty($data['contentImage']) )
        {
            $data['contentImage'] = is_string($data['contentImage'])
                ? array( 'src' => $data['contentImage'] )
                : $data['contentImage'];
        }
        else
        {
            $data['contentImage'] = null;
        }
        
        if ( !empty($data["avatar"]["userId"]) )
        {
            $avatarData = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($data["avatar"]["userId"]));
            $data["avatar"] = $avatarData[$data["avatar"]["userId"]];
        }
        
        $data['contentImage'] = empty($data['contentImage']) ? array() : $data['contentImage'];
        $data['toolbar'] = empty($data['toolbar']) ? array() : $data['toolbar'];
        if(isset($params['timestamp'])) {
            $data['toolbar'][] =
                array(
                    'type' => 'text',
                    'label' => UTIL_DateTime::formatDate((int)$params['timestamp'], true),
                    'class' => 'ow_console_invt_toolbar_date'
                );
        }
        $data['key'] = isset($data['key']) ? $data['key'] : $params['key'];
        $data['viewed'] = isset($params['viewed']) && !$params['viewed'];
        $data['url'] = isset($data['url']) ? $data['url'] : null;

        return $data;
    }

    public function renderItem( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        if (is_string($data) )
        {
            return;
        }

        $interface = $this->processDataInterface($params, $data);

        if ( empty($interface) )
        {
            return;
        }

        $item = new NOTIFICATIONS_CMP_NotificationItem();
        $item->setAvatar($interface['avatar']);
        $item->setContent($interface['string']);
        $item->setKey($interface['key']);
        $item->setToolbar($interface['toolbar']);
        $item->setContentImage($interface['contentImage']);
        $item->setUrl($interface['url']);

        if ( $interface['viewed'] )
        {
            $item->addClass('ow_console_new_message');
        }

        $event->setData($item->render());
    }


    public function pluginActivate( OW_Event $e )
    {
        $params = $e->getParams();
        $pluginKey = $params['pluginKey'];

        $this->service->setNotificationStatusByPluginKey($pluginKey, true);
    }

    public function pluginDeactivate( OW_Event $e )
    {
        $params = $e->getParams();
        $pluginKey = $params['pluginKey'];

        $this->service->setNotificationStatusByPluginKey($pluginKey, false);
    }

    public function pluginUninstall( OW_Event $e )
    {
        $params = $e->getParams();
        $pluginKey = $params['pluginKey'];

        $this->service->deleteNotificationByPluginKey($pluginKey);
    }

    public function afterInits()
    {
        OW::getEventManager()->bind('notifications.on_item_render', array($this, 'renderItem'));
    }
    
    public function genericAfterInits()
    {
        OW::getEventManager()->bind('notifications.remove', array($this, 'removeNotification'));
        OW::getEventManager()->bind('notifications.change.birthday.like', array($this, 'changeBirthdayLikeNotification'));
    }

    public function init()
    {
        $this->genericInit();
        
        OW::getEventManager()->bind(OW_EventManager::ON_PLUGINS_INIT, array($this, 'afterInits'));

        OW::getEventManager()->bind(OW_EventManager::ON_AFTER_PLUGIN_ACTIVATE, array($this, 'pluginActivate'));
        OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_PLUGIN_DEACTIVATE, array($this, 'pluginDeactivate'));
        OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_PLUGIN_UNINSTALL, array($this, 'pluginUninstall'));

        OW::getEventManager()->bind('console.load_list', array($this, 'loadList'));
        OW::getEventManager()->bind('console.ping', array($this, 'ping'));
        OW::getEventManager()->bind('console.collect_items', array($this, 'collectItems'));
    }
    
    public function genericInit()
    {
        OW::getEventManager()->bind('notifications.add', array($this, 'addNotification'));
        OW::getEventManager()->bind(OW_EventManager::ON_PLUGINS_INIT, array($this, 'genericAfterInits'));
    }
}