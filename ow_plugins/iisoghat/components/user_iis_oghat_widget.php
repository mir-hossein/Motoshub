<?php

/**
 * IIS Oghat widget
 *
 * @since 1.0
 */
class IISOGHAT_CMP_UserIisOghatWidget extends BASE_CLASS_Widget
{

    /**
     * IISOGHAT_CMP_UserIisOghatWidget constructor.
     * @param BASE_CLASS_WidgetParameter $params
     */
    public function __construct( BASE_CLASS_WidgetParameter $params )
    {
        parent::__construct();
        $this->assignList($params);
    }

    private function assignList($params)
    {

        $sevices = IISOGHAT_BOL_Service::getInstance();
        $cities = $sevices->getAllCity();

        $defaultCity = array();

        $citiesList = array();
        foreach($cities as $city){
            $cityInformation['name'] = $city->name;
            $cityInformation['longitude'] = $city->longitude;
            $cityInformation['latitude'] = $city->latitude;
            $cityInformation['default'] = false;

            if($city->default == 1){
                $cityInformation['default'] = true;
                $defaultCity['name'] = $city->name;
                $defaultCity['longitude'] = $city->longitude;
                $defaultCity['latitude'] = $city->latitude;
            }
            $citiesList[] = $cityInformation;
        }

        $this->assign('cities',$citiesList);
        $lang = OW::getLanguage();

        $lang->addKeyForJs('iisoghat', 'Azan_am');
        $lang->addKeyForJs('iisoghat', 'Azan_pm');
        $lang->addKeyForJs('iisoghat', 'Sunrise');
        $lang->addKeyForJs('iisoghat', 'Sunset');
        $lang->addKeyForJs('iisoghat', 'azan_maghreb');
        $lang->addKeyForJs('iisoghat', 'Azan_am_time_horizon');
        $lang->addKeyForJs('iisoghat', 'Azan_pm_time_horizon');
        $lang->addKeyForJs('iisoghat', 'azan_maghreb_time_horizon');
        $lang->addKeyForJs('iisoghat', 'until');

        $imageSrc1 = OW::getPluginManager()->getPlugin('iisoghat')->getStaticUrl() . 'images/1-1.gif';
        $imageSrc2 = OW::getPluginManager()->getPlugin('iisoghat')->getStaticUrl() . 'images/1-2.gif';
        $timeUrl = OW::getRouter()->urlForRoute('iisoghat.get.time');

        $this->assign('imageSrc1',$imageSrc1);
        $this->assign('imageSrc2',$imageSrc2);
        $this->assign('timeUrl',$timeUrl);

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iisoghat')->getStaticJsUrl() . 'iisoghat.js', 'text/javascript', (-100));
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iisoghat')->getStaticCssUrl() . 'iisoghat.css' , 'all', -100);
        $callMainFunction = 'main_oghat(\''.$defaultCity['name'].'\',\''.$imageSrc1.'\',\''.$imageSrc2.'\',\''.$timeUrl.'\');';
        OW::getDocument()->addOnloadScript('coord('.$defaultCity['longitude'].', '.$defaultCity['latitude'].');'.$callMainFunction.'setInterval("'.$callMainFunction.'",30000);');
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_WRAP_IN_BOX => true,
            self::SETTING_TITLE => OW_Language::getInstance()->text('iisoghat', 'main_menu_item'),
            self::SETTING_ICON => self::ICON_USER
        );
    }

    public static function getAccess()
    {
        return self::ACCESS_MEMBER;
    }
}