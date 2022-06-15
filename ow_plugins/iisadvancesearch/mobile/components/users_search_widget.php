<?php

/**
 * IIS Advance Search
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisadvancesearch
 * @since 1.0
 */

class IISADVANCESEARCH_MCMP_UsersSearchWidget extends BASE_CLASS_Widget
{
    /**
     * @param BASE_CLASS_WidgetParameter $paramObj
     */
    public function __construct( BASE_CLASS_WidgetParameter $paramObj )
    {
        parent::__construct();

        OW::getLanguage()->addKeyForJs('base', 'more');
        $jsDir = OW::getPluginManager()->getPlugin("iisadvancesearch")->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir . "iisadvancesearch-mobile.js");
        OW::getDocument()->addOnloadScript(';iisadvancesearch_search_users(\''.OW::getRouter()->urlForRoute('iisadvancesearch.search_users', array('type'=>'all', 'key' => '')).'\', "#iisadvancedsearch_search_users",12,false);');

        $toolbar = array('label'=>OW::getLanguage()->text('iisadvancesearch','view_all_users'),
                'href'=>OW::getRouter()->urlForRoute('iisadvancesearch.list.users', array('type'=>'all')));
        $this->assign('toolbar', $toolbar);

        $this->setTemplate(OW::getPluginManager()->getPlugin('iisadvancesearch')->getMobileCmpViewDir() . 'users_search_widget.html');
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_SHOW_TITLE => false,
            self::SETTING_WRAP_IN_BOX => true,
            self::SETTING_TITLE => OW_Language::getInstance()->text('iisadvancesearch', 'search_users'),
            self::SETTING_ICON => self::ICON_USER
        );
    }
}
