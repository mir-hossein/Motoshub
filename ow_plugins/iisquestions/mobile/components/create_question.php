<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 3/4/18
 * Time: 8:52 AM
 */
class IISQUESTIONS_MCMP_CreateQuestion extends OW_MobileComponent
{
    const UNIQUE_ID_PREFIX = 'create_question_cmp';
    protected $needsPrivacy = true;
    protected $context;
    protected $contextId;

    public function __construct($context, $contextId)
    {
        parent::__construct();
        $this->context = $context;
        $this->contextId = $contextId;
        if (!OW::getUser()->isAuthenticated() || !IISQUESTIONS_BOL_Service::getInstance()->canUserCreate(OW::getUser()->getId())) {
            $this->setVisible(false);
            return;
        }

        $template = OW::getPluginManager()->getPlugin('iisquestions')->getMobileCmpViewDir() . 'create_question.html';
        $this->setTemplate($template);
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $uniqueId = self::UNIQUE_ID_PREFIX;
        $this->assign('uniqueId', $uniqueId);
        $url = OW::getRouter()->urlForRoute('iisquestion-create');
        $form = new IISQUESTIONS_CLASS_CreateQuestionForm($url, $this->context, $this->contextId);
        if ($this->context == 'groups')
            $this->assign('addOptions',false);
        else
            $this->assign('addOptions',true);
        $this->addForm($form);
    }
}