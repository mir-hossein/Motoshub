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
 * Forum edit topic action controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.controllers
 * @since 1.0
 */
class FORUM_CTRL_EditTopic extends OW_ActionController
{

    /**
     * Controller's default action
     *
     * @param array $params
     * @throws AuthorizationException
     * @throws Redirect404Exception
     */
    public function index( array $params = null )
    {
        $forumService = FORUM_BOL_ForumService::getInstance();

        if ( !isset($params['id']) || !($topicId = (int) $params['id']) )
        {
            throw new Redirect404Exception();
        }

        $topicDto = $forumService->findTopicById($topicId);

        if ( !$topicDto )
        {
            throw new Redirect404Exception();
        }

        $forumGroup = $forumService->getGroupInfo($topicDto->groupId);
        $forumSection = $forumService->findSectionById($forumGroup->sectionId);

        $isHidden = $forumSection->isHidden;

        $userId = OW::getUser()->getId();

        if ( $isHidden )
        {
            $isModerator = OW::getUser()->isAuthorized($forumSection->entity);

            $eventParams = array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId, 'action' => 'add_topic');
            $event = new OW_Event('forum.check_permissions', $eventParams);
            OW::getEventManager()->trigger($event);
            $canPost = $event->getData();
            $groupPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
            if (!empty($forumGroup )) {
                $section = FORUM_BOL_SectionDao::getInstance()->findById($forumGroup->sectionId);
            }
            if (!empty($forumGroup )&& isset($forumGroup->entityId) && isset($section) && $section->entity=='groups' && $groupPlugin && $groupPlugin->isActive() ) {
                $isChannel = false;
                $channelEvent = OW::getEventManager()->trigger(new OW_Event('iisgroupsplus.on.channel.add.widget',
                    array('groupId' => $forumGroup->entityId)));
                $isChannelParticipant = $channelEvent->getData()['channelParticipant'];
                if (isset($isChannelParticipant) && $isChannelParticipant) {
                    $isChannel = true;
                }

                $isAuthorizedCreate = true;
                $groupSettingEvent = OW::getEventManager()->trigger(new OW_Event('can.create.topic',
                    array('groupId' => $forumGroup->entityId)));
                if (isset($groupSettingEvent->getData()['accessCreateTopic'])) {
                    $isAuthorizedCreate = $groupSettingEvent->getData()['accessCreateTopic'];
                }
                $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($forumGroup->entityId);
                $isModerator = GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($groupDto);
                if (!$isModerator) {
                    if (!$isAuthorizedCreate) {
                        throw new Redirect404Exception();
                    } else if ($isAuthorizedCreate && $isChannel) {
                        throw new Redirect404Exception();
                    }
                }
            }
            //check permissions
            $canEdit = OW::getUser()->isAuthorized($forumSection->entity, 'add_topic') && $userId == $topicDto->userId;

            if ( !$isModerator )
            {
                if ( !$canPost )
                {
                    throw new AuthorizationException();
                }
                else if ( !$canEdit )
                {
                    $status = BOL_AuthorizationService::getInstance()->getActionStatus(
                        $forumSection->entity, 'add_topic'
                    );
                    throw new AuthorizationException($status['msg']);
                }
            }
        }
        else
        {
            $isModerator = OW::getUser()->isAuthorized('forum');
            $canEdit = OW::getUser()->isAuthorized('forum', 'edit') && $userId == $topicDto->userId;

            if ( !$canEdit && !$isModerator )
            {
                throw new AuthorizationException();
            }
        }

        // first topic's post
        $postDto = $forumService->findTopicFirstPost($topicId);
        $this->assign('post', $postDto);

        $uid = IISSecurityProvider::generateUniqueId();
        $editTopicForm = $this->generateEditTopicForm($topicDto, $postDto, $uid);
        $this->addForm($editTopicForm);
        $lang = OW::getLanguage();
        $router = OW::getRouter();

        $topicInfo = $forumService->getTopicInfo($topicId);
        $groupUrl = $router->urlForRoute('group-default', array('groupId' => $topicDto->groupId));
        $topicUrl = $router->urlForRoute('topic-default', array('topicId' => $topicDto->id));
        
