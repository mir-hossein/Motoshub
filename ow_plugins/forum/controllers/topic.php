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
 * Forum topic action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.controllers
 * @since 1.0
 */
class FORUM_CTRL_Topic extends OW_ActionController
{
    private $forumService;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->forumService = FORUM_BOL_ForumService::getInstance();

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'forum', 'forum');
        }
    }

    /**
     * Controller's default action
     *
     * @param array $params
     * @throws AuthorizationException
     * @throws Redirect404Exception
     */
    public function index( array $params )
    {
        if ( !isset($params['topicId']) || ($topicDto = $this->forumService->findTopicById($params['topicId'])) === null )
        {
            throw new Redirect404Exception();
        }

        if ( $topicDto != FORUM_BOL_ForumService::STATUS_APPROVED )
        {
            //throw new Redirect404Exception();
        }

        $forumGroup = $this->forumService->findGroupById($topicDto->groupId);
        $forumSection = $this->forumService->findSectionById($forumGroup->sectionId);

        $isHidden = $forumSection->isHidden;

        $userId = OW::getUser()->getId();
        $isOwner = ( $topicDto->userId == $userId ) ? true : false;

        $postReplyPermissionErrorText = null;

        if ( $isHidden )
        {
            $event = new OW_Event('forum.can_view', array(
                'entity' => $forumSection->entity,
                'entityId' => $forumGroup->entityId
                ), true);
            OW::getEventManager()->trigger($event);

            $canView = $event->getData();

            $isModerator = OW::getUser()->isAuthorized($forumSection->entity);

            $params = array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId, 'action' => 'edit_topic');
            $event = new OW_Event('forum.check_permissions', $params);
            OW::getEventManager()->trigger($event);
            $canEdit = $event->getData();

            $params = array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId, 'action' => 'add_topic');
            $event = new OW_Event('forum.check_permissions', $params);
            OW::getEventManager()->trigger($event);

            $canPost = $event->getData();

            $params = array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId, 'action' => 'delete_topic');
            $event = new OW_Event('forum.check_permissions', $params);
            OW::getEventManager()->trigger($event);
            $canDelete = $event->getData();

            $postReplyPermissionErrorText = OW::getLanguage()->text($forumSection->entity, 'post_reply_permission_error');

            $canMoveToHidden = BOL_AuthorizationService::getInstance()->isActionAuthorized($forumSection->entity, 'move_topic_to_hidden') && $isModerator;

            //$eventParams = array('pluginKey' => $forumSection->entity, 'action' => 'add_post');
            //TODO Zaph:create action that will check if user allowed to delete post separately from topic
        }
        else
        {
            $isModerator = OW::getUser()->isAuthorized('forum');

            $canView = OW::getUser()->isAuthorized('forum', 'view');
            $canEdit = $isOwner || $isModerator;
            $canDelete = OW::getUser()->isAuthorized('forum', 'delete');
            $canPost = OW::getUser()->isAuthorized('forum', 'edit');
            $canMoveToHidden = BOL_AuthorizationService::getInstance()->isActionAuthorized('forum', 'move_topic_to_hidden') && $isModerator;

            //$eventParams = array('pluginKey' => 'forum', 'action' => 'add_post');
        }
        $channelEvent = OW::getEventManager()->trigger(new OW_Event('iisgroupsplus.on.channel.add.widget',
            array('groupId'=>$forumGroup->entityId)));
        $isChannelParticipant = $channelEvent->getData()['channelParticipant'];
        if ( isset($isChannelParticipant) && $isChannelParticipant){
            $canPost = false;
            $this->assign('isChannelParticipant', true);
        }

        $plugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
        if(isset($plugin)&& $plugin->isActive()) {
            $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($forumGroup->entityId);
            if (isset($groupDto) && GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($groupDto)) {
                $isModerator = true;
            }
        }

        $canLock = $canSticky = $isModerator;

        if ( !$canView && !$isModerator )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('forum', 'view');
            throw new AuthorizationException($status['msg']);
        }

        if ( $forumGroup->isPrivate )
        {
            if ( !$userId )
            {
                throw new AuthorizationException();
            }
            else if ( !$isModerator )
            {
                if ( !$this->forumService->isPrivateGroupAvailable($userId, json_decode($forumGroup->roles)) )
                {
                    throw new AuthorizationException();
                }
            }
        }

        $page = !empty($_GET['page']) && (int) $_GET['page'] ? abs((int) $_GET['page']) : 1;

        //update topic's view count
        $topicDto->viewCount += 1;
        $this->forumService->saveOrUpdateTopic($topicDto);

        //update user read info
        $this->forumService->setTopicRead($topicDto->id, $userId);

        $topicInfo = $this->forumService->getTopicInfo($topicDto->id);
        $postList = $this->forumService->getTopicPostList($topicDto->id, $page);

        OW::getEventManager()->trigger(new OW_Event('forum.topic_post_list', array('list' => $postList)));

        $this->assign('isHidden', $isHidden);

        // adds forum caption if any
        if ( $isHidden )
        {
            $event = new OW_Event('forum.find_forum_caption', array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId));
            OW::getEventManager()->trigger($event);

            $eventData = $event->getData();

            /** @var OW_Component $componentForumCaption */
            $componentForumCaption = $eventData['component'];

            if ( !empty($componentForumCaption) )
            {
                $this->assign('componentForumCaption', $componentForumCaption->render());
            }
            else
            {
                $componentForumCaption = false;
                $this->assign('componentForumCaption', $componentForumCaption);
            }

            $eParams = array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId, 'action' => 'edit_topic');
            $event = new OW_Event('forum.check_permissions', $eParams);
            OW::getEventManager()->trigger($event);
            if ( $event->getData() )
            {
                $canLock = $canSticky = true;
            }
        }

        $this->assign('postReplyPermissionErrorText', $postReplyPermissionErrorText);
        $this->assign('isHidden', $isHidden);
        $this->assign('isOwner', $isOwner);
        $this->assign('canPost', $canPost);
        $this->assign('canLock', $canLock);
        $this->assign('canSticky', $canSticky);
        $this->assign('canSubscribe', OW::getUser()->isAuthorized('forum', 'subscribe'));
        $this->assign('isSubscribed', $userId && FORUM_BOL_SubscriptionService::getInstance()->isUserSubscribed($userId, $topicDto->id));

        if ( !$postList )
        {
            throw new Redirect404Exception();
        }

        $toolbars = array();
        $lang = OW::getLanguage();

        $langQuote = $lang->text('forum', 'quote');
        $langFlag = $lang->text('base', 'flag');
        $langEdit = $lang->text('forum', 'edit');
        $langDelete = $lang->text('forum', 'delete');

        $iteration = 0;
        $userIds = array();
        $postIds = array();
        $flagItems = array();

        $firstTopicPost = $this->forumService->findTopicFirstPost($topicDto->id);
        OW::getEventManager()->trigger(new OW_Event('on.load.post.list.in.forum'));
        foreach ( $postList as &$post )
        {
            $post['text'] = UTIL_HtmlTag::linkify($post['text']);
            $post['permalink'] = $this->forumService->getPostUrl($post['topicId'], $post['id'], true, $page);
            $post['number'] = ($page - 1) * $this->forumService->getPostPerPageConfig() + $iteration + 1;

            if ( $iteration == 0 )
            {
                $firstPostText = htmlspecialchars(mb_substr(strip_tags($post['text']), 0, 154));
            }

            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $post['text'])));
            if(isset($stringRenderer->getData()['string'])){
                $post['text'] = ($stringRenderer->getData()['string']);
            }

            $iisformplusEvent = OW::getEventManager()->trigger(new OW_Event('on.handle.more.in.forum',array('post'=>$post)));
            if(isset($iisformplusEvent->getData()['post']))
            {
                $post = $iisformplusEvent->getData()['post'];
            }
            // get list of users
            if ( !in_array($post['userId'], $userIds) )
                $userIds[$post['userId']] = $post['userId'];

            $toolbar = array();

            $label = OW::getLanguage()->text('forum', 'toolbar_post_number', array("num"=> $post['number'] ));
            array_push($toolbar, array('class' => 'post_permalink', 'href' => $post['permalink'], 'label' => $label));

            if ( $userId )
            {
                if ( !$topicDto->locked && ($canEdit || $canPost) )
                {
                    array_push($toolbar, array('id' => $post['id'], 'class' => 'quote_post', 'href' => 'javascript://', 'label' => $langQuote));
                }

                if ( $userId != (int) $post['userId'] )
                {
                    $lagItemKey = 'flag_' . $post['id'];
                    $flagItems[$lagItemKey] = array(
                        'id' => $post['id'],
                        'title' => $post['text'],
                        'href' => $this->forumService->getPostUrl($post['topicId'], $post['id'])
                    );

                    array_push($toolbar, array('label' => $langFlag, 'href' => 'javascript://', 'id' => $lagItemKey, 'class' => 'post_flag_item'));
                }
            }

            if ( $isModerator || ($userId == (int) $post['userId'] && !$topicDto->locked) )
            {
                $href = $iteration == 0 && $page == 1 ?
                    OW::getRouter()->urlForRoute('edit-topic', array('id' => $post['topicId'])) :
                    OW::getRouter()->urlForRoute('edit-post', array('id' => $post['id']));

                array_push($toolbar, array('id' => $post['id'], 'href' => $href, 'label' => $langEdit));

                if ( !($iteration == 0 && $page == 1) )
                {
                    array_push($toolbar, array('id' => $post['id'], 'class' => 'delete_post', 'href' => 'javascript://', 'label' => $langDelete));
                }

                if ( $iteration === 0 && !$isOwner && $isModerator && $topicInfo['status'] == FORUM_BOL_ForumService::STATUS_APPROVAL )
                {
                    $toolbar[] = array('id' => $topicInfo['id'], 'href' => OW::getRouter()->urlForRoute('forum_approve_topic', array('id' => $topicInfo['id'])), 'label' => OW::getLanguage()->text('forum', 'approve_topic'));
                }
            }

            $toolbars[$post['id']] = $toolbar;

            if ( count($post['edited']) && !in_array($post['edited']['userId'], $userIds) )
                $userIds[$post['edited']['userId']] = $post['edited']['userId'];

            $iteration++;

            array_push($postIds, $post['id']);
        }

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'jquery-fieldselection.js');

        $js = UTIL_JsGenerator::newInstance()
            ->newVariable('flagItems', $flagItems)
            ->jQueryEvent(
            '.post_flag_item a', 'click', 'var inf = flagItems[this.id];
                if (inf.id == '.$firstTopicPost->id.' ){
                    OW.flagContent("'.FORUM_BOL_ForumService::FEED_ENTITY_TYPE.'", '.$firstTopicPost->topicId.');
                }
                else{
                    OW.flagContent("'.FORUM_BOL_ForumService::FEED_POST_ENTITY_TYPE.'", inf.id);
                }'
        );

        OW::getDocument()->addOnloadScript($js, 1001);
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('forum')->getStaticJsUrl() .'forum.js');
        OW::getDocument()->addStyleSheet(OW_PluginManager::getInstance()->getPlugin("forum")->getStaticCssUrl() .'forum.css');

        $this->assign('toolbars', $toolbars);

        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($userIds);
        $this->assign('avatars', $avatars);

        $enableAttachments = OW::getConfig()->getValue('forum', 'enable_attachments');
        $this->assign('enableAttachments', $enableAttachments);

        $uid = IISSecurityProvider::generateUniqueId();
        $addPostForm = $this->generateAddPostForm($topicDto->id, $uid);
        $this->addForm($addPostForm);

        $addPostInputId = $addPostForm->getElement('text')->getId();

        if ( $enableAttachments )
        {
            $attachments = FORUM_BOL_PostAttachmentService::getInstance()->findAttachmentsByPostIdList($postIds);

            /* ======== This block aims for attachment icons in forum posts using IISFORUMPLUS plugin ======= */
            $attachmentsEvent=OW::getEventManager()->trigger(new OW_Event('iis.on.before.attachments.icon.render', array('attachments' => $attachments)));
            $iconEnable=$attachmentsEvent->getData();
            $attachmentIcons=false;
            if(isset($iconEnable)){
                $attachments=$attachmentsEvent->getData();
                $attachmentIcons=true;
                $this->assign('attachmentIcons', $attachmentIcons);
            }
            /* =============================================== End =========================================== */

            $this->assign('attachments', $attachments);

            $attachmentCmp = new BASE_CLASS_FileAttachment('forum', $uid);
            $this->addComponent('attachmentsCmp', $attachmentCmp);
        }

        $plugin = OW::getPluginManager()->getPlugin('forum');

        $indexUrl = OW::getRouter()->urlForRoute('forum-default');
        $groupUrl = OW::getRouter()->urlForRoute('group-default', array('groupId' => $topicDto->groupId));
        /**
         * deletePostCode
         */
        $postDeleteCode='';
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$topicDto->id,'isPermanent'=>true,'activityType'=>'delete_post')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $postDeleteCode = $iisSecuritymanagerEvent->getData()['code'];
        }
        $deletePostUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('delete-post',
            array('topicId' => $topicDto->id, 'postId' => 'postId')),array('code' =>$postDeleteCode));
        /**
         * stickyTopicCode
         */
        $stickyTopicCode='';
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$topicDto->id,'isPermanent'=>true,'activityType'=>'sticky_topic')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $stickyTopicCode = $iisSecuritymanagerEvent->getData()['code'];
        }
        $stickyTopicUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('sticky-topic', array('topicId' => $topicDto->id, 'page' => $page))
            ,array('code' =>$stickyTopicCode));
        /**
         * lockTopicCode
         */
        $lockTopicCode='';
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$topicDto->id,'isPermanent'=>true,'activityType'=>'lock_topic')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $lockTopicCode = $iisSecuritymanagerEvent->getData()['code'];
        }
        $lockTopicUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('lock-topic', array('topicId' => $topicDto->id, 'page' => $page))
            ,array('code' =>$lockTopicCode));
        /**
         * deleteTopicCode
         */
        $deleteTopicCode='';
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$topicDto->id,'isPermanent'=>true,'activityType'=>'delete_topic')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $deleteTopicCode = $iisSecuritymanagerEvent->getData()['code'];
        }
        $deleteTopicUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('delete-topic', array('topicId' => $topicDto->id))
            ,array('code' =>$deleteTopicCode));

        $getPostUrl = OW::getRouter()->urlForRoute('get-post', array('postId' => 'postId'));
        $moveTopicUrl = OW::getRouter()->urlForRoute('move-topic');
        /**
         * subscribeTopicCode
         */
        $subscribeTopicCode='';
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$topicDto->id,'isPermanent'=>true,'activityType'=>'subscribe_topic')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $subscribeTopicCode = $iisSecuritymanagerEvent->getData()['code'];
        }
        $subscribeTopicUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('subscribe-topic', array('id' => $topicDto->id))
            ,array('code' =>$subscribeTopicCode));
        /**
         * unSubscribeTopicCode
         */
        $unSubscribeTopicCode='';
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$topicDto->id,'isPermanent'=>true,'activityType'=>'unSubscribe_topic')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $unSubscribeTopicCode = $iisSecuritymanagerEvent->getData()['code'];
        }
        $unsubscribeTopicUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('unsubscribe-topic', array('id' => $topicDto->id))
            ,array('code' =>$unSubscribeTopicCode));
        $topicInfoJs = json_encode(array('sticky' => $topicDto->sticky, 'locked' => $topicDto->locked, 'ishidden' => $isHidden && !$canMoveToHidden));

        $onloadJs = "
			ForumTopic.deletePostUrl = '$deletePostUrl';
			ForumTopic.stickyTopicUrl = '$stickyTopicUrl';
			ForumTopic.lockTopicUrl = '$lockTopicUrl';
			ForumTopic.subscribeTopicUrl = '$subscribeTopicUrl';
			ForumTopic.unsubscribeTopicUrl = '$unsubscribeTopicUrl';
			ForumTopic.deleteTopicUrl = '$deleteTopicUrl';
			ForumTopic.getPostUrl = '$getPostUrl';
			ForumTopic.add_post_input_id = '$addPostInputId';
			ForumTopic.construct($topicInfoJs);
			";

        OW::getDocument()->addOnloadScript($onloadJs);

        OW::getDocument()->addScript($plugin->getStaticJsUrl() . "forum.js");
        OW::getDocument()->addStyleSheet(OW_PluginManager::getInstance()->getPlugin("forum")->getStaticCssUrl() .'forum.css');

        // add language keys for javascript
        $lang->addKeyForJs('forum', 'sticky_topic_confirm');
        $lang->addKeyForJs('forum', 'unsticky_topic_confirm');
        $lang->addKeyForJs('forum', 'lock_topic_confirm');
        $lang->addKeyForJs('forum', 'unlock_topic_confirm');
        $lang->addKeyForJs('forum', 'delete_topic_confirm');
        $lang->addKeyForJs('forum', 'delete_post_confirm');
        $lang->addKeyForJs('forum', 'edit_topic_title');
        $lang->addKeyForJs('forum', 'edit_post_title');
        $lang->addKeyForJs('forum', 'move_topic_title');
        $lang->addKeyForJs('forum', 'confirm_delete_attachment');
        $lang->addKeyForJs('forum', 'forum_quote');
        $lang->addKeyForJs('forum', 'forum_quote_from');

        // first topic's post
        $postDto = $firstTopicPost;

        //posts count on page
        $count = $this->forumService->getPostPerPageConfig();

        $postCount = $this->forumService->findTopicPostCount($topicDto->id);
        $pageCount = ceil($postCount / $count);

        $groupSelect = $this->forumService->getGroupSelectList($topicDto->groupId, $canMoveToHidden, $userId);
        $moveTopicForm = $this->generateMoveTopicForm($moveTopicUrl, $groupSelect, $topicDto);
        $this->addForm($moveTopicForm);

        $Paging = new BASE_CMP_Paging($page, $pageCount, $count);

        $this->assign('paging', $Paging->render());

        if ( $isHidden )
        {
            OW::getNavigation()->deactivateMenuItems(OW_Navigation::MAIN);
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, $forumSection->entity, $eventData['key']);

            OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'topic_page_heading', array(
                'topic' => $topicInfo['title'],
                'content' => ''
            )));

            $bcItems = array(
                array(
                    'href' => OW::getRouter()->urlForRoute('group-default', array('groupId' => $forumGroup->getId())),
                    'label' => OW::getLanguage()->text($forumSection->entity, 'view_all_topics')
                )
            );

            $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems);
            $this->addComponent('breadcrumb', $breadCrumbCmp);
        }
        else
        {
            $bcItems = array(
                array(
                    'href' => $indexUrl,
                    'label' => $lang->text('forum', 'forum_group')
                ),
                array(
                    'href' => OW::getRouter()->urlForRoute('section-default', array('sectionId' => $topicInfo['sectionId'])),
                    'label' => $topicInfo['sectionName']
                ),
                array(
                    'href' => $groupUrl,
                    'label' => $topicInfo['groupName']
                )
            );

            $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems, $lang->text('forum', 'topic_location'));
            $this->addComponent('breadcrumb', $breadCrumbCmp);

            OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'topic_page_heading', array(
                'topic' => $topicInfo['title'],
                'content' => $topicInfo['status'] == FORUM_BOL_ForumService::STATUS_APPROVED ? '' : OW::getLanguage()->text('forum', 'pending_approval')
            )));
        }

        OW::getDocument()->setHeadingIconClass('ow_ic_script');

        $this->assign('indexUrl', $indexUrl);
        $this->assign('groupUrl', $groupUrl);

        $this->assign('topicInfo', $topicInfo);
        $eventPostListData = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_FORUM_POST_RENDER, array('postList' => $postList)));
        if(isset($eventPostListData->getData()['postList'])){
            $postList = $eventPostListData->getData()['postList'];
        }
        $this->assign('postList', $postList);
        $this->assign('page', $page);

        $this->assign('userId', $userId);
        $this->assign('isModerator', $isModerator);
        $this->assign('canEdit', $canEdit);
        $this->assign('canDelete', $canDelete);
        $this->assign('canMoveToHidden', $canMoveToHidden);

        // remember the last forum page
        OW::getSession()->set('last_forum_page', OW_URL_HOME . OW::getRequest()->getRequestUri());

