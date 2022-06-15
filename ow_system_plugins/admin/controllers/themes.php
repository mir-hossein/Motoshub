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
 * Themes manage admin controller class.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.admin.controllers
 * @since 1.0
 */
class ADMIN_CTRL_Themes extends ADMIN_CTRL_StorageAbstract
{
    const DELETE_ALL_THEMES_KEY = 'all_themes';

    /**
     * @var BASE_CMP_ContentMenu
     */
    protected $menu;

    /**
     * @var OW_Feedback
     */
    protected $feedback;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultAction("chooseTheme");
        $this->feedback = OW::getFeedback();
    }

    public function init()
    {
        $language = OW::getLanguage();
        $pageActions = array("choose_theme", "add_theme");
        $menuItems = array();

        foreach ( $pageActions as $key => $item )
        {
            $menuItem = new BASE_MenuItem();
            $menuItem->setKey($item)->setLabel($language->text("admin", "themes_menu_item_" . $item))->setOrder($key)->setUrl(OW::getRouter()->urlFor(__CLASS__,
                    $item));
            $menuItems[] = $menuItem;
        }

        $this->menu = new BASE_CMP_ContentMenu($menuItems);
        $this->addComponent("contentMenu", $this->menu);
        $this->setPageHeading($language->text("admin", "themes_choose_page_title"));
    }

    public function chooseTheme()
    {
        $language = OW::getLanguage();
        $router = OW::getRouter();

        $this->themeService->updateThemeList();
        $this->themeService->updateThemesInfo();
        $themes = $this->themeService->findAllThemes();
        $themesInfo = array();

        $activeTheme = OW::getThemeManager()->getSelectedTheme()->getDto()->getKey();
        $checkThemes=OW::getEventManager()->trigger(new OW_Event('themes.check.features.availability',array('check'=>true)));
        /* @var $theme BOL_Theme */
        foreach ( $themes as $theme )
        {
            $themesInfo[$theme->getKey()] = array(
                "key" => $theme->getKey(),
                "title" => $theme->getTitle(),
                "iconUrl" => $this->themeService->getStaticUrl($theme->getKey()) . BOL_ThemeService::ICON_FILE,
                "previewUrl" => $this->themeService->getStaticUrl($theme->getKey()) . BOL_ThemeService::PREVIEW_FILE,
                "active" => ( $theme->getKey() == $activeTheme ),
                "changeUrl" => $router->urlFor(__CLASS__, "changeTheme",
                    array(BOL_StorageService::URI_VAR_KEY => $theme->getKey())),
                "update_url" => ( ((int) $theme->getUpdate() == 1) ) ? $router->urlFor("ADMIN_CTRL_Themes",
                        "updateRequest", array(BOL_StorageService::URI_VAR_KEY => $theme->getKey())) : false,
            );

            $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$theme->getId(),'isPermanent'=>true,'activityType'=>'change_theme')));
            if(isset($iisSecuritymanagerEvent->getData()['code'])){
                $code = $iisSecuritymanagerEvent->getData()['code'];
                $themesInfo[$theme->getKey()]["changeUrl"] = $router->urlFor(__CLASS__, "changeTheme",
                    array(BOL_StorageService::URI_VAR_KEY => $theme->getKey(),'code'=>$code));
            }
            if ( !in_array($theme->getKey(), array(BOL_ThemeService::DEFAULT_THEME, $activeTheme)) )
            {
                /*
                * check if delete button must be disabled or not
                */
                if(!isset($checkThemes)  || ! isset($checkThemes->getData()['hideRemoveButton'])) {
                    $themesInfo[$theme->getKey()]["delete_url"] = $router->urlFor(__CLASS__, "deleteTheme",
                        array("key" => $theme->getKey()));
                    $iisSecuritymanagerEvent = OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                        array('senderId' => Ow::getUser()->getId(), 'receiverId' => $theme->getId(), 'isPermanent' => true, 'activityType' => 'delete_theme')));
                    if (isset($iisSecuritymanagerEvent->getData()['code'])) {
                        $code = $iisSecuritymanagerEvent->getData()['code'];
                        $themesInfo[$theme->getKey()]["delete_url"] = $router->urlFor(__CLASS__, "deleteTheme",
                            array("key" => $theme->getKey(), 'code' => $code));
                    }
                }
            }

            if ( $theme->getLicenseCheckTimestamp() > 0 )
            {
                $params = array(
                    BOL_StorageService::URI_VAR_BACK_URI => urlencode($router->uriForRoute("admin_themes_choose")),
                    BOL_StorageService::URI_VAR_KEY => $theme->getKey(),
                    BOL_StorageService::URI_VAR_ITEM_TYPE => BOL_StorageService::URI_VAR_ITEM_TYPE_VAL_THEME,
                    BOL_StorageService::URI_VAR_DEV_KEY => $theme->getDeveloperKey(),
                    BOL_StorageService::URI_VAR_RETURN_RESULT => 0
                );
                $themesInfo[$theme->getKey()]["license_url"] = OW::getRequest()->buildUrlQueryString($router->urlFor("ADMIN_CTRL_Storage",
                        "checkItemLicense"), $params);
            }

            $xmlInfo = $this->themeService->getThemeXmlInfoForKey($theme->getKey());
            $themesInfo[$theme->getKey()] = array_merge($themesInfo[$theme->getKey()], $xmlInfo);
        }

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin("admin")->getStaticJsUrl() . "theme_select.js");
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin("base")->getStaticJsUrl() . "jquery.sticky.js");

        $addData = array(
            "deleteConfirmMsg" => $language->text("admin", "themes_choose_delete_confirm_msg"),
            "deleteActiveThemeMsg" => $language->text("admin", "themes_cant_delete_active_theme")
        );

        $event = new OW_Event("admin.themes_list_view", array("ctrl" => $this, "type" => "index"), $themesInfo);
        OW::getEventManager()->trigger($event);
        $themesInfo = $event->getData();

        OW::getDocument()->addOnloadScript(
            "window.owThemes = new ThemesSelect(" . json_encode($themesInfo) . ", " . json_encode($addData) . ");
        	$('.selected_theme_info input.theme_select_submit').click(function(){
    			window.location.href = '{$themesInfo[$activeTheme]['changeUrl']}';
    		});
            $('.selected_theme_info_stick').sticky({topSpacing:60});
            $('.admin_themes_select a.theme_icon').click( function(){ $('.theme_info .theme_control_button').hide(); });"
        );

        $this->assign("adminThemes",
            array(BOL_ThemeService::DEFAULT_THEME => $themesInfo[BOL_ThemeService::DEFAULT_THEME]));
        $this->assign("themeInfo", $themesInfo[$activeTheme]);
        $event = new OW_Event("admin.filter_themes_to_choose", array(), $themesInfo);
        OW::getEventManager()->trigger($event);
        $this->assign("themes", $event->getData());

        // add theme
        $form = new Form("theme-add");
        $form->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);
        $file = new FileField("file");
        $form->addElement($file);

        $submit = new Submit("submit");
        $submit->setValue($language->text("admin", "plugins_manage_add_submit_label"));
        $form->addElement($submit);
        $event = OW::getEventManager()->trigger(new OW_Event('base.on_before_theme_add_form'));
        $addFileForm = !isset($event->getData()['disable']);
        if($addFileForm){
            $this->assign('formAdd', true);
        }

        $this->addForm($form);

        if ( OW::getRequest()->isPost() && $addFileForm )
        {
            if ( $form->isValid($_POST) )
            {
                $data = $form->getValues();
                $result = UTIL_File::checkUploadedFile($_FILES["file"]);

                if ( !$result["result"] )
                {
                    $this->feedback->error($result["message"]);
                    $this->redirect($router->urlForRoute("admin_themes_choose"));
                }

                $pluginfilesDir = OW::getPluginManager()->getPlugin("base")->getPluginFilesDir();

                $tempFile = $this->getTemDirPath() . UTIL_String::getRandomStringWithPrefix("theme_add") . ".zip";
                $tempDirName = UTIL_String::getRandomStringWithPrefix("theme_add");

                if ( !OW::getStorage()->moveFile($_FILES["file"]["tmp_name"], $tempFile) )
                {
                    $this->feedback->error($language->text("admin", "manage_theme_add_move_file_error"));
                    $this->redirect($router->urlForRoute("admin_themes_choose"));
                }

                $zip = new ZipArchive();

                if ( $zip->open($tempFile) === true )
                {
                    $zip->extractTo($this->getTemDirPath() . $tempDirName);
                    $zip->close();
                }
                else
                {
                    $this->feedback->error($language->text("admin", "manage_theme_add_extract_error"));
                    $this->redirect($router->urlForRoute("admin_themes_choose"));
                }

                OW::getStorage()->removeFile($tempFile);
                $this->redirect(OW::getRequest()->buildUrlQueryString($router->urlFor(__CLASS__, "processAdd"),
                        array("dir" => $tempDirName)));
            }
        }

        $deleteCode='';
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>rand(1,10000),'isPermanent'=>true,'activityType'=>'delete_theme')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $deleteCode = $iisSecuritymanagerEvent->getData()['code'];
        }
        $this->assign("all_del_url",  OW::getRouter()->urlFor(__CLASS__, "deleteTheme", array("key" => self::DELETE_ALL_THEMES_KEY,'code'=>$deleteCode)));
    }

    public function processAdd()
    {
        $language = OW::getLanguage();
        $router = OW::getRouter();

        $tempDirName = empty($_GET["dir"]) ? null : str_replace(DS, "", $_GET["dir"]);

        if ( empty($tempDirName) || !OW::getStorage()->fileExists($this->getTemDirPath() . $tempDirName) )
        {
            $this->feedback->error($language->text("admin", "manage_plugins_add_ftp_move_error"));
            $this->redirect($router->urlForRoute("admin_themes_choose"));
        }

        $tempDirPath = $this->getTemDirPath() . $tempDirName;

        // locate plugin.xml file to find plugin source root dir
        $themes = UTIL_File::findFiles($tempDirPath, array("xml"));
        $localThemeRootPath = null;

        foreach ( $themes as $themeXml )
        {
            if ( basename($themeXml) == BOL_ThemeService::THEME_XML )
            {
                $localThemeRootPath = dirname($themeXml) . DS;
            }
        }

        if ( $localThemeRootPath == null )
        {
            $this->feedback->error($language->text("admin", "manage_theme_add_extract_error"));
            $this->redirect($router->urlForRoute('admin_themes_choose'));
        }

        //@author Issa Annamoradnejad
        //to enable updating existing themes

        //get plugin.xml info
        $themeXmlInfo = simplexml_load_file($localThemeRootPath . BOL_ThemeService::THEME_XML);
        if ( $themeXmlInfo == null )
        {
            $this->feedback->error($language->text("admin", "manage_themes_theme_not_found"));
            $this->redirect($router->urlForRoute('admin_themes_choose'));
        }
        $remoteDir = OW_DIR_THEME . basename($localThemeRootPath);
        $themeWithSameKey = BOL_ThemeService::getInstance()->findThemeByKey($themeXmlInfo->key);
        $is_new_item = true;
        if ( OW::getStorage()->fileExists($remoteDir) && isset($themeWithSameKey) )
        {
            $is_new_item = false;
            if( $themeXmlInfo->build <= $themeWithSameKey->getBuild())
            {
                $this->feedback->info($language->text("admin", "manage_themes_up_to_date_message"));
                $this->redirect($router->urlForRoute('admin_themes_choose'));
            }
        }

        $ftp = $this->getFtpConnection();
        $ftp->uploadDir($localThemeRootPath, $remoteDir);
        UTIL_File::removeDir($tempDirPath);

        //@author Issa Annamoradnejad
        //to ensure accessibility of new files by UserGroup
        $ftp->chmod_r($remoteDir, 0775, 0664);

        if($is_new_item) {
            $this->feedback->info($language->text("base", "themes_item_add_success_message"));
        }else{
            $this->feedback->info($language->text("base", "themes_item_update_success_message"));
        }
        $this->redirect($router->urlForRoute("admin_themes_choose"));
    }

    public function changeTheme( array $params )
    {
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            $code =$params['code'];
            if(!isset($code)){
                throw new Redirect404Exception();
            }
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'change_theme')));
        }
        OW::getEventManager()->trigger(new OW_Event('core.before_theme_install', array("paramsData" => $params)));

        $themeDto = $this->getThemeDtoByKeyInParamsArray($params);
        $backUrl = OW::getRouter()->urlForRoute("admin_themes_choose");
        $language = OW::getLanguage();

        if ( $themeDto == null )
        {
            $this->feedback->error($language->text("admin", "theme_manage_empty_key_error_msg"));
            $this->redirect($backUrl);
        }

        // get remote info about the plugin
        $itemData = $this->storageService->getItemInfoForUpdate($themeDto->getKey(), $themeDto->getDeveloperKey());

        // check if it's free
        // We dont need to check license yet
        if ( false && empty($itemData[BOL_StorageService::URI_VAR_FREEWARE]) && ($themeDto->getLicenseKey() == null || !$this->storageService->checkLicenseKey($themeDto->getKey(),
                $themeDto->getDeveloperKey(), $themeDto->getLicenseKey())) )
        {
            if ( !isset($_GET[BOL_StorageService::URI_VAR_LICENSE_CHECK_COMPLETE]) )
            {
                $requestParams = array(
                    BOL_StorageService::URI_VAR_KEY => $themeDto->getKey(),
                    BOL_StorageService::URI_VAR_DEV_KEY => $themeDto->getDeveloperKey(),
                    BOL_StorageService::URI_VAR_ITEM_TYPE => BOL_StorageService::URI_VAR_ITEM_TYPE_VAL_THEME,
                    BOL_StorageService::URI_VAR_BACK_URI => urlencode(OW::getRequest()->getRequestUri()),
                    "back-button-uri" => urlencode(OW::getRouter()->uriForRoute("admin_themes_choose"))                );

                $this->redirect(OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor("ADMIN_CTRL_Storage",
                            "checkItemLicense"), $requestParams));
            }
            else
            {
                $this->feedback->error($language->text("admin", "manage_theme_activate_invalid_license_key"));
                $this->redirect($backUrl);
            }
        }

        OW::getConfig()->saveConfig("base", "selectedTheme", $themeDto->getKey());
        OW::getEventManager()->trigger(new OW_Event("base.change_theme",
            array(/* name for back. compat. */"name" => $themeDto->getKey(), "key" => $themeDto->getKey())));
        $this->feedback->info(OW::getLanguage()->text("admin", "theme_change_success_message"));

        $this->redirect($backUrl);
    }

    public function updateRequest( array $params )
    {
        //TODO merge method with platform update request
        $themeDto = $this->getThemeDtoByKeyInParamsArray($params);
        $language = OW::getLanguage();
        $router = OW::getRouter();

        $remoteThemeInfo = (array) $this->storageService->getItemInfoForUpdate($themeDto->getKey(),
                $themeDto->getDeveloperKey(), $themeDto->getBuild());

        if ( empty($remoteThemeInfo) || !empty($remoteThemeInfo["error"]) )
        {
            $this->assign("text", $language->text("admin", "theme_update_request_error"));
            return;
        }

        if ( empty($remoteThemeInfo[BOL_StorageService::URI_VAR_FREEWARE]) && ($themeDto->getLicenseKey() == null || !$this->storageService->checkLicenseKey($themeDto->getKey(),
                $themeDto->getDeveloperKey(), $themeDto->getLicenseKey())) )
        {
            if ( !isset($_GET[BOL_StorageService::URI_VAR_LICENSE_CHECK_COMPLETE]) )
            {
                $get = array(
                    BOL_StorageService::URI_VAR_KEY => $themeDto->getKey(),
                    BOL_StorageService::URI_VAR_DEV_KEY => $themeDto->getDeveloperKey(),
                    BOL_StorageService::URI_VAR_ITEM_TYPE => BOL_StorageService::URI_VAR_ITEM_TYPE_VAL_THEME,
                    BOL_StorageService::URI_VAR_BACK_URI => urlencode(OW::getRequest()->getRequestUri())
                );
                $this->redirect(OW::getRequest()->buildUrlQueryString($router->urlFor("ADMIN_CTRL_Storage",
                            "checkItemLicense"), $get));
            }
            else
            {
                $this->assign("text", $language->text("admin", "theme_license_request_error"));
                return;
            }
        }

        $this->assign("text",
            $language->text("admin", "free_theme_request_text",
                array("oldVersion" => $themeDto->getBuild(), "newVersion" => $remoteThemeInfo["build"], "name" => $themeDto->getTitle())));
        $this->assign("updateUrl", $router->urlFor(__CLASS__, "update", $params));
        $this->assign("returnUrl", $router->urlForRoute("admin_themes_choose"));
        $this->assign("changeLog", $remoteThemeInfo["changeLog"]);

        if ( OW::getConfig()->getValue("base", "update_soft") )
        {
            $this->assign("platformUpdateAvail", true);
        }
    }

    /***
     * @param array $params
     *
     *
     * @author Issa Annamoradnejad
     * fixed permission problems after update + removing temp files
     */
    public function update( array $params )
    {
        $themeDto = $this->getThemeDtoByKeyInParamsArray($params);
        $language = OW::getLanguage();
        $router = OW::getRouter();

        if ( !empty($_GET["mode"]) )
        {
            switch ( trim($_GET["mode"]) )
            {
                case "theme_up_to_date":
                    $this->feedback->warning($language->text("admin", "manage_themes_up_to_date_message"));
                    break;

                case "theme_update_success":
                    $this->feedback->info($language->text("admin", "manage_themes_update_success_message"));
                    break;

                default :
                    $this->feedback->error($language->text("admin", "manage_themes_update_process_error"));
                    break;
            }

            $this->redirect($router->urlForRoute("admin_themes_choose"));
        }

        $remoteThemeInfo = (array) $this->storageService->getItemInfoForUpdate($themeDto->getKey(),
                $themeDto->getDeveloperKey(), $themeDto->getBuild());

        if ( empty($remoteThemeInfo) || !empty($remoteThemeInfo["error"]) )
        {
            $this->feedback->error($language->text("admin", "manage_themes_update_process_error"));
            $this->redirect($router->urlForRoute("admin_themes_choose"));
        }

        if ( !(bool) $remoteThemeInfo["freeware"] && ($themeDto->getLicenseKey() == null || !$this->storageService->checkLicenseKey($themeDto->getKey(),
                $themeDto->getDeveloperKey(), $themeDto->getLicenseKey())) )
        {
            $this->feedback->error($language->text("admin", "manage_plugins_update_invalid_key_error"));
            $this->redirect($router->urlForRoute("admin_themes_choose"));
        }

        $ftp = $this->getFtpConnection();

        try
        {
            $archivePath = $this->storageService->downloadItem($themeDto->getKey(), $themeDto->getDeveloperKey(),
                $themeDto->getLicenseKey());
        }
        catch ( Exception $e )
        {
            $this->feedback->error($e->getMessage());
            $this->redirect($router->urlForRoute("admin_themes_choose"));
        }

        if ( !OW::getStorage()->fileExists($archivePath) )
        {
            $this->feedback->error($language->text("admin", "theme_update_download_error"));
            $this->redirect($router->urlForRoute("admin_themes_choose"));
        }

        $zip = new ZipArchive();

        $pluginfilesDir = OW::getPluginManager()->getPlugin("base")->getPluginFilesDir();
        $tempDirPath = $pluginfilesDir . UTIL_HtmlTag::generateAutoId("theme_update") . DS;

        if ( $zip->open($archivePath) === true )
        {
            $zip->extractTo($tempDirPath);
            $zip->close();
        }
        else
        {
            $this->feedback->error($language->text("admin", "theme_update_download_error"));
            $this->redirect($router->urlForRoute("admin_themes_choose"));
        }

        $result = UTIL_File::findFiles($tempDirPath, array("xml"));
        $localThemeRootPath = null;

        foreach ( $result as $item )
        {
            if ( basename($item) == BOL_ThemeService::THEME_XML )
            {
                $localThemeRootPath = dirname($item) . DS;
            }
        }

        if ( $localThemeRootPath == null )
        {
            $this->feedback->error($language->text("admin", "manage_theme_update_extract_error"));
            $this->redirect($router->urlForRoute("admin_themes_choose"));
        }

        $remoteDir = OW_DIR_THEME . $themeDto->getKey();

        if ( !OW::getStorage()->fileExists($remoteDir) )
        {
            $this->feedback->error($language->text("admin", "manage_theme_update_theme_not_found"));
            $this->redirect($router->urlForRoute("admin_themes_choose"));
        }

        $ftp->uploadDir($localThemeRootPath, $remoteDir);
        UTIL_File::removeDir($localThemeRootPath);

        //@author Issa Annamoradnejad
        //remove temp files and folders
        UTIL_File::removeDir($tempDirPath);
        OW::getStorage()->removeFile($archivePath);

        //@author Issa Annamoradnejad
        //to ensure accessibility of new files by UserGroup
        $ftp->chmod_r($remoteDir, 0775, 0664);

        $params = array("theme" => $themeDto->getKey(), "back-uri" => urlencode(OW::getRequest()->getRequestUri()));
        $this->redirect(OW::getRequest()->buildUrlQueryString($this->storageService->getUpdaterUrl(), $params));
    }

    public function deleteTheme( $params )
    {
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            $code =$params['code'];
            if(!isset($code)){
                throw new Redirect404Exception();
            }
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'delete_theme')));
        }
        $language = OW::getLanguage();
        $ftp = $this->getFtpConnection();
        $key = trim($params["key"]);

        if ( $key == self::DELETE_ALL_THEMES_KEY){
            $availableThemes = $this->themeService->findAllThemes();
            $currentTheme = OW_ThemeManager::getInstance()->getCurrentTheme()->getDto()->key;
            $selectedTheme = OW_ThemeManager::getInstance()->getSelectedTheme()->getDto()->key;
            foreach($availableThemes as $themeDto){
                if( !in_array($themeDto->getKey(), [$currentTheme, $selectedTheme]) ) {
                    $this->themeService->deleteTheme($themeDto->getId(), true);
                    $ftp->rmDir($this->themeService->getRootDir($themeDto->getKey()));
                }
            }
        }else{
            $themeDto = $this->getThemeDtoByKeyInParamsArray($params);

            if ( OW::getThemeManager()->getDefaultTheme()->getDto()->getKey() == $themeDto->getKey() )
            {
                $this->feedback->error($language->text("admin", "themes_cant_delete_default_theme"));
                $this->redirect(OW::getRouter()->urlForRoute("admin_themes_choose"));
            }

            if ( OW::getThemeManager()->getCurrentTheme()->getDto()->getKey() == $themeDto->getKey() )
            {
                $this->feedback->error($language->text("admin", "themes_cant_delete_active_theme"));
                $this->redirect(OW::getRouter()->urlForRoute("admin_themes_choose"));
            }

            $this->themeService->deleteTheme($themeDto->getId(), true);
            $ftp->rmDir($this->themeService->getRootDir($themeDto->getKey()));
        }

        $this->feedback->info($language->text("admin", "themes_delete_success_message"));
        $this->redirect(OW::getRouter()->urlForRoute("admin_themes_choose"));
    }

    private function getThemeDtoByKeyInParamsArray( $params )
    {
        if ( !empty($params["key"]) )
        {
            $themeDto = $this->themeService->findThemeByKey(trim($params["key"]));
        }

        if ( !empty($themeDto) )
        {
            return $themeDto;
        }

        $this->feedback->error(OW::getLanguage()->text("admin", "manage_themes_theme_not_found"));
        $this->redirect(OW::getRouter()->urlForRoute("admin_themes_choose"));
    }
}
