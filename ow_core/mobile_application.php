<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_core
 * @method static OW_MobileApplication getInstance()
 * @since 1.0
 */
class OW_MobileApplication extends OW_Application
{
    use OW_Singleton;

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->context = self::CONTEXT_MOBILE;
    }

    /**
     * ---------
     */
    public function handleRequest()
    {
        $baseConfigs = OW::getConfig()->getValues('base');

        //members only
        if ( (int) $baseConfigs['guests_can_view'] === BOL_UserService::PERMISSIONS_GUESTS_CANT_VIEW && !OW::getUser()->isAuthenticated() )
        {
            $attributes = array(
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'BASE_MCTRL_User',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'standardSignIn'
            );

            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.members_only', $attributes);
            $this->addCatchAllRequestsException('base.members_only_exceptions', 'base.members_only');
        }

        //splash screen
        if ( (bool) OW::getConfig()->getValue('base', 'splash_screen') && !isset($_COOKIE['splashScreen']) )
        {
            $attributes = array(
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'BASE_MCTRL_BaseDocument',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'splashScreen',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_REDIRECT => true,
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_JS => true,
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ROUTE => 'base_page_splash_screen'
            );

            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.splash_screen', $attributes);
            $this->addCatchAllRequestsException('base.splash_screen_exceptions', 'base.splash_screen');
        }

        // password protected
        if ( (int) $baseConfigs['guests_can_view'] === BOL_UserService::PERMISSIONS_GUESTS_PASSWORD_VIEW && !OW::getUser()->isAuthenticated() && !isset($_COOKIE['base_password_protection'])
        )
        {
            $attributes = array(
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'BASE_MCTRL_BaseDocument',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'passwordProtection'
            );

            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.password_protected', $attributes);
            $this->addCatchAllRequestsException('base.password_protected_exceptions', 'base.password_protected');
        }

        // maintenance mode
        if ( (bool) $baseConfigs['maintenance'] && !OW::getUser()->isAdmin() )
        {
            $attributes = array(
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'BASE_MCTRL_BaseDocument',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'maintenance',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_REDIRECT => true
            );

            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.maintenance_mode', $attributes);
            $this->addCatchAllRequestsException('base.maintenance_mode_exceptions', 'base.maintenance_mode');
        }


        try
        {
            OW::getRequestHandler()->dispatch();
        }
        catch ( RedirectException $e )
        {
            $this->redirect($e->getUrl(), $e->getRedirectCode());
        }
        catch ( InterceptException $e )
        {
            OW::getRequestHandler()->setHandlerAttributes($e->getHandlerAttrs());
            $this->handleRequest();
        }
    }

    /**
     * Method called just before request responding.
     */
    public function finalize()
    {
        $document = OW::getDocument();

        $meassages = OW::getFeedback()->getFeedback();

        foreach ( $meassages as $messageType => $messageList )
        {
            foreach ( $messageList as $message )
            {
                $document->addOnloadScript("OWM.message(" . json_encode($message) . ", '" . $messageType . "');");
            }
        }

        $event = new OW_Event(OW_EventManager::ON_FINALIZE);
        OW::getEventManager()->trigger($event);
    }

    /**
     * System method. Don't call it!!!
     */
    public function onBeforeDocumentRender()
    {
        $this->check_file_size_js();
        $document = OW::getDocument();

        $document->addStyleSheet(OW::getPluginManager()->getPlugin('base')->getStaticCssUrl() . 'mobile.css', 'all', -100);
        $document->addStyleSheet(OW::getThemeManager()->getCssFileUrl(true), 'all', (-90));

        if ( OW::getThemeManager()->getCurrentTheme()->getDto()->getCustomCssFileName() !== null )
        {
            $document->addStyleSheet(OW::getThemeManager()->getThemeService()->getCustomCssFileUrl(OW::getThemeManager()->getCurrentTheme()->getDto()->getKey(),
                    true));
        }

        $language = OW::getLanguage();
        OW::getLanguage()->addKeyForJs('base', 'text_copied_to_clipboard');
        OW::getLanguage()->addKeyForJs('base', 'copy');

        if ( $document->getTitle() === null )
        {
            $document->setTitle($language->text('mobile', 'page_default_title'));
        }

        if ( $document->getDescription() === null )
        {
            $document->setDescription($language->text('mobile', 'page_default_description'));
        }

        if ( $document->getHeading() === null )
        {
            $document->setHeading($language->text('mobile', 'page_default_heading'));
        }

        $document->addMetaInfo('viewport', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
    }

    /***
     *
     */
    public function check_file_size_js(){
        $maxUploadSize = OW::getConfig()->getValue('base', 'attch_file_max_size_mb');
        $validFileExtensions = json_decode(OW::getConfig()->getValue('base', 'attch_ext_list'), true);
        $js ="
function getAttachmentExtension(filename){
    var ext = '';
    if(filename.lastIndexOf('.')>0){
        ext = filename.substr(filename.lastIndexOf('.')+1);
    }
    if(ext===''){
        var match = document.cookie.match(new RegExp('(^| )UsingMobileApp=([^;]+)'));
        if (match && match[2]==='android')
        {
            ext = 'mp3';
        }
    }
    return ext;
}
var checkFileInputSizeAndExtension = function(input) {
    if(input.files.length==0)
    {
        return true;
    }
    var ext = getAttachmentExtension(input.files[0].name);
    var validFileExtensions = ".json_encode($validFileExtensions).";
    function jsItemInArrayExists(arr, obj) {
        for(var i=0; i<arr.length; i++) {
            if (arr[i] == obj) return true;
        }
        return false;
    }
    if(!jsItemInArrayExists(validFileExtensions,ext.toLowerCase())){
        OWM.error('".OW::getLanguage()->text('base', 'upload_file_extension_is_not_allowed')."');
        return false;
    }

    if(input.files[0].size>".$maxUploadSize."*1024*1024){
        OWM.error('".OW::getLanguage()->text('base', 'upload_file_max_upload_filesize_error')."');
        return false;
    }
};

$(function() {
    var inputFileItems;
    var change_function_wrapper = function(e) {
        //console.log($(this));
        if($(this).val()) {
            if(checkFileInputSizeAndExtension) {
                var att_element = $(this)[0];
                if(false){
                    var before_id = $(this).attr('id');
                    $(this).attr('id', 'input_uploader_tmp');
                    id = $(this).attr('id');
                    var att_element = document.getElementById(id);
                    if(before_id==undefined)
                        $(this).removeAttr('id');
                    else
                        $(this).attr('id', before_id);
                }
                if(att_element != null){
                    if (checkFileInputSizeAndExtension(att_element)==false) {
                        att_element.value = '';
                        var inputFile = $(this);
                        inputFile.val('');
                        var before_events = jQuery._data(att_element).events;
                        //inputFile.replaceWith(cloned = inputFile.clone(true));
                        e.preventDefault(e);
                        console.log('file upload prevented!');
                        return false;
                    }
                }
            }
            $(this).trigger('changeOthers');
        }
    };
    setInterval(function() {
        var text_input_selector = 'input[type=file][name!=videoUpload]';
        inputFileItems = $(text_input_selector).length;
        $(text_input_selector).each(function(i,elem){
            if($(elem).attr('changeFunctionSet')==undefined){
                $(elem).attr('changeFunctionSet', true);
                if(jQuery._data(elem).events == undefined){
                    $(elem).on('change',change_function_wrapper);
                }else{
                    jQuery._data(elem).events.changeOthers = jQuery._data(elem).events.change;
                    jQuery._data(elem).events.change = jQuery._data(elem).events.changeX;
                    $(elem).on('change',change_function_wrapper);
                }
            }
        });
    }, 1000);
});

$('form[name=newsfeed_update_status],form[name=newMessageForm],form[name=newMailMessageForm],form[name=fileUploadForm]').submit(function(e) {
	$(this).ajaxSubmit({
        beforeSubmit: function() {
            if($('#progress-div').length==0)
                $('body').append('<div id=\"progress-div\"><div id=\"progress-bar\"></div></div>');
            $('#progress-div').attr('style',
                'border: rgba(121, 228, 125, 0.52) 1px solid;  background-color: rgba(212, 255, 218, 0.84);\
                padding: 3px 3px;   margin: 0px 0px;    position: fixed;  z-index: 10000;\
                right: 0px;    top: 0px;   left: 0px; direction:initial;');
            $('#progress-bar').attr('style',
                'padding: 4px 0px;    margin: 0px;    height: 18px;    text-align: center;    width: 0%;\
                -webkit-transition: width .3s;    -moz-transition: width .3s;    transition: width .3s;\
                -moz-border-radius: 4px;     -webkit-border-radius: 4px;    border-radius: 4px;\
                color: white;    background-color: rgb(43,194,83);  position: relative;  \
                box-shadow: inset 0 2px 9px rgba(255,255,255,0.3), inset 0 -2px 6px rgba(0,0,0,0.4);');
            $('#progress-bar #progress-status').attr('style', 'height:inherit; overflow:hidden');
            
            $('#progress-bar').width('0%');
            $('#progress-div').css({'display': 'block'});
        },
        uploadProgress: function (event, position, total, percentComplete){
            $('#progress-bar').width(percentComplete + '%');
            $('#progress-bar').html('<div id=\"progress-status\">' + percentComplete +' %</div>');
        },
        success:function (){
            $('#progress-div').css({'display':'none'});
            $('#progress-bar').width('0%');
        },
        error: function(e){
            $('#progress-div').css({'display': 'none'});
            $('#progress-bar').width('0%');
        },
        complete:function(xhr) {
            $('#progress-div').css({'display':'none'});
            $('#progress-bar').width('0%');
            $('body').append(xhr.responseText);
        },
    });
    return false;
})
            ";
        OW::getDocument()->addScriptDeclarationBeforeIncludes( $js);
    }

    public function activateMenuItem()
    {
        
    }

    protected function newDocument()
    {
        $language = BOL_LanguageService::getInstance()->getCurrent();
        $document = new OW_HtmlDocument();
        $document->setTemplate(OW::getThemeManager()->getMasterPageTemplate('mobile_html_document'));
        $document->setCharset('UTF-8');
        $document->setMime('text/html');
        $document->setLanguage($language->getTag());

        if ( $language->getRtl() )
        {
            $document->setDirection('rtl');
        }
        else
        {
            $document->setDirection('ltr');
        }

        if ( (bool) OW::getConfig()->getValue('base', 'favicon') )
        {
            $document->setFavicon(OW::getPluginManager()->getPlugin('base')->getUserFilesUrl() . 'favicon.ico');
        }

        $document->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'jquery.min.js',
            'text/javascript', (-100));
        $document->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'jquery.form.min.js',
            'text/javascript', (-100));
        $document = $this->includeJConfirm($document);

        $document->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'mobile.js', 'text/javascript', (-50));
        $document->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'clipboard.js');
        OW::getEventManager()->bind(OW_EventManager::ON_AFTER_REQUEST_HANDLE, array($this, 'onBeforeDocumentRender'));

        return $document;
    }

    protected function initRequestHandler()
    {
        OW::getRequestHandler()->setStaticPageAttributes('BASE_MCTRL_BaseDocument', 'staticDocument');
    }

    protected function findAllStaticDocs()
    {
        return BOL_NavigationService::getInstance()->findAllMobileStaticDocuments();
    }

    protected function findFirstMenuItem( $availableFor )
    {
        return BOL_NavigationService::getInstance()->findFirstLocal($availableFor, OW_Navigation::MOBILE_TOP);
    }

    protected function getSiteRootRoute()
    {
        return new OW_Route('base_default_index', '/', 'BASE_MCTRL_WidgetPanel', 'index');
    }

    protected function getMasterPage()
    {
        return new OW_MobileMasterPage();
    }
}
