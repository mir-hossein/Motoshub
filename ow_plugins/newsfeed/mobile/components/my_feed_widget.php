<?php


/**
 * @author Mohammad Agha Abbasloo <a.mohammad85@gmail.com>
 * @package newsfeed
 */
class NEWSFEED_MCMP_MyFeedWidget extends BASE_CLASS_Widget
{
    private $userId;

    public function __construct( BASE_CLASS_WidgetParameter $params )
    {
        $this->assign('userId',OW::getUser()->getId());
        $this->setTemplate(OW::getPluginManager()->getPlugin('newsfeed')->getMobileCmpViewDir() . 'my_feed_widget.html');
    }


    public static function getAccess()
    {
        return self::ACCESS_MEMBER;
    }


    protected function getMenuCmp( $menuItems )
    {
        return new BASE_MCMP_WidgetMenu($menuItems);
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_WRAP_IN_BOX => true,
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_TITLE => OW::getLanguage()->text('newsfeed', 'widget_feed_title'),
            self::SETTING_ICON => self::ICON_USER
        );
    }
}