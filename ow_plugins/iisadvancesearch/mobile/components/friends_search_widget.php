<?php

/**
 * IIS Advance Search
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisadvancesearch
 * @since 1.0
 */

class IISADVANCESEARCH_MCMP_FriendsSearchWidget extends BASE_CLASS_Widget
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
        OW::getDocument()->addOnloadScript(';iisadvancesearch_search_users(\''.OW::getRouter()->urlForRoute('iisadvancesearch.search_friends', array('key' => '')).'\', "#iisadvancedsearch_search_friends",12,false);');

        $this->setTemplate(OW::getPluginManager()->getPlugin('iisadvancesearch')->getMobileCmpViewDir() . 'friends_search_widget.html');
    }

    public static function getAccess()
    {
        return self::ACCESS_MEMBER;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_WRAP_IN_BOX => true,
            self::SETTING_TITLE => OW_Language::getInstance()->text('iisadvancesearch', 'search_friends'),
            self::SETTING_ICON => self::ICON_USER
        );
    }
}
