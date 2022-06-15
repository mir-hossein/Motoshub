<?php

class ADMIN_CTRL_Settings extends ADMIN_CTRL_Abstract
{

    public function __construct()
    {
        parent::__construct();
    }

    private function getMenu()
    {
        $language = OW::getLanguage();

        $menuItems = array();

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('admin', 'menu_item_basics'));
        $item->setUrl(OW::getRouter()->urlForRoute('admin_settings_main'));
        $item->setKey('basics');
        $item->setIconClass('ow_ic_gear_wheel');
        $item->setOrder(0);
        $menuItems[] = $item;

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('admin', 'menu_item_page_settings'));
        $item->setUrl(OW::getRouter()->urlForRoute('admin_settings_page'));
        $item->setKey('page');
        $item->setIconClass('ow_ic_file');
        $item->setOrder(1);
        $menuItems[] = $item;

        if ( !defined('OW_PLUGIN_XP') )
        {
            $item = new BASE_MenuItem();
            $item->setLabel($language->text('admin', 'menu_item_mail_settings'));
            $item->setUrl(OW::getRouter()->urlForRoute('admin_settings_mail'));
            $item->setKey('mail');
            $item->setIconClass('ow_ic_mail');
            $item->setOrder(2);
            $menuItems[] = $item;
        }

        return new BASE_CMP_ContentMenu($menuItems);
    }

    public function index()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::ADMIN_SETTINGS, 'admin', 'sidebar_menu_item_main_settings');
        }

        $language = OW::getLanguage();

        $configSaveForm = new ConfigSaveForm();
        $this->addForm($configSaveForm);
        $currencyEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_CURRENCY_FIELD_APPEAR));
        if(isset($currencyEvent->getData()['hide'])){
            $this->assign('hideCurrency', true);
        }

        $configs = OW::getConfig()->getValues('base');

        if ( OW::getRequest()->isPost() && $configSaveForm->isValid($_POST) && isset($_POST['save']) )
        {
            $res = $configSaveForm->process();
            OW::getFeedback()->info($language->text('admin', 'main_settings_updated'));

            $this->redirect();
        }

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('admin', 'heading_main_settings'));
            OW::getDocument()->setHeadingIconClass('ow_ic_gear_wheel');
        }

        $configSaveForm->getElement('siteTitle')->setValue($configs['site_name']);

        $this->assign('showVerifyButton', false);

        if ( defined('OW_PLUGIN_XP') )
        {
            $this->assign('showVerifyButton', $configs['unverify_site_email'] !== $configs['site_email']);
            $configSaveForm->getElement('siteEmail')->setValue($configs['unverify_site_email']);
        }
        else
        {
            $configSaveForm->getElement('siteEmail')->setValue($configs['site_email']);
        }


        $configSaveForm->getElement('tagline')->setValue($configs['site_tagline']);
        $configSaveForm->getElement('description')->setValue($configs['site_description']);
        $configSaveForm->getElement('timezone')->setValue($configs['site_timezone']);
        $configSaveForm->getElement('relativeTime')->setValue($configs['site_use_relative_time'] === '1' ? true : false);
        $configSaveForm->getElement('militaryTime')->setValue($configs['military_time'] === '1' ? true : false);
        $configSaveForm->getElement('enableCaptcha')->setValue($configs['enable_captcha']);

        $language->addKeyForJs('admin', 'verify_site_email');

        $jsDir = OW::getPluginManager()->getPlugin("admin")->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir . "main_settings.js");

        $script = ' var main_settings = new mainSettings( ' . json_encode(OW::getRouter()->urlFor("ADMIN_CTRL_Settings", "ajaxResponder")) . ' )';

        OW::getDocument()->addOnloadScript($script);
    }

    public function userInput()
    {
        $showFileSizeInput = true;
        $fileSizeManagementEvent = OW::getEventManager()->trigger(new OW_Event('base.on_before_setting_content_load'));
        if(isset($fileSizeManagementEvent->getData()['showFileSizeInput'])){
            $showFileSizeInput = $fileSizeManagementEvent->getData()['showFileSizeInput'];
        }
        $language = OW::getLanguage();
        $config = OW::getConfig();

        $settingsForm = new Form('input_settings');

        $userCustomHtml = new CheckboxField('user_custom_html');
        $userCustomHtml->setLabel($language->text('admin', 'input_settings_user_custom_html_disable_label'));
        $userCustomHtml->setDescription($language->text('admin', 'input_settings_user_custom_html_disable_desc'));
        $settingsForm->addElement($userCustomHtml);

        $userRichMedia = new CheckboxField('user_rich_media');
        $userRichMedia->setLabel($language->text('admin', 'input_settings_user_rich_media_disable_label'));
        $userRichMedia->setDescription($language->text('admin', 'input_settings_user_rich_media_disable_desc'));
        $settingsForm->addElement($userRichMedia);

        $commentsRichMedia = new CheckboxField('comments_rich_media');
        $commentsRichMedia->setLabel($language->text('admin', 'input_settings_comments_rich_media_disable_label'));
        $commentsRichMedia->setDescription($language->text('admin', 'input_settings_comments_rich_media_disable_desc'));
        $settingsForm->addElement($commentsRichMedia);

        $maxUploadMaxFilesize = BOL_FileService::getInstance()->getUploadMaxFilesize();

        $this->assign('maxUploadMaxFilesize', $maxUploadMaxFilesize);

        $maxUploadMaxFilesizeValidator = new FloatValidator(0, $maxUploadMaxFilesize);
        $maxUploadMaxFilesizeValidator->setErrorMessage($language->text('admin', 'settings_max_upload_size_error'));

        $maxUploadSize = null;
        if($showFileSizeInput) {
            $maxUploadSize = new TextField('max_upload_size');
            $maxUploadSize->setLabel($language->text('admin', 'input_settings_max_upload_size_label'));
            $maxUploadSize->addValidator($maxUploadMaxFilesizeValidator);
            $settingsForm->addElement($maxUploadSize);
        }

        $resourceList = new Textarea('resource_list');
        $resourceList->setLabel($language->text('admin', 'input_settings_resource_list_label'));
        $resourceList->setDescription($language->text('admin', 'input_settings_resource_list_desc'));
        $settingsForm->addElement($resourceList);

        $attchMaxUploadSize = null;
        if($showFileSizeInput) {
            $attchMaxUploadSize = new TextField('attch_max_upload_size');
            $attchMaxUploadSize->setLabel($language->text('admin', 'input_settings_attch_max_upload_size_label'));
            $attchMaxUploadSize->addValidator($maxUploadMaxFilesizeValidator);
            $settingsForm->addElement($attchMaxUploadSize);
        }

        $this->assign('showFileSizeInput', $showFileSizeInput);

        $imageCompressionPercentage = new TextField('image_compression_percentage');
        $imageCompressionPercentage->setLabel($language->text('admin', 'input_image_compression_percentage_label'));
        if(!$config->configExists('base', 'image_compression_percentage')){
            $config->addConfig('base', 'image_compression_percentage', 90);
        }
        $imageCompressionValue = $config->getValue('base','image_compression_percentage');
        $imageCompressionPercentage->setValue(100 - $imageCompressionValue);
        $imageCompressionPercentage->setDescription($language->text('admin', 'input_image_compression_percentage_desc'));
        $imageCompressionPercentage->addValidator(new IntValidator(0,99));
        $settingsForm->addElement($imageCompressionPercentage);

        $attchExtList = new Textarea('attch_ext_list');
        $attchExtList->setLabel($language->text('admin', 'input_settings_attch_ext_list_label'));
        $attchExtList->setDescription($language->text('admin', 'input_settings_attch_ext_list_desc'));
        $attchExtList->addValidator( new FileExtensionsValidator() );
        $settingsForm->addElement($attchExtList);

        $submit = new Submit('save');
        $submit->setValue($language->text('admin', 'save_btn_label'));
        $settingsForm->addElement($submit);

        $this->addForm($settingsForm);

        if ( OW::getRequest()->isPost() )
        {
            if ( $settingsForm->isValid($_POST) )
            {
                $data = $settingsForm->getValues();

                $config->saveConfig('base', 'tf_comments_rich_media_disable', (int) $data['comments_rich_media']);
                $config->saveConfig('base', 'tf_user_custom_html_disable', (int) $data['user_custom_html']);
                $config->saveConfig('base', 'tf_user_rich_media_disable', (int) $data['user_rich_media']);
                $config->saveConfig('base', 'tf_max_pic_size', round((float) $data['max_upload_size'], 2));
                $config->saveConfig('base', 'attch_file_max_size_mb', round((float) $data['attch_max_upload_size'], 2));
                $config->saveConfig('base', 'image_compression_percentage', 100 - (int) $data['image_compression_percentage']);

                if ( !empty($data['resource_list']) )
                {
                    $res = array_unique(preg_split('/' . PHP_EOL . '/', $data['resource_list']));
                    $config->saveConfig('base', 'tf_resource_list', json_encode(array_map('trim', $res)));
                }

                $extList = array();

                if ( !empty($data['attch_ext_list']) )
                {
                    $extList = array_unique(preg_split('/' . PHP_EOL . '/', $data['attch_ext_list']));
                }

                $config->saveConfig('base', 'attch_ext_list', json_encode(array_map('trim', $extList)));

                OW::getFeedback()->info($language->text('admin', 'settings_submit_success_message'));
                $this->redirect();
            }
            else
            {
                OW::getFeedback()->error($language->text('admin', 'settings_submit_error_message'));
            }

        }

        $userCustomHtml->setValue($config->getValue('base', 'tf_user_custom_html_disable'));
        $userRichMedia->setValue($config->getValue('base', 'tf_user_rich_media_disable'));
        $commentsRichMedia->setValue($config->getValue('base', 'tf_comments_rich_media_disable'));
        if($maxUploadSize != null){
            $maxUploadSize->setValue(round((float) $config->getValue('base', 'tf_max_pic_size'), 2));
        }
        $resourceList->setValue(implode(PHP_EOL, json_decode($config->getValue('base', 'tf_resource_list'))));
        if($attchMaxUploadSize != null) {
            $attchMaxUploadSize->setValue(round((float)$config->getValue('base', 'attch_file_max_size_mb'), 2));
        }
        $attchExtList->setValue(implode(PHP_EOL, json_decode($config->getValue('base', 'attch_ext_list'))));

        OW::getDocument()->setHeading(OW::getLanguage()->text('admin', 'heading_user_input_settings'));
        OW::getDocument()->setHeadingIconClass('ow_ic_gear_wheel');
    }

    public function user()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::ADMIN_SETTINGS, 'admin', 'sidebar_menu_item_user_settings');
        }

        $language = OW::getLanguage();

        $avatarService = BOL_AvatarService::getInstance();

        if ( isset($_GET['del-avatar']) && in_array($_GET['del-avatar'], array(1, 2)) )
        {
            $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
            if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
                $code =$_GET['code'];
                if(!isset($code)){
                    throw new Redirect404Exception();
                }
                OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                    array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'delete_avatar')));
            }
            $del = $avatarService->deleteCustomDefaultAvatar((int) $_GET['del-avatar']);
            if ( $del )
            {
                OW::getFeedback()->info($language->text('admin', 'default_avatar_deleted'));
            }

            $this->redirect(OW::getRouter()->urlForRoute('admin_settings_user'));
        }else{
            $receiverId = rand(1,10000);
            $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$receiverId,'isPermanent'=>true,'activityType'=>'delete_avatar')));
            if(isset($iisSecuritymanagerEvent->getData()['code'])){
                $code = $iisSecuritymanagerEvent->getData()['code'];
                $this->assign('deleteCode',$code);
            }
        }

        $uploadMaxFilesize = (float) ini_get("upload_max_filesize");
        $postMaxSize = (float) ini_get("post_max_size");

        $maxUploadMaxFilesize = BOL_FileService::getInstance()->getUploadMaxFilesize();
        $this->assign('maxUploadMaxFilesize', $maxUploadMaxFilesize);       
        
        $userSettingsForm = new UserSettingsForm($maxUploadMaxFilesize);
        $this->addForm($userSettingsForm);

        $conf = OW::getConfig();
        
        $avatarSize = $conf->getValue('base', 'avatar_size');
        $bigAvatarSize = $conf->getValue('base', 'avatar_big_size');
        $avatarUploadSize = $conf->getValue('base', 'avatar_max_upload_size');

        $userSettingsForm->getElement('avatar_max_upload_size')->setValue((float)$avatarUploadSize);
        $userSettingsForm->getElement('avatarSize')->setValue($avatarSize);
        $userSettingsForm->getElement('bigAvatarSize')->setValue($bigAvatarSize);
        $userSettingsForm->getElement('displayName')->setValue($conf->getValue('base', 'display_name_question'));

        // privacy
        $userSettingsForm->getElement('who_can_join')->setValue($conf->getValue('base', 'who_can_join'));
        $userSettingsForm->getElement('who_can_invite')->setValue($conf->getValue('base', 'who_can_invite'));
        $userSettingsForm->getElement('guests_can_view')->setValue($conf->getValue('base', 'guests_can_view'));
        $userSettingsForm->getElement('user_approve')->setValue($conf->getValue('base', 'mandatory_user_approve'));

        // profile questions 
        $userSettingsForm->getElement('user_view_presentation')->
                setValue((OW::getConfig()->getValue('base', 'user_view_presentation') == 'tabs'));

        $this->assign('displayConfirmEmail', !defined('OW_PLUGIN_XP'));

        if ( OW::getRequest()->isPost() && $userSettingsForm->isValid($_POST) )
        {
            if ( !empty($_FILES['avatar']['tmp_name']) && !UTIL_File::validateImage($_FILES['avatar']['name'])
                || !empty($_FILES['bigAvatar']['tmp_name']) && !UTIL_File::validateImage($_FILES['bigAvatar']['name']) )
            {
                OW::getFeedback()->error($language->text('base', 'not_valid_image'));
                $this->redirect();
            }

            $values = $userSettingsForm->getValues();
            $guestPassword = OW_Config::getInstance()->getValue('base', 'guests_can_view_password');

            if ( (int) $values['guests_can_view'] === 3 && empty($values['password']) && is_null($guestPassword) )
            {
                OW::getFeedback()->error($language->text('admin', 'permission_global_privacy_empty_pass_error_message'));
                $this->redirect();
            }
            else if ( (int) $values['guests_can_view'] === 3 && strlen(trim($values['password'])) < 4 && strlen(trim($values['password'])) > 0 )
            {
                OW::getFeedback()->error($language->text('admin', 'permission_global_privacy_pass_length_error_message'));
                $this->redirect();
            }
            
        

            $res = $userSettingsForm->process();
            OW::getFeedback()->info($language->text('admin', 'user_settings_updated'));
            $this->redirect();
        }

        $avatar = $avatarService->getDefaultAvatarUrl(1);
        $avatarBig = $avatarService->getDefaultAvatarUrl(2);
        $this->assign('avatar', $avatar);
        $this->assign('avatarBig', $avatarBig);

        $custom = json_decode($conf->getValue('base', 'default_avatar'), true);
        $this->assign('customAvatar', $custom);

        $language->addKeyForJs('admin', 'confirm_avatar_delete');

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('admin', 'heading_user_settings'));
            OW::getDocument()->setHeadingIconClass('ow_ic_gear_wheel');
        }

        OW::getNavigation()->deactivateMenuItems(OW_Navigation::ADMIN_SETTINGS);
    }

    public function page()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::ADMIN_SETTINGS, 'admin', 'sidebar_menu_item_main_settings');
        }

        $language = OW::getLanguage();

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('admin', 'heading_page_settings'));
            OW::getDocument()->setHeadingIconClass('ow_ic_file');
        }

        $form = new Form('page_settings');
        $form->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);
        $this->addForm($form);

        $customizePage = true;
        $customizePageEvent = OW::getEventManager()->trigger(new OW_Event('base.on_before_setting_page_load'));
        if(isset($customizePageEvent->getData()['customizePage'])){
            $customizePage = $customizePageEvent->getData()['customizePage'];
        }
        $this->assign('customizePage', $customizePage);

        if($customizePage) {
            $headCode = new Textarea('head_code');
            $headCode->setLabel($language->text('admin', 'page_settings_form_headcode_label'));
            $headCode->setDescription($language->text('admin', 'page_settings_form_headcode_desc'));
            $form->addElement($headCode);

            $bottomCode = new Textarea('bottom_code');
            $bottomCode->setLabel($language->text('admin', 'page_settings_form_bottomcode_label'));
            $bottomCode->setDescription($language->text('admin', 'page_settings_form_bottomcode_desc'));
            $form->addElement($bottomCode);
        }

        $favicon = new FileField('favicon');
        $favicon->setLabel($language->text('admin', 'page_settings_form_favicon_label'));
        $favicon->setDescription($language->text('admin', 'page_settings_form_favicon_desc'));
        $form->addElement($favicon);

        $enableFavicon = new CheckboxField('enable_favicon');
        $form->addElement($enableFavicon);

        $submit = new Submit('save');
        $submit->setValue($language->text('admin', 'save_btn_label'));
        $form->addElement($submit);

        $faviconPath = OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'favicon.ico';
        $faviconUrl = OW::getPluginManager()->getPlugin('base')->getUserFilesUrl() . 'favicon.ico?r='. rand();
        $defaultFaviconPath = OW_DIR_ROOT . 'favicon.ico';

        $this->assign('faviconSrc', $faviconUrl);

        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                $data = $form->getValues();
                if($customizePage) {
                    OW::getConfig()->saveConfig('base', 'html_head_code', $data['head_code']);
                    OW::getConfig()->saveConfig('base', 'html_prebody_code', $data['bottom_code']);
                }
                if ( !empty($_FILES['favicon']['name']) )
                {
                    if ( (int) $_FILES['favicon']['error'] === 0 && is_uploaded_file($_FILES['favicon']['tmp_name']) && UTIL_File::getExtension($_FILES['favicon']['name']) === 'ico' )
                    {
                        if ( OW::getStorage()->fileExists($faviconPath) )
                        {
                            OW::getStorage()->removeFile($faviconPath, true);
                        }

                        OW::getStorage()->moveFile($_FILES['favicon']['tmp_name'], $faviconPath, true);

                        if ( OW::getStorage()->fileExists($_FILES['favicon']['tmp_name']) )
                        {
                            OW::getStorage()->removeFile($_FILES['favicon']['tmp_name'], true);
                        }

                        OW::getFeedback()->info($language->text('admin', 'settings_submit_success_message'));
                    }
                    else
                    {
                        OW::getFeedback()->error($language->text('admin', 'page_settings_favicon_submit_error_message'));
                    }
                }

                OW::getConfig()->saveConfig('base', 'favicon', !empty($data['enable_favicon']));

                //to fix favicon for pictures and other cases
                if(!empty($data['enable_favicon'])) {
                    OW::getStorage()->copyFile($faviconPath, $defaultFaviconPath, true);
                }else{
                    @OW::getStorage()->removeFile($defaultFaviconPath);
                }
            }
            else
            {
                OW::getFeedback()->error($language->text('admin', 'settings_submit_error_message'));
            }

            $this->redirect();
        }

        if($customizePage) {
            $headCode->setValue(OW::getConfig()->getValue('base', 'html_head_code'));
            $bottomCode->setValue(OW::getConfig()->getValue('base', 'html_prebody_code'));
        }
        $enableFavicon->setValue((int) OW::getConfig()->getValue('base', 'favicon'));
        $this->assign('faviconEnabled', OW::getConfig()->getValue('base', 'favicon'));

        $script = "$('#{$enableFavicon->getId()}').change(function(){ if(this.checked){ $('#favicon_enabled').show();$('#favicon_desabled').hide(); $('{$favicon->getId()}').attr('disabled', true);}else{ $('#favicon_enabled').hide();$('#favicon_desabled').show(); $('{$favicon->getId()}').attr('disabled', false);} });";
        OW::getDocument()->addOnloadScript($script);
    }

    public function mail()
    {
        if ( defined('OW_PLUGIN_XP') )
        {
            throw new Redirect404Exception();
        }

        OW::getEventManager()->trigger(new OW_Event('base.on_before_smtp_handle'));

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::ADMIN_SETTINGS, 'admin', 'sidebar_menu_item_main_settings');
        }

        $language = OW::getLanguage();

        $mailSettingsForm = new MailSettingsForm();
        $this->addForm($mailSettingsForm);

        $configs = OW::getConfig()->getValues('base');

        //Mail settings
        $mailSettingsForm->getElement('mailSmtpEnabled')->setValue((bool) $configs['mail_smtp_enabled']);

        $mailSettingsForm->getElement('mailSmtpHost')->setValue($configs['mail_smtp_host'])->setRequired(true);
        $mailSettingsForm->getElement('mailSmtpUser')->setValue($configs['mail_smtp_user']);
        $mailSettingsForm->getElement('mailSmtpPassword')->setValue($configs['mail_smtp_password']);
        $mailSettingsForm->getElement('mailSmtpPort')->setValue($configs['mail_smtp_port']);
        $mailSettingsForm->getElement('mailSmtpConnectionPrefix')->setValue($configs['mail_smtp_connection_prefix']);

        if ( OW::getRequest()->isPost() && $mailSettingsForm->isValid($_POST) )
        {
            $res = $mailSettingsForm->process();
            OW::getFeedback()->info($language->text('admin', 'mail_settings_updated'));
            $this->redirect();
        }

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->setHeading(OW::getLanguage()->text('admin', 'heading_mail_settings'));
            OW::getDocument()->setHeadingIconClass('ow_ic_mail');

            OW::getNavigation()->activateMenuItem(OW_Navigation::ADMIN_SETTINGS, 'admin', 'sidebar_menu_item_main_settings');
        }

        $smtpEnabled = false;
        if ( BOL_MailService::getInstance()->getTransfer() === BOL_MailService::TRANSFER_SMTP )
        {
            $smtpTestresponder = json_encode(OW::getRouter()->urlFor('ADMIN_CTRL_Settings', 'ajaxSmtpTestConnection'));
            $readyJs = "
                jQuery('#smtp_test_connection').click(function(){
                    window.OW.inProgressNode(this);
                    var self = this;
                    jQuery.get($smtpTestresponder, function(r){
                        window.OW.activateNode(self);
                        var errorMessage='".OW::getLanguage()->text('admin', 'smtp_test_connection_failed')."';
                        if(r === ''){ r= errorMessage};
                        $.alert(r);
                    });
                });
            ";
            OW::getDocument()->addOnloadScript($readyJs);
            $smtpEnabled = true;
        }

        $this->assign('smtpEnabled', $smtpEnabled);
    }

    /***
     * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
     */
    public function log()
    {
        $eventCheckLogSetting=OW::getEventManager()->trigger(new OW_Event('base.on.before.log.settings.load'));
        if(isset($eventCheckLogSetting->getData()['viewLogSettings']) && $eventCheckLogSetting->getData()['viewLogSettings']==false)
        {
            throw new Redirect404Exception();
        }
        OW::getDocument()->setTitle(OW::getLanguage()->text('admin', 'heading_log_settings'));
        OW::getDocument()->setHeadingIconClass('ow_ic_gear_wheel');
        OW::getNavigation()->deactivateMenuItems(OW_Navigation::ADMIN_SETTINGS);

        $config = OW::getConfig();
        $configs = $config->getValues('base');

        $def_val_1 = defined('OW_LOG_LEVEL')? OW_LOG_LEVEL: 'default';
        $def_val_3 = defined('OW_LOG_OUTPUT_FORMAT')? OW_LOG_OUTPUT_FORMAT: 'default';

        $language = OW::getLanguage();

        $form = new Form('logSettingsForm');

        // File log
        $enabled_3 = new CheckboxField('file_log_enabled');
        $val_tmp = isset($configs['file_log_enabled'])? $configs['file_log_enabled']: true;
        $enabled_3->setValue($val_tmp);
        $form->addElement($enabled_3);

        $tmpField1 = new Selectbox('file_log_level');
        $val_tmp = isset($configs['file_log_level'])? $configs['file_log_level']: $def_val_1;
        $tmpField1->setLabel($language->text('admin', 'settings_log_level_label'))
            ->setValue($val_tmp)
            ->setRequired();
        $tmpField1->addOption('default', $language->text('admin','default'));
        $tmpField1->addOption('100', 'Debug');
        $tmpField1->addOption('200', 'Info');
        $tmpField1->addOption('250', 'Notice');
        $tmpField1->addOption('300', 'Warning');
        $tmpField1->addOption('400', 'Error');
        $tmpField1->addOption('500', 'Critical');
        $tmpField1->addOption('550', 'Alert');
        $tmpField1->addOption('600', 'Emergency');
        $form->addElement($tmpField1);

        $tmpField3 = new Selectbox('file_output_format');
        $val_tmp = isset($configs['file_output_format'])? $configs['file_output_format']: $def_val_3;
        $tmpField3->setLabel($language->text('admin', 'settings_log_output_format_label'))
            ->setValue($val_tmp)
            ->setRequired();
        $tmpField3->setDescription($language->text('admin', 'settings_log_output_handler_desc'));
        $tmpField3->addOption('default', $language->text('admin','default'));
        $tmpField3->addOption('line', 'Line');
        $tmpField3->addOption('json', 'JSON');
        $form->addElement($tmpField3);

        // stdout log
        $enabled_3 = new CheckboxField('stdout_log_enabled');
        $val_tmp = isset($configs['stdout_log_enabled'])? $configs['stdout_log_enabled']: false;
        $enabled_3->setValue($val_tmp);
        $form->addElement($enabled_3);

        $tmpField1 = new Selectbox('stdout_log_level');
        $val_tmp = isset($configs['stdout_log_level'])? $configs['stdout_log_level']: $def_val_1;
        $tmpField1->setLabel($language->text('admin', 'settings_log_level_label'))
            ->setValue($val_tmp)
            ->setRequired();
        $tmpField1->addOption('default', $language->text('admin','default'));
        $tmpField1->addOption('100', 'Debug');
        $tmpField1->addOption('200', 'Info');
        $tmpField1->addOption('250', 'Notice');
        $tmpField1->addOption('300', 'Warning');
        $tmpField1->addOption('400', 'Error');
        $tmpField1->addOption('500', 'Critical');
        $tmpField1->addOption('550', 'Alert');
        $tmpField1->addOption('600', 'Emergency');
        $form->addElement($tmpField1);

        $tmpField3 = new Selectbox('stdout_output_format');
        $val_tmp = isset($configs['stdout_output_format'])? $configs['stdout_output_format']: $def_val_3;
        $tmpField3->setLabel($language->text('admin', 'settings_log_output_format_label'))
            ->setValue($val_tmp)
            ->setRequired();
        $tmpField3->addOption('default', $language->text('admin','default'));
        $tmpField3->addOption('line', 'Line');
        $tmpField3->addOption('json', 'JSON');
        $form->addElement($tmpField3);

        // elastic log
        $enabled_3 = new CheckboxField('elastic_log_enabled');
        $val_tmp = isset($configs['elastic_log_enabled'])? $configs['elastic_log_enabled']: false;
        $enabled_3->setValue($val_tmp);
        $form->addElement($enabled_3);

        $tmpField1 = new Selectbox('elastic_log_level');
        $val_tmp = isset($configs['elastic_log_level'])? $configs['elastic_log_level']: $def_val_1;
        $tmpField1->setLabel($language->text('admin', 'settings_log_level_label'))
            ->setValue($val_tmp)
            ->setRequired();
        $tmpField1->addOption('default', $language->text('admin','default'));
        $tmpField1->addOption('100', 'Debug');
        $tmpField1->addOption('200', 'Info');
        $tmpField1->addOption('250', 'Notice');
        $tmpField1->addOption('300', 'Warning');
        $tmpField1->addOption('400', 'Error');
        $tmpField1->addOption('500', 'Critical');
        $tmpField1->addOption('550', 'Alert');
        $tmpField1->addOption('600', 'Emergency');
        $form->addElement($tmpField1);

        $element_port = new TextField('elastic_host');
        $val_tmp = isset($configs['elastic_host'])? $configs['elastic_host']: 'localhost';
        $element_port->setValue($val_tmp)->setLabel('Host');
        $form->addElement($element_port);

        $element_port = new TextField('elastic_port');
        $val_tmp = isset($configs['elastic_port'])? $configs['elastic_port']: '9200';
        $element_port->setValue($val_tmp)->setLabel('Port');
        $form->addElement($element_port);

        //save
        $tmpField4 = new Submit('save');
        $form->addElement($tmpField4);

        $this->addForm($form);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            $config->saveConfig('base', 'file_log_enabled', $data['file_log_enabled']);
            $config->saveConfig('base', 'stdout_log_enabled', $data['stdout_log_enabled']);
            $config->saveConfig('base', 'elastic_log_enabled', $data['elastic_log_enabled']);

            $values = ['file_log_level', 'file_output_format', 'stdout_log_level', 'stdout_output_format', 'elastic_log_level', 'elastic_host', 'elastic_port'];
            foreach( $values as $key) {
                $val_tmp = $data[$key];
                if ($config->configExists('base', $key)) {
                    if ($val_tmp == 'default') {
                        $config->deleteConfig('base', $key);
                    } else {
                        $config->saveConfig('base', $key, $val_tmp);
                    }
                } else {
                    if ($val_tmp != 'default') {
                        $config->addConfig('base', $key, $val_tmp);
                    }
                }
            }

            OW::getFeedback()->info($language->text('admin', 'log_settings_updated'));
            $this->redirect();
        }

        // TEST Form
        $form2 = new Form('testForm');
        $tmpField6 = new TextField('title');
        $tmpField6->setLabel($language->text('admin', 'title'))
            ->setValue('Test From Log Settings')
            ->setRequired();
        $form2->addElement($tmpField6);

        $tmpField7 = new Selectbox('log_level');
        $tmpField7->setLabel($language->text('admin', 'settings_log_level_label'))
            ->setRequired()
            ->setValue('Info');
        $tmpField7->addOption('Debug', 'Debug');
        $tmpField7->addOption('Info', 'Info');
        $tmpField7->addOption('Notice', 'Notice');
        $tmpField7->addOption('Warning', 'Warning');
        $tmpField7->addOption('Error', 'Error');
        $tmpField7->addOption('Critical', 'Critical');
        $tmpField7->addOption('Alert', 'Alert');
        $tmpField7->addOption('Emergency', 'Emergency');
        $form2->addElement($tmpField7);

        $tmpField8 = new Submit('save');
        $form2->addElement($tmpField8);

        $this->addForm($form2);
        if ( OW::getRequest()->isPost() && $form2->isValid($_POST) )
        {
            OW::getLogger()->writeLog($_POST['log_level'], $_POST['title']);
            OW::getFeedback()->info($language->text('admin', 'log_sent_successfully'));
            $this->redirect();
        }
    }

    public function ajaxSmtpTestConnection()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        try
        {
            $result = BOL_MailService::getInstance()->smtpTestConnection();
        }
        catch ( LogicException $e )
        {
            exit($e->getMessage());
        }

        if ( $result )
        {
            $responce = OW::getLanguage()->text('admin', 'smtp_test_connection_success');
        }
        else
        {
            $responce = OW::getLanguage()->text('admin', 'smtp_test_connection_failed');
        }

        exit($responce);
    }

    public function ajaxResponder()
    {
        if ( empty($_POST["command"]) || !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        $command = (string) $_POST["command"];

        switch ( $command )
        {
            case 'sendVerifyEmail':

                $result = false;

                $email = trim($_POST["email"]);

                if ( UTIL_Validator::isEmailValid($email) )
                {
                    OW::getConfig()->saveConfig('base', 'unverify_site_email', $email);

                    $siteEmail = OW::getConfig()->getValue('base', 'site_email');

                    if ( $siteEmail !== $email )
                    {
                        $type = 'info';
                        BOL_EmailVerifyService::getInstance()->sendSiteVerificationMail(false);
                        $message = OW::getLanguage()->text('base', 'email_verify_verify_mail_was_sent');
                        $result = true;
                    }
                    else
                    {
                        $type = 'warning';
                        $message = OW::getLanguage()->text('admin', 'email_already_verify');
                    }
                }

                $responce = json_encode(array('result' => $result, 'type' => $type, 'message' => $message));

                break;
        }

        exit($responce);
    }
}

