<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisnews.bol.service
 * @since 1.0
 */
class EntryService
{
    const FEED_ENTITY_TYPE = 'news-entry';
    const PRIVACY_ACTION_VIEW_NEWS_POSTS = 'news_view_news_entrys';
    const PRIVACY_ACTION_COMMENT_NEWS_POSTS = 'news_comment_news_entrys';

    const POST_STATUS_PUBLISHED = 0;
    const POST_STATUS_DRAFT = 1;
    const POST_STATUS_DRAFT_WAS_NOT_PUBLISHED = 2;
    const POST_STATUS_APPROVAL = 3;

    const EVENT_AFTER_DELETE = 'news.after_delete';
    const EVENT_BEFORE_DELETE = 'news.before_delete';
    const EVENT_AFTER_EDIT = 'news.after_edit';
    const EVENT_AFTER_ADD = 'news.after_add';

    const IISNEWS_BEFORE_IMAGE_UPDATE = 'iisnews_before_image_update';
    const IISNEWS_AFTER_IMAGE_UPDATE = 'iisnews_after_image_update';

    const ON_BEFORE_NEWS_LIST_VIEW_RENDER = 'iis.on.before.news.list.view.render';

    const EVENT_UNINSTALL_IN_PROGRESS = 'iisnews.uninstall_in_progress';

    /*
     * @var BLOG_BOL_BlogService
     */
    private static $classInstance;

    /**
     * @var array
     */
    private $config = array();

    /*
      @var EntryDao
     */
    private $dao;

    private function __construct()
    {
        $this->dao = EntryDao::getInstance();

        $this->config['allowedMPElements'] = array();
    }

    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns class instance
     *
     * @return EntryService
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
            self::$classInstance = new self();

