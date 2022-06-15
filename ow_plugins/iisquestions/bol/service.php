<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 10:05 AM
 */
class IISQUESTIONS_BOL_Service
{
    const PRIVACY_EVERYBODY = 'everybody';
    const MULTIPLE_ANSWER = 'multiple_answer';
    const ONE_ANSWER = 'one_answer';
    const PRIVACY_ONLY_FOR_ME = 'only_for_me';
    const PRIVACY_FRIENDS_ONLY = 'friends_only';
    const ENTITY_TYPE = 'question';
    const EVENT_ON_INTERACT_PERMISSION_CHECK = 'questions.interact_permission_check';

    const EVENT_ON_LIST_ITEM_RENDER = "questions.on_list_item_render";
    const EVENT_ON_QUESTION_RENDER = "questions.on_question_render";

    const EVENT_BEFORE_QUESTION_ADDED = 'questions.before_question_added';

    const EVENT_QUESTION_ADDED = 'questions.question_added';
    const EVENT_QUESTION_REMOVED = 'questions.question_removed';

    const EVENT_BEFORE_QUESTION_REMOVED = 'questions.before_question_removed';

    const EVENT_OPTION_ADDED = 'questions.option_added';
    const EVENT_OPTION_REMOVE = 'questions.option_remove';

    const EVENT_ANSWER_ADDED = 'questions.answer_added';
    const EVENT_ANSWER_REMOVED = 'questions.answer_removed';

    const EVENT_POST_ADDED = 'questions.post_added';
    const EVENT_POST_REMOVED = 'questions.post_removed';

    const EVENT_FOLLOW_ADDED = 'questions.follow_added';
    const EVENT_FOLLOW_REMOVED = 'questions.follow_removed';

    const EVENT_QUESTION_ASKED = 'questions.question_asked';
    const EVENT_QUESTION_BEFORE_ASK = 'questions.question_before_ask';

    private static $INSTANCE;
    private $answerDao;
    private $optionDao;
    private $questionDao;
    private $subscribeDao;

    public static function getInstance()
    {
        if (!isset(self::$INSTANCE))
            self::$INSTANCE = new self();
        return self::$INSTANCE;
    }

    public function __construct()
    {
        $this->answerDao = IISQUESTIONS_BOL_AnswerDao::getInstance();
        $this->optionDao = IISQUESTIONS_BOL_OptionDao::getInstance();
        $this->questionDao = IISQUESTIONS_BOL_QuestionDao::getInstance();
        $this->subscribeDao = IISQUESTIONS_BOL_SubscribeDao::getInstance();
    }

    /**
     * @param string $addOption
     * @param bool $isMultiple
     * @param string $context
     * @param $contextId
     * @param array $options
     * @return IISQUESTIONS_BOL_Question
     */
    public function createQuestion($addOption, $isMultiple, $context, $contextId, $options = array())
    {
        $currentUser = OW::getUser();
        if (!$currentUser->isAuthenticated())
            return null;
        if (!$this->canUserCreate($currentUser->getId()))
            return null;
        $owner = $currentUser->getId();
        $question = new IISQUESTIONS_BOL_Question();
        $question->owner = $owner;
        if (isset($_POST["forwardType"]) && $_POST["forwardType"] == 'groups' && $addOption == 'friends_only') {
            $addOption = "only_for_me";
        }
        $question->addOption = $addOption;
        $question->context = $context;
        $question->contextId = $contextId;
        $question->isMultiple = $isMultiple;
        $question->timeStamp = time();

        $this->questionDao->save($question);

        foreach ($options as $option) {
            if (!empty($option))
                IISQUESTIONS_BOL_Service::getInstance()->addOption($question->getId(), $option);
        }
        $this->subscribe($currentUser->getId(), $question->getId());
        return $question;
    }

    public function editQuestion($questionId, $addOption, $isMultiple)
    {
        if (!$this->canCurrentUserEdit($questionId))
            return null;
        $question = $this->findQuestion($questionId);
        $question->addOption = $addOption;
        $question->isMultiple = $isMultiple;
        $question->timeStamp = time();

        $this->questionDao->save($question);
        return $question;
    }

    public function removeQuestion($questionId)
    {
        if (!$this->canCurrentUserEdit($questionId))
            return null;
        foreach ($this->optionDao->findOptionList($questionId) as $option)
            IISQUESTIONS_BOL_Service::getInstance()->removeOption($option);
        $this->questionDao->deleteById($questionId);
        $event = new OW_Event('feed.delete_item', array(
            'entityId' => $questionId,
            'entityType' => IISQUESTIONS_BOL_Service::ENTITY_TYPE
        ));
        OW_EventManager::getInstance()->trigger($event);
        $this->removeNotification(IISQUESTIONS_BOL_Service::ENTITY_TYPE, $questionId);
    }

    private function addOption($questionId, $text)
    {
        $option = new IISQUESTIONS_BOL_Option();
        $option->userId = OW::getUser()->getId();
        $option->questionId = $questionId;
        $option->text = $text;
        $option->timeStamp = time();
        $this->optionDao->save($option);
        return $option;
    }

    public function createOption($questionId, $text)
    {
        if (!$this->canUserAddOption($questionId))
            return false;
        $option = $this->addOption($questionId, $text);
        $user = BOL_UserService::getInstance()->findUserById(OW::getUser()->getId());
        $subscribes = $this->findSubscribes($questionId);
        foreach ($subscribes as $subscribe) {
            if ($subscribe->userId == OW::getUser()->getId())
                continue;
            /** @var IISQUESTIONS_BOL_Question $question */
            $question = $this->questionDao->findById($questionId);
            $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
            if(isset($pluginNewsfeed) && $pluginNewsfeed->isActive()){
                $action = NEWSFEED_BOL_Service::getInstance()->findAction($question->entityType,$question->entityId);
                $actionData = json_decode($action->data,true);
                $questionText =  UTIL_String::truncate( $actionData['status'], 60, '...' );
                $this->createNotification(
                    $subscribe->userId,
                    IISQUESTIONS_BOL_Service::ENTITY_TYPE . '_option',
                    $option->getId(),
                    'add_option',
                    $this->findAvatar(OW::getUser()->getId()),
                    'iisquestions+question_add_option_notification',
                    array(
                        'userUrl' => OW::getRouter()->urlForRoute('base_user_profile', array('username' => $user->username)),
                        'userName' => BOL_UserService::getInstance()->getDisplayName($user->getId()),
                        'questionText' => $questionText,
                        'questionUrl' => OW::getRouter()->urlForRoute('newsfeed_view_item', array('actionId' => $action->getId())),
                        'option' => UTIL_String::truncate( $text, 120, '...' )
                    ),
                    OW::getRouter()->urlForRoute('newsfeed_view_item', array('actionId' => $action->getId()))
                );
            }
        }
        return true;
    }