/**
 * Save Configurations form class
 */
class ConfigSaveForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        parent::__construct('configSaveForm');

        $language = OW::getLanguage();

        $siteTitleField = new TextField('siteTitle');
        $siteTitleField->setRequired(true);
        $this->addElement($siteTitleField);

        $siteEmailField = new TextField('siteEmail');
        $siteEmailField->setRequired(true);
        $siteEmailField->addValidator(new EmailValidator());
        $this->addElement($siteEmailField);

        $taglineField = new TextField('tagline');
        $taglineField->setRequired(false);
        $this->addElement($taglineField);

        $descriptionField = new Textarea('description');
        $descriptionField->setRequired(false);
        $this->addElement($descriptionField);
        
        $dispalyCaptcha = new CheckboxField('enableCaptcha');
        $this->addElement($dispalyCaptcha);

        $timezoneField = new Selectbox('timezone');
        $timezoneField->setRequired(true);
        $timezoneField->setOptions(UTIL_DateTime::getTimezones());
        $this->addElement($timezoneField);

        $relativeTimeField = new CheckboxField('relativeTime');
        $this->addElement($relativeTimeField);

        $militaryTimeField = new CheckboxField('militaryTime');
        $this->addElement($militaryTimeField);

        // -- date format --
        $dateFieldFormat = new Selectbox("dateFieldFormat");
        $dateFieldFormat->setLabel($language->text('base', 'questions_config_date_field_format_label'));

        $dateFormatValue = OW::getConfig()->getValue('base', 'date_field_format');

        $dateFormatArray = array(BOL_QuestionService::DATE_FIELD_FORMAT_MONTH_DAY_YEAR, BOL_QuestionService::DATE_FIELD_FORMAT_DAY_MONTH_YEAR);

        $options = array();

        foreach ( $dateFormatArray as $key )
        {
            $options[$key] = $language->text('base', 'questions_config_date_field_format_' . $key);
        }

        $dateFieldFormat->setOptions($options);
        $dateFieldFormat->setHasInvitation(false);
        $dateFieldFormat->setValue($dateFormatValue);
        $dateFieldFormat->setRequired();

        $this->addElement($dateFieldFormat);
        // -- date format --