        return self::$classInstance;
    }

    public function save( $dto )
    {
        $dao = $this->dao;

        $dao->save($dto);
    }

    /**
     * @param $id
     * @return Entry
     */
    public function findById( $id )
    {
        $dao = $this->dao;

        return $dao->findById($id);
    }

    //<USER-BLOG>

    private function deleteByAuthorId( $userId ) // do not use it!!
    {
        //$this->dao->deleteByAuthorId($userId);
    }
    /*
     * $which can take on of two following 'next', 'prev' values
     */

    public function findAdjacentUserEntry( $id, $entryId, $which )
    {
        return $this->dao->findAdjacentUserEntry($id, $entryId, $which);
    }

    /***
     * @param $userId
     * @param $first
     * @param $count
     * @return array<Entry>
     */
    public function findUserEntryList( $userId, $first, $count )
    {
        return $this->dao->findUserEntryList($userId, $first, $count);
    }

    public function findUserDraftList( $userId, $first, $count )
    {
        return $this->dao->findUserDraftList($userId, $first, $count);
    }

    public function countUserEntry( $userId )
    {
        return $this->dao->countUserEntry($userId);
    }

    public function findEntryList($first, $count )
    {
        return $this->dao->findEntryList($first, $count);
    }

    public function countEntry()
    {
        return $this->dao->countEntry();
    }

    public function countUserEntryComment( $userId )
    {
        return $this->dao->countUserEntryComment($userId);
    }

    public function countUserDraft( $userId )
    {
        return $this->dao->countUserDraft($userId);
    }

    public function findUserEntryCommentList( $userId, $first, $count )
    {
        return $this->dao->findUserEntryCommentList($userId, $first, $count);
    }

    public function findUserLastEntry( $userId )
    {
        return $this->dao->findUserLastEntry($userId);
    }

    public function findUserArchiveData( $id )
    {
        return $this->dao->findUserArchiveData($id);
    }

    public function findArchiveData(  )
    {
        return $this->dao->findArchiveData();
    }

    public function findUserEntryListByPeriod( $id, $lb, $ub, $first, $count )
    {
        return $this->dao->findUserEntryListByPeriod($id, $lb, $ub, $first, $count);
    }

    public function countUserEntryByPeriod( $id, $lb, $ub )
    {
        return $this->dao->countUserEntryByPeriod($id, $lb, $ub);
    }


    public function findEntryListByPeriod($lb, $ub, $first, $count )
    {
        return $this->dao->findEntryListByPeriod($lb, $ub, $first, $count);
    }

    public function countEntryByPeriod($lb, $ub )
    {
        return $this->dao->countEntryByPeriod($lb, $ub);
    }



    public function findList( $first, $count )
    {
        return $this->dao->findList($first, $count);
    }

    public function countAll()
    {
        return $this->dao->countAll();
    }

    public function countEntrys()
    {
        return $this->dao->countEntrys();
    }

    public function findTopRatedList( $first, $count )
    {
        return $this->dao->findTopRatedList($first, $count);
    }

    public function findLatestPublicEntryIds( $first, $count )
    {
        return $this->dao->findLatestPublicEntryIds($first, $count);
    }

    public function findLatestPublicEntries( $first, $count )
    {
        return $this->dao->findLatestPublicEntries($first, $count);
    }

    public function findListByTag( $tag, $first, $count )
    {
        return $this->dao->findListByTag($tag, $first, $count);
    }

    public function findListByTags( $tags, $first, $count )
    {
        return $this->dao->findListByTags($tags, $first, $count);
    }

    public function countByTag( $tag )
    {
        return $this->dao->countByTag($tag);
    }

    public function findIdListBySearch( $q, $first, $count )
    {
        $ex = new OW_Example();
        $ex->andFieldLike('title', '%'.$q.'%');
        $ex->setOrder('timestamp desc')->setLimitClause(0, $first + $count);
        $list1 = $this->dao->findIdListByExample($ex);

        $ex = new OW_Example();
        $ex->andFieldLike('entry', '%'.$q.'%');
        $ex->setOrder('timestamp desc')->setLimitClause(0, $first + $count);
        $list2 = $this->dao->findIdListByExample($ex);

        $list = array_unique(array_merge($list1, $list2));
        return array_splice($list, $first, $count );
    }

    public function findListCountBySearch( $q )
    {
        $ex = new OW_Example();
        $ex->andFieldLike('title', '%'.$q.'%');
        $ex->setOrder('timestamp desc');
        $list1 = $this->dao->findIdListByExample($ex);

        $ex = new OW_Example();
        $ex->andFieldLike('entry', '%'.$q.'%');
        $ex->setOrder('timestamp desc');
        $list2 = $this->dao->findIdListByExample($ex);

        $list = array_unique(array_merge($list1, $list2));
        return count($list);
    }

    public function delete( Entry $dto )
    {
        if( !empty($dto->image) )
        {
            $storage = OW::getStorage();
            $storage->removeFile($this->generateImagePath($dto->image));
            $storage->removeFile($this->generateImagePath($dto->image, false));
        }
        $this->deleteEntry($dto->getId());
    }

    //</SITE-BLOG>

    public function findListByIdList( $list )
    {
        return $this->dao->findListByIdList($list);
    }

    public function onAuthorSuspend( OW_Event $event )
    {
        $params = $event->getParams();
    }

    /**
     * Get set of allowed tags for news
     *
     * @return array
     */
    public function getAllowedHtmlTags()
    {
        return array("object", "embed", "param", "strong", "i", "u", "a", "!--more--", "img", "blockquote", "span", "pre", "iframe");
    }

    public function updateNewsPrivacy( $userId, $privacy )
    {
        $count = $this->countUserEntry($userId);
        $entities = EntryService::getInstance()->findUserEntryList($userId, 0, $count);
        $entityIds = array();

        foreach ($entities as $entry)
        {
            $entityIds[] = $entry->getId();
        }

        $status = ( $privacy == 'everybody' ) ? true : false;

        $event = new OW_Event('base.update_entity_items_status', array(
            'entityType' => 'news-entry',
            'entityIds' => $entityIds,
            'status' => $status,
        ));
        OW::getEventManager()->trigger($event);

        $this->dao->updateNewsPrivacy( $userId, $privacy );
        OW::getCacheManager()->clean( array( EntryDao::CACHE_TAG_POST_COUNT ));
    }

    public function processEntryText($text)
    {
        $text = str_replace('&nbsp;', ' ', $text);
        $text = strip_tags($text);
        return $text;
    }

    public function findUserNewCommentCount($userId)
    {
        return $this->dao->countUserEntryNewComment($userId);
    }

    public function deleteEntry($entryId)
    {
        BOL_CommentService::getInstance()->deleteEntityComments('news-entry', $entryId);
        BOL_RateService::getInstance()->deleteEntityRates($entryId, 'news-entry');
        BOL_TagService::getInstance()->deleteEntityTags($entryId, 'news-entry');
        BOL_FlagService::getInstance()->deleteByTypeAndEntityId(IISNEWS_CLASS_ContentProvider::ENTITY_TYPE, $entryId);

        OW::getCacheManager()->clean( array( EntryDao::CACHE_TAG_POST_COUNT ));

        OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array('entityType' => 'news-entry', 'entityId' => $entryId)));

        $entry = $this->findById($entryId);
        if( !empty($entry->image) )
        {
            $storage = OW::getStorage();
            $storage->removeFile($this->generateImagePath($entry->image));
            $storage->removeFile($this->generateImagePath($entry->image, false));
        }
        $this->dao->deleteById($entryId);
    }

    public function findEntryListByIds($entryIds)
    {
        return $this->dao->findByIdList($entryIds);
    }

    public function getEntryUrl($entry)
    {
        return OW::getRouter()->urlForRoute('entry', array('id'=>$entry->getId()));
    }



    //-------------------------------------------------

    public function onCollectAddNewContentItem( BASE_CLASS_EventCollector $event )
    {
        $resultArray = array(
            BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_write',
            BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('iisnews', 'add_new_link'),
            BASE_CMP_AddNewContent::DATA_KEY_ID => 'addNewNewsEntryBtn'
        );

        if ( OW::getUser()->isAuthenticated() && OW::getUser()->isAuthorized('iisnews', 'add') )
        {
            $resultArray[BASE_CMP_AddNewContent::DATA_KEY_URL] = OW::getRouter()->urlForRoute('entry-save-new');

            $event->add($resultArray);
        }
        else
        {
            $resultArray[BASE_CMP_AddNewContent::DATA_KEY_URL] = 'javascript://';

            $status = BOL_AuthorizationService::getInstance()->getActionStatus('iisnews', 'add');

            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $script = '$("#addNewNewsEntryBtn").click(function(){
                    OW.authorizationLimitedFloatbox('.json_encode($status['msg']).');
                });';
                OW::getDocument()->addOnloadScript($script);

                $event->add($resultArray);
            }
        }
    }

    public function onCollectNotificationActions( BASE_CLASS_EventCollector $e )
    {
        $e->add(array(
            'section' => 'iisnews',
            'action' => 'news-add_comment',
            'description' => OW::getLanguage()->text('iisnews', 'email_notifications_setting_comment'),
            'selected' => true,
            'sectionLabel' => OW::getLanguage()->text('iisnews', 'notification_section_label'),
            'sectionIcon' => 'ow_ic_write'
        ));

        $e->add(array(
            'section' => 'iisnews',
            'action' => 'news-add_news',
            'description' => OW::getLanguage()->text('iisnews', 'email_notifications_setting_news'),
            'selected' => true,
            'sectionLabel' => OW::getLanguage()->text('iisnews', 'notification_section_label'),
            'sectionIcon' => 'ow_ic_write'
        ));
    }

    public function onAddNewsEnt( OW_Event $event )
    {
        $params = $event->getParams();

        if (empty($params['entityType']) || $params['entityType'] !== 'news-entry')
            return;

        $entityId = $params['entityId'];
        $userService = BOL_UserService::getInstance();

        $entryService = EntryService::getInstance();


        $userId = $params['userId'];
        $entry = $entryService->findById($entityId);
        $data['subject']=$entry->getTitle();
        $data['body']=$entry->getEntry();
        $actor = array(
            'name' => BOL_UserService::getInstance()->getDisplayName($userId),
            'url' => BOL_UserService::getInstance()->getUserUrl($userId)
        );
        $numberOfUsers = BOL_UserService::getInstance()->count(true);
        $users = BOL_UserService::getInstance()->findList(0, $numberOfUsers, true);
        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));
        $sentenceCorrected = false;
        $description = nl2br(UTIL_String::truncate(strip_tags($entry->entry), 300, '...'));
        if (mb_strlen($entry->entry) > 300 )
        {
            $sentence = $entry->entry;
            $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_HALF_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
            if(isset($event->getData()['correctedSentence'])){
                $sentence = $event->getData()['correctedSentence'];
                $sentenceCorrected = true;
            }
            $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
            if(isset($event->getData()['correctedSentence'])){
                $sentence = $event->getData()['correctedSentence'];
                $sentenceCorrected = true;
            }
        }
        if($sentenceCorrected){
            $description=nl2br($sentence.'...');
        }
        if ( !empty($entry->image) )
        {
            $entryImage =$this->generateImageUrl($entry->image, true, true);
        }
        $contentImage = array();

        if ( !empty($entryImage) )
        {
            $contentImage = array('src' => $entryImage);
        }
        foreach ( $users as  $user ) {
            if($user->getId() ==$entry->authorId){
                continue;
            }
            $event = new OW_Event('notifications.add', array(
                'pluginKey' => 'iisnews',
                'entityType' => 'news-add_news',
                'entityId' => (int)$entry->getId(),
                'action' => 'news-add_news',
                'userId' => $user->getId(),
                'time' => time()
            ), array(
                'avatar' => $avatars[$entry->authorId],
                'string' => array(
                    'key' => 'iisnews+news_notification_string',
                    'vars' => array(
                        'actor' => $actor['name'],
                        'actorUrl' => $actor['url'],
                        'title' => $entry->getTitle(),
                        'url' => OW::getRouter()->urlForRoute('entry', array('id' => $entry->getId()))
                    )
                ),
                'content' => $description,
                'url' => OW::getRouter()->urlForRoute('entry', array('id' => $entry->getId())),
                'contentImage' => $contentImage
            ));
            OW::getEventManager()->trigger($event);
            $notifService = NOTIFICATIONS_BOL_Service::getInstance();
            $notification = $notifService->findNotification('news-add_news', (int)$entry->getId(), $user->getId());
            if(isset($notification)) {
                $notification->sent = 0;
                $notifService->saveNotification($notification);
            }
        }

        /* $roles = $params['roles'];
            $total = $userService->findMassMailingUserCount(true, $roles);
            $start = 0;
            $ignoreUnsubscribe = true;
           $result = $userService->findMassMailingUsers($start, $total, $ignoreUnsubscribe, $roles);
            $mails = array();
            $userIdList = array();

            foreach ( $result as $user )
            {
                $userIdList[] = $user->id;
            }

            $displayNameList = $userService->getDisplayNamesForList($userIdList);
            $event = new BASE_CLASS_EventCollector('base.add_global_lang_keys');
            OW::getEventManager()->trigger($event);
            $vars = call_user_func_array('array_merge', $event->getData());

            foreach ( $result as $key => $user )
            {
                $vars['user_email'] = $user->email;


                $mail = OW::getMailer()->createMail();
                $mail->setSender(OW::getConfig()->getValue('base', 'site_email'));
                $mail->setSenderSuffix(false);

                $mail = OW::getMailer()->createMail();
                $mail->addRecipientEmail($user->email);

                $vars['user_name'] = $displayNameList[$user->id];

                $subjectText = UTIL_String::replaceVars($data['subject'], $vars);
                $mail->setSubject($subjectText);

                $htmlContent = UTIL_String::replaceVars($data['body'], $vars);

                $mail->setHtmlContent($htmlContent);

                $textContent = preg_replace("/\<br\s*[\/]?\s*\>/", "\n", $htmlContent);
                $textContent = strip_tags($textContent);
                $mail->setTextContent($textContent);

                $mails[] = $mail;
            }

            OW::getMailer()->addListToQueue($mails);*/
    }
    public function onAddNewsEntryComment( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['entityType']) || $params['entityType'] !== 'news-entry' )
            return;

        $entityId = $params['entityId'];
        $userId = $params['userId'];
        $commentId = $params['commentId'];

        $entryService = EntryService::getInstance();

        $entry = $entryService->findById($entityId);


        if ( $userId == $entry->authorId )
        {
            return;
        }

        $actor = array(
            'name' => BOL_UserService::getInstance()->getDisplayName($userId),
            'url' => BOL_UserService::getInstance()->getUserUrl($userId)
        );

        $comment = BOL_CommentService::getInstance()->findComment($commentId);

        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));

        $event = new OW_Event('notifications.add', array(
            'pluginKey' => 'iisnews',
            'entityType' => 'news-add_comment',
            'entityId' => (int) $comment->getId(),
            'action' => 'news-add_comment',
            'userId' => $entry->authorId,
            'time' => time()
        ), array(
            'avatar' => $avatars[$userId],
            'string' => array(
                'key' => 'iisnews+comment_notification_string',
                'vars' => array(
                    'actor' => $actor['name'],
                    'actorUrl' => $actor['url'],
                    'title' => $entry->getTitle(),
                    'url' => OW::getRouter()->urlForRoute('entry', array('id' => $entry->getId())),
                    'comment' => UTIL_String::truncate( $comment->getMessage(), 120, '...' )
                )
            ),
            'content' => $comment->getMessage(),
            'url' => OW::getRouter()->urlForRoute('entry', array('id' => $entry->getId()))
        ));

        OW::getEventManager()->trigger($event);
    }

    public function onDeleteComment( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['entityType']) || $params['entityType'] !== 'news-entry' )
            return;

        $entityId = $params['entityId'];
        $userId = $params['userId'];
        $commentId = (int) $params['commentId'];
    }

    public function onUnregisterUser( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['deleteContent']) )
        {
            return;
        }

        OW::getCacheManager()->clean(array(EntryDao::CACHE_TAG_POST_COUNT));

        $userId = $params['userId'];

        $count = (int) $this->countUserEntry($userId);

        if ( $count == 0 )
        {
            return;
        }

        $list = $this->findUserEntryList($userId, 0, $count);

        foreach ( $list as $entry )
        {
            $this->delete($entry);
        }
    }

    public function onCollectEnabledAdsPages( BASE_CLASS_EventCollector $event )
    {
        $event->add('iisnews');
    }

    public function onCollectAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'iisnews' => array(
                    'label' => $language->text('iisnews', 'auth_group_label'),
                    'actions' => array(
                        'add' => $language->text('iisnews', 'auth_action_label_add'),
                        'view' => $language->text('iisnews', 'auth_action_label_view'),
                        'add_comment' => $language->text('iisnews', 'auth_action_label_add_comment')
                    )
                )
            )
        );
    }

    public function onCollectFeedConfigurableActivity( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(array(
            'label' => $language->text('iisnews', 'feed_content_label'),
            'activity' => '*:news-entry'
        ));
    }

    /*
    public function onCollectFeedPrivacyActions( BASE_CLASS_EventCollector $event )
    {
        $event->add(array('*:news-entry', EntryService::PRIVACY_ACTION_VIEW_BLOG_POSTS));
    }

    public function onCollectPrivacyActionList( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $privacyValueEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PRIVACY_ITEM_ADD, array('key' => EntryService::PRIVACY_ACTION_VIEW_BLOG_POSTS)));
        $defaultValue = 'everybody';
        if(isset($privacyValueEvent->getData()['value'])){
            $defaultValue = $privacyValueEvent->getData()['value'];
        }
        $action = array(
            'key' => EntryService::PRIVACY_ACTION_VIEW_BLOG_POSTS,
            'pluginKey' => 'iisnews',
            'label' => $language->text('iisnews', 'privacy_action_view_news_entrys'),
            'description' => '',
            'defaultValue' => $defaultValue
        );

        $event->add($action);
        $privacyValueEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PRIVACY_ITEM_ADD, array('key' => EntryService::PRIVACY_ACTION_COMMENT_BLOG_POSTS)));
        $defaultValue = 'everybody';
        if(isset($privacyValueEvent->getData()['value'])){
            $defaultValue = $privacyValueEvent->getData()['value'];
        }
        $action = array(
            'key' => EntryService::PRIVACY_ACTION_COMMENT_BLOG_POSTS,
            'pluginKey' => 'iisnews',
            'label' => $language->text('iisnews', 'privacy_action_comment_news_entrys'),
            'description' => '',
            'defaultValue' => $defaultValue
        );

        $event->add($action);
    }


    public function onChangeActionPrivacy( OW_Event $event )
    {
        $params = $event->getParams();

        $userId = (int) $params['userId'];
        $actionList = $params['actionList'];
        $actionList = is_array($actionList) ? $actionList : array();

        if ( empty($actionList[EntryService::PRIVACY_ACTION_VIEW_BLOG_POSTS]) )
        {
            return;
        }

        EntryService::getInstance()->updateBlogsPrivacy($userId, $actionList[EntryService::PRIVACY_ACTION_VIEW_BLOG_POSTS]);
    }

    */

    public function onCollectQuickLinks( BASE_CLASS_EventCollector $event )
    {
        $userId = OW::getUser()->getId();
        $username = OW::getUser()->getUserObject()->getUsername();

        $entryCount = (int) $this->countUserEntry($userId);
        $draftCount = (int) $this->countUserDraft($userId);
        $count = $entryCount + $draftCount;
        if ( $count > 0 )
        {
            if ( $entryCount > 0 )
            {
                $url = OW::getRouter()->urlForRoute('iisnews-manage-entrys');
            }
            else if ( $draftCount > 0 )
            {
                $url = OW::getRouter()->urlForRoute('iisnews-manage-drafts');
            }

            $event->add(array(
                BASE_CMP_QuickLinksWidget::DATA_KEY_LABEL => OW::getLanguage()->text('iisnews', 'my_news'),
                BASE_CMP_QuickLinksWidget::DATA_KEY_URL => OW::getRouter()->urlForRoute('user-iisnews', array('user' => $username)),
                BASE_CMP_QuickLinksWidget::DATA_KEY_COUNT => $count,
                BASE_CMP_QuickLinksWidget::DATA_KEY_COUNT_URL => $url,
            ));
        }
    }

    public function onAddNewsEntry( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( $params['entityType'] != 'news-entry' )
        {
            return;
        }

        $entry = $this->findById($params['entityId']);

        $sentenceCorrected = false;
        if ( mb_strlen($entry->entry) > 300 )
        {
            $sentence = strip_tags($entry->entry);
            $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_HALF_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
            if(isset($event->getData()['correctedSentence'])){
                $sentence = $event->getData()['correctedSentence'];
                $sentenceCorrected = true;
            }
            $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
            if(isset($event->getData()['correctedSentence'])){
                $sentence = $event->getData()['correctedSentence'];
                $sentenceCorrected = true;
            }
        }
        if($sentenceCorrected){
            $content = $sentence.'...';
        }
        else{
            $content = UTIL_String::truncate(strip_tags($entry->entry), 300, '...');
        }
        $title = UTIL_String::truncate(strip_tags($entry->title), 350, '...');
        if($entry->getImage()){
            $StaticImageUrl = $this->generateImageUrl($entry->getImage(), false);
            $StaticThumbnailUrl = $this->generateImageUrl($entry->getImage(), true);
            $imageUrl=IISSecurityProvider::getInstance()->setHomeUrlVariable($StaticImageUrl);
            $thumbnailUrl=IISSecurityProvider::getInstance()->setHomeUrlVariable($StaticThumbnailUrl);
            $data = array(
                'time' => (int) $entry->timestamp,
                'ownerId' => $entry->authorId,
                'string'=>array("key" => "iisnews+feed_add_item_label"),
                'content' => array(
                    "format" => "image_content",
                    "vars" => array(
                        "image" => $imageUrl,
                        "thumbnail" => $thumbnailUrl,
                        "title" => $title,
                        'description' => $content,
                        'url' => array(
                            "routeName" => 'entry',
                            "vars" => array('id' => $entry->id)
                        ),
                        'iconClass' => 'ow_ic_news'
                    )
                ),
                'view' => array(
                    'iconClass' => 'ow_ic_calendar'
                ),
            );
        }else {
            $data = array(
                'time' => (int)$entry->timestamp,
                'ownerId' => $entry->authorId,
                'string' => array("key" => "iisnews+feed_add_item_label"),
                'content' => array(
                    'format' => 'content',
                    'vars' => array(
                        'title' => $title,
                        'description' => $content,
                        'url' => array(
                            "routeName" => 'entry',
                            "vars" => array('id' => $entry->id)
                        ),
                        'iconClass' => 'ow_ic_news'
                    )
                ),
                'view' => array(
                    'iconClass' => 'ow_ic_write'
                )
            );
        }
        $e->setData($data);
    }

    public function onUpdateNewsEntry( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( $params['entityType'] != 'news-entry' )
        {
            return;
        }

        $entry = $this->findById($params['entityId']);

        $sentenceCorrected = false;
        if ( mb_strlen($entry->entry) > 300 )
        {
            $sentence = strip_tags($entry->entry);
            $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_HALF_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
            if(isset($event->getData()['correctedSentence'])){
                $sentence = $event->getData()['correctedSentence'];
                $sentenceCorrected = true;
            }
            $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
            if(isset($event->getData()['correctedSentence'])){
                $sentence = $event->getData()['correctedSentence'];
                $sentenceCorrected = true;
            }
        }
        if($sentenceCorrected){
            $content = nl2br($sentence.'...');
        }
        else{
            $content = nl2br(UTIL_String::truncate(strip_tags($entry->entry), 300, '...'));
        }
        $title = UTIL_String::truncate(strip_tags($entry->title), 100, '...');
        if($entry->getImage()){
            $StaticImageUrl = $this->generateImageUrl($entry->getImage(), false);
            $StaticThumbnailUrl = $this->generateImageUrl($entry->getImage(), true);
            $imageUrl=IISSecurityProvider::getInstance()->setHomeUrlVariable($StaticImageUrl);
            $thumbnailUrl=IISSecurityProvider::getInstance()->setHomeUrlVariable($StaticThumbnailUrl);
            $data = array(
                'time' => (int) $entry->timestamp,
                'ownerId' => $entry->authorId,
                'string'=>array("key" => "iisnews+feed_add_item_label"),
                'content' => array(
                    "format" => "image_content",
                    "vars" => array(
                        "image" => $imageUrl,
                        "thumbnail" => $thumbnailUrl,
                        "title" => $title,
                        'description' => $content,
                        'url' => array(
                            "routeName" => 'entry',
                            "vars" => array('id' => $entry->id)
                        ),
                        'iconClass' => 'ow_ic_news'
                    )
                ),
                'view' => array(
                    'iconClass' => 'ow_ic_calendar'
                ),
            );
        }else {
            $data = array(
                'time' => (int)$entry->timestamp,
                'ownerId' => $entry->authorId,
                'string' => array("key" => "iisnews+feed_add_item_label"),
                'content' => array(
                    'format' => 'content',
                    'vars' => array(
                        'title' => $title,
                        'description' => $content,
                        'url' => array(
                            "routeName" => 'entry',
                            "vars" => array('id' => $entry->id)
                        ),
                        'iconClass' => 'ow_ic_news'
                    )
                ),
                'view' => array(
                    'iconClass' => 'ow_ic_write'
                )
            );
        }
        $e->setData($data);
    }

    public function onFeedAddComment( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != 'news-entry' )
        {
            return;
        }

        $entry = $this->findById($params['entityId']);
        $userId = $entry->getAuthorId();

        $userName = BOL_UserService::getInstance()->getDisplayName($userId);
        $userUrl = BOL_UserService::getInstance()->getUserUrl($userId);
        $userEmbed = '<a href="' . $userUrl . '">' . $userName . '</a>';

        if ( $userId == $params['userId'] )
        {
            return;
            /*$string = array(
                'key'=>'iisnews+feed_activity_owner_entry_string'
            );*/
        }
        else
        {
            $string = array(
                'key'=>'iisnews+feed_activity_entry_string',
                'vars'=>array('user' => $userEmbed)
            );
        }

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
            'activityType' => 'comment',
            'activityId' => $params['commentId'],
            'entityId' => $params['entityId'],
            'entityType' => $params['entityType'],
            'userId' => $params['userId'],
            'pluginKey' => 'iisnews'
        ), array(
            'string' => $string
        )));
    }

    public function onFeedAddLike( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != 'news-entry' )
        {
            return;
        }

        $entry = $this->findById($params['entityId']);
        $userId = $entry->getAuthorId();

        $userName = BOL_UserService::getInstance()->getDisplayName($userId);
        $userUrl = BOL_UserService::getInstance()->getUserUrl($userId);
        $userEmbed = '<a href="' . $userUrl . '">' . $userName . '</a>';

        if ( $userId == $params['userId'] )
        {
            return;
/*            $string = array(
                'key'=>'iisnews+feed_activity_owner_entry_string_like'
            );*/
        }
        else
        {
            $string = array(
                'key'=>'iisnews+feed_activity_entry_string_like',
                'vars'=>array('user' => $userEmbed)
            );
        }

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
            'activityType' => 'like',
            'activityId' => $params['userId'],
            'entityId' => $params['entityId'],
            'entityType' => $params['entityType'],
            'userId' => $params['userId'],
            'pluginKey' => 'iisnews'
        ), array(
            'string' => $string
        )));
    }

    public function sosialSharingGetNewsInfo( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();
        $data['display'] = false;

        if ( empty($params['entityId']) )
        {
            return;
        }

        if ( $params['entityType'] == 'user_news' )
        {
            if( BOL_AuthorizationService::getInstance()->isActionAuthorizedForGuest('iisnews', 'view') )
            {
                $data['display'] = true;
            }

            $event->setData($data);
            return;
        }

        if ( $params['entityType'] == 'news' )
        {
            $newstDto = EntryService::getInstance()->findById($params['entityId']);

            $displaySocialSharing = true;
            /*
            try
            {
                $eventParams = array(
                    'action' => 'news_view_news_entrys',
                    'ownerId' => $newstDto->getAuthorId(),
                    'viewerId' => 0
                );

                OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
            }
            catch ( RedirectException $ex )
            {
                $displaySocialSharing = false;
            }
            */


            if ( $displaySocialSharing && ( !BOL_AuthorizationService::getInstance()->isActionAuthorizedForGuest('iisnews', 'view') || $newstDto->isDraft() ) )
            {
                $displaySocialSharing = false;
            }

            if ( !empty($newstDto) )
            {
                $data['display'] = $displaySocialSharing;
            }

            $event->setData($data);
        }
    }

    //-----------------------------------------------

    public function deleteComment( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['entityType']) || $params['entityType'] !== 'news-entry' )
            return;

        $commentId = (int) $params['commentId'];
        OW::getEventManager()->call('notifications.remove', array(
            'entityType' => 'news-add_comment',
            'entityId' => $commentId
        ));
    }

    /**
     * Returns news image and icon path.
     *
     * @param integer $imageId
     * @param boolean $icon
     * @return string
     */
    public function generateImagePath( $imageId, $icon = true )
    {
        $imagesDir = OW::getPluginManager()->getPlugin('iisnews')->getUserFilesDir();
        $ext = '.jpg';
        $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN, array('fullPath' => $imagesDir . ( $icon ? 'iisnews_icon_' : 'iisnews_image_' ) . $imageId)));
        if(isset($checkAnotherExtensionEvent->getData()['ext'])){
            $ext = $checkAnotherExtensionEvent->getData()['ext'];
        }
        return $imagesDir . ( $icon ? 'iisnews_icon_' : 'iisnews_image_' ) . $imageId . $ext;
    }

    /**
     * Returns news image and icon url.
     *
     * @param integer $imageId
     * @param boolean $icon
     * @return string
     */
    public function generateImageUrl( $imageId, $icon = true )
    {
        return OW::getStorage()->getFileUrl($this->generateImagePath($imageId, $icon));
    }

    /**
     * Returns default news image url.
     */
    public function generateDefaultImageUrl()
    {
        return OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'no-picture.png';
    }

    /**
     * Makes and saves news standard image and icon.
     *
     * @param string $imagePath
     * @param integer $imageId
     */
    public function saveNewsImage( $tmpPath, $imageId )
    {
        $event = new OW_Event(self::IISNEWS_BEFORE_IMAGE_UPDATE, array(
            "tmpPath" => $tmpPath,
            "eventId" => $imageId
        ), array(
            "tmpPath" => $tmpPath
        ));
        OW::getEventManager()->trigger($event);
        $data = $event->getData();
        $imagePath = $data["tmpPath"];

        $storage = OW::getStorage();

        if ( $storage->fileExists($this->generateImagePath($imageId)) )
        {
            $storage->removeFile($this->generateImagePath($imageId));
            $storage->removeFile($this->generateImagePath($imageId, false));
        }

        $pluginfilesDir = OW::getPluginManager()->getPlugin('iisnews')->getPluginFilesDir();

        $tmpImgPath = $pluginfilesDir . 'img_' .IISSecurityProvider::generateUniqueId() . '.jpg';
        $tmpIconPath = $pluginfilesDir . 'icon_' . IISSecurityProvider::generateUniqueId() . '.jpg';

        $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN, array('source' => $imagePath, 'destination' => $tmpImgPath)));
        if(isset($checkAnotherExtensionEvent->getData()['destination'])){
            $tmpImgPath = $checkAnotherExtensionEvent->getData()['destination'];
        }

        $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN, array('source' => $imagePath, 'destination' => $tmpIconPath)));
        if(isset($checkAnotherExtensionEvent->getData()['destination'])){
            $tmpIconPath = $checkAnotherExtensionEvent->getData()['destination'];
        }

        $image = new UTIL_Image($imagePath);
        $image->resizeImage(400, null)->saveImage($tmpImgPath)
            ->resizeImage(100, 100, true)->saveImage($tmpIconPath);

        $storage->copyFile($tmpIconPath, $this->generateImagePath($imageId));
        $storage->copyFile($tmpImgPath,$this->generateImagePath($imageId, false));

        OW::getEventManager()->trigger(new OW_Event(self::IISNEWS_AFTER_IMAGE_UPDATE, array(
            "tmpPath" => $tmpPath,
            "eventId" => $imageId
        )));

        OW::getStorage()->removeFile($imagePath);
        OW::getStorage()->removeFile($tmpImgPath);
        OW::getStorage()->removeFile($tmpIconPath);
    }

    public function onCollectSearchItems( OW_Event $event ){
        if (!OW::getUser()->isAdmin() && !OW::getUser()->isAuthorized('iisnews', 'view'))
        {
            return;
        }
        $params = $event->getParams();
        $searchValue = '';
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
        $resultData = $this->findListByIdList($idList);

        $result = array();
        $count = 0;
        foreach($resultData as $item){
            if($item->isDraft)
                continue;
            $itemInformation = array();
            $itemInformation['title'] = $item->getTitle();
            $itemInformation['id'] = $item->id;
            $itemInformation['createdDate'] = $item->getTimestamp();
            $itemInformation['link'] = $this->getEntryUrl($item);
            if(isset($item->image)) {
                $itemInformation['image'] = $this->generateImageUrl($item->getImage(), true);
            }else{
                $itemInformation['image'] = OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'no-picture.png';
            }
            $itemInformation['label'] = OW::getLanguage()->text('iisadvancesearch', 'news_label');
            $result[] = $itemInformation;
            $count++;
            if($count == $maxCount){
                break;
            }
        }

        $data = $event->getData();
        $data['iisnews'] = array('label' => OW::getLanguage()->text('iisadvancesearch', 'news_label'), 'data' => $result);
        $event->setData($data);
    }

    public function getEntryList( $case, $first, $count )
    {
        $service = EntryService::getInstance();

        $list = array();
        $itemsCount = 0;

        switch ( $case )
        {
            case 'most-discussed':

                OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'most_discussed_title'));
                OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'most_discussed_description'));

                $commentService = BOL_CommentService::getInstance();

                $info = array();

                $info = $this->findMostCommentedEntryList($first, $count);

                $idList = array();

                foreach ( $info as $item )
                {
                    $idList[] = $item['id'];
                }

                if ( empty($idList) )
                {
                    break;
                }

                $dtoList = $service->findListByIdList($idList);

                foreach ( $dtoList as $dto )
                {
                    $info[$dto->id]['dto'] = $dto;

                    $list[] = array(
                        'dto' => $dto,
                        'commentCount' => $info[$dto->id] ['commentCount'],
                    );
                }

                function sortMostCommented( $e, $e2 )
                {

                    return $e['commentCount'] < $e2['commentCount'];
                }
                usort($list, 'sortMostCommented');

                $itemsCount = EntryDao::getInstance()->findCommentedEntryCount();

                break;

            case 'top-rated':

                OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'top_rated_title'));
                OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'top_rated_description'));

                $info = array();

                $info = $this->findMostRatedEntryList($first, $count);

                $idList = array();

                foreach ( $info as $item )
                {
                    $idList[] = $item['id'];
                }

                if ( empty($idList) )
                {
                    break;
                }

                $dtoList = $service->findListByIdList($idList);

                foreach ( $dtoList as $dto )
                {

                    $list[] = array(
                        'dto' => $dto,
                        'avgScore' => $info[$dto->id] ['avgScore'],
                        'ratesCount' => $info[$dto->id] ['ratesCount']
                    );
                }

                function sortTopRated( $e, $e2 )
                {
                    if ($e['avgScore'] == $e2['avgScore'])
                    {
                        if ($e['ratesCount'] == $e2['ratesCount'])
                        {
                            return 0;
                        }

                        return $e['ratesCount'] < $e2['ratesCount'];
                    }
                    return $e['avgScore'] < $e2['avgScore'];
                }
                usort($list, 'sortTopRated');

                $itemsCount = EntryDao::getInstance()->findMostRatedEntryCount();

                break;

            case 'browse-by-tag':
                if ( empty($_GET['tag']) )
                {
                    $mostPopularTagsArray = BOL_TagService::getInstance()->findMostPopularTags('news-entry', 20);
                    $mostPopularTags = "";

                    foreach ( $mostPopularTagsArray as $tag )
                    {
                        $mostPopularTags .= $tag['label'] . ", ";
                    }

                    OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'browse_by_tag_title'));
                    OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'browse_by_tag_description', array('tags' => $mostPopularTags)));

                    break;
                }

                $dtoList = $service->findListByTag( strip_tags(UTIL_HtmlTag::stripTags($_GET['tag'])), $first, $count);

                $itemsCount = $service->countByTag( strip_tags(UTIL_HtmlTag::stripTags($_GET['tag'])));

                foreach ( $dtoList as $dto )
                {
                    if ($dto->isDraft())
                    {
                        continue;
                    }
                    $list[] = array('dto' => $dto);
                }