    public function removeOption($optionId)
    {
        $option = IISQUESTIONS_BOL_Service::findOption($optionId);
        if (!$this->canCurrentUserEdit($option->questionId) && $option->userId != OW::getUser()->getId())
            return false;
        IISQUESTIONS_BOL_Service::getInstance()->removeAnswerByOption($optionId);
        $this->optionDao->deleteById($optionId);
        $this->removeNotification(IISQUESTIONS_BOL_Service::ENTITY_TYPE . '_option', $optionId);
        return true;
    }

    public function editOption($optionId, $optionText)
    {
        $option = IISQUESTIONS_BOL_Service::findOption($optionId);
        if (!$this->canCurrentUserEdit($option->questionId) && $option->userId != OW::getUser()->getId())
            return null;
        $option = $this->findOption($optionId);
        $option->text = $optionText;
        $option->timeStamp = time();
        $this->optionDao->save($option);
    }

    public function addAnswer($userId, $questionId, $optionId)
    {
        if (!$this->canUserView($questionId))
            return null;
        $answer = new IISQUESTIONS_BOL_Answer();
        $answer->userId = $userId;
        $answer->questionId = $questionId;
        $answer->optionId = $optionId;
        $answer->timeStamp = time();
        $question = $this->questionDao->findById($answer->questionId);
        if(isset($question)){
            if (!$question->isMultiple){
                $optionIdList = $this->optionDao->findOptionList($questionId);
                $answers = $this->answerDao->findUserAnswerList( $userId, $optionIdList );
                if (!empty($answers)){
                    foreach ($answers as $answered){
                        $this->removeAnswer($userId, $answered->optionId);
                    }
                }
            }
            $this->answerDao->save($answer);


            $user = BOL_UserService::getInstance()->findUserById($userId);
            /** @var IISQUESTIONS_BOL_Option $option */
            $option = $this->optionDao->findById($optionId);
            $subscribes = $this->findSubscribes($questionId);
            $question = $this->questionDao->findById($questionId);
            $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
            if(isset($pluginNewsfeed) && $pluginNewsfeed->isActive()){
                $action = NEWSFEED_BOL_Service::getInstance()->findAction($question->entityType,$question->entityId);
                $actionData = json_decode($action->data,true);
                $questionText =  UTIL_String::truncate( $actionData['status'], 60, '...' );
                foreach ($subscribes as $subscribe) {
                    if ($subscribe->userId == $userId)
                        continue;
                    $this->createNotification(
                        $subscribe->userId,
                        IISQUESTIONS_BOL_Service::ENTITY_TYPE . '_answer',
                        $answer->getId(),
                        'add_answer',
                        $this->findAvatar($userId),
                        'iisquestions+add_answer_notification',
                        array(
                            'userUrl' => OW::getRouter()->urlForRoute('base_user_profile', array('username' => $user->username)),
                            'userName' => BOL_UserService::getInstance()->getDisplayName($user->getId()),
                            'questionText' => $questionText,
                            'questionUrl' => OW::getRouter()->urlForRoute('newsfeed_view_item', array('actionId' => $action->getId())),
                            'option' => UTIL_String::truncate( $option->text, 120, '...' )
                        ),
                        OW::getRouter()->urlForRoute('newsfeed_view_item', array('actionId' => $action->getId()))
                    );
                }
            }
        }
    }

    public function removeAnswer($userId, $optionId)
    {
        $option = IISQUESTIONS_BOL_Service::findOption($optionId);
        if (!$this->canUserView($option->questionId))
            return null;
        $answers = $this->answerDao->findAnswerByOptionAndUser($userId, $optionId);
        foreach ($answers as $answer) {
            $this->removeNotification(IISQUESTIONS_BOL_Service::ENTITY_TYPE . '_answer', $answer->getId());
        }
        $this->answerDao->deleteByUserAndOption($userId, $optionId);
    }

    public function removeAnswerByOption($optionId)
    {
        $answers = $this->answerDao->findAnswerByOption($optionId);
        foreach ($answers as $answer) {
            $this->removeNotification(IISQUESTIONS_BOL_Service::ENTITY_TYPE . '_answer', $answer->getId());
        }
        $this->answerDao->deleteByOption($optionId);
    }

    public function subscribe($userId, $questionId)
    {
        if (!$this->canUserView($questionId))
            return null;
        $subscribe = new IISQUESTIONS_BOL_Subscribe();
        $subscribe->userId = $userId;
        $subscribe->questionId = $questionId;
        $subscribe->timeStamp = time();

        $this->subscribeDao->save($subscribe);
    }

    public function isSubscribedByUser($userId, $questionId)
    {
        $subscribe = $this->subscribeDao->findSubscribeByQuestionAndUser($userId, $questionId);
        return $subscribe != null;
    }

    public function unsubscribe($userId, $questionId)
    {
        if (!$this->canUserView($questionId))
            return null;
        $this->subscribeDao->deleteByUserAndQuestion($userId, $questionId);
    }

    public function findSubscribes($questionId)
    {
        return $this->subscribeDao->findSubscribeByQuestion($questionId);
    }