//        $imagesAllowPicUpload = new CheckboxField('tf-allow-pic-upload');
//
//        $imagesAllowPicUpload->setLabel(OW::getLanguage()->text('base', 'tf_allow_pics'))
//            ->setValue(OW::getConfig()->getValue('base', 'tf_allow_pic_upload'));
//
//        $this->addElement($imagesAllowPicUpload);
//
//        $imageMaxSizeField = new TextField('tf-max-image-size');
//
//        $imageMaxSizeField->setValue(OW::getConfig()->getValue('base', 'tf_max_pic_size'))
//            ->setLabel(OW::getLanguage()->text('base', 'tf_max_img_size'))
//            ->addValidator(new IntValidator())->setRequired();
//
//        $this->addElement($imageMaxSizeField);
        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('admin', 'save_btn_label'));
        $this->addElement($submit);
    }

    /**
     * Updates video plugin configuration
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();
        $config = OW::getConfig();

        //begin update lang cache
        $siteName = $config->getValue('base', 'site_name');

        $config->saveConfig('base', 'site_name', $values['siteTitle']);

        if ( $siteName != $config->getValue('base', 'site_name') )
        {
            BOL_LanguageService::getInstance()->generateCacheForAllActiveLanguages();
        }

        if ( defined('OW_PLUGIN_XP') )
        {
            //end update lang cache
            $siteEmail = $config->getValue('base', 'unverify_site_email');

            if ( $siteEmail !== trim($values['siteEmail']) )
            {
                $config->saveConfig('base', 'unverify_site_email', $values['siteEmail']);
                BOL_EmailVerifyService::getInstance()->sendSiteVerificationMail();
            }
        }
        else
        {
            $config->saveConfig('base', 'site_email', $values['siteEmail']);
        }

//        join_display_photo_upload  	true  	Display photo upload on join page
//        join_photo_upload_set_required 	false 	Set required photo upload field on join page
//        join_display_terms_of_use

        $config->saveConfig('base', 'site_tagline', $values['tagline']);
        $config->saveConfig('base', 'site_description', $values['description']);
        $config->saveConfig('base', 'enable_captcha', $values['enableCaptcha']);
        $config->saveConfig('base', 'site_timezone', $values['timezone']);
        $config->saveConfig('base', 'site_use_relative_time', $values['relativeTime'] ? '1' : '0');
        $config->saveConfig('base', 'military_time', $values['militaryTime'] ? '1' : '0');
        $config->saveConfig('base', 'date_field_format', $values['dateFieldFormat']);
//        $config->saveConfig('base', 'tf_allow_pic_upload', $values['tf-allow-pic-upload']);
//        $config->saveConfig('base', 'tf_max_pic_size', $values['tf-max-image-size']);

        return array('result' => true);
    }

}

/**
 * Save Configurations form class
 */
class UserSettingsForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct($maxUploadMaxFilesize)
    {
        parent::__construct('userSettingsForm');

        $this->setEnctype("multipart/form-data");

        $language = OW::getLanguage();

        // avatar size Field
        $avatarSize = new TextField('avatarSize');
        $avatarSize->setRequired(true);
        $validator = new IntValidator(40, 150);
        $validator->setErrorMessage($language->text('admin', 'user_settings_avatar_size_error', array('max' => 150)));
        $avatarSize->addValidator($validator);
        $this->addElement($avatarSize->setLabel($language->text('admin', 'user_settings_avatar_size_label')));

        // big avatar size Field
        $bigAvatarSize = new TextField('bigAvatarSize');
        $bigAvatarSize->setRequired(true);
        $validator = new IntValidator(150, 250);
        $validator->setErrorMessage($language->text('admin', 'user_settings_big_avatar_size_error', array('max' => 250)));
        $bigAvatarSize->addValidator($validator);
        $this->addElement($bigAvatarSize->setLabel($language->text('admin', 'user_settings_big_avatar_size_label')));
        
        // --- avatar max size

        $maxUploadMaxFilesizeValidator = new FloatValidator(0, $maxUploadMaxFilesize);
        $maxUploadMaxFilesizeValidator->setErrorMessage($language->text('admin', 'settings_max_upload_size_error'));
        
        $avatarMaxUploadSize = new TextField('avatar_max_upload_size');
        $avatarMaxUploadSize->setLabel($language->text('admin', 'input_settings_avatar_max_upload_size_label'));
        $avatarMaxUploadSize->addValidator($maxUploadMaxFilesizeValidator);
        $this->addElement($avatarMaxUploadSize);
        // --- avatar max size
        
        if ( !defined('OW_PLUGIN_XP') )
        {
            // confirm Email
            $confirmEmail = new CheckboxField('confirmEmail');
            $confirmEmail->setValue(OW::getConfig()->getValue('base', 'confirm_email'));
            $this->addElement($confirmEmail->setLabel($language->text('admin', 'user_settings_confirm_email')));
        }

        // display name Field
        $displayNameField = new Selectbox('displayName');
        $displayNameField->setRequired(true);

        $questions = array(
            'username' => $language->text('base', 'questions_question_username_label'),
            'realname' => $language->text('base', 'questions_question_realname_label')
        );

        $displayNameField->setHasInvitation(false);
        $displayNameField->setOptions($questions);
        $this->addElement($displayNameField->setLabel($language->text('admin', 'user_settings_display_name')));

        $avatar = new FileField('avatar');
        $this->addElement($avatar);

        $bigAvatar = new FileField('bigAvatar');
        $this->addElement($bigAvatar);

        // --- Join Page -----///
        $config = OW::getConfig();
        if (!OW::getPluginManager()->isPluginActive('iissso')){
            $usernameMin = $config->configExists('base', 'username_chars_min')?$config->getValue('base', 'username_chars_min'):1;
            $usernameMax = $config->configExists('base', 'username_chars_max')?$config->getValue('base', 'username_chars_max'):32;
            $usernameLengthField = new Range('username_length_range');
            $usernameLengthField->setLabel($language->text('admin', 'user_settings_username_length_range_label'));
            $usernameLengthField->setDescription($language->text('admin', 'user_settings_username_length_range_desc'));
            $usernameLengthField->setMinValue(1);
            $usernameLengthField->setMaxValue(32);
            $usernameLengthField->setValue($usernameMin.'-'.$usernameMax);
            $this->addElement($usernameLengthField);
        }
        // --

        $joinConfigField = new Selectbox('join_display_photo_upload');

        $options = array(
            BOL_UserService::CONFIG_JOIN_DISPLAY_PHOTO_UPLOAD => $language->text('base', 'config_join_display_photo_upload_display_label'),
            BOL_UserService::CONFIG_JOIN_DISPLAY_AND_SET_REQUIRED_PHOTO_UPLOAD => $language->text('base', 'config_join_display_photo_upload_display_and_require_label'),
            BOL_UserService::CONFIG_JOIN_NOT_DISPLAY_PHOTO_UPLOAD => $language->text('base', 'config_join_display_photo_upload_not_display_label')
        );

        $joinConfigField->addOptions($options);
        $joinConfigField->setHasInvitation(false);
        $joinConfigField->setValue(OW::getConfig()->getValue('base', 'join_display_photo_upload'));
        $this->addElement($joinConfigField);

        // --

        $joinConfigField = new CheckboxField('join_display_terms_of_use');
        $joinConfigField->setValue(OW::getConfig()->getValue('base', 'join_display_terms_of_use'));
        $this->addElement($joinConfigField);

        //--- privacy -----///
        $config = OW::getConfig();
        $baseConfigs = $config->getValues('base');

        $userApprove = new CheckboxField('user_approve');
        $userApprove->setLabel($language->text('admin', 'permissions_index_user_approve'));
        $this->addElement($userApprove);

        $whoCanJoin = new RadioField('who_can_join');
        $whoCanJoin->addOptions(array('1' => $language->text('admin', 'permissions_index_anyone_can_join'), '2' => $language->text('admin', 'permissions_index_by_invitation_only_can_join')));
        $whoCanJoin->setLabel($language->text('admin', 'permissions_index_who_can_join'));
        $this->addElement($whoCanJoin);

        $whoCanInvite = new RadioField('who_can_invite');
        $whoCanInvite->addOptions(array('1' => $language->text('admin', 'permissions_index_all_users_can_invate'), '2' => $language->text('admin', 'permissions_index_admin_only_can_invate')));
        $whoCanInvite->setLabel($language->text('admin', 'permissions_index_who_can_invite'));
        $this->addElement($whoCanInvite);

        $guestsCanView = new RadioField('guests_can_view');
        $guestsCanView->addOptions(array('1' => $language->text('admin', 'permissions_index_yes'), '2' => $language->text('admin', 'permissions_index_no'), '3' => $language->text('admin', 'permissions_index_with_password')));
        $guestsCanView->setLabel($language->text('admin', 'permissions_index_guests_can_view_site'));
        $guestsCanView->setDescription($language->text('admin', 'permissions_idex_if_not_yes_will_override_settings'));
        $this->addElement($guestsCanView);

        $password = new TextField('password');
        $password->setHasInvitation(true);
        if($baseConfigs['guests_can_view'] == 3)
        {
            $password->setInvitation($language->text('admin', 'change_password'));
        }
        else
        {
            $password->setInvitation($language->text('admin', 'add_password'));
        }
        $this->addElement($password);
        // --- //
        
        //-- profile questions --//
        $userViewPresentationnew = new CheckboxField("user_view_presentation");
        $userViewPresentationnew->setLabel($language->text('base', 'questions_config_user_view_presentation_label'));
        $userViewPresentationnew->setDescription($language->text('base', 'questions_config_user_view_presentation_description'));

        $this->addElement($userViewPresentationnew);
        // --- //

        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('admin', 'save_btn_label'));
        $this->addElement($submit);
    }

    /**
     * Updates user settings configuration
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();

        $config = OW::getConfig();

        $config->saveConfig('base', 'avatar_size', $values['avatarSize']);
        $config->saveConfig('base', 'avatar_big_size', $values['bigAvatarSize']);
        $config->saveConfig('base', 'display_name_question', $values['displayName']);

        $config->saveConfig('base', 'join_display_photo_upload', $values['join_display_photo_upload']);
        $config->saveConfig('base', 'join_display_terms_of_use', $values['join_display_terms_of_use']);
        
        $config->saveConfig('base', 'avatar_max_upload_size', round((float) $values['avatar_max_upload_size'], 2));

        if ( !defined('OW_PLUGIN_XP') )
        {
            $config->saveConfig('base', 'confirm_email', $values['confirmEmail']);
        }

        $avatarService = BOL_AvatarService::getInstance();

        if ( isset($_FILES['avatar']['tmp_name']) )
        {
            $avatarService->setCustomDefaultAvatar(1, $_FILES['avatar']);
        }

        if ( isset($_FILES['bigAvatar']['tmp_name']) )
        {
            $avatarService->setCustomDefaultAvatar(2, $_FILES['bigAvatar']);
        }

        if ( !empty($values['username_length_range']) ){
            $range = explode('-', $values['username_length_range']);
            if ($config->configExists('base', 'username_chars_min')){
                $config->saveConfig('base', 'username_chars_min', intval($range[0]));
            }else{
                $config->addConfig('base', 'username_chars_min', intval($range[0]));
            }
            if ($config->configExists('base', 'username_chars_max')){
                $config->saveConfig('base', 'username_chars_max', intval($range[1]));
            }else{
                $config->addConfig('base', 'username_chars_max', intval($range[1]));
            }
        }

        // privacy
        $config->saveConfig('base', 'who_can_join', (int) $values['who_can_join']);
        $config->saveConfig('base', 'who_can_invite', (int) $values['who_can_invite']);
        $config->saveConfig('base', 'mandatory_user_approve', ((bool) $values['user_approve'] ? 1 : 0));

        
        
        if((int) $values['guests_can_view'] == 3)
        {
            $adminEmail = OW::getUser()->getEmail();
            $senderMail = $config->getValue('base', 'site_email');
            $mail = OW::getMailer()->createMail();
            $mail->addRecipientEmail($adminEmail);
            $mail->setSender($senderMail);
            $mail->setSenderSuffix(false);
            $mail->setSubject(OW::getLanguage()->text( 'admin', 'site_password_letter_subject', array()));
            $mail->setTextContent( OW::getLanguage()->text( 'admin', 'site_password_letter_template_text', array('password' => $values['password'])));
            $mail->setHtmlContent( OW::getLanguage()->text( 'admin', 'site_password_letter_template_html', array('password' => $values['password'])));
            try
            {
                OW::getMailer()->send($mail);
            }
            catch (Exception $e)
            {
                OW::getLogger()->writeLog(OW_Log::ERROR,'admin.send_password_message', array('message'=>$e->getMessage()));
            }
            $salt=md5(UTIL_String::getRandomString(8, 5));
            $values['password'] = json_encode(array('guestPassword'=>hash('sha256', IISSecurityProvider::getStaticPepper().$salt.$values['password']),'guestSalt'=>$salt));
            $config->saveConfig('base', 'guests_can_view_password', $values['password']);
        }
        else
        {
            $config->saveConfig('base', 'guests_can_view_password', null);

        }
        
        $config->saveConfig('base', 'guests_can_view', (int) $values['guests_can_view']);

        // profile questions 
        isset($_POST['user_view_presentation'])
            ? $config->saveConfig('base', 'user_view_presentation', 'tabs')
            : $config->saveConfig('base', 'user_view_presentation', 'table');

        return array('result' => true);
    }
}

/**
 * File extensions Validator
 *
 * @author Alex Ermashev <alexermashev@gmail.com>
 * @package ow_core
 * @since 1.8.4
 */