        $lang->addKeyForJs('forum', 'confirm_delete_attachment');

        $attachmentService = FORUM_BOL_PostAttachmentService::getInstance();

        $enableAttachments = OW::getConfig()->getValue('forum', 'enable_attachments');
        $this->assign('enableAttachments', $enableAttachments);

        if ( $enableAttachments )
        {
            $attachments = $attachmentService->findAttachmentsByPostIdList(array($postDto->id));
            foreach ($attachments as $attachment) {
                for ($i = 0; $i < count($attachment); $i++) {
                    $ext = UTIL_File::getExtension($attachment[$i]['fileName']);
                    $attachment[$i]['extension'] = $ext;
                    $attachments[$attachment[$i]['postId']] = $attachment;
                }
            }
            $this->assign('attachments', $attachments);

            $attachmentCmp = new BASE_CLASS_FileAttachment('forum', $uid);
            $this->addComponent('attachmentsCmp', $attachmentCmp);
        }
        
        if ( OW::getRequest()->isPost() && $editTopicForm->isValid($_POST) )
        {
            $values = $editTopicForm->getValues();
            
            // update the topic
            $forumService->editTopic($userId, 
                    $values, $topicDto, $postDto, $forumSection, $forumGroup);

            $this->redirect($topicUrl);
        }

        OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'edit_topic_title'));
        OW::getDocument()->setHeadingIconClass('ow_ic_edit');

        $this->assign('isHidden', $isHidden);
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$topicDto->id,'isPermanent'=>true,'activityType'=>'delete_attachment')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $attachmentDeleteCode = $iisSecuritymanagerEvent->getData()['code'];
            $this->assign('attachmentDeleteCode',$attachmentDeleteCode);
        }
        if ( $isHidden )
        {
            $event = new OW_Event('forum.find_forum_caption', array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId));
            OW::getEventManager()->trigger($event);

            $eventData = $event->getData();

            /** @var OW_Component $componentForumCaption */
            $componentForumCaption = $eventData['component'];

            if (!empty($componentForumCaption))
            {
                $this->assign('componentForumCaption', $componentForumCaption->render());
            }
            else
            {
                $componentForumCaption = false;
                $this->assign('componentForumCaption', $componentForumCaption);
            }

            $bcItems = array(
                array(
                    'href' => OW::getRouter()->urlForRoute('topic-default', array('topicId' => $topicId )),
                    'label' => OW::getLanguage()->text('forum', 'back_to_topic')
                )
            );

            $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems);
            $this->addComponent('breadcrumb', $breadCrumbCmp);

            OW::getNavigation()->deactivateMenuItems(OW_Navigation::MAIN);
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, $forumSection->entity, $eventData['key']);
        }
        else
        {
            $bcItems = array(
                array(
                    'href' => $router->urlForRoute('forum-default'),
                    'label' => $lang->text('forum', 'forum_group')
                ),
                array(
                    'href' => $router->urlForRoute('forum-default') . '#section-' . $topicInfo['sectionId'],
                    'label' => $topicInfo['sectionName']
                ),
                array(
                    'href' => $groupUrl,
                    'label' => $topicInfo['groupName']
                ),
                array(
                    'href' => $topicUrl,
                    'label' => htmlspecialchars($topicDto->title)
                )
            );

            $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems, $lang->text('forum', 'topic_location'));
            $this->addComponent('breadcrumb', $breadCrumbCmp);

            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'forum', 'forum');
        }
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('forum')->getStaticJsUrl() .'forum.js');
        OW::getDocument()->addStyleSheet(OW_PluginManager::getInstance()->getPlugin("forum")->getStaticCssUrl() .'forum.css');

    }

    /**
     * Generates edit topic form.
     *
     * @param FORUM_BOL_Topic $topicDto
     * @param FORUM_BOL_Post $postDto
     * @param $uid
     * @return Form
     */
    private function generateEditTopicForm( FORUM_BOL_Topic $topicDto, FORUM_BOL_Post $postDto, $uid )
    {
        $form = new FORUM_CLASS_TopicEditForm(
            'edit-topic-form', 
            $uid,
            $topicDto,
            $postDto
        );

        $this->addForm($form);
        return $form;
    }
}
