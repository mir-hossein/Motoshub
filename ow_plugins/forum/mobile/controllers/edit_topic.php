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
 * Forum edit topic controller
 *
 * @author Alex Ermashev <alexermashev@gmail.com>
 * @package ow.ow_plugins.forum.mobile.controllers
 * @since 1.0
 */
class FORUM_MCTRL_EditTopic extends FORUM_MCTRL_AbstractForum
{
    /**
     * Controller's default action
     *
     * @param array $params
     * @throws AuthorizationException|Redirect404Exception|AuthenticateException
     */
    public function index( array $params = null )
    {
        if ( !isset($params['id']) || !($topicId = (int) $params['id']) )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        // check permissions
        if ( !OW::getUser()->isAuthorized('forum', 'edit') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('forum', 'edit');
            throw new AuthorizationException($status['msg']);
        }

        $forumService = FORUM_BOL_ForumService::getInstance();
        $topicDto = $forumService->findTopicById($topicId);
        $postDto = $forumService->findTopicFirstPost($topicId);

        if ( !$topicDto || !$postDto )
        {
            throw new Redirect404Exception();
        }

        $forumGroup = $forumService->getGroupInfo($topicDto->groupId);
        $forumSection = $forumService->findSectionById($forumGroup->sectionId);
        //commented codes prevented users to edit forum's topic of a group in mobile version
/*        if ( $forumSection->isHidden )
        {
            throw new Redirect404Exception();
        }*/

        $userId = OW::getUser()->getId();
        $isModerator = OW::getUser()->isAuthorized('forum');
        $isHidden = $forumSection->isHidden;
        if($isHidden) {
            $plugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
            if(isset($plugin) && $plugin->isActive() && isset($forumGroup->entityId)) {
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
                if (!$isModerator) {
                    if (!$isAuthorizedCreate) {
                        throw new Redirect404Exception();
                    } else if ($isAuthorizedCreate && $isChannel) {
                        throw new Redirect404Exception();
                    }
                }
            }
        }
        $canEdit = OW::getUser()->isAuthorized('forum', 'edit') && $userId == $topicDto->userId;

        if ( !$canEdit && !$isModerator )
        {
            throw new AuthorizationException();
        }

        $attachmentUid = IISSecurityProvider::generateUniqueId();

        // get a form instance
        $form = new FORUM_CLASS_TopicEditForm(
            'topic_edit_form', 
            $attachmentUid,
            $topicDto,
            $postDto,
            true
        );

        // validate the form
        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            // update the topic
            $this->forumService->
                    editTopic($userId, $data, $topicDto, $postDto, $forumSection, $forumGroup);

            $this->redirect(OW::getRouter()->
                    urlForRoute('topic-default', array('topicId' => $topicId)));
        }

        OW::getFeedback()->
                error(OW::getLanguage()->text('base', 'form_validate_common_error_message'));

        // an error occured
        $this->redirect(OW::getRouter()->
                        urlForRoute('topic-default', array('topicId' => $topicId)));
    }
}
