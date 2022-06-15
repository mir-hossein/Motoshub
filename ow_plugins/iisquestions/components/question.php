<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 1:07 PM
 */
class IISQUESTIONS_CMP_Question extends OW_Component
{
    const UNIQUE_ID_PREFIX = 'question_';

    private $editMode;
    /**
     * @var IISQUESTIONS_BOL_Question
     */
    private $question;
    public $questionIdtmp;
    private $uniqueId;
    private $userId;
    private $options;

    public function __construct($question, $userId, $editMode = false, $questionIdtmp = false, $options = '')
    {
        parent::__construct();

        $this->userId = $userId;
        if (isset($question)){
            $this->question = $question;
            $this->uniqueId = self::UNIQUE_ID_PREFIX .$this->question->getId();
        }
        else if(isset($questionIdtmp) && $questionIdtmp != false){
            $this->questionIdtmp = $questionIdtmp;
            $this->options = $options;
            $this->uniqueId = self::UNIQUE_ID_PREFIX .$this->questionIdtmp ;
        }
        else{
            $this->questionIdtmp = IISSecurityProvider::generateUniqueId();
            $this->uniqueId = self::UNIQUE_ID_PREFIX .$this->questionIdtmp ;
        }
        $this->editMode = $editMode;
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $language = OW::getLanguage();
        $language->addKeyForJs('base', 'are_you_sure');

        $this->assign('uniqueId', $this->uniqueId);
        if (isset($this->question)){
            $optionList = IISQUESTIONS_BOL_Service::getInstance()->findOptionList($this->question->getId());

        }
        else{
            $this->options = json_decode($this->options);
            $optionList = array();
            if ($this->options != null && $this->options != ''){
                foreach ($this->options as $text){
                    $option = new IISQUESTIONS_BOL_Option();
                    $option->text = $text;
                    $option->questionId = $this->questionIdtmp;
                    $option->userId = $this->userId;
                    $option->timeStamp = time();
                    $optionList[] = $option;

                }
            }

        }

        $this->addComponent('optionList', new IISQUESTIONS_CMP_OptionList($this->question, $optionList, $this->userId, $this->editMode));
        if (!isset($this->question) && OW::getUser()->isAuthenticated()){
            $this->addComponent('addOption', new IISQUESTIONS_CMP_AddOption($this->questionIdtmp));
        }
        else if (IISQUESTIONS_BOL_Service::getInstance()->canCurrentUserAddOption($this->question->getId())){
            $this->addComponent('addOption', new IISQUESTIONS_CMP_AddOption($this->question->getId()));
        }

        $js = $this->getJs();
        OW::getDocument()->addOnloadScript($js);
    }

    public function getJs()
    {
        $questionId = isset($this->question) ? $this->question->getId() : $this->questionIdtmp;
        $js = UTIL_JsGenerator::composeJsString('question_map[{$questionId}] = new QUESTIONS_Question({$uniqueId},{$questionId},{$ajaxUrl});', array(
            'uniqueId' => $this->uniqueId,
            'questionId' => $questionId,
            'ajaxUrl' => OW::getRouter()->urlForRoute('iisquestion-reload'),
        ));
        return $js;
    }

    function getComponents()
    {
        return $this->components;
    }
}