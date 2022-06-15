<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 3/4/18
 * Time: 8:57 AM
 */
class IISQUESTIONS_CLASS_AddOptionForm extends Form
{
    const FORM_NAME = 'add_option_form';

    /**
     * IISQUESTIONS_CLASS_CreateQuestionForm constructor.
     * @param $questionId
     *  @param $newQuestion
     */
    public function __construct($questionId, $newQuestion)
    {
        parent::__construct(self::FORM_NAME);
        $language = OW::getLanguage();

        $this->setAjax();
        $this->setAjaxResetOnSuccess(false);
        $this->setAction(OW::getRouter()->urlForRoute('iisoption-add'));

        $field = new TextField('option');
        $field->setRequired();
        $field->setHasInvitation(true);
        $field->setInvitation($language->text('iisquestions', 'question_add_option_inv'));
        $this->addElement($field);

        $field = new HiddenField('questionId');
        $field->setValue($questionId);
        $this->addElement($field);

        $field = new HiddenField('newQuestion');
        if ($newQuestion){
            $field->setValue('true');
            $this->bindJsFunction(self::BIND_SUCCESS, 'function(data){question_map["'.$questionId.'"].edit(data)}');
        }
        else{
            $field->setValue('false');
            $this->bindJsFunction(self::BIND_SUCCESS, 'function(data){question_map['.$questionId.'].edit(data)}');
        }
        $this->addElement($field);

        $submit = new Submit('save');
        $submit->setValue($language->text('iisquestions', 'attachments_take_save_label'));
        $this->addElement($submit);

        if (!OW::getRequest()->isAjax()) {
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_question_empty');
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_question_min_length');
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_question_max_length');
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_question_two_apt_required');
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_question_dublicate_option');
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_option_max_length');
        }
    }
}