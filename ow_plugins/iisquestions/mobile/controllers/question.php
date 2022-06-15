<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 3/3/18
 * Time: 11:04 AM
 */
class IISQUESTIONS_MCTRL_Question extends OW_MobileActionController
{
    /**
     *
     * @var IISQUESTIONS_BOL_Service
     */
    private $service;

    public function __construct()
    {
        parent::__construct();

        $this->service = IISQUESTIONS_BOL_Service::getInstance();
    }

    public function question($params)
    {
        $questionId = (int)$params['qid'];
        $question = $this->service->findQuestion($questionId);

        if (!isset($question)) {
            throw new Redirect404Exception;
        }
        if (!$this->service->canUserView($questionId)) {
            throw new Redirect404Exception;
        }

        $language = OW::getLanguage();

        OW::getDocument()->setTitle($language->text('iisquestions', 'question_page_title'));
        OW::getDocument()->setDescription($language->text('iisquestions', 'question_page_description', array(
            'question' => UTIL_String::truncate(strip_tags($question->text), 200)
        )));
        IISQUESTIONS_CLASS_UserInterfaceUtils::getInstance()->addStaticResources(OW::getDocument(),false,true);

        $cssDir = OW::getPluginManager()->getPlugin("iisquestions")->getStaticCssUrl();
        OW::getDocument()->addStyleSheet($cssDir . "iisquestions.css");

        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'iisquestions', 'main_menu_list');
        $canUserEdit = $this->service->canCurrentUserEdit($question);
        $cmp = new IISQUESTIONS_MCMP_Question($question, OW::getUser()->getId(), $canUserEdit);
        $this->addComponent('question', $cmp);
    }

    public function reloadQuestion($params)
    {
        if (!OW::getRequest()->isAjax()) {
            exit(json_encode(array('error' => 'only ajax is allowed.')));
        }
        if (!isset($_POST['questionId'])) {
            exit(json_encode(array('error' => 'data is incomplete.')));
        }
        $questionId = UTIL_HtmlTag::stripTags($_POST['questionId']);

        $onLoadJsListOriginal = $this->service->getOnLoadScripts();
        if (isset($_POST['newQuestion'])){
            $cmp = new IISQUESTIONS_MCMP_Question(null, OW::getUser()->getId(), OW::getUser()->isAuthenticated(), $_POST['questionId'], $_POST['options']);
        }
        else{
            $question = $this->service->findQuestion($questionId);
            $canUserEdit = $this->service->canCurrentUserEdit($questionId);
            $cmp = new IISQUESTIONS_MCMP_Question($question, OW::getUser()->getId(), $canUserEdit);
        }
        $result = $cmp->render();
        $js = array();
        $jsFiles = OW::getDocument()->getJavaScripts()['added'];
        $onLoadJsList = $this->service->getOnLoadScripts();
        foreach ($onLoadJsList as $value)
            if (!isset($onLoadJsListOriginal) || !in_array($value, $onLoadJsListOriginal))
                $js[] = $value instanceOf UTIL_JsGenerator ? $value->generateJs() : $value;
        exit(json_encode(array('result' => $result, 'js' => $js, 'js_files' => $jsFiles)));
    }

    public function getQuestionInfo($params)
    {
        if (!OW::getRequest()->isAjax()) {
            exit(json_encode(array('error' => 'only ajax is allowed.')));
        }
        if (!isset($_POST['questionId']) && (!isset($_POST['entityType']) || !isset($_POST['entityId']))) {
            exit(json_encode(array('error' => 'data is incomplete.')));
        }
        if(isset($_POST['questionId'])) {
            $questionId = UTIL_HtmlTag::stripTags($_POST['questionId']);
        }else{
            $entityId = UTIL_HtmlTag::stripTags($_POST['entityId']);
            $entityType = UTIL_HtmlTag::stripTags($_POST['entityType']);
            /** @var IISQUESTIONS_BOL_Question $question */
            $question = IISQUESTIONS_BOL_QuestionDao::getInstance()->findByEntity($entityId,$entityType);
            if(!isset($question))
            {
                return;
            }
            $questionId = $question->getId();
        }
        $uniqueId = IISQUESTIONS_CMP_Question::UNIQUE_ID_PREFIX . $questionId;
        $reloadUrl = OW::getRouter()->urlForRoute('iisquestion-reload');
        exit(json_encode(array('uniqueId' => $uniqueId, 'reloadUrl' => $reloadUrl,'questionId'=>$questionId)));
    }

    public function createQuestion()
    {
        if (!OW::getRequest()->isAjax()) {
            exit(json_encode(array('error' => 'only ajax is allowed.')));
        }else{
                exit(json_encode($this->service->createQuestionMobile()));
            }
    }

    public function editQuestion()
    {
        if (!OW::getRequest()->isAjax()) {
            exit(json_encode(array('error' => 'only ajax is allowed.')));
        }
        $questionId = UTIL_HtmlTag::stripTags($_POST['question_id']);
        if (!$this->service->canCurrentUserEdit($questionId))
            exit(json_encode(array('error' => '404')));
        $allowAddOption = UTIL_HtmlTag::stripTags($_POST[IISQUESTIONS_CLASS_CreateQuestionForm::ALLOW_ADD_OPTION_FIELD_NAME]);
        $allowMultipleAnswers = UTIL_HtmlTag::stripTags($_POST[IISQUESTIONS_CLASS_EditQuestionForm::ALLOW_MULTIPLE_ANSWERS_NAME]);
        if ($allowMultipleAnswers == IISQUESTIONS_BOL_Service::MULTIPLE_ANSWER)
            $isMultiple = true;
        else
            $isMultiple = false;
        $question = $this->service->editQuestion($questionId, $allowAddOption, $isMultiple);
        $result = array('msg' => OW::getLanguage()->text('iisquestions', 'question_create_successfully'), 'questionId' => $question->getId());
        exit(json_encode($result));
    }

    public function questionHome($param)
    {
        $language = OW::getLanguage();

        OW::getDocument()->setTitle($language->text('iisquestions', 'list_all_page_title'));
        OW::getDocument()->setDescription($language->text('iisquestions', 'list_all_page_description'));
        OW::getDocument()->setHeading($language->text('iisquestions', 'list_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_lens');

        IISQUESTIONS_CLASS_UserInterfaceUtils::getInstance()->addStaticResources(OW::getDocument(),false,true);

        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'iisquestions', 'main_menu_list');

        $userId = OW::getUser()->getId();
        $type = array_key_exists('type', $param) ? $param['type'] : null;
        if (!isset($type)) {
            $type = 'all';
        }
        $cmp = $this->service->createFeedListMobile($type);
        if (isset($cmp)){
            $this->addComponent('list', $cmp);
        }

        $menu = new IISQUESTIONS_MCMP_ListMenu($type);
        $this->addComponent('menu', $menu);
    }

    public function addOption($params)
    {
        if (!OW::getRequest()->isAjax())
            exit(json_encode(array('error' => 'request must be ajax.')));
        if (!OW::getUser()->isAuthenticated())
            exit(json_encode(array('error' => 'user must login.')));
        if (!isset($_POST['questionId']) || !isset($_POST['option']))
            exit(json_encode(array('error' => 'parameters are incomplete.')));
        if (isset($_POST['newQuestion']) && $_POST['newQuestion'] == 'true'){
            exit(json_encode(array('newQuestion' => 'true',
                'questionId' => $_POST['questionId'],'option' => $_POST['option'] )));
        }
        else{
            $questionId = UTIL_HtmlTag::stripTags($_POST['questionId']);
            $option = UTIL_HtmlTag::stripTags($_POST['option']);
            $question = $this->service->findQuestion($questionId);
            if(!isset($question))
                exit(json_encode(array('error'=>'question not found.')));
            $this->service->createOption($questionId,$option);
            exit(json_encode(array('result' => 'ok')));
        }

    }

    public function answer($params)
    {
        if (!OW::getRequest()->isAjax())
            exit(json_encode(array('error' => 'request must be ajax.')));
        if (!OW::getUser()->isAuthenticated())
            exit(json_encode(array('error' => 'user must login.')));
        if (!isset($_POST['optionId']))
            exit(json_encode(array('error' => 'parameters are incomplete.')));
        $optionId = UTIL_HtmlTag::stripTags($_POST['optionId']);
        $option = $this->service->findOption($optionId);
        if (!isset($option))
            exit();
        if (!$this->service->findAnsweredStatusByOption(OW::getUser()->getId(), $option->getId()))
            $this->service->addAnswer(OW::getUser()->getId(), $option->questionId, $option->getId());
        else
            $this->service->removeAnswer(OW::getUser()->getId(), $option->getId());
        exit(json_encode(array('result' => 'ok')));
    }

    public function deleteOption($params)
    {
        if (!OW::getRequest()->isAjax())
            exit(json_encode(array('error' => 'request must be ajax.')));
        if (!OW::getUser()->isAuthenticated())
            exit(json_encode(array('error' => 'user must login.')));
        if (!isset($_POST['optionId']))
            exit(json_encode(array('error' => 'parameters are incomplete.')));
        $optionId = UTIL_HtmlTag::stripTags($_POST['optionId']);
        $this->service->removeOption($optionId);
        exit(json_encode(array('result' => 'ok')));
    }

    public function editOption($params)
    {
        if (!OW::getRequest()->isAjax())
            exit(json_encode(array('error' => 'request must be ajax.')));
        if (!OW::getUser()->isAuthenticated())
            exit(json_encode(array('error' => 'user must login.')));
        if (!isset($_POST['optionId']))
            exit(json_encode(array('error' => 'parameters are incomplete.')));
        $optionId = UTIL_HtmlTag::stripTags($_POST['optionId']);
        $optionText = UTIL_HtmlTag::stripTags($_POST['option']);
        $this->service->editOption($optionId, $optionText);
        exit(json_encode(array('result' => 'ok')));
    }

    public function subscribe($params)
    {
        if (!OW::getRequest()->isAjax())
            exit(json_encode(array('error' => 'request must be ajax.')));
        if (!OW::getUser()->isAuthenticated())
            exit(json_encode(array('error' => 'user must login.')));
        if (!isset($_POST['questionId']))
            exit(json_encode(array('error' => 'parameters are incomplete.')));
        $questionId = UTIL_HtmlTag::stripTags($_POST['questionId']);
        $subscribed = $this->service->isSubscribedByUser(OW::getUser()->getId(), $questionId);
        if (!$subscribed) {
            $this->service->subscribe(OW::getUser()->getId(), $questionId);
            $title = OW::getLanguage()->text('iisquestions', 'toolbar_unfollow_btn');
        } else {
            $this->service->unsubscribe(OW::getUser()->getId(), $questionId);
            $title = OW::getLanguage()->text('iisquestions', 'toolbar_follow_btn');
        }
        exit(json_encode(array(
            'msg' => OW::getLanguage()->text('iisquestions', 'question_operation_successfully'),
            'title' => $title
        )));
    }

    public function deleteQuestion($params)
    {
        if (!OW::getRequest()->isAjax())
            exit(json_encode(array('error' => 'request must be ajax.')));
        if (!OW::getUser()->isAuthenticated())
            exit(json_encode(array('error' => 'user must login.')));
        if (!isset($_POST['questionId']))
            exit(json_encode(array('error' => 'parameters are incomplete.')));
        $questionId = UTIL_HtmlTag::stripTags($_POST['questionId']);
        $this->service->removeQuestion($questionId);
        exit(json_encode(array(
            'msg' => OW::getLanguage()->text('iisquestions', 'question_operation_successfully')
        )));
    }
}