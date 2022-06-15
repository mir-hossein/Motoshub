<?php

class IISPROFILEMANAGEMENT_BOL_Service
{
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

    public function onBeforeDocumentRender()
    {
        $jsDir = OW::getPluginManager()->getPlugin('iisprofilemanagement')->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir.'iisprofilemanagement.js');
    }

    public function onBeforeProfileEditRenderJsFunction(){
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iisprofilemanagement')->getStaticCssUrl().'iisprofilemanagement.css');
        $jsDir = OW::getPluginManager()->getPlugin('iisprofilemanagement')->getStaticJsUrl();
        $language = OW::getLanguage();
        $language->addKeyForJs('base', 'edit_index');
        $language->addKeyForJs('admin', 'change_password');
        OW::getDocument()->addOnloadScript("if($('#edit_profile_link').length>0 && $('#edit_profile_link')[0]['href']){
         var profileEditUrl=$('#edit_profile_link')[0]['href']; 
         if (!$('.ow_bl.ow_profile_action_toolbar.clearfix.ow_small.ow_left').length>0){
            $('.ow_canvas .ow_content').prepend('<div class=\"ow_profile_gallery_action_toolbar ow_profile_action_toolbar_wrap clearfix ow_stdmargin\"><ul class=\"ow_bl ow_profile_action_toolbar clearfix ow_small ow_left\"></div>');
         }
         $('.ow_bl.ow_profile_action_toolbar.clearfix.ow_small.ow_left').append('<li><a href='+profileEditUrl+'>".OW::getLanguage()->text('base','edit_index')."</a></li>');
        }
        if($('#change_password_button').length>0){
            $('.ow_bl.ow_profile_action_toolbar.clearfix.ow_small.ow_left').append('<li><a id=change_password_button>".OW::getLanguage()->text('admin','change_password')."</a></li>');
        }
        ");

    }

}