//        OW::getDocument()->setTitle($topicInfo['title']);
//        OW::getDocument()->setDescription($firstPostText);

        $this->addComponent('search', new FORUM_CMP_ForumSearch(array('scope' => 'topic', 'topicId' => $topicDto->id)));

        $tb = array();

        $toolbarEvent = new BASE_CLASS_EventCollector('forum.collect_topic_toolbar_items', array(
            'topicId' => $topicDto->id,
            'topicDto' => $topicDto
        ));

        OW::getEventManager()->trigger($toolbarEvent);

        foreach ( $toolbarEvent->getData() as $toolbarItem )
        {
            array_push($tb, $toolbarItem);
        }
        $this->assign('tb', $tb);

        $params = array(
            "sectionKey" => "forum",
            "entityKey" => "topic",
            "title" => "forum+meta_title_topic",
            "description" => "forum+meta_desc_topic",
            "keywords" => "forum+meta_keywords_topic",
            "vars" => array( "topic_name" => $topicInfo['title'], "topic_description" => $firstPostText )
        );

        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));

        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$topicDto->id,'isPermanent'=>true,'activityType'=>'delete_attachment')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $attachmentDeleteCode = $iisSecuritymanagerEvent->getData()['code'];
            $this->assign('attachmentDeleteCode',$attachmentDeleteCode);
        }
    }

    public function ajaxDeleteAttachment()
    {
        $result = array('result' => false);

        if ( !isset($_POST['attachmentId']) || !OW::getRequest()->isAjax())
        {
            exit(json_encode($result));
        }

        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            $code =$_POST['attachmentDeleteCode'];
            if(!isset($code)){
                throw new Redirect404Exception();
            }
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'delete_attachment')));
        }

        $attachmentService = FORUM_BOL_PostAttachmentService::getInstance();
        $forumService = FORUM_BOL_ForumService::getInstance();
        $lang = OW::getLanguage();

        $attachment = $attachmentService->findPostAttachmentById((int) $_POST['attachmentId']);

        if ( $attachment )
        {
            $userId = OW::getUser()->getId();
            $isModerator = OW::getUser()->isAuthorized('forum');

            $post = $forumService->findPostById($attachment->postId);

            if ( $post )
            {
                if ( $isModerator || $post->userId == $userId )
                {
                    $attachmentService->deleteAttachment($attachment->id);

                    $result = array('result' => true, 'msg' => $lang->text('forum', 'attachment_deleted'));
                }
            }
        }
        else
        {
            $result = array('result' => false);
        }

        exit(json_encode($result));
    }

    /**
     * This action adds a post and after execution redirects to default action
     *
     * @param array $params
     * @throws Redirect404Exception
     * @throws AuthenticateException
     */
    public function addPost( array $params )
    {
        if ( !isset($params['topicId']) || !($topicId = (int) $params['topicId']) )
        {
            throw new Redirect404Exception();
        }

        $topicDto = $this->forumService->findTopicById($topicId);

        if ( !$topicDto )
        {
            throw new Redirect404Exception();
        }

        $uid = $params['uid'];

        $addPostForm = $this->generateAddPostForm($topicId, $uid);

        if ( OW::getRequest()->isPost() && $addPostForm->isValid($_POST) )
        {
            $data = $addPostForm->getValues();

            if ( $data['topic'] && $data['topic'] == $topicDto->id && !$topicDto->locked )
            {
                if ( !OW::getUser()->getId() )
                {
                    throw new AuthenticateException();
                }

                $postDto = $this->forumService->addPost($topicDto, $data);
                $this->redirect($this->forumService->getPostUrl($topicId, $postDto->id));
            }
            else{
                $this->redirect(OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId)));
            }
        }
        else
        {
            OW::getFeedback()->error(OW::getLanguage()->text('forum', 'error_adding_post'));
            $this->redirect(OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId)));
        }
    }

    /**
     * This action deletes thread post
     * and after execution redirects to default action
     *
     * @param array $params
     * @throws Redirect404Exception
     */
    public function deletePost( array $params )
    {
        if ( !isset($params['topicId']) || !($topicId = (int) $params['topicId']) || !isset($params['postId']) || !($postId = (int) $params['postId']) )
        {
            throw new Redirect404Exception();
        }
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            if(!isset($_GET['code'])){
                throw new Redirect404Exception();
            }
            $code = $_GET['code'];
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'delete_post')));
        }

        $topicDto = $this->forumService->findTopicById($topicId);
        $postDto = $this->forumService->findPostById($postId);

        $userId = OW::getUser()->getId();
        $isModerator = OW::getUser()->isAuthorized('forum');

        $forumGroup = $this->forumService->findGroupById($topicDto->groupId);
        $forumSection = $this->forumService->findSectionById($forumGroup->sectionId);

        if ( $forumSection->isHidden )
        {
            $eParams = array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId, 'action' => 'edit_topic');
            $event = new OW_Event('forum.check_permissions', $eParams);
            OW::getEventManager()->trigger($event);

            if ( $event->getData() )
            {
                $isModerator = true;
            }
        }

        if ( $topicDto && $postDto && ($postDto->userId == $userId || $isModerator) )
        {
            $prevPostDto = $this->forumService->findPreviousPost($topicId, $postId);

            if ( $prevPostDto )
            {
                $topicDto->lastPostId = $prevPostDto->id;
                $this->forumService->saveOrUpdateTopic($topicDto);

                $this->forumService->deletePost($postId);
                $postUrl = $this->forumService->getPostUrl($topicId, $prevPostDto->id, false);
            }
        }
        else
        {
            $postUrl = $this->forumService->getPostUrl($topicId, $postId, false);
        }

        $this->redirect($postUrl);
    }

    /**
     * This action sets the topic sticky or unsticky
     * and after execution redirects to default action
     *
     * @param array $params
     * @throws Redirect404Exception
     */
    public function stickyTopic( array $params )
    {
        if ( !isset($params['topicId']) || !($topicId = (int) $params['topicId']) || !isset($params['page']) || !($page = (int) $params['page']) )
        {
            throw new Redirect404Exception();
        }
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            if(!isset($_GET['code'])){
                throw new Redirect404Exception();
            }
            $code = $_GET['code'];
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'sticky_topic')));
        }
        $isModerator = OW::getUser()->isAuthorized('forum');

        $topicDto = $this->forumService->findTopicById($topicId);

        if ( $topicDto )
        {
            $forumGroup = $this->forumService->findGroupById($topicDto->groupId);
            $forumSection = $this->forumService->findSectionById($forumGroup->sectionId);

            if ( $forumSection->isHidden )
            {
                $eParams = array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId, 'action' => 'edit_topic');
                $event = new OW_Event('forum.check_permissions', $eParams);
                OW::getEventManager()->trigger($event);

                if ( $event->getData() )
                {
                    $isModerator = true;
                }
            }

            if ( $isModerator )
            {
                $topicDto->sticky = ($topicDto->sticky) ? 0 : 1;
                $this->forumService->saveOrUpdateTopic($topicDto);
            }
        }

        $topicUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId));

        $this->redirect($topicUrl . "?page=$page");
    }

    /**
     * This action locks or unlocks the topic
     * and after execution redirects to default action
     *
     * @param array $params
     * @throws Redirect404Exception
     */
    public function lockTopic( array $params )
    {
        if ( !isset($params['topicId']) || !($topicId = (int) $params['topicId']) || !isset($params['page']) || !($page = (int) $params['page']) )
        {
            throw new Redirect404Exception();
        }
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            if(!isset($_GET['code'])){
                throw new Redirect404Exception();
            }
            $code = $_GET['code'];
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'lock_topic')));
        }
        $isModerator = OW::getUser()->isAuthorized('forum');

        $topicDto = $this->forumService->findTopicById($topicId);

        if ( $topicDto )
        {
            $forumGroup = $this->forumService->findGroupById($topicDto->groupId);
            $forumSection = $this->forumService->findSectionById($forumGroup->sectionId);

            if ( $forumSection->isHidden )
            {
                $eParams = array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId, 'action' => 'edit_topic');
                $event = new OW_Event('forum.check_permissions', $eParams);
                OW::getEventManager()->trigger($event);

                if ( $event->getData() )
                {
                    $isModerator = true;
                }
            }

            if ( $isModerator )
            {
                $topicDto->locked = ($topicDto->locked) ? 0 : 1;
                $this->forumService->saveOrUpdateTopic($topicDto);
            }
        }

        $topicUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId));

        $this->redirect($topicUrl . "?page=$page");
    }

    /**
     * This action deletes the topic
     * and after execution redirects to default action
     *
     * @param array $params
     * @throws Redirect404Exception
     */
    public function deleteTopic( array $params )
    {
        if ( !isset($params['topicId']) || !($topicId = (int) $params['topicId']) )
        {
            throw new Redirect404Exception();
        }
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            if(!isset($_GET['code'])){
                throw new Redirect404Exception();
            }
            $code = $_GET['code'];
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'delete_topic')));
        }
        $isModerator = OW::getUser()->isAuthorized('forum');

        $topicDto = $this->forumService->findTopicById($topicId);
        $userId = OW::getUser()->getId();

        $redirectUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId));

        if ( $topicDto )
        {
            $forumGroup = $this->forumService->findGroupById($topicDto->groupId);
            $forumSection = $this->forumService->findSectionById($forumGroup->sectionId);

            if ( $forumSection->isHidden )
            {
                $eParams = array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId, 'action' => 'delete_topic');
                $event = new OW_Event('forum.check_permissions', $eParams);
                OW::getEventManager()->trigger($event);

                if ( $event->getData() )
                {
                    $isModerator = true;
                }
            }

            if ( $isModerator || ($userId == $topicDto->userId && OW::getUser()->isAuthorized('forum', 'delete')) )
            {
                $groupId = $topicDto->groupId;
                $this->forumService->deleteTopic($topicId);

                $redirectUrl = OW::getRouter()->urlForRoute('group-default', array('groupId' => $groupId));
            }
        }

        $this->redirect($redirectUrl);
    }

    /**
     * This action gets the post called by ajax request
     *
     * @param array $params
     * @throws Redirect404Exception
     */
    public function getPost( array $params )
    {
        if ( isset($params['postId']) && $postId = (int) $params['postId'] )
        {
            if ( OW::getRequest()->isAjax() )
            {
                $postDto = FORUM_BOL_ForumService::getInstance()->findPostById($postId);
                if (!$postDto){
                    exit();
                }

                $topicDto = $this->forumService->findTopicById($postDto->topicId);
                if ( !$topicDto ){
                    exit();
                }

                $forumGroup = $this->forumService->findGroupById($topicDto->groupId);
                $forumSection = $this->forumService->findSectionById($forumGroup->sectionId);
                $isHidden = $forumSection->isHidden;
                $postReplyPermissionErrorText = null;
                $canView = false;
                $isModerator = OW::getUser()->isAuthorized('forum');
                if ( $isHidden )
                {
                    $event = new OW_Event('forum.can_view', array(
                        'entity' => $forumSection->entity,
                        'entityId' => $forumGroup->entityId
                    ), true);
                    OW::getEventManager()->trigger($event);
                    $canView = $event->getData();
                    $isModerator = OW::getUser()->isAuthorized($forumSection->entity);
                }
                else
                {
                    $canView = OW::getUser()->isAuthorized('forum', 'view');
                }

                if ( !$canView && !$isModerator )
                {
                    exit();
                }

                if ( $forumGroup->isPrivate )
                {
                    if(!OW::getUser()->isAuthenticated()){
                        exit();
                    }

                    if ( !$isModerator )
                    {
                        if ( !$this->forumService->isPrivateGroupAvailable(OW::getUser()->getId(), json_decode($forumGroup->roles)) )
                        {
                            exit();
                        }
                    }
                }

                 $postQuote = new FORUM_CMP_ForumPostQuote(array(
                     'quoteId' => $postId
                 ));

                 echo json_encode($postQuote->render());
            }
            else
            {
                throw new Redirect404Exception();
            }
        }

        exit();
    }

    public function subscribeTopic( $params )
    {
        if ( !empty($params['id']) )
        {
            $isModerator = OW::getUser()->isAuthorized('forum');
            $forumService = FORUM_BOL_ForumService::getInstance();
            $topicDto = $forumService->findTopicById($params['id']);
            if(!isset($topicDto)){
                throw new Redirect404Exception();
            }
            $forumGroup = $this->forumService->findGroupById($topicDto->groupId);
            if ( $forumGroup->isPrivate )
            {
                if ( !$isModerator )
                {
                    if ( !$this->forumService->isPrivateGroupAvailable(OW::getUser()->getId(), json_decode($forumGroup->roles)) )
                    {
                        throw new AuthorizationException();
                    }
                }
            }
            $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
            if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
                if(!isset($_GET['code'])){
                    throw new Redirect404Exception();
                }
                $code = $_GET['code'];
                OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                    array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'subscribe_topic')));
            }
            $subscribeService = FORUM_BOL_SubscriptionService::getInstance();
            $userId = OW::getUser()->getId();
            $topicId = (int) $params['id'];

            if ( OW::getUser()->isAuthorized('forum', 'subscribe') && !$subscribeService->isUserSubscribed($userId, $topicId) )
            {
                $subscription = new FORUM_BOL_Subscription;
                $subscription->userId = $userId;
                $subscription->topicId = $topicId;

                $subscribeService->addSubscription($subscription);

                echo json_encode(array('msg' => OW::getLanguage()->text('forum', 'subscription-added')));
            }
        }

        exit();
    }

    public function unsubscribeTopic( $params )
    {
        if ( !empty($params['id']) )
        {
            $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
            if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
                if(!isset($_GET['code'])){
                    throw new Redirect404Exception();
                }
                $code = $_GET['code'];
                OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                    array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'unSubscribe_topic')));
            }
            $isModerator = OW::getUser()->isAuthorized('forum');
            $forumService = FORUM_BOL_ForumService::getInstance();
            $topicDto = $forumService->findTopicById($params['id']);
            if(!isset($topicDto)){
                throw new Redirect404Exception();
            }
            $forumGroup = $this->forumService->findGroupById($topicDto->groupId);
            if ( $forumGroup->isPrivate )
            {
                if ( !$isModerator )
                {
                    if ( !$this->forumService->isPrivateGroupAvailable(OW::getUser()->getId(), json_decode($forumGroup->roles)) )
                    {
                        throw new AuthorizationException();
                    }
                }
            }
            $subscribeService = FORUM_BOL_SubscriptionService::getInstance();
            $userId = OW::getUser()->getId();
            $topicId = (int) $params['id'];

            if ( $subscribeService->isUserSubscribed($userId, $topicId) )
            {
                $subscribeService->deleteSubscription($userId, $topicId);

                echo json_encode(array('msg' => OW::getLanguage()->text('forum', 'subscription-canceled')));
            }
        }

        exit();
    }

    /**
     * Get text search service
     * 
     * @return FORUM_BOL_TextSearchService
     */
    private function getTextSearchService()
    {
        return FORUM_BOL_TextSearchService::getInstance();
    }

    /**
     * This action moves the topic called by ajax request
     */
    public function moveTopic()
    {
        $userId = OW::getUser()->getId();
        $isModerator = OW::getUser()->isAuthorized('forum');

        if ( OW::getRequest()->isAjax() && $_POST['topic-id'] )
        {
            $topicId = (int) $_POST['topic-id']; // moved topic
            $groupId = (int) $_POST['group-id']; // new forum id

            $groupDto = $this->forumService->findGroupById($groupId); // new forum info
            $topicDto = $this->forumService->findTopicById($topicId); // moved topic dto

            if ( $groupDto === null || $topicDto === null || !$isModerator )
            {
                exit();
            }

            //create replace topic
            $replaceTopicDto = new FORUM_BOL_Topic();

            $replaceTopicDto->groupId = $topicDto->groupId; // use the old forum
            $replaceTopicDto->userId = $userId;
            $replaceTopicDto->title = $topicDto->title;
            $replaceTopicDto->locked = 1;
            $replaceTopicDto->temp = 1;

            $this->forumService->saveOrUpdateTopic($replaceTopicDto);

            $oldGroupDto = $this->forumService->findGroupById($topicDto->groupId);

            $topicUrl = OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicDto->id));
            $oldGroupUrl = OW::getRouter()->urlForRoute('group-default', array('groupId' => $topicDto->groupId));

            //create replace topic's post
            $replacePostDto = new FORUM_BOL_Post();

            $replacePostDto->topicId = $replaceTopicDto->id;
            $replacePostDto->userId = $userId;
            $replacePostDto->createStamp = time();
            $replacePostDto->text = OW::getLanguage()->text('forum', 'moved_to', array('topicUrl' => $topicUrl));

            $this->forumService->saveOrUpdatePost($replacePostDto);

            $replaceTopicDto->lastPostId = $replacePostDto->id;
            $this->forumService->saveOrUpdateTopic($replaceTopicDto);

            //create notification post
            $postDto = new FORUM_BOL_Post();

            $postDto->topicId = $topicDto->id;
            $postDto->userId = $userId;
            $postDto->createStamp = time();
            $postDto->text = OW::getLanguage()->text('forum', 'moved_from', array('groupUrl' => $oldGroupUrl, 'groupName' => $oldGroupDto->name));

            $this->forumService->saveOrUpdatePost($postDto);

            $topicDto->groupId = $groupDto->id;
            $topicDto->lastPostId = $postDto->id;

            $this->forumService->saveOrUpdateTopic($topicDto, true);

            echo json_encode($this->forumService->getPostUrl($replaceTopicDto->id, $replacePostDto->id, false));
        }
        else
        {
            throw new Redirect404Exception();
        }

        exit();
    }

    /**
     * Generates add post form.
     *
     * @param int $topicId
     * @param string $uid
     * @return Form
     */
    private function generateAddPostForm( $topicId, $uid )
    {
        $form = new FORUM_CLASS_PostForm(
            'add-post-form', 
            $uid, 
            $topicId,
            false
        );

        $form->setAction(OW::getRouter()->
                urlForRoute('add-post', array('topicId' => $topicId, 'uid' => $uid)));
        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_FORUM_POST_FORM_CREATE, array('form' => $form)));
        $this->addForm($form);
        return $form;
    }

    /**
     * Generates move topic form.
     *
     * @param string $actionUrl
     * @param $groupSelect
     * @param $topicDto
     * @return Form
     */
    private function generateMoveTopicForm( $actionUrl, $groupSelect, $topicDto )
    {
        $form = new Form('move-topic-form');

        $form->setAction($actionUrl);

        $topicIdField = new HiddenField('topic-id');
        $topicIdField->setValue($topicDto->id);
        $form->addElement($topicIdField);

        $group = new ForumSelectBox('group-id');
        $group->setOptions($groupSelect);
        $group->setValue($topicDto->groupId);
        $group->addAttribute("style", "width: 300px;");
        $group->setRequired(true);
        $form->addElement($group);

        $submit = new Submit('save');
        $submit->setValue(OW::getLanguage()->text('forum', 'move_topic_btn'));
        $form->addElement($submit);

        $form->setAjax(true);

        return $form;
    }

    public function approve( $params )
    {
        if ( !OW::getUser()->isAuthorized('forum') )
        {
            exit();
        }

        $entityId = $params['id'];

        $backUrl = OW::getRouter()->urlForRoute('topic-default', array(
            'topicId' => $entityId
        ));

        $event = new OW_Event("moderation.approve", array(
            "entityType" => FORUM_CLASS_ContentProvider::ENTITY_TYPE,
            "entityId" => $entityId
        ));

        OW::getEventManager()->trigger($event);

        $data = $event->getData();

        if ( empty($data) )
        {
            $this->redirect($backUrl);
        }

        if ( $data["message"] )
        {
            OW::getFeedback()->info($data["message"]);
        }
        else
        {
            OW::getFeedback()->error($data["error"]);
        }

        $this->redirect($backUrl);
    }
}
