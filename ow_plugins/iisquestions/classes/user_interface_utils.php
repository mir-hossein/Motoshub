<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 3/4/18
 * Time: 9:21 AM
 */
class IISQUESTIONS_CLASS_UserInterfaceUtils
{
    private static $INSTANCE;

    public static function getInstance()
    {
        if (!isset(self::$INSTANCE))
            self::$INSTANCE = new self();
        return self::$INSTANCE;
    }

    /**
     * @param $document
     * @param bool $ajax
     * @param bool $mobile
     */
    public function addStaticResources($document, $ajax = false, $mobile = false)
    {
        $plugin = OW::getPluginManager()->getPlugin('iisquestions');

        $staticUrl = $plugin->getStaticUrl();
        if ($mobile)
            $scriptUrl = $staticUrl . 'js/questions_mobile.js';
        else
            $scriptUrl = $staticUrl . 'js/questions.js';
        $styleUrl = $staticUrl . 'css/questions.css';
        $document->addOnloadScript(UTIL_JsGenerator::composeJsString('window.question_info_url = "'. OW::getRouter()->urlForRoute('iisquestion-info').'";'));
        if (!$ajax) {
            $document->addScript($scriptUrl);
            $document->addStyleSheet($styleUrl);
        } else {
            $document->addOnloadScript(UTIL_JsGenerator::composeJsString('
                if ( !window.QUESTIONS_Loaded )
                {

                    OW.addScriptFiles([{$scriptUrl}], function(){
                        if ( window.EQAjaxLoadCallbacksRun )
                        {
                            window.EQAjaxLoadCallbacksRun();
                        }
                    });
                    OW.addCssFile({$styleUrl});

                 }
            ', array(
                'styleUrl' => $styleUrl,
                'scriptUrl' => $scriptUrl
            )));
        }

        $document->addOnloadScript(UTIL_JsGenerator::composeJsString('
                mobile = {$mobile};
            ', array(
            'mobile' => $mobile
        )));

        OW::getLanguage()->addKeyForJs('iisquestions', 'selector_title_friends');
        OW::getLanguage()->addKeyForJs('iisquestions', 'selector_title_users');
        OW::getLanguage()->addKeyForJs('iisquestions', 'followers_fb_title');
        OW::getLanguage()->addKeyForJs('base', 'ajax_floatbox_users_title');
        OW::getLanguage()->addKeyForJs('iisquestions', 'question_add_option_inv');
        OW::getLanguage()->addKeyForJs('iisquestions', 'toolbar_unfollow_btn');
        OW::getLanguage()->addKeyForJs('iisquestions', 'toolbar_follow_btn');
    }
}