<?php

/* Friends  */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisadvancesearch
 * @since 1.0
 */

class FRIENDS_MCMP_FriendsListWidget extends BASE_CLASS_Widget
{
    public function __construct( BASE_CLASS_WidgetParameter $params )
    {
        parent::__construct( );
        $service = FRIENDS_BOL_Service::getInstance();
        $userId = OW::getUser()->getId();
        $count = (int) $params->customParamList['count'];

        $idList = $service->findUserFriendsInList($userId, 0, $count);
        $total = $service->countFriends($userId);
        $userService = BOL_UserService::getInstance();

        $eventParams =  array(
            'action' => 'friends_view',
            'ownerId' => $userId,
            'viewerId' => OW::getUser()->getId()
        );

        try
        {
            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        catch( RedirectException $e )
        {
            $this->setVisible(false);
            return;
        }

        if ( empty($idList) && !$params->customizeMode )
        {
            $this->setVisible(false);
            return;
        }

        if( !empty($idList) )
        {
            $this->addComponent('userList', new BASE_MCMP_AvatarUserList($idList));
        }

        $username = BOL_UserService::getInstance()->getUserName($userId);

        $toolbar = array();

        if ( $total > $count )
        {
            $toolbar = array(
                array('label'=>OW::getLanguage()->text('base','view_all'), 'href'=>OW::getRouter()->urlForRoute('friends_user_friends', array('user'=>$username)))
            );
            $this->setSettingValue(self::SETTING_TOOLBAR, $toolbar);
        }

        $this->assign('toolbar', $toolbar);

        $this->setTemplate(OW::getPluginManager()->getPlugin('friends')->getMobileCmpViewDir() . 'friends_list_widget.html');
    }

    public static function getAccess()
    {
        return self::ACCESS_MEMBER;
    }

    public static function getSettingList()
    {
        $settingList = array();

        $settingList['count'] = array(
            'presentation' => 'number',
            'label' => OW::getLanguage()->text('friends', 'user_widget_settings_count'),
            'value' => '9'
        );

        return $settingList;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_WRAP_IN_BOX => true,
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_TITLE => OW::getLanguage()->text('friends', 'widget_friends'),
            self::SETTING_ICON => self::ICON_USER
        );
    }
}