class FileExtensionsValidator extends OW_Validator
{
    /**
     * List of disallowed extensions
     *
     * @var array
     */
    protected $disallowedExtensions = array(
        'php*',
        'phtml'
    );

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->errorMessage = OW::getLanguage()->text('admin', 'wrong_file_extension', array(
            'extensions' => implode(',', $this->disallowedExtensions)
        ));
    }

    public function isValid( $value )
    {
        $values = explode(PHP_EOL, $value);

        foreach($values as $extension)
        {
            foreach($this->disallowedExtensions as $disallowedExtensions)
            {
                if ( preg_match('/' . $disallowedExtensions . '/i', $extension) )
                {
                    return false;
                }
            }
        }

        return true;
    }
}

/**
 * Save Configurations form class
 */
class MailSettingsForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        parent::__construct('mailSettingsForm');

        $language = OW::getLanguage();

        // Mail Settings
        $smtpField = new CheckboxField('mailSmtpEnabled');
        $this->addElement($smtpField);

        $smtpField = new TextField('mailSmtpHost');
        $this->addElement($smtpField);

        $smtpField = new TextField('mailSmtpUser');
        $this->addElement($smtpField);

        $smtpField = new TextField('mailSmtpPassword');
        $this->addElement($smtpField);

        $smtpField = new TextField('mailSmtpPort');
        $this->addElement($smtpField);

        $smtpField = new Selectbox('mailSmtpConnectionPrefix');
        $smtpField->setHasInvitation(true);
        $smtpField->setInvitation(OW::getLanguage()->text('admin', 'mail_smtp_secure_invitation'));
        $smtpField->addOption('ssl', 'SSL');
        $smtpField->addOption('tls', 'TLS');
        $this->addElement($smtpField);

        // submit
        $submit = new Submit('save');
        $submit->setValue($language->text('admin', 'save_btn_label'));
        $this->addElement($submit);
    }

    /**
     * Updates user settings configuration
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();
        $config = OW::getConfig();

        $config->saveConfig('base', 'mail_smtp_enabled', $values['mailSmtpEnabled'] ? '1' : '0');
        $config->saveConfig('base', 'mail_smtp_host', $values['mailSmtpHost']);
        $config->saveConfig('base', 'mail_smtp_user', $values['mailSmtpUser']);
        $config->saveConfig('base', 'mail_smtp_password', $values['mailSmtpPassword']);
        $config->saveConfig('base', 'mail_smtp_port', $values['mailSmtpPort']);
        $config->saveConfig('base', 'mail_smtp_connection_prefix', $values['mailSmtpConnectionPrefix']);
        OW::getEventManager()->trigger(new OW_Event('base.on_after_smtp_update', array('values' => $values)));
        return array('result' => true);
    }
}
