<?php

/**
 * Copyright (c) 2016, Milad Heshmati
 * All rights reserved.
 */

/**
 * @author Milad Heshmati <milad.heshmati@gmail.com>
 * @package ow_plugins.iiswidgetplus
 * @since 1.0
 */
final class IISWIDGETPLUS_BOL_Service
{
    private function __construct()
    {
    }

    /***
     * @var
     */
    private static $classInstance;

    /***
     * @return IISWIDGETPLUS_BOL_Service
     */
    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function beforeNewsListViewRender(OW_EVENT $event){
        $param = $event->getParams();
        if(isset($param['newsView'])){
            OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticJsUrl().'iiswidgetplus.js');
            $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));

            if(isset($mobileEvent->getData()['isMobileVersion'])&& $mobileEvent->getData()['isMobileVersion']==true) {
                $imgInfoSrc = OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticUrl() . 'img/info.svg';
                OW::getDocument()->addOnloadScript('addChangeVisibilityOfNewsWidgets("' . $imgInfoSrc . '")');
            }
        }
    }

    public function beforeGroupListViewRender(OW_EVENT $event){
        $param = $event->getParams();
        if(isset($param['GroupListView'])){
            OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticJsUrl().'iiswidgetplus.js');

            $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
            if(isset($mobileEvent->getData()['isMobileVersion'])&& $mobileEvent->getData()['isMobileVersion']==true) {
                $plugin = BOL_PluginDao::getInstance()->findPluginByKey('iisgroupsplus');
                $iisgroupplus=isset($plugin) && $plugin->isActive();
                $imgInfoSrc = OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticUrl() . 'img/info.svg';
                OW::getDocument()->addOnloadScript('addChangeVisibilityOfGroupListWidgets("' . $imgInfoSrc . '","'.$iisgroupplus.'")');
            }
        }
    }

    public function beforeGroupViewRender(OW_EVENT $event){
        $param = $event->getParams();
        if (isset($param['groupView'])) {
            OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticJsUrl() . 'iiswidgetplus.js');

            $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION, array('check' => true)));
            if (isset($mobileEvent->getData()['isMobileVersion']) && $mobileEvent->getData()['isMobileVersion'] == true) {
                $imgInfoSrc = OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticUrl() . 'img/info.svg';
                OW::getDocument()->addOnloadScript('addChangeVisibilityOfGroupWidgets("' . $imgInfoSrc . '")');

                $imgInviteSrc = OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticUrl() . 'img/invite.svg';
                OW::getDocument()->addStyleDeclaration("input#GROUPS_InviteLink {background-image: url('" . $imgInviteSrc . "');}");

                $imgForumSrc = OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticUrl() . 'img/forum.svg';
                OW::getDocument()->addStyleDeclaration("input#GROUPS_ForumLink {background-image: url('" . $imgForumSrc . "');}");
            }
        }
    }
    public function addWidgetJS()
    {
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticJsUrl() . 'highlight.pack.js');
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticJsUrl() . 'jquery.cookie.js');
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticJsUrl() . 'jquery.collapsible.js');
        OW::getDocument()->addOnloadScript("
        $.fn.slideFadeToggle = function(speed, easing, callback) {
            return this.animate({opacity: 'toggle', height: 'toggle'}, speed, easing, callback);
        };

        $('.page_collapsible').collapsible({
            defaultOpen: 'body_open',
            cookieName: 'body2',
            speed: 'slow',
            animateOpen: function (elem, opts) { 
                elem.next().slideFadeToggle(opts.speed);
            },
            animateClose: function (elem, opts) { 
                elem.next().slideFadeToggle(opts.speed);
            },
            loadOpen: function (elem) { 
                elem.next().show();
            },
            loadClose: function (elem, opts) {
                elem.next().hide();
            }

        });
        ");

        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticCssUrl() . 'iiswidgetplus.css');
        OW::getDocument()->addOnloadScript("$('.owm_group_view .owm_brief_info .owm_box span.page_collapsible').click();");
        OW::getDocument()->addOnloadScript("$('.owm_group_view .owm_view_file_list .owm_box span.page_collapsible').click();");
        OW::getDocument()->addOnloadScript("$('.owm_group_view .owm_view_user_list .owm_box span.page_collapsible').click();");
        OW::getDocument()->addOnloadScript("$('.owm_iisnews_page .owm_iisnews_widgets .owm_box span.page_collapsible').click();");
        OW::getDocument()->addOnloadScript("$('.owm_group_view .owm_iisreport_widget  .owm_box span.page_collapsible').click();");

        OW::getDocument()->addOnloadScript("
                $('.owm_group_view .owm_brief_info .owm_box .owm_box_cap').click(function(){\$('.owm_group_view .owm_brief_info .owm_box span.page_collapsible').click()});
                 ");
        OW::getDocument()->addOnloadScript("
                $('.owm_group_view .owm_view_file_list .owm_box .owm_box_cap').click(function(){\$('.owm_group_view .owm_view_file_list .owm_box span.page_collapsible').click()});
                 ");
        OW::getDocument()->addOnloadScript("
                $('.owm_group_view .owm_view_user_list .owm_box .owm_box_cap').click(function(){\$('.owm_group_view .owm_view_user_list .owm_box span.page_collapsible').click()});
                 ");

        OW::getDocument()->addOnloadScript("
                $('.owm_group_view .owm_iisreport_widget  .owm_box .owm_box_cap').click(function(){\$('.owm_group_view .owm_iisreport_widget  .owm_box span.page_collapsible').click()});
        ");

        $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
        if(isset($mobileEvent->getData()['isMobileVersion'])&& $mobileEvent->getData()['isMobileVersion']==true) {
            $js = '
            var enable_send_btn = function(text, btn_sel){
                if(text == ""){
                    $(btn_sel).removeClass("owm_send_btn_available");
                }else{
                    $(btn_sel).addClass("owm_send_btn_available");
                }
            }
            $(\'#mailboxConversationFooter #newMessageText\').on(\'input\', function(){
                enable_send_btn(this.value, "#mailboxConversationFooter #newMessageSendBtn");
            });
            $(\'*[name=commentText]\').on(\'input\', function(){
                enable_send_btn(this.value, ".owm_newsfeed_comments input[name=comment-submit]");
            });
            ';
            OW::getDocument()->addScriptDeclaration( $js );

            $defaultImg = OW::getPluginManager()->getPlugin('iiswidgetplus')->getStaticUrl() . 'img/default.svg';
            OW::getDocument()->addOnloadScript("$('#newMessageText').parent().prepend($('#newMessageSendBtn'));");
            OW::getDocument()->addOnloadScript("$('#newMessageText').parent().prepend($('#mailbox_att_btn_c'));");
            OW::getDocument()->addOnloadScript("$(\".owm_mail_add_name img\").attr(\"onerror\",\"this.src='".$defaultImg."'\");");
        }
    }
}
