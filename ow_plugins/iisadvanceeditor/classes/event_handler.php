<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisadvanceeditor.bol
 * @since 1.0
 */
class IISADVANCEEDITOR_CLASS_EventHandler
{
    const GET_MAX_SYMBOLS_COUNT = "iisadvanceeditor.get.max.symbols.count";
    private static $classInstance;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
    }

    public function init()
    {
        $eventManager = OW::getEventManager();
        $eventManager->bind(OW_EventManager::ON_FINALIZE, array($this, 'onFinalize'));
        $eventManager->bind(OW_EventManager::ON_AFTER_ROUTE, array($this, 'onAfterRoute'));
        $eventManager->bind("iisadvanceeditor.get.max.symbols.count",array($this,'onGetMaxSymbolsCount'));
    }

    public function onFinalize(OW_Event $event)
    {
        $requri = OW::getRequest()->getRequestUri();
        $config = OW::getConfig();
        $htmlDisableStatus = false;
        $mediaDisableStatus = false;
        $ck_enabled = false;
        if ((strpos($requri, 'edit') !== false && strpos($requri, 'admin') === false) ||
            (strpos($requri, 'add') !== false && strpos($requri, 'admin') === false) ||
            strpos($requri, 'create') !== false ||
            strpos($requri, 'new') !== false ||
            strpos($requri, 'admin/guideline') !== false ||
            strpos($requri, 'admin/questions') !== false ||
            strpos($requri, 'admin/edit-question') !== false ||
            strpos($requri, 'admin/mass-mailing') !== false ||
            strpos($requri, 'iisvitrin/admin') !== false ||
            strpos($requri, 'iisterms/admin') !== false ||
            strpos($requri, 'forum/topic/') !== false ||
            strpos($requri, 'iiscompetition/') !== false ||
            strpos($requri, 'iisslideshow/admin') !== false ||
            strpos($requri, 'iisticketing/ticket') !== false
        ) {

            $ck_enabled = true;
        }
        if($config->configExists('base','tf_user_custom_html_disable'))
        {
            $htmlDisableStatus= $config->getValue('base','tf_user_custom_html_disable');
        }
        if($config->configExists('base','tf_user_rich_media_disable'))
        {
            $mediaDisableStatus= $config->getValue('base','tf_user_rich_media_disable');
        }
        if ($ck_enabled === true && !$htmlDisableStatus) {
            $mediaPlugins = '';

             if(!$mediaDisableStatus && !(strpos($requri, 'event') !== false
                     || strpos($requri, 'group') !== false
                     || strpos($requri, 'video') !== false)){
                $mediaPlugins = 'ow_video,ow_image';
            }
            $more = "";
            if(!(strpos($requri, 'event') !== false ||
                 strpos($requri, 'group') !== false ||
                 strpos($requri, 'video') !== false)){
                if($mediaPlugins!=''){
                    $more = ",";
                }
                $more .= "ow_more";
            }

            //choose CKEditor language based on user's specified language
            $lang = 'fa';
            $currentLanguageTag = BOL_LanguageService::getInstance()->getCurrent()->getTag();
            if($currentLanguageTag=='en'){
                $lang = 'en';
            }

            OW::getDocument()->addStyleSheet(OW_URL_STATIC_PLUGINS . 'iisadvanceeditor/css/init.css');
            OW::getDocument()->addScript(OW_URL_STATIC_PLUGINS . 'iisadvanceeditor/js/ckeditor/ckeditor.js');
            OW::getDocument()->addScript(OW_URL_STATIC_PLUGINS . 'iisadvanceeditor/js/init.js');
            OW::getDocument()->addOnloadScript("
                window.CKCONFIG=
                {
                toolbar: 'Basic',
                customConfig : '',
                ow_imagesUrl : '" . OW::getRouter()->urlFor('BASE_CTRL_MediaPanel', 'index', array('pluginKey' => 'blog', 'id' => '__id__')) . "',
                language : '" . BOL_LanguageService::getInstance()->getCurrent()->getTag() . "',
                disableNativeSpellChecker : false,
                extraPlugins: '" . $mediaPlugins . $more .  "',
                removePlugins : 'image,contextmenu,liststyle,tabletools,tableselection',
                linkShowAdvancedTab : false,
                allowedContent:'h1 h2 h3 h4 h5 h6 ul ol blockquote div p li table tbody tr th td strong em i b u span; a[href,target]; img[src,height,width]; *[id,alt,title,dir]{*}(*); table[*]',
                autoGrow_onStartup: true,
                uiColor: '#fdfdfd',
                language: '$lang'
                };
                iisadvanceeditor_textarea_check();
            ", 900);
        }
    }

    public function onAfterRoute(OW_Event $event)
    {
        OW::getDocument()->addStyleSheet(OW_PluginManager::getInstance()->getPlugin('iisadvanceeditor')->getStaticJsUrl() . 'ckeditor/contents.css');
        OW::getDocument()->addStyleSheet(OW_PluginManager::getInstance()->getPlugin('iisadvanceeditor')->getStaticCssUrl() . 'init.css');
    }
    public function onGetMaxSymbolsCount(OW_Event $event){
        if(OW::getConfig()->configExists('iisadvanceeditor','MaxSymbolsCount'))
        {
            $msxSymbolsCount = OW::getConfig()->getValue('iisadvanceeditor','MaxSymbolsCount');
            $event->setData(array('maxSymbolsCount' => $msxSymbolsCount));
        }
    }
}