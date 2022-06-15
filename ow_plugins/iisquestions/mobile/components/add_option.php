<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 1:09 PM
 */
class IISQUESTIONS_MCMP_AddOption extends OW_Component
{
    private $uniqueId;
    private $questionId;
    const UNIQUE_ID_PREFIX = 'question_add_option_';

    public function __construct($questionId)
    {
        parent::__construct();
        $this->questionId = $questionId;
        $this->uniqueId = self::UNIQUE_ID_PREFIX . $questionId;
        $this->setTemplate(OW::getPluginManager()->getPlugin('iisquestions')->getMobileCmpViewDir() . 'add_option.html');
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();
        $this->assign('uniqueId', $this->uniqueId);

        OW::getDocument()->addOnloadScript($this->getJs());
    }

    public function getJs()
    {
        return UTIL_JsGenerator::composeJsString('question_map[{$questionId}].setAddOption(new QUESTIONS_AddOption({$uniqueId},{$questionId},{$ajaxUrl}));', array(
            'uniqueId' => $this->uniqueId,
            'questionId' => $this->questionId,
            'ajaxUrl' => OW::getRouter()->urlForRoute('iisoption-add'),
        ));
    }
}