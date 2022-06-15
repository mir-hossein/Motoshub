<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 3/3/18
 * Time: 10:58 AM
 */
class IISQUESTIONS_CLASS_UrlMapping
{
    public function init(){
        OW::getRouter()->addRoute(new OW_Route('iisquestions-index', 'iisquestions', 'IISQUESTIONS_CTRL_Question', 'questionHome'));
        OW::getRouter()->addRoute(new OW_Route('iisquestions-home', 'iisquestion_home/:type', 'IISQUESTIONS_CTRL_Question', 'questionHome'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-create', 'iisquestions/create', 'IISQUESTIONS_CTRL_Question', 'createQuestion'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-reload', 'iisquestions/reload', 'IISQUESTIONS_CTRL_Question', 'reloadQuestion'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-edit', 'iisquestions/edit', 'IISQUESTIONS_CTRL_Question', 'editQuestion'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-info', 'iisquestions/info', 'IISQUESTIONS_CTRL_Question', 'getQuestionInfo'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-answer', 'iisquestions/answer', 'IISQUESTIONS_CTRL_Question', 'answer'));
        OW::getRouter()->addRoute(new OW_Route('iisoption-add', 'iisquestions/options/add', 'IISQUESTIONS_CTRL_Question', 'addOption'));
        OW::getRouter()->addRoute(new OW_Route('iisoption-delete', 'iisquestions/options/delete', 'IISQUESTIONS_CTRL_Question', 'deleteOption'));
        OW::getRouter()->addRoute(new OW_Route('iisoption-edit', 'iisquestions/options/edit', 'IISQUESTIONS_CTRL_Question', 'editOption'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-subscribe', 'iisquestions/subscribe', 'IISQUESTIONS_CTRL_Question', 'subscribe'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-delete', 'iisquestions/delete', 'IISQUESTIONS_CTRL_Question', 'deleteQuestion'));
    }

    public function mobileInit(){
        OW::getRouter()->addRoute(new OW_Route('iisquestions-index', 'iisquestions', 'IISQUESTIONS_MCTRL_Question', 'questionHome'));
        OW::getRouter()->addRoute(new OW_Route('iisquestions-home', 'iisquestion_home/:type', 'IISQUESTIONS_MCTRL_Question', 'questionHome'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-create', 'iisquestions/create', 'IISQUESTIONS_MCTRL_Question', 'createQuestion'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-reload', 'iisquestions/reload', 'IISQUESTIONS_MCTRL_Question', 'reloadQuestion'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-edit', 'iisquestions/edit', 'IISQUESTIONS_MCTRL_Question', 'editQuestion'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-info', 'iisquestions/info', 'IISQUESTIONS_MCTRL_Question', 'getQuestionInfo'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-answer', 'iisquestions/answer', 'IISQUESTIONS_MCTRL_Question', 'answer'));
        OW::getRouter()->addRoute(new OW_Route('iisoption-add', 'iisquestions/options/add', 'IISQUESTIONS_MCTRL_Question', 'addOption'));
        OW::getRouter()->addRoute(new OW_Route('iisoption-delete', 'iisquestions/options/delete', 'IISQUESTIONS_MCTRL_Question', 'deleteOption'));
        OW::getRouter()->addRoute(new OW_Route('iisoption-edit', 'iisquestions/options/edit', 'IISQUESTIONS_MCTRL_Question', 'editOption'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-subscribe', 'iisquestions/subscribe', 'IISQUESTIONS_MCTRL_Question', 'subscribe'));
        OW::getRouter()->addRoute(new OW_Route('iisquestion-delete', 'iisquestions/delete', 'IISQUESTIONS_MCTRL_Question', 'deleteQuestion'));
    }
}