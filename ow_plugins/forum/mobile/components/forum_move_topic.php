<?php
/**
 * Copyright (c) 2019, Atena Gholamzade
 * All rights reserved.
 */

/**
 * @author Atena Gholamzade <xpara2x.ag@gmail.com>
 * @package ow_plugins.forum.controllers
 * @since 1.0
 */

class FORUM_MCMP_ForumMoveTopic extends OW_MobileComponent
{
    private $forumService;
    public function __construct($topicId)
    {
        parent::__construct();
        //check if authorized
        if(!OW::getUser()->isAuthenticated()) {
            exit(json_encode(array('result'=>false, 'content' => '404')));
        }
        $this->forumService = FORUM_BOL_ForumService::getInstance();
        $topicDto = $this->forumService->findTopicById($topicId['topicId']);
        $forumGroup = $this->forumService->findGroupById($topicDto->groupId);
        $forumSection = $this->forumService->findSectionById($forumGroup->sectionId);
        $canMoveToHidden = BOL_AuthorizationService::getInstance()->isActionAuthorized($forumSection->entity, 'move_topic_to_hidden') && $isModerator;
        $userId = OW::getUser()->getId();
        $moveTopicUrl = OW::getRouter()->urlForRoute('move-topic');
        $groupSelect = $this->forumService->getGroupSelectList($topicDto->groupId, $canMoveToHidden, $userId);
        $moveTopicForm = $this->generateMoveTopicForm($moveTopicUrl, $groupSelect, $topicDto);

        $this->addForm($moveTopicForm);
    }

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
}
