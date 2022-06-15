<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 3/4/18
 * Time: 8:57 AM
 */
class IISQUESTIONS_CLASS_EditOptionForm extends Form
{
    const FORM_NAME = 'edit_option_form';

    /**
     * IISQUESTIONS_CLASS_CreateQuestionForm constructor.
     * @param IISQUESTIONS_BOL_Option $option
     */
    public function __construct($option)
    {
        parent::__construct(self::FORM_NAME);
        $language = OW::getLanguage();

        $this->setAjax();
        $this->setAjaxResetOnSuccess(false);
        $this->setAction(OW::getRouter()->urlForRoute('iisoption-edit'));
        $this->bindJsFunction(self::BIND_SUCCESS, 'function(data){question_map['.$option->questionId.'].edit(data)}');

        $field = new TextField('option');
        $field->setRequired();
        $field->setValue($option->text);
        $field->setHasInvitation(true);
        $field->setInvitation($language->text('iisquestions', 'question_add_option_inv'));
        $this->addElement($field);

        $field = new HiddenField('optionId');
        $field->setValue($option->getId());
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