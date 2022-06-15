<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 3/4/18
 * Time: 8:57 AM
 */
class IISQUESTIONS_CLASS_CreateQuestionForm extends Form
{
    const ALLOW_ADD_OPTION_FIELD_NAME = 'allowAddOptions';
    const OPTIONS_FIELD_NAME = 'options';
    const CONTEXT_FIELD_NAME = 'question_context';
    const CONTEXT_ID_FIELD_NAME = 'question_context_id';
    const MULTIPLE_ID_FIELD_NAME = 'question_multiple';
    const SAVE_FIELD_NAME = 'save';
    const FORM_NAME = 'create_question_form';
    protected $ajaxUrl;

    /**
     * IISQUESTIONS_CLASS_CreateQuestionForm constructor.
     * @param string $ajaxUrl
     * @param $context
     * @param $contextId
     */
    public function __construct($ajaxUrl, $context, $contextId)
    {
        parent::__construct(self::FORM_NAME);
        $this->ajaxUrl = $ajaxUrl;
        $language = OW::getLanguage();

        $this->setAjax(true);
        $this->setAction($ajaxUrl);
        if ($context != 'groups') {
            $field = new Selectbox(self::ALLOW_ADD_OPTION_FIELD_NAME);
            $field->setLabel(OW::getLanguage()->text('iisquestions', 'question_add_allow_add_opt'));
            $options = array();
            $options[IISQUESTIONS_BOL_Service::PRIVACY_EVERYBODY] = OW::getLanguage()->text("privacy", "privacy_everybody");
            $options[IISQUESTIONS_BOL_Service::PRIVACY_ONLY_FOR_ME] = OW::getLanguage()->text("privacy", "privacy_only_for_me");
            $options[IISQUESTIONS_BOL_Service::PRIVACY_FRIENDS_ONLY] = OW::getLanguage()->text("friends", "privacy_friends_only");
            $field->setHasInvitation(false);
            $field->setOptions($options);
            $field->addAttribute('class', 'statusPrivacy');
            $field->setValue(IISQUESTIONS_BOL_Service::PRIVACY_ONLY_FOR_ME);
            $this->addElement($field);
        }

        $field = new TextField(self::OPTIONS_FIELD_NAME);
        $field->setHasInvitation(true);
        $field->setInvitation($language->text('iisquestions', 'question_add_option_inv'));
        $this->addElement($field);

        $contextField = new HiddenField(self::CONTEXT_FIELD_NAME);
        $contextField->setValue($context);
        $this->addElement($contextField);

        $contextIdField = new HiddenField(self::CONTEXT_ID_FIELD_NAME);
        $contextIdField->setValue($contextId);
        $this->addElement($contextIdField);

        $submit = new Submit(self::SAVE_FIELD_NAME);
        $submit->setValue($language->text('iisquestions', 'question_add_save'));
        $this->addElement($submit);

        if (!OW::getRequest()->isAjax()) {
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_question_empty');
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_question_min_length');
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_question_max_length');
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_question_two_apt_required');
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_question_dublicate_option');
            OW::getLanguage()->addKeyForJs('iisquestions', 'feedback_option_max_length');
            $this->initJsResponder();
        }
    }

    public function initJsResponder()
    {
        $js = UTIL_JsGenerator::composeJsString(
            'createQuestionForm = new CreateQuestionForm({$formName},{$url},{$infoUrl});',
            array(
                'formName' => self::FORM_NAME,
                'url' => $this->ajaxUrl,
                'infoUrl' => OW::getRouter()->urlForRoute('iisquestion-info')
            )
        );
        OW::getDocument()->addOnloadScript($js);
    }
}