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
 * @author Alex Ermashev <alexermashev@gmail.com>
 * @package ow.ow_plugins.forum.mobile.controllers
 * @since 1.0
 */
class FORUM_MCTRL_AddTopic extends FORUM_MCTRL_AbstractForum
{
    /**
     * Controller's default action
     *
     * @param array $params
     * @throws AuthorizationException
     */
    public function index( array $params = null )
    {
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

        $backGroupId = isset($params['groupId']) && (int) $params['groupId'] 
            ? (int) $params['groupId'] 
            : -1;

        $userId = OW::getUser()->getId();

        $form = new FORUM_CLASS_TopicAddForm(
            "topic_form",
            IISSecurityProvider::generateUniqueId(),
            $this->forumService->getGroupSelectList(0, false, $userId), 
            $backGroupId, 
            true
        );

        // validate the form
        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            $forumGroupId = !empty($data['group']) ? $data['group'] : -1;
            $forumGroup   = $this->forumService->getGroupInfo($forumGroupId);
            $forumSection = $forumGroup 
                ? $this->forumService->findSectionById($forumGroup->sectionId)
                : null;

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
            // you cannot add new topics in hidden sections
            if ( !$forumGroup || $forumSection->isHidden )
            {
                //throw new Redirect404Exception();
            }

            $isHidden = $forumSection->isHidden ? true : false;            
            $topicDto = $this->forumService->addTopic($forumGroup, $isHidden, $userId, $data);

            $this->redirect(OW::getRouter()->
                        urlForRoute('topic-default', array('topicId' => $topicDto->id)));
        }

        OW::getFeedback()->
                error(OW::getLanguage()->text('base', 'form_validate_common_error_message'));

        // an error occured
        $this->redirect(OW::getRouter()->
                        urlForRoute('group-default', array('groupId' => $backGroupId)));
    }
}