//                OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'browse_by_tag_item_title', array('tag' => strip_tags(UTIL_HtmlTag::stripTags($_GET['tag'])))));
//                OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'browse_by_tag_item_description', array('tag' => strip_tags(UTIL_HtmlTag::stripTags($_GET['tag'])))));

                break;

            case 'latest':
                OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'latest_title'));
                OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'latest_description'));

                $arr = $service->findList($first, $count);

                foreach ( $arr as $item )
                {
                    $list[] = array('dto' => $item);
                }

                $itemsCount = $service->countEntrys();

                break;

            case 'search-results':
                if ( empty($_GET['q']) )
                {
                    break;
                }
                $q = UTIL_HtmlTag::escapeHtml($_GET['q']);
                $q = mb_strtolower($q);

                $info = EntryService::getInstance()->findIdListBySearch($q,$first,$count);
                $itemsCount = EntryService::getInstance()->findListCountBySearch($q);

                foreach ( $info as $item )
                {
                    $idList[] = $item;
                }

                if ( empty($idList) )
                {
                    break;
                }

                $dtoList = $service->findListByIdList($idList);

                function sortByTimestamp( $entry1, $entry2 )
                {
                    return $entry1->timestamp < $entry2->timestamp;
                }
                usort($dtoList, 'sortByTimestamp');


                foreach ( $dtoList as $dto )
                {
                    if ($dto->isDraft())
                    {
                        continue;
                    }
                    $list[] = array('dto' => $dto);
                }

                break;

        }

        $eventForEnglishFieldSupport = new OW_Event('iismultilingualsupport.show.data.in.multilingual', array('list' => $list,'entityType'=>'news','display'=>'list'));
        OW::getEventManager()->trigger($eventForEnglishFieldSupport);
        if(isset($eventForEnglishFieldSupport->getData()['multiData'])){
            $list = $eventForEnglishFieldSupport->getData()['multiData'];
        }
        return array($list, $itemsCount);
    }

    public function findMostCommentedEntryList( $first, $count )
    {
        $resultArray = EntryDao::getInstance()->findMostCommentedEntryList($first, $count);

        $resultList = array();

        foreach ( $resultArray as $item )
        {
            $resultList[$item['id']] = $item;
        }

        return $resultList;
    }

    public function findMostRatedEntryList( $first, $count, $exclude = null )
    {
        $arr = EntryDao::getInstance()->findMostRatedEntryList($first, $count, $exclude);

        $resultArray = array();

        foreach ( $arr as $value )
        {
            $resultArray[$value['id']] = $value;
        }

        return $resultArray;
    }

    public function feedOnItemRenderActivity( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        if ($params['action']['entityType'] != 'news-entry') {
            return;
        }
        $data["disabled"] = false;
        $newsEntry = $this->findById($params['action']['entityId']);
        if ( isset($newsEntry) ){
            $data['content']['vars']['description'] = $newsEntry->getEntry();
        }
        if (isset($data['content']['vars']['description'])) {
            $data['content']['vars']['description']=strip_tags($data['content']['vars']['description']);
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $data['content']['vars']['description'])));
            if (isset($stringRenderer->getData()['string'])) {
                $data['content']['vars']['description'] = ($stringRenderer->getData()['string']);
            }
        }
        $event->setData($data);
    }
}