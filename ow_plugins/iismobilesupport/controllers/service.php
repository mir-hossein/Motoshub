<?php

class IISMOBILESUPPORT_CTRL_Service extends OW_ActionController
{

    public function useMobile($params)
    {
        $service = IISMOBILESUPPORT_BOL_Service::getInstance();
        if(!$service->isUserShoouldUseOnlyMobile()){
            $this->redirect(OW_URL_HOME);
        }else {
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

            $masterPageFileDir = OW::getThemeManager()->getMasterPageTemplate('blank');
            OW::getDocument()->getMasterPage()->setTemplate($masterPageFileDir);
            $this->assign('logout', '<a href="' . OW::getRouter()->urlForRoute('base_sign_out') . '">' . OW::getLanguage()->text('base', 'console_item_label_sign_out') . '</a>');
        }
    }

    public function downloadLatestVersion($params){
        $type = $params['type'];
        if($type == 'android'){
            $type = 1;
        }elseif($type=='ios'){
            $type = 2;
        }elseif($type=='native'){
            $type = 3;
        }else{
            throw new Redirect404Exception();
        }
        $version = IISMOBILESUPPORT_BOL_Service::getInstance()->getLastVersions($type);
        if(!isset($version)){
            throw new Redirect404Exception();
        }
        $this->redirect($version->url);
    }

    public function setWebToken($params){
        if(!OW::getUser()->isAuthenticated() || empty($_POST['token'])){
            exit(json_encode(array('result' => false)));
        }
        $cookie = '';
        if (isset($_COOKIE['ow_login'])){
            $cookie = $_COOKIE['ow_login'];
        }
        $row = IISMOBILESUPPORT_BOL_Service::getInstance()->findDeviceTokenRow(OW::getUser()->getId(), $_POST['token'], $cookie);
        if(!isset($row)){
            IISMOBILESUPPORT_BOL_Service::getInstance()->deleteUserDeviceByCookie(OW::getUser()->getId(), $cookie);
            IISMOBILESUPPORT_BOL_Service::getInstance()->saveDevice(OW::getUser()->getId(), $_POST['token'], IISMOBILESUPPORT_BOL_Service::getInstance()->webFcmKey, $cookie);
        }
        exit(json_encode(array('result' => true)));
    }
}