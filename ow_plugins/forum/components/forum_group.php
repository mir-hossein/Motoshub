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
 * Forum group component class.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum.components
 * @since 1.5.3
 */
class FORUM_CMP_ForumGroup extends OW_Component
{
    /**
     * @var FORUM_BOL_ForumService
     */
    private $forumService;

    public function __construct( array $params )
    {
        parent::__construct();

        $this->forumService = FORUM_BOL_ForumService::getInstance();

        if ( !isset($params['groupId']) || !($groupId = (int) $params['groupId']) )
        {
            $this->setVisible(false);
            return;
        }

        $groupInfo = $this->forumService->getGroupInfo($groupId);
        if ( !$groupInfo )
        {
            $this->setVisible(false);
            return;
        }

        $forumSection = $this->forumService->findSectionById($groupInfo->sectionId);
        if ( !$forumSection )
        {
            $this->setVisible(false);
            return;
        }

        $lang = OW::getLanguage();
        $isHidden = $forumSection->isHidden;
        $userId = OW::getUser()->getId();
        $authError = $lang->text('base', 'authorization_failed_feedback');
        $forumGroup=$this->forumService->findGroupByEntityId('groups', $groupInfo->entityId);
        if ( $isHidden )
        {
            $isModerator = OW::getUser()->isAuthorized($forumSection->entity);

            $event = new OW_Event('forum.can_view', array(
                'entity' => $forumSection->entity,
                'entityId' => $groupInfo->entityId
            ), true);
            OW::getEventManager()->trigger($event);

            $canView = $event->getData();

            $eventParams = array('entity' => $forumSection->entity, 'entityId' => $groupInfo->entityId, 'action' => 'add_topic');
            $event = new OW_Event('forum.check_permissions', $eventParams);
            OW::getEventManager()->trigger($event);

            $canEdit = $event->getData();
        }
        else
        {
            $isModerator = OW::getUser()->isAuthorized('forum');

            $canView = OW::getUser()->isAuthorized('forum', 'view');
            if ( !$canView )
            {
                $viewError = BOL_AuthorizationService::getInstance()->getActionStatus('forum', 'view');
                $authError = $viewError['msg'];
            }

            $canEdit = OW::getUser()->isAuthorized('forum', 'edit');

            $canEdit = $canEdit || $isModerator ? true : false;
        }

        $groupPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
        if (!empty($forumGroup)) {
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
                    $canEdit = false;
                } else if ($isAuthorizedCreate && $isChannel) {
                    $canEdit = false;
                }
            }
        }

        if ( $groupInfo->isPrivate )
        {
            if ( !$userId )
            {
                $this->assign('authError', $authError);
                return;
            }
            else if ( !$isModerator )
            {
                if ( !$this->forumService->isPrivateGroupAvailable($userId, json_decode($groupInfo->roles)) )
                {
                    $this->assign('authError', $authError);
                    return;
                }
            }
        }

        if ( !$canView )
        {
            $this->assign('authError', $authError);
            return;
        }

        $page = !empty($_GET['page']) && (int) $_GET['page'] ? abs((int) $_GET['page']) : 1;

        if ( !$groupInfo )
        {
            $forumUrl = OW::getRouter()->urlForRoute('forum-default');
            OW::getApplication()->redirect($forumUrl);
        }

        $sort_order = isset($_GET['order']) && strtolower($_GET['order']) == 'asc' ? 'ASC' : 'DESC';
        $this->assign('sort_order',$sort_order);
        $up_or_down = str_replace(array('ASC','DESC'), array('up','down'), $sort_order);
        $this->assign('up_or_down',$up_or_down);
        $asc_or_desc = $sort_order == 'ASC' ? 'desc' : 'asc';
        $this->assign('asc_or_desc',$asc_or_desc);
        $topicColumns = FORUM_BOL_ForumService::getInstance()->getTopicColumns();
        $column = isset($_GET['column']) && in_array($_GET['column'], $topicColumns) ? $_GET['column'] : $topicColumns[3];
        $this->assign('column',$column);
        if(sizeof($_GET) > 0 && !array_diff(array_keys($_GET), ['order','column']) == array_diff(['order','column'], array_keys($_GET)))
        {
            $this->assign('urlHasParams',true);
        }
        $this->assign('currentUrl',FORUM_BOL_ForumService::getInstance()->getCleanCurrentUrlWithoutSortParameters());

        $topicList = $this->forumService->getGroupTopicList($groupId, $page,null,array(),$column,$sort_order);
        $topicCount = $this->forumService->getGroupTopicCount($groupId);

        $topicIds = array();
        $userIds = $this->forumService->getGroupTopicAuthorList($topicList, $topicIds);

        $attachments = FORUM_BOL_PostAttachmentService::getInstance()->getAttachmentsCountByTopicIdList($topicIds);
        $this->assign('attachments', $attachments);

        $usernames = BOL_UserService::getInstance()->getUserNamesForList($userIds);
        $this->assign('usernames', $usernames);

        $displayNames = BOL_UserService::getInstance()->getDisplayNamesForList($userIds);
        $this->assign('displayNames', $displayNames);

        $perPage = $this->forumService->getTopicPerPageConfig();
        $pageCount = ($topicCount) ? ceil($topicCount / $perPage) : 1;
        $paging = new BASE_CMP_Paging($page, $pageCount, $perPage);
        $this->assign('paging', $paging->render());

        $addTopicUrl = OW::getRouter()->urlForRoute('add-topic', array('groupId' => $groupId));
        $this->assign('addTopicUrl', $addTopicUrl);

        $this->assign('topicCount',$topicCount);

        $this->assign('canEdit', $canEdit);
        $this->assign('groupId', $groupId);
        $this->assign('topicList', $topicList);
        $this->assign('isHidden', $isHidden);
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('forum')->getStaticCssUrl().'forum.css');
        $showCaption = !empty($params['caption']) ? $params['caption'] : false;
        if ( $showCaption )
        {
            $groupName = htmlspecialchars($groupInfo->name);
            OW::getDocument()->setHeading(OW::getLanguage()->text('forum', 'forum_page_heading', array('forum' => $groupName)));
            OW::getDocument()->setHeadingIconClass('ow_ic_forum');

            OW::getDocument()->setTitle($groupName);
            OW::getDocument()->setDescription(
                OW::getLanguage()->text('forum', 'group_meta_description', array('group' => $groupName))
            );

            if ( $isHidden )
            {
                $event = new OW_Event('forum.find_forum_caption', array('entity' => $forumSection->entity, 'entityId' => $groupInfo->entityId));
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

                OW::getNavigation()->deactivateMenuItems(OW_Navigation::MAIN);
                OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, $forumSection->entity, $eventData['key']);
            }
            else
            {
                $bcItems = array(
                    array(
                        'href' => OW::getRouter()->urlForRoute('forum-default'),
                        'label' => OW::getLanguage()->text('forum', 'forum_group')
                    ),
                    array(
                        'href' => OW::getRouter()->urlForRoute('section-default', array('sectionId' => $groupInfo->sectionId)),
                        'label' => $forumSection->name
                    ),
                    array(
                        'label' => $groupInfo->name
                    )
                );

                $breadCrumbCmp = new BASE_CMP_Breadcrumb($bcItems);
                $this->addComponent('breadcrumb', $breadCrumbCmp);
            }
        }

        $this->addComponent('search', new FORUM_CMP_ForumSearch(array('scope' => 'group', 'groupId' => $groupId)));
        $this->assign('showCaption', $showCaption);
    }
}