<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iismobilesupport.bol
 * @since 1.0
 */
class IISMOBILESUPPORT_BOL_WebServiceQuestions
{
    private static $classInstance;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
    }

    public function prepareOptionData($option)
    {
        $answeredOneOptions=false;
        $optionCount = IISQUESTIONS_BOL_Service::getInstance()->findAnswerCountByOption($option->id);
        if ($optionCount == null) {
            $optionCount = 0;
        } else {
            $optionCount = (int) $optionCount;
        }
        $answered = IISQUESTIONS_BOL_Service::getInstance()->findAnsweredStatusByOption(OW::getUser()->getId(), $option->getId());
        if($answered) {
            $answeredOneOptions = true;
        }

        $userCanRemoveOption = IISQUESTIONS_BOL_Service::getInstance()->canCurrentUserEdit($option->questionId) || $option->userId == OW::getUser()->getId();

        return array('questionData'=>array(
            'id' => (int) $option->id,
            'userId' => (int) $option->userId,
            'questionId' => (int) $option->questionId,
            'text' => $option->text,
            'answered' => $answered,
            'count' => $optionCount,
            'canRemove' => $userCanRemoveOption,
            'timestamp' => $option->timeStamp,
        ),'answeredOneOptions'=>$answeredOneOptions);
    }

    public function addAnswer(){
        if(!IISSecurityProvider::checkPluginActive('iisquestions', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        if(!isset($_GET['optionId']) || empty($_GET['optionId'])){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $optionId = UTIL_HtmlTag::stripTags($_GET['optionId']);
        $option = IISQUESTIONS_BOL_Service::getInstance()->findOption($optionId);
        if (!isset($option)) {
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $status = 'add';
        if (!IISQUESTIONS_BOL_Service::getInstance()->findAnsweredStatusByOption(OW::getUser()->getId(), $option->getId())){
            IISQUESTIONS_BOL_Service::getInstance()->addAnswer(OW::getUser()->getId(), $option->questionId, $option->getId());
        }
        else{
            $status = 'remove';
            IISQUESTIONS_BOL_Service::getInstance()->removeAnswer(OW::getUser()->getId(), $option->getId());
        }
        $options=IISQUESTIONS_BOL_Service::getInstance()->findOptionList($option->questionId);
        $optionsData=array();
        foreach ($options as $optionItem){
            $prepareOptionData=$this->prepareOptionData($optionItem);
            $optionsData[]=$prepareOptionData['questionData'];
        }
        return array('valid' => true, 'id' => (int) $optionId, 'status' => $status,'optionsData'=>$optionsData, 'questionId' => (int) $option->questionId);
    }

    public function addQuestionOption(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisquestions', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $text = null;
        $questionId = null;

        if(isset($_POST['text'])){
            $text = $_POST['text'];
            $text = UTIL_HtmlTag::stripTags($text);
        }

        if(isset($_POST['question_id'])){
            $questionId = $_POST['question_id'];
            $questionId = UTIL_HtmlTag::stripTags($questionId);
        }

        if($questionId == null || $text == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        /** @var IISQUESTIONS_BOL_Question $question */
        $question = IISQUESTIONS_BOL_Service::getInstance()->findQuestion($questionId);
        if(!isset($question)) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $action = NEWSFEED_BOL_Service::getInstance()->findAction($question->entityType, $question->entityId);
        if($action == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $text = trim($text);

        if ($text == '') {
            return array('valid' => false, 'message' => 'empty_text');
        }

        $valid = IISQUESTIONS_BOL_Service::getInstance()->createOption($questionId, $text);
        if (!$valid) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $options = IISQUESTIONS_BOL_Service::getInstance()->findOptionList($questionId);
        $optionsData = array();
        foreach ($options as $optionItem){
            $prepareOptionData = $this->prepareOptionData($optionItem);
            $optionsData[]=$prepareOptionData['questionData'];
        }
        return array('valid' => true, 'optionsData' => $optionsData, 'questionId' => (int) $questionId);
    }

    public function removeQuestionOption(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisquestions', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $optionId = null;

        if(isset($_POST['optionId'])){
            $optionId = $_POST['optionId'];
            $optionId = UTIL_HtmlTag::stripTags($optionId);
        }

        if($optionId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $option = IISQUESTIONS_BOL_Service::getInstance()->findOption($optionId);

        if($option == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $valid = IISQUESTIONS_BOL_Service::getInstance()->removeOption($optionId);

        if ($valid == true) {
            $options = IISQUESTIONS_BOL_Service::getInstance()->findOptionList($option->questionId);
            $optionsData = array();
            foreach ($options as $optionItem) {
                $prepareOptionData = $this->prepareOptionData($optionItem);
                $optionsData[] = $prepareOptionData['questionData'];
            }

            return array('valid' => true, 'optionsData' => $optionsData, 'optionId' => $optionId, 'questionId' => (int) $option->questionId);
        }

        return array('valid' => false, 'message' => 'authorization_error');
    }
}