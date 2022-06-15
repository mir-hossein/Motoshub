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
 * Forum add topic controller
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.controllers
 * @since 1.0
 */
class FORUM_CTRL_AddTopic extends OW_ActionController
{

    /**
     * Controller's default action
     *
     * @param array $params
     * @throws AuthorizationException
     * @throws AuthenticateException
     */
    public function index( array $params = null )
    {
        $groupId = isset($params['groupId']) && (int) $params['groupId'] ? (int) $params['groupId'] : 0;

        $forumService = FORUM_BOL_ForumService::getInstance();

        $forumGroup = $forumService->getGroupInfo($groupId);
        if ( $forumGroup )
        {
            if(isset($forumGroup->entityId)) {
                $groupPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
                $section=FORUM_BOL_SectionDao::getInstance()->findById($forumGroup->sectionId);
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
            }
            $forumSection = $forumService->findSectionById($forumGroup->sectionId);
            $isHidden = $forumSection->isHidden;
        }
        else
        {
            $isHidden = false;
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $userId = OW::getUser()->getId();

        $this->assign('authMsg', null);

        if ( $isHidden && isset($forumSection) )
        {
            $eventParams = array('entity' => $forumSection->entity, 'entityId' => $forumGroup->entityId, 'action' => 'add_topic');
            $event = new OW_Event('forum.check_permissions', $eventParams);
            OW::getEventManager()->trigger($event);

            if ( !$event->getData() )
            {
                throw new AuthorizationException();
            }
            $canAdd = $event->getData();
            if (!$canAdd)
            {
                $status = BOL_AuthorizationService::getInstance()->getActionStatus($forumSection->entity, 'add_topic');
                throw new AuthorizationException($status['msg']);
            }

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

            $bcItems = array(
                array(
                    'href' => OW::getRouter()->urlForRoute('group-default', array('groupId' => $forumGroup->getId())),
                    'label' => OW::getLanguage()->text($forumSection->entity, 'view_all_topics')
                )
            );

            $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems);
            $this->addComponent('breadcrumb', $breadCrumbCmp);

            OW::getNavigation()->deactivateMenuItems(OW_Navigation::MAIN);
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, $forumSection->entity, $eventData['key']);

            $groupSelect = array(array('label' => $forumGroup->name, 'value' => $forumGroup->getId(), 'disabled' => false));

            OW::getDocument()->setHeading(OW::getLanguage()->text($forumSection->entity, 'create_new_topic', array('group' => $forumGroup->name)));
        }
        else
        {
            $canEdit = OW::getUser()->isAuthorized('forum', 'edit');

            if ( !$userId )
            {
                throw new AuthorizationException();
            }
            else if ( !$canEdit )
            {
                $status = BOL_AuthorizationService::getInstance()->getActionStatus('forum', 'edit');
                throw new AuthorizationException($status['msg']);
            }

            if ( !OW::getRequest()->isAjax() )
            {
                OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'forum', 'forum');
            }

            $groupSelect = $forumService->getGroupSelectList(0, false, $userId);

            OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'create_new_topic'));
        }

        OW::getDocument()->setDescription(OW::getLanguage()->text('forum', 'meta_description_add_topic'));
        OW::getDocument()->setTitle(OW::getLanguage()->text('forum', 'meta_title_add_topic'));
        OW::getDocument()->setHeadingIconClass('ow_ic_write');

        $this->assign('isHidden', $isHidden);

        $uid = IISSecurityProvider::generateUniqueId();
        $form = $this->generateForm($groupSelect, $groupId, $isHidden, $uid);

        OW::getDocument()->addStyleDeclaration('
			.disabled_option {
				color: #9F9F9F;
    		}
		');

        $enableAttachments = OW::getConfig()->getValue('forum', 'enable_attachments');
        if ( $enableAttachments )
        {
            $attachmentCmp = new BASE_CLASS_FileAttachment('forum', $uid);
            $this->addComponent('attachments', $attachmentCmp);
        }
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('forum')->getStaticJsUrl() .'forum.js');
        OW::getDocument()->addStyleSheet(OW_PluginManager::getInstance()->getPlugin("forum")->getStaticCssUrl() . 'forum.css');

        $this->assign('enableAttachments', $enableAttachments);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            if ( $data['group'] )
            {
                // create a new topic
                if ( !isset($forumSection) )
                {
                    $topicDto = $forumService->addTopic($forumGroup, $isHidden, $userId, $data);
                }
                else 
                {
                    $topicDto = $forumService->addTopic($forumGroup, $isHidden, $userId, $data, $forumSection);
                }

                OW::getEventManager()->trigger(new OW_Event('on.forum.group.topic.add', array(
                    "groupId" => $forumGroup->entityId,
                    "topicId" => $topicDto->id,
                    "topicTitle" => $topicDto->title
                )));
                $this->redirect(OW::getRouter()->
                        urlForRoute('topic-default', array('topicId' => $topicDto->id)));
            }
            else
            {
                $form->getElement('group')->addError(OW::getLanguage()->text('forum', 'select_group_error'));
            }
        }
    }

    /**
     * Generates Add Topic Form.
     *
     * @param array $groupSelect
     * @param int $groupId
     * @param $isHidden
     * @param $uid
     * @return Form
     */
    private function generateForm( $groupSelect, $groupId, $isHidden, $uid )
    {
        $form = new FORUM_CLASS_TopicAddForm(
            'add-topic-form', 
            $uid, 
            $groupSelect, 
            $groupId,
            false,
            $isHidden
        );

        $this->addForm($form);
        return $form;
    }
}
