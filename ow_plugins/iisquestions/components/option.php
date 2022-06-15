<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 1:11 PM
 */
class IISQUESTIONS_CMP_Option extends OW_Component
{
    const UNIQUE_ID_PREFIX = 'question_option_';
    /**
     *
     * @var IISQUESTIONS_BOL_Option
     */
    private $option;
    private $answered;
    private $answerCount;
    private $percents;
    private $userIds;
    private $multiple;
    private $disabled;
    private $editMode;
    private $uniqueId;
    private $voted = false;

    public function setVoted( $voted = true )
    {
        $this->voted = (bool) $voted;
    }
    public function getOptionId()
    {
        if(isset($this->option)){
            return $this->option->getId();
        }
        return null;
    }

    public function __construct(IISQUESTIONS_BOL_Option $opt, $userId, $editMode = false, $multiple = true, $disabled = false)
    {
        parent::__construct();

        $this->option = $opt;
        $this->editMode = $editMode;
        if ($opt->getId() == 0){
            $this->uniqueId = self::UNIQUE_ID_PREFIX.$opt->questionId.'_'.IISSecurityProvider::generateUniqueId();
        }
        else{
            $this->uniqueId = self::UNIQUE_ID_PREFIX.$opt->questionId.'_'.$opt->getId();
        }
        $this->multiple = $multiple;
        $this->answerCount = IISQUESTIONS_BOL_Service::getInstance()->findAnswerCountByOption($this->option->getId());
        $this->percents = IISQUESTIONS_BOL_Service::getInstance()->findAnswerPercentByOption($this->option->questionId, $this->option->getId());
        $this->answered = IISQUESTIONS_BOL_Service::getInstance()->findAnsweredStatusByOption($userId, $this->option->getId());
        $this->disabled = $disabled;
        $this->userIds = IISQUESTIONS_BOL_Service::getInstance()->findUserAnsweredByOption($this->option->getId());
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $tplOption = array();
        $tplOption['id'] = $this->option->id;
        $tplOption['text'] = $this->option->text;
        $tplOption['count'] = $this->answerCount;
        $tplOption['percents'] = $this->percents;
        $tplOption['answered'] = $this->answered;
        $tplOption['multiple'] = $this->multiple;
        $tplOption['disabled'] = $this->disabled;
        $tplOption['editMode'] = $this->editMode;
        $tplOption['voted'] = $this->voted;

        $avatarList = new IISQUESTIONS_CMP_Answers($this->userIds, $this->answerCount);

        $tplOption['users'] = $avatarList->render();
        $idList = $this->userIds;
        $userIds = array();
        foreach ($idList as $item)
            $userIds[] = (int) $item;
        $showUsers = 'javascript: OW.showUsers('.json_encode($userIds).')';
        $this->assign('userIds', $showUsers);
        $this->assign('option', $tplOption);

        $this->assign('uniqueId', $this->uniqueId);

        OW::getDocument()->addOnloadScript($this->getJs());
    }

    function getJs()
    {
        return UTIL_JsGenerator::composeJsString('question_map[{$questionId}].addOption(new QUESTIONS_Option({$uniqueId},{$optionId},{$ajaxUrls},{$questionId},{$answered},{$multiple},{$answerError}));', array(
            'uniqueId' => $this->uniqueId,
            'questionId' => $this->option->questionId,
            'optionId' => $this->option->getId(),
            'ajaxUrls' => array('delete'=>OW::getRouter()->urlForRoute('iisoption-delete'),'answer'=>OW::getRouter()->urlForRoute('iisquestion-answer')),
            'answered' => $this->answered,
            'multiple' => $this->multiple,
            'answerError' => !OW::getUser()->isAuthenticated()? OW::getLanguage()->text('iisquestions', 'guest_answer_error') : ''
        ));
    }

    function getComponents()
    {
        return $this->components;
    }
}