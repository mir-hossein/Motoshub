<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 1:08 PM
 */
class IISQUESTIONS_CMP_OptionList extends OW_Component
{
    private $editMode;
    /**
     * @var IISQUESTIONS_BOL_Question
     */
    private $question;
    private $userId;
    private $optionList;
    private $optionComponents = array();
    /**
     *
     * @var IISQUESTIONS_BOL_Service
     */
    protected $service;


    public function __construct($question, array $optionList, $userId, $editMode = false)
    {
        parent::__construct();

        $this->optionList = $optionList;
        $this->userId = $userId;
        $this->question = $question;
        $this->editMode = $editMode;
        $this->service = IISQUESTIONS_BOL_Service::getInstance();
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();
        $optionCmpList = array();
        $optionIdList = array();
        $isMultiple = $this->question != null ? $this->question->isMultiple : 1;
        foreach ($this->optionList as $option) {
            $optionCmp = new IISQUESTIONS_CMP_Option($option, $this->userId, $this->editMode || $option->userId == $this->userId, $isMultiple);
            $this->optionComponents[] = $optionCmp;
            $optionIdList[] = $option->id;
        }
        $answeredOptionIdList = $this->service->findUserAnsweredOptionIdList($this->userId, $optionIdList);
        foreach ( $this->optionComponents as $optionCmp )
        {
            if (in_array($optionCmp->getOptionId(), $answeredOptionIdList)){
                $optionCmp->setVoted(true);
            }
            $optionCmpList[] = $optionCmp->render();
        }
        $this->assign('list', $optionCmpList);
    }
}