<?php

class IISMOBILESUPPORT_MCTRL_Service extends OW_MobileActionController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index($params)
    {
        if (isset($params['key'])) {
            if ($params['key'] == 'information') {
                $generalWebService = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance();
                $information = array();
                $information['menuTypeMainItems'] = $generalWebService->getMenuTypeMainItems();
                $information['menuTypeBottomItems'] = $generalWebService->getMenuTypeBottomItems();
                $information['languageItems'] = $this->findActiveLanguages();
                $information['userInformation'] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformation(false, true);
                $versionCode = null;
                if(isset($_GET['versionCode'])){
                    $versionCode  = $_GET['versionCode'];
                }
                $service = IISMOBILESUPPORT_BOL_Service::getInstance();
                $information['androidInformation'] = $service->getAppInformation(IISMOBILESUPPORT_BOL_Service::getInstance()->AndroidKey, $versionCode);
                $information['iosInformation'] = $service->getAppInformation(IISMOBILESUPPORT_BOL_Service::getInstance()->iOSKey, $versionCode);
                OW::getEventManager()->trigger(new OW_Event('iismobilesupport.save.login.cookie'));
                exit(json_encode($information));
            }
        }
        exit();
    }

    public function getInformation($params){
        $generalWebService = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance();
        if(isset($params['type'])){
            $generalWebService->manageRequestHeader($params['type']);
            $generalWebService->generateWebserviceResult($params['type']);
        }
        exit($generalWebService->makeJson(array("input_error")));
    }

    public function action($params){
        $generalWebService = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance();
        if(isset($params['type'])){
            $generalWebService->manageRequestHeader($params['type'], 'action');
            $generalWebService->generateWebserviceResult($params['type'], 'action');
        }
        exit($generalWebService->makeJson(array("input_error")));
    }

    public function findActiveLanguages()
    {
        $languages = BOL_LanguageService::getInstance()->getLanguages();
        $session_language_id = BOL_LanguageService::getInstance()->getCurrent()->getId();

        $active_languages = array();

        foreach ($languages as $id => $language) {
            if ($language->status == 'active') {
                $tag = $this->parseCountryFromTag($language->tag);
                if ($tag['label'] == 'fa') {
                    $tag['label'] = 'فا';
                }

                $active_lang = array(
                    'id' => $language->id,
                    'label' => $tag['label'],
                    'order' => $language->order,
                    'tag' => $language->tag,
                    'url' => OW::getRequest()->buildUrlQueryString(null, array("language_id" => $language->id)),
                    'is_current' => false,
                    'isRtl' => $language->getRtl()
                );

                $active_lang['url'] = str_replace('"', '%22', $active_lang['url']);
                $active_lang['url'] = str_replace('\'', '\\\'', $active_lang['url']);

                if ($session_language_id == $language->id) {
                    $active_lang['is_current'] = true;
                }

                $active_languages[] = $active_lang;
            }
        }

        return $active_languages;
    }

    protected function parseCountryFromTag($tag)
    {
        $tags = preg_match("/^([a-zA-Z]{2})$|^([a-zA-Z]{2})-([a-zA-Z]{2})(-\w*)?$/", $tag, $matches);

        if (empty($matches)) {
            return array("label" => $tag, "country" => "");
        }
        if (!empty($matches[1])) {
            $country = strtolower($matches[1]);
            return array("label" => $matches[1], "country" => "_" . $country);
        } else if (!empty($matches[2])) {
            $country = strtolower($matches[3]);
            return array("label" => $matches[2], "country" => "_" . $country);
        }

        return "";
    }

    public function useMobile($params)
    {
        if(!IISMOBILESUPPORT_BOL_Service::getInstance()->isUserShoouldUseOnlyMobile()){
            $this->redirect(OW_URL_HOME);
        }else {
            $service = IISMOBILESUPPORT_BOL_Service::getInstance();

            $androidLastVersion = $service->getLastVersions($service->AndroidKey);
            $iosLastVersion = $service->getLastVersions($service->iOSKey);
            $nativeAndroidLastVersion = $service->getLastVersions($service->nativeFcmKey);

            if($androidLastVersion != null){
                $this->assign('androidDownloadUrl', $androidLastVersion->url);
            }

            if($iosLastVersion != null){
                $this->assign('iosDownloadUrl', $iosLastVersion->url);
            }

            if($nativeAndroidLastVersion != null){
                $this->assign('nativeAndroidDownloadUrl', $nativeAndroidLastVersion->url);
            }

            $cssUrl = OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticCssUrl() . "iismobilesupport.css";
            OW::getDocument()->addStyleSheet($cssUrl);
            $this->assign('logout', '<a href="' . OW::getRouter()->urlForRoute('base_sign_out') . '">' . OW::getLanguage()->text('base', 'console_item_label_sign_out') . '</a>');
        }
    }

    public function notifications(){
        $plugin = BOL_PluginDao::getInstance()->findPluginByKey('iismainpage');
        if(isset($plugin) && $plugin->isActive() && !IISMAINPAGE_BOL_Service::getInstance()->isDisabled('notifications')) {
            $this->redirect(OW::getRouter()->urlForRoute('iismainpage.notifications'));
        }
        $cssUrl = OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticCssUrl() . "iismobilesupport.css";
        OW::getDocument()->addStyleSheet($cssUrl);
        $cmp = new BASE_MCMP_ConsoleNotificationsPage();
        $this->addComponent('cmp', $cmp);
    }
}