    public function findAvatar($userId)
    {
        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));
        return $avatars[$userId];
    }

    public function createNotification($userId, $entityType, $entityId, $action, $avatar, $text, $textVars, $url)
    {
        $params = array(
            'pluginKey' => 'iisquestions',
            'entityType' => $entityType,
            'entityId' => $entityId,
            'action' => $action,
            'userId' => $userId,
            'time' => time()
        );

        $data = array(
            'avatar' => $avatar,
            'string' => array(
                'key' => $text,
                'vars' => $textVars
            ),
            'url' => $url,
        );

        $event = new OW_Event('notifications.add', $params, $data);
        OW::getEventManager()->trigger($event);
    }

    public function removeNotification($entityType, $entityId)
    {
        OW::getEventManager()->call('notifications.remove', array(
            'entityType' => $entityType,
            'entityId' => $entityId
        ));
    }

    public function onNotifyActions(BASE_CLASS_EventCollector $e)
    {
        $e->add(array(
            'section' => 'iisquestions',
            'action' => 'add_option',
            'sectionIcon' => 'ow_ic_files',
            'sectionLabel' => OW::getLanguage()->text('iisquestions', 'plugin_label'),
            'description' => OW::getLanguage()->text('iisquestions', 'addOption_label'),
            'selected' => true
        ));
        $e->add(array(
            'section' => 'iisquestions',
            'action' => 'add_answer',
            'sectionIcon' => 'ow_ic_files',
            'sectionLabel' => OW::getLanguage()->text('iisquestions', 'plugin_label'),
            'description' => OW::getLanguage()->text('iisquestions', 'addAnswer_label'),
            'selected' => true
        ));
    }

    public function findOptionList($questionId)
    {
        $idList = $this->optionDao->findOptionList($questionId);
        $optionList = array();
        foreach ($idList as $id) {
            $optionList[] = $this->optionDao->findById($id);
        }
        return $optionList;
    }

    public function questionCommentsCount($questionId)
    {
        return BOL_CommentService::getInstance()->findCommentCount(self::ENTITY_TYPE, $questionId);
    }

    public function questionSubscribeCount($questionId)
    {
        return $this->subscribeDao->findSubscribeCountByQuestion($questionId);
    }

    public function questionAnsweredCount($questionId)
    {
        return $this->answerDao->findAnswerCountByQuestion($questionId);
    }

    public function createFeed()
    {
    }

    public function findQuestion($questionId)
    {
        return $this->questionDao->findById($questionId);
    }

    public function findOption($optionId)
    {
        return $this->optionDao->findById($optionId);
    }

    /**
     * @param $questionId
     * @return bool
     */
    public function canUserView($questionId)
    {
        if (OW::getUser()->isAdmin() || OW::getUser()->isAuthorized('iisquestions'))
            return true;
        $question = $this->findQuestion($questionId);
        if ($question->privacy == IISQUESTIONS_BOL_Service::PRIVACY_EVERYBODY)
            return true;
        if ($question->privacy == IISQUESTIONS_BOL_Service::PRIVACY_FRIENDS_ONLY) {
            $friendship = OW::getEventManager()->call('plugin.friends.check_friendship', array('userId' => OW::getUser()->getId(), 'friendId' => $question->owner));
            if (!empty($friendship) && $friendship->getStatus() == 'active') {
                return true;
            }
            $friendship = OW::getEventManager()->call('plugin.friends.check_friendship', array('userId' => $question->owner, 'friendId' => OW::getUser()->getId()));
            if (!empty($friendship) && $friendship->getStatus() == 'active') {
                return true;
            }
        }
        return OW::getUser()->getId() == $question->owner;
    }

    /**
     * @param $questionId
     * @return bool
     */
    public function canUserEdit($questionId)
    {
        if (OW::getUser()->isAdmin() || OW::getUser()->isAuthorized('iisquestions'))
            return true;
        /** @var IISQUESTIONS_BOL_Question $question */
        $question = $this->findQuestion($questionId);
        if ($question->context == 'groups') {
            $group = $this->findGroupById($question->contextId);
            if (isset($group) && $this->canEditGroup($group))
                return true;
        }
        return OW::getUser()->getId() == $question->owner;
    }

    public function canCurrentUserAddOption($questionId)
    {
        return OW::getUser()->isAuthenticated() && $this->canUserAddOption($questionId);
    }

    public function canCurrentUserEdit($questionId)
    {
        return OW::getUser()->isAuthenticated() && $this->canUserEdit($questionId);
    }

    /**
     * @param $questionId
     * @return bool
     */
    public function canUserAddOption($questionId)
    {
        if (OW::getUser()->isAdmin() || OW::getUser()->isAuthorized('iisquestions'))
            return true;
        /** @var IISQUESTIONS_BOL_Question $question */
        $question = $this->findQuestion($questionId);
        if ($question->addOption == IISQUESTIONS_BOL_Service::PRIVACY_EVERYBODY)
            return true;
        if ($question->context == 'groups') {
            $group = $this->findGroupById($question->contextId);
            if (isset($group) && $this->canEditGroup($group))
                return true;
        }
        if ($question->addOption == IISQUESTIONS_BOL_Service::PRIVACY_FRIENDS_ONLY) {
            $friendship = OW::getEventManager()->call('plugin.friends.check_friendship', array('userId' => OW::getUser()->getId(), 'friendId' => $question->owner));
            if (!empty($friendship) && $friendship->getStatus() == 'active') {
                return true;
            }
            $friendship = OW::getEventManager()->call('plugin.friends.check_friendship', array('userId' => $question->owner, 'friendId' => OW::getUser()->getId()));
            if (!empty($friendship) && $friendship->getStatus() == 'active') {
                return true;
            }
        }
        if ($question->addOption == IISQUESTIONS_BOL_Service::PRIVACY_ONLY_FOR_ME)
            return OW::getUser()->getId() == $question->owner;
        return false;
    }

    /**
     * @param $userId
     * @return bool
     */
    public function canUserCreate($userId)
    {
        return true;
    }

    public function findAllQuestions($last = 0, $count = 5)
    {
        if (OW::getUser()->isAdmin() || OW::getUser()->isAuthorized('iisquestions')){
            $list = $this->questionDao->findAllSortByTime($count, $last);
            $listCount = $this->questionDao->findCountAll();
        }
        else{
            $list = $this->questionDao->findAllByUserSortByTime($count, $last);
            $listCount = $this->questionDao->findCountAllByUser();
        }
        return array($list, $listCount);
    }

    public function findMyQuestions($last = 0, $count = 5)
    {
        $list = $this->questionDao->findMyQuestionSortByTime($count, $last);
        $listCount = $this->questionDao->findMyQuestionCount();
        return array($list, $listCount);
    }

    public function findFriendQuestions($last = 0, $count = 5)
    {
        $list = $this->questionDao->findFriendQuestionSortByTime($count, $last);
        $listCount = $this->questionDao->findFriendQuestionCount();
        return array($list, $listCount);
    }

    public function findHottestQuestions($last = 0, $count = 5)
    {
        $list = $this->questionDao->findAllHottestQuestion($count, $last);
        $listCount = $this->questionDao->findAllHottestQuestionCount();
        return array($list, $listCount);
    }

    public function findAnswerCountByOption($optionId)
    {
        return $this->answerDao->findAnswerCountByOption($optionId);
    }

    public function findAnswerPercentByOption($questionId, $optionId)
    {
        $answerCountByOption = $this->findAnswerCountByOption($optionId);
        $answerCountByQuestion = $this->answerDao->findAnswerCountByQuestion($questionId);
        if ($answerCountByQuestion == 0 || $answerCountByOption == 0)
            return 0;
        return ($answerCountByOption * 100) / $answerCountByQuestion;
    }

    public function findAnsweredStatusByOption($userId, $optionId)
    {
        return $this->answerDao->isAnsweredByUser($userId, $optionId);
    }

    public function findUserAnsweredByOption($optionId)
    {
        $answers = $this->answerDao->findAnswerByOption($optionId);
        $userIds = array();
        foreach ($answers as $answer) {
            $userIds[] = $answer->userId;
        }
        return $userIds;
    }

    public function addButtonToNewsfeed(OW_Event $event)
    {
        if ($this->canUserCreate(OW::getUser()->getId())) {
            $params = $event->getParams();
            $form = $params['form'];
            $context = $form->getElement('feedType')->getValue();
            $contextId = $form->getElement('feedId')->getValue();
            IISQUESTIONS_CLASS_UserInterfaceUtils::getInstance()->addStaticResources(OW::getDocument(), false, false);
            OW::getDocument()->addOnloadScript('$(\'.ow_status_update_btn_block .ow_attachment_icons\').append(\'<span class="ow_cursor_pointer"><span id="IISQUESTIONS_Add" class="iisquestions_add" href="javascript://"></span></span>\');');
            $createFormJs = UTIL_JsGenerator::composeJsString(
                'createQuestionForm = new CreateQuestionForm({$formName},{$url},{$infoUrl});',
                array(
                    'formName' => IISQUESTIONS_CLASS_CreateQuestionForm::FORM_NAME,
                    'url' => OW::getRouter()->urlForRoute('iisquestion-create'),
                    'infoUrl' => OW::getRouter()->urlForRoute('iisquestion-info')
                )
            );

            if($context == "groups")
                $createParams = $this->createQuestionDesktopGroupsFeed();
            else
                $createParams = $this->createQuestionDesktop();

            $js = UTIL_JsGenerator::composeJsString(
                'setUpCreateQuestion({$editUrl},{$createUrl},{$context},{$contextId},{$js},{$createParams});',
                array(
                    'context' => $context,
                    'contextId' => $contextId,
                    'editUrl' => OW_Router::getInstance()->urlForRoute('iisquestion-edit'),
                    'createUrl' => OW_Router::getInstance()->urlForRoute('iisquestion-create'),
                    'js' => $createFormJs,
                    'createParams' => $createParams
                )
            );
            OW::getDocument()->addOnloadScript($js);
        }

    }

    public function addButtonToNewsfeedMobile(OW_Event $event)
    {
        if ($this->canUserCreate(OW::getUser()->getId())) {
            $params = $event->getParams();
            $form = $params['form'];
            $context = $form->getElement('feedType')->getValue();
            $contextId = $form->getElement('feedId')->getValue();
            IISQUESTIONS_CLASS_UserInterfaceUtils::getInstance()->addStaticResources(OW::getDocument(), false, true);
            OW::getDocument()->addOnloadScript('$(\'.owm_newsfeed_block .owm_newsfeed_status_update_add_cont\').append(\'<span class="ow_cursor_pointer"><span id="IISQUESTIONS_Add" class="iisquestions_add" href="javascript://"></span></span>\');');
            $createFormJs = UTIL_JsGenerator::composeJsString(
                'createQuestionForm = new CreateQuestionForm({$formName},{$url},{$infoUrl});',
                array(
                    'formName' => IISQUESTIONS_CLASS_CreateQuestionForm::FORM_NAME,
                    'url' => OW::getRouter()->urlForRoute('iisquestion-create'),
                    'infoUrl' => OW::getRouter()->urlForRoute('iisquestion-info')
                )
            );

            if($context == "groups")
                $createParams = $this->createQuestionMobileGroupsFeed();
            else
                $createParams = $this->createQuestionMobile();
            $js = UTIL_JsGenerator::composeJsString(
                'setUpCreateQuestion({$editUrl},{$createUrl},{$context},{$contextId},{$js},{$createParams})',
                array(
                    'context' => $context,
                    'contextId' => $contextId,
                    'editUrl' => OW_Router::getInstance()->urlForRoute('iisquestion-edit'),
                    'createUrl' => OW_Router::getInstance()->urlForRoute('iisquestion-create'),
                    'js' => $createFormJs,
                    'createParams' => $createParams
                )
            );
            OW::getDocument()->addOnloadScript($js);
        }

    }

    public function addInputFieldsToNewsfeed(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['form']) && $this->canUserCreate(OW::getUser()->getId())) {
            $pinInput = new HiddenField('question_id');
            $pinInput->setValue('');
            $pinInput->addAttribute("id", "question_id");
            $params['form']->addElement($pinInput);

            $pinInput = new HiddenField('question_hidden');
            $pinInput->setValue(true);
            $pinInput->addAttribute("id", "question_hidden");
            $params['form']->addElement($pinInput);

            $pinInput = new HiddenField('question_data');
            $pinInput->setValue(json_encode(array('only_for_me','one_answer')));
            $pinInput->addAttribute("id", "question_data");
            $params['form']->addElement($pinInput);
            $submitId = $params['form']->getSubmitElement("save")->getId();
            $textareaId = $params['form']->getElement("status")->getId();
            $js = UTIL_JsGenerator::composeJsString('        
            $("#"+{$submitId}).click(function() {
                if ($("#"+{$textareaId}).val() != "" && $("#question_hidden").val() === "false" && JSON.parse($("#question_data").val()).length === 2 ){
                    var jc = $.confirm({$emptyOptionMsg});
                    jc.buttons.ok.action = function () {
                        $("#'.$params['form']->getId().'").submit();
                    }
                    return false;
                }
            });', array('submitId' => $submitId, 'textareaId' => $textareaId
            , "emptyOptionMsg" => OW::getLanguage()->text('iisquestions', 'are_you_sure_empty_option')));

            OW::getDocument()->addOnloadScript( $js );
        }
    }

    public function onFeedRender(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['data']) && isset($params['data']['question_id']))
        {
            IISQUESTIONS_CLASS_UserInterfaceUtils::getInstance()->addStaticResources(OW::getDocument());
            /** @var IISQUESTIONS_BOL_Question $question */
            $questionId=$params['data']['question_id'];
            $question = $this->questionDao->findById($questionId);
            if (isset($question)) {
                $data = $event->getData();
                $questionComponent = new IISQUESTIONS_CMP_Question($question, OW::getUser()->getId(), $this->canCurrentUserEdit($question->getId()));
                $data["content"] = $data["content"] . $questionComponent->render();
                $event->setData($data);
            }
        }
    }

    public function onFeedRenderMobile(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['data']) && isset($params['data']['question_id']))
        {
            IISQUESTIONS_CLASS_UserInterfaceUtils::getInstance()->addStaticResources(OW::getDocument(),false,true);

            /** @var IISQUESTIONS_BOL_Question $question */
            $questionId=$params['data']['question_id'];
            $question = $this->questionDao->findById($questionId);
            if (isset($question)) {
                $data = $event->getData();
                $questionComponent = new IISQUESTIONS_MCMP_Question($question, OW::getUser()->getId(), $this->canCurrentUserEdit($question->getId()));
                $data["content"] = $data["content"] . $questionComponent->render();
                $event->setData($data);
            }
        }
    }

    public function genericItemRender(OW_Event $event)
    {
        $params = $event->getParams();
        $data = $event->getData();
        $entityId = $params['action']['entityId'];
        $entityType = $params['action']['entityType'];
        $action = null;
        $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        if(isset($pluginNewsfeed) && $pluginNewsfeed->isActive()){
            $action = NEWSFEED_BOL_Service::getInstance()->findAction($entityType, $entityId);
        }
        if (!isset($action))
            return;
        $content = json_decode($action->data, true);
        if (isset($content['question_id'])) {
            IISQUESTIONS_CLASS_UserInterfaceUtils::getInstance()->addStaticResources(OW::getDocument());
            /** @var IISQUESTIONS_BOL_Question $question */
            $question = $this->questionDao->findById($content['question_id']);
            if (!isset($question))
                return;
            if ($params['action']['entityType'] == 'groups-status') {
                $groupId = $params['feedId'];
                $group = $this->findGroupById($groupId);
                $canEdit = $this->canEditGroup($group) ? true : $this->canCurrentUserEdit($question->getId());
            } else {
                $canEdit = $this->canCurrentUserEdit($question->getId());
            }
            if (isset($canEdit)) {
                if ($canEdit) {
                    if (isset($question)) {
                        array_unshift($data['contextMenu'], array(
                            'label' => OW::getLanguage()->text('iisquestions', 'toolbar_edit_btn'),
                            'class' => 'newsfeed_edit_btn',
                            'id' => 'iisquestions_edit_' . $question->getId()
                        ));
                    }
                }
            }
            $js = UTIL_JsGenerator::composeJsString('
                    question_details_map[{$questionId}] = new QUESTIONS_QuestionDetails({$questionId},{$subscribeUrl},{$editUrl},{$subscribeError});
                ', array(
                    'questionId' => $question->getId(),
                    'subscribeUrl' => OW::getRouter()->urlForRoute('iisquestion-subscribe'),
                    'editUrl' => OW::getRouter()->urlForRoute('iisoption-edit'),
                    'subscribeError' => !OW::getUser()->isAuthenticated()? OW::getLanguage()->text('iisquestions', 'guest_subscribe_error') : ''
                )
            );
            OW::getDocument()->addOnloadScript($js, 50);
            if (isset($question)) {
                $subscribe = $this->subscribeDao->findSubscribeByQuestionAndUser(OW::getUser()->getId(), $question->getId());
                $thisUserHasSubscribed = isset($subscribe);
                array_unshift($data['contextMenu'], array(
                    'label' => OW::getLanguage()->text('iisquestions', $thisUserHasSubscribed ? 'toolbar_unfollow_btn' : 'toolbar_follow_btn'),
                    'class' => 'iisquestions_subscribe',
                    'id' => 'iisquestions_subscribe_' . $question->getId()
                ));
            }
            $event->setData($data);
        }
    }

    public function genericItemRenderMobile(OW_Event $event)
    {
        $params = $event->getParams();
        $data = $event->getData();
        $entityId = $params['action']['entityId'];
        $entityType = $params['action']['entityType'];
        $action = null;
        $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        if(isset($pluginNewsfeed) && $pluginNewsfeed->isActive()){
            $action = NEWSFEED_BOL_Service::getInstance()->findAction($entityType, $entityId);
        }
        if (!isset($action))
            return;
        $content = json_decode($action->data, true);
        if (isset($content['question_id'])) {
            IISQUESTIONS_CLASS_UserInterfaceUtils::getInstance()->addStaticResources(OW::getDocument(),false,true);
            /** @var IISQUESTIONS_BOL_Question $question */
            $question = $this->questionDao->findById($content['question_id']);
            if (!isset($question))
                return;
            if ($params['action']['entityType'] == 'groups-status') {
                $groupId = $params['feedId'];
                $group = $this->findGroupById($groupId);
                $canEdit = $this->canEditGroup($group) ? true : $this->canCurrentUserEdit($question->getId());
            } else {
                $canEdit = $this->canCurrentUserEdit($question->getId());
            }
            if (isset($canEdit)) {
                if ($canEdit) {
                    if (isset($question)) {
                        array_unshift($data['contextMenu'], array(
                            'label' => OW::getLanguage()->text('iisquestions', 'toolbar_edit_btn'),
                            'class' => 'newsfeed_edit_btn ',
                            'id' => 'iisquestions_edit_' . $question->getId()
                        ));
                    }
                }
            }
            $js = UTIL_JsGenerator::composeJsString('
                    question_details_map[{$questionId}] = new QUESTIONS_QuestionDetails({$questionId},{$subscribeUrl},{$editUrl},{$subscribeError});
                ', array(
                    'questionId' => $question->getId(),
                    'subscribeUrl' => OW::getRouter()->urlForRoute('iisquestion-subscribe'),
                    'editUrl' => OW::getRouter()->urlForRoute('iisoption-edit'),
                    'subscribeError' => !OW::getUser()->isAuthenticated()? OW::getLanguage()->text('iisquestions', 'guest_subscribe_error') : ''
                )
            );
            OW::getDocument()->addOnloadScript($js, 50);
            if (isset($question)) {
                $subscribe = $this->subscribeDao->findSubscribeByQuestionAndUser(OW::getUser()->getId(), $question->getId());
                $thisUserHasSubscribed = isset($subscribe);
                array_unshift($data['contextMenu'], array(
                    'label' => OW::getLanguage()->text('iisquestions', $thisUserHasSubscribed ? 'toolbar_unfollow_btn' : 'toolbar_follow_btn'),
                    'class' => 'iisquestions_subscribe',
                    'id' => 'iisquestions_subscribe_' . $question->getId()
                ));
            }
            $event->setData($data);
        }
    }

    public function canEditGroup($group)
    {
        if (!OW::getPluginManager()->isPluginActive('groups') || !isset($group))
            return false;
        $isAuthenticated = OW::getUser()->isAuthenticated();
        $canEditGroup = GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($group);
        $isModerator = OW::getUser()->isAuthorized('groups');

        return $isAuthenticated && ($canEditGroup || $isModerator);
    }

    private function findGroupById($groupId)
    {
        if (!OW::getPluginManager()->isPluginActive('groups'))
            return null;
        return GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
    }

    public function createFeedList($feedType, $feedId = null)
    {
        /** @var IISQUESTIONS_CLASS_NewsfeedDriver $driver */
        $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        $component = null;
        if(isset($pluginNewsfeed) && $pluginNewsfeed->isActive()){
            $driver = OW::getClassInstance("IISQUESTIONS_CLASS_NewsfeedDriver");
            $driver->setup(array('feedType' => $feedType,'viewMore' => true,'displayCount'=>10));
            $component = OW::getClassInstance("NEWSFEED_CMP_Feed", $driver, $feedType, $feedId);
            $component->setup(array('viewMore' => true,'displayCount'=>10));
        }
        return $component;
    }

    public function createFeedListMobile($feedType, $feedId = null)
    {
        /** @var IISQUESTIONS_CLASS_NewsfeedDriver $driver */
        $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        $component = null;
        if(isset($pluginNewsfeed) && $pluginNewsfeed->isActive()){
            $driver = OW::getClassInstance("IISQUESTIONS_CLASS_NewsfeedDriver");
            $driver->setup(array('feedType' => $feedType,'viewMore' => true,'displayCount'=>10));
            $component = OW::getClassInstance("NEWSFEED_MCMP_Feed", $driver, $feedType, $feedId);
            $component->setup(array('viewMore' => true,'displayCount'=>10));
        }
        return $component;
    }

    public function deleteAction(OW_Event $event)
    {
        $params = $event->getParams();
        $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        if ( isset($params['entityType']) && isset($params['entityId']) &&
            isset($pluginNewsfeed) && $pluginNewsfeed->isActive() ) {
            $action = NEWSFEED_BOL_Service::getInstance()->findAction($params['entityType'], $params['entityId']);
            if (!isset($action))
                return;
            $content = json_decode($action->data, true);
            if (isset($content['question_id'])) {
                $question = $this->questionDao->findById($content['question_id']);
                if (isset($question))
                    $this->removeQuestion($question->getId());
            }
        }
    }

    public function onEntityAction(OW_Event $event)
    {
        $data = $event->getData();
        $questionId = null;
        $hidden = true;
        $questionData = json_encode(array());
        $feedId = null;
        $feedType = null;

        if(isset($_POST['question_id'])){
            $questionId = $_POST['question_id'];
        }

        if(isset($_POST['feedId'])){
            $feedId = $_POST['feedId'];
        }

        if(isset($_POST['feedType'])){
            $feedType = $_POST['feedType'];
        }

        if (isset($_POST['question_hidden']) && !($_POST['question_hidden'] == 'true')) {
            $hidden = false;
        }

        if(isset($data['questionData'])) {
            if (isset($data['questionData']['question_id'])) {
                $questionId = $data['questionData']['question_id'];
            }

            if (isset($data['questionData']['questionData'])) {
                $questionData = $data['questionData']['questionData'];
            }

            if (isset($data['questionData']['hidden'])) {
                $hidden = $data['questionData']['hidden'];
            }
        }

        if ($feedId == null && OW::getUser()->isAuthenticated() && $feedType == 'user') {
            $feedId = OW::getUser()->getId();
        }

        if($questionId == null ||
            $feedId == null ||
            $feedType == null ||
            $hidden){
            return;
        }

        if(isset($_POST['question_data'])){
            $questionData = $_POST['question_data'];
        }

        $questionId = $this->processAddQuestion($feedType, $feedId, $questionData);
        if ($questionId != null ) {
            $data['question_id'] = $questionId;
            unset($data['questionData']);
            $event->setData($data);
        }
    }

    public function onForward(OW_Event $event)
    {
        $params = $event->getParams();
        if(!isset($params['actionData'])){
            return;
        }

        $actionData = $params['actionData'];
        if(!isset($actionData->question_id)){
            return;
        }

        $questionData = array();

        $questionId = $actionData->question_id;
        $question= $this->findQuestion($questionId);
        if(isset($question)){
            $options = $this->findOptionList($questionId);
            $optionListForForward=array();
            $originalOptionList=array();
            $optionListForForward[] = $question->addOption;
            if($question->isMultiple==1)
            {
                $optionListForForward[]=IISQUESTIONS_BOL_Service::MULTIPLE_ANSWER;
            }
            else{
                $optionListForForward[]=IISQUESTIONS_BOL_Service::ONE_ANSWER;
            }
            foreach ($options as $option) {
                $optionListForForward[]=$option->text;
                $originalOptionList[]=$option->text;
            }
            //$newQuestion = $this->createQuestion($question->addOption, $question->isMultiple,$question->context,$question->contextId,$originalOptionList);
            $questionData['question_id'] = $questionId;
            $questionData['hidden'] = false;
            $questionData['questionData'] = json_encode($optionListForForward);
            $event->setData(array('data' => array('questionData' => $questionData)));
        }
    }

    private function addQuestion()
    {
        if (isset($_POST['question_id']) && isset($_POST['question_hidden']) && !($_POST['question_hidden'] == 'true')) {
            return $this->processAddQuestion($_POST['feedType'], $_POST['feedId'], $_POST['question_data']);
        }
        return null;
    }

    private function processAddQuestion($feedType, $feedId, $questionData)
    {
        $context = UTIL_HtmlTag::stripTags($feedType);
        $contextId = UTIL_HtmlTag::stripTags($feedId);
        $data = json_decode($questionData);
        $allowAddOptions = $data[0];
        if($data[1] == IISQUESTIONS_BOL_Service::MULTIPLE_ANSWER)
            $isMultiple = true;
        else
            $isMultiple = false;
        array_shift($data);
        array_shift($data);
        $question = $this->createQuestion($allowAddOptions, $isMultiple, $context, $contextId, $data);
        return $question->getId();
    }

    public function feedAdded(OW_Event $event)
    {
        $params = $event->getParams();
        if ((isset($_POST['question_id']) && isset($_POST['question_hidden']) && !($_POST['question_hidden']  == 'true')) || isset($_POST["forwardType"])  ) {
            $entityId = $params['entityId'];
            $entityType = $params['entityType'];
            $action = NEWSFEED_BOL_Service::getInstance()->findAction($entityType, $entityId);
            $json = json_decode($action->data, true);
            if (isset($json["question_id"])){
                $questionId = $json["question_id"];
                /** @var IISQUESTIONS_BOL_Question $question */
                $question = $this->questionDao->findById($questionId);
                if (!isset($question))
                    return;
                $question->entityId = $entityId;
                $question->entityType = $entityType;
                $iisSecurityEssentialPlugin = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
                if(isset($iisSecurityEssentialPlugin) && $iisSecurityEssentialPlugin->isActive()) {
                    $privacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->getActionPrivacyByActionId($action->getId());
                if (isset($_POST["forwardType"]) && $_POST["forwardType"] == 'user') {
                    $profileOwnerPrivacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->getActionValueOfPrivacy('other_post_on_feed_newsfeed', $_POST["feedId"]);
                    $privacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->validatePrivacy($profileOwnerPrivacy);
                }
                if (isset($_POST["forwardType"]) && $_POST["forwardType"] == 'groups') {
                    $privacy = "everybody";
                }
                    $question->privacy = $privacy;
                }
                $this->questionDao->save($question);
            }
        }
    }

    public function onCollectAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'iisquestions' => array(
                    'label' => $language->text('iisquestions', 'plugin_label'),
                    'actions' => array(
                        'add_answer' => $language->text('iisquestions', 'auth_add_answer'),
                        'answer' => $language->text('iisquestions', 'auth_answer'),
                    )
                )
            )
        );
    }

    public function findUserAnsweredOptionIdList( $userId, $optionIds )
    {
        $answerList = array();
        $optionIds = (empty($optionIds) || $optionIds == null)  ? array() : $optionIds;
        if (!empty($optionIds)){
            $answerList = $this->answerDao->findUserAnswerList($userId, $optionIds);
        }
        $answeredOptionIds = array();
        foreach ( $answerList as $answer ){
            $answeredOptionIds[] = $answer->optionId;
        }
        return $answeredOptionIds;
    }

    public function getOnLoadScripts()
    {
        $reflectionClass = new ReflectionClass('OW_HtmlDocument');
        $reflectionProperty = $reflectionClass->getProperty('onloadJavaScript');
        $reflectionProperty->setAccessible(true);
        $onLoadJsList = $reflectionProperty->getValue(OW::getDocument());
        $result = array();
        if (isset($onLoadJsList['items']) && is_array($onLoadJsList['items']) && !empty($onLoadJsList['items'])) {
            foreach ($onLoadJsList['items'] as $item)
                $result = array_merge($result, $item);
        }
        return $result;
    }

    public function createQuestionDesktop() {
        $onLoadJsListOriginal = $this->getOnLoadScripts();
        $cmp = new IISQUESTIONS_CMP_Question(null, OW::getUser()->getId(), true);
        $html = $cmp->render();
        $js = array();
        $onLoadJsList = $this->getOnLoadScripts();
        $jsFiles = OW::getDocument()->getJavaScripts()['added'];
        foreach ($onLoadJsList as $value)
            if (!isset($onLoadJsListOriginal) || !in_array($value, $onLoadJsListOriginal))
                $js[] = $value instanceOf UTIL_JsGenerator ? $value->generateJs() : $value;
        $result = array('msg' => OW::getLanguage()->text('iisquestions', 'question_create_successfully'), 'questionId' => $cmp->questionIdtmp,'html'=>$html, 'js' => $js, 'js_files' => $jsFiles,
            'questionSetting'=>'<div style="display: -webkit-box;" id="newsfeed_status_select_options"><div id="allow_add_option" class="questions-add-answers-options"><div class="ow_question_label_option"><label for="input_2xigasuw">'
                .OW::getLanguage()->text("iisquestions", "question_add_allow_add_opt").
                '</label></div><div class="ow_question_label_option"><select name="allowAddOptions" id="input_2xigasuw" class="statusPrivacy"><option value="everybody">'
                .OW::getLanguage()->text("privacy", "privacy_everybody").'</option><option selected="selected" value="only_for_me">'.
                OW::getLanguage()->text("privacy", "privacy_only_for_me").'</option><option value="friends_only">'.
                OW::getLanguage()->text("friends", "privacy_friends_only").'</option></select></div></div>'.
                '<div id="allow_multiple_answers" class="questions-add-allow-multiple-answers"><div class="ow_question_label_option"><label for="input_2xigasuv">'
                .OW::getLanguage()->text("iisquestions", "question_add_allow_multiple_answer").
                '</label></div><div class="ow_question_label_option"><select name="allowMultipleAnswers" id="input_2xigasuv" class="statusPrivacy"><option selected="selected" value="one_answer">'
                .OW::getLanguage()->text("iisquestions", "one_answer").'</option><option value="multiple_answer">'.
                OW::getLanguage()->text("iisquestions", "multiple_answers").'</option></select></div></div></div>');
        return $result;
    }
    public function createQuestionDesktopGroupsFeed() {
        $onLoadJsListOriginal = $this->getOnLoadScripts();
        $cmp = new IISQUESTIONS_CMP_Question(null, OW::getUser()->getId(), true);
        $html = $cmp->render();
        $js = array();
        $onLoadJsList = $this->getOnLoadScripts();
        $jsFiles = OW::getDocument()->getJavaScripts()['added'];
        foreach ($onLoadJsList as $value)
            if (!isset($onLoadJsListOriginal) || !in_array($value, $onLoadJsListOriginal))
                $js[] = $value instanceOf UTIL_JsGenerator ? $value->generateJs() : $value;
        $result = array('msg' => OW::getLanguage()->text('iisquestions', 'question_create_successfully'), 'questionId' => $cmp->questionIdtmp,'html'=>$html, 'js' => $js, 'js_files' => $jsFiles,
            'questionSetting'=>'<div style="display: -webkit-box;" id="newsfeed_status_select_options"><div id="allow_add_option" class="questions-add-answers-options"><div class="ow_question_label_option"><label for="input_2xigasuw">'
                .OW::getLanguage()->text("iisquestions", "question_add_allow_add_opt").
                '</label></div><div class="ow_question_label_option"><select name="allowAddOptions" id="input_2xigasuw" class="statusPrivacy"><option value="everybody">'
                .OW::getLanguage()->text("privacy", "privacy_everybody").'</option><option selected="selected" value="only_for_me">'.
                OW::getLanguage()->text("privacy", "privacy_only_for_me").'</option><option value="friends_only">'.
                OW::getLanguage()->text("friends", "privacy_friends_only").'</option></select></div></div>'.
                '<div id="allow_multiple_answers" class="questions-add-allow-multiple-answers"><div class="ow_question_label_option"><label for="input_2xigasuv">'
                .OW::getLanguage()->text("iisquestions", "question_add_allow_multiple_answer").
                '</label></div><div class="ow_question_label_option"><select name="allowMultipleAnswers" id="input_2xigasuv" class="statusPrivacy"><option selected="selected" value="one_answer">'
                .OW::getLanguage()->text("iisquestions", "one_answer").'</option><option value="multiple_answer">'.
                OW::getLanguage()->text("iisquestions", "multiple_answers").'</option></select></div></div></div>');
        return $result;
    }

    public function createQuestionMobile() {
        $onLoadJsListOriginal = $this->getOnLoadScripts();
        $cmp = new IISQUESTIONS_MCMP_Question(null, OW::getUser()->getId(), true);
        $html = $cmp->render();
        $js = array();
        $onLoadJsList = $this->getOnLoadScripts();
        $jsFiles = OW::getDocument()->getJavaScripts()['added'];
        foreach ($onLoadJsList as $value)
            if (!isset($onLoadJsListOriginal) || !in_array($value, $onLoadJsListOriginal))
                $js[] = $value instanceOf UTIL_JsGenerator ? $value->generateJs() : $value;
        $result = array('msg' => OW::getLanguage()->text('iisquestions', 'question_create_successfully'), 'questionId' => $cmp->questionIdtmp,'html'=>$html, 'js' => $js, 'js_files' => $jsFiles,
            'questionSetting'=>'<div style="display: block; margin-right: 10px;  margin-left: 10px;"><div id="owm_allow_add_option" class="owm_questions-add-answers-options owm_text_align"><label for="input_2xigasuw" class="owm_sameWidth">'.
                OW::getLanguage()->text("iisquestions", "question_add_allow_add_opt").'</label><select name="allowAddOptions" id="input_2xigasuw" class="statusPrivacy owm_sameWidth"><option value="everybody">'.
                OW::getLanguage()->text("privacy", "privacy_everybody").'</option><option selected="selected" value="only_for_me">'.OW::getLanguage()->text("privacy", "privacy_only_for_me").
                '</option><option value="friends_only">'. OW::getLanguage()->text("friends", "privacy_friends_only").'</option></select></div>'.
                '<div id="owm_allow_multiple_answers" class="owm_questions-add-allow-multiple-answers owm_text_align"><label for="input_2xigasuv" class="owm_sameWidth">'.
                OW::getLanguage()->text("iisquestions", "question_add_allow_multiple_answer").'</label><select name="allowMultipleAnswers" id="input_2xigasuv" class="statusPrivacy owm_sameWidth"><option selected="selected" value="one_answer">'.
                OW::getLanguage()->text("iisquestions", "one_answer").'</option>'.'<option value="multiple_answer">'. OW::getLanguage()->text("iisquestions", "multiple_answers").'</option></select></div></div>');
        return $result;
    }
    public function createQuestionMobileGroupsFeed() {
        $onLoadJsListOriginal = $this->getOnLoadScripts();
        $cmp = new IISQUESTIONS_MCMP_Question(null, OW::getUser()->getId(), true);
        $html = $cmp->render();
        $js = array();
        $onLoadJsList = $this->getOnLoadScripts();
        $jsFiles = OW::getDocument()->getJavaScripts()['added'];
        foreach ($onLoadJsList as $value)
            if (!isset($onLoadJsListOriginal) || !in_array($value, $onLoadJsListOriginal))
                $js[] = $value instanceOf UTIL_JsGenerator ? $value->generateJs() : $value;
        $result = array('msg' => OW::getLanguage()->text('iisquestions', 'question_create_successfully'), 'questionId' => $cmp->questionIdtmp,'html'=>$html, 'js' => $js, 'js_files' => $jsFiles,
            'questionSetting'=>'<div style="display: block; margin-right: 10px;  margin-left: 10px;"><div id="owm_allow_add_option" class="owm_questions-add-answers-options owm_text_align"><label for="input_2xigasuw" class="owm_sameWidth">'.
                OW::getLanguage()->text("iisquestions", "question_add_allow_add_opt").'</label><select name="allowAddOptions" id="input_2xigasuw" class="statusPrivacy owm_sameWidth"><option value="everybody">'.
                OW::getLanguage()->text("privacy", "privacy_everybody").'</option><option selected="selected" value="only_for_me">'.OW::getLanguage()->text("privacy", "privacy_only_for_me").
                '</option><option value="friends_only">'. OW::getLanguage()->text("friends", "privacy_friends_only").'</option></select></div>'.
                '<div id="owm_allow_multiple_answers" class="owm_questions-add-allow-multiple-answers owm_text_align"><label for="input_2xigasuv" class="owm_sameWidth">'.
                OW::getLanguage()->text("iisquestions", "question_add_allow_multiple_answer").'</label><select name="allowMultipleAnswers" id="input_2xigasuv" class="statusPrivacy owm_sameWidth"><option selected="selected" value="one_answer">'.
                OW::getLanguage()->text("iisquestions", "one_answer").'</option>'.'<option value="multiple_answer">'. OW::getLanguage()->text("iisquestions", "multiple_answers").'</option></select></div></div>');
        return $result;
    }
    public function deleteAllActions() {
        $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        if(isset($pluginNewsfeed) && $pluginNewsfeed->isActive()){
            $actions = $this->findAllActions();
            NEWSFEED_BOL_Service::getInstance()->deleteActionByList($actions);
        }
    }

    public function findAllActions() {
        $questions = $this->questionDao->findAll();
        $entityIdList = array();
        foreach ($questions as $question){
            if(!empty($question->entityId)){
                $entityIdList[] = $question->entityId;
            }
        }
        $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        if(isset($pluginNewsfeed) && $pluginNewsfeed->isActive()){
            return NEWSFEED_BOL_Service::getInstance()->findActionListByEntityIdsAndEntityType($entityIdList, 'user-status');
        }
            return array();
    }

}