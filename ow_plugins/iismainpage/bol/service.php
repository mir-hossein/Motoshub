<?php

/**
 * iismainpage
 */
/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismainpage
 * @since 1.0
 */

class IISMAINPAGE_BOL_Service
{

    static $item_count = 20;

    /**
     * Constructor.
     */
    private function __construct()
    {
    }
    /**
     * Singleton instance.
     *
     * @var IISMAINPAGE_BOL_Service
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return IISMAINPAGE_BOL_Service
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function getMenu($type){
        $menus = array();
        $imgSource = OW::getPluginManager()->getPlugin('iismainpage')->getStaticUrl() . 'img/';

        $service = IISMAINPAGE_BOL_Service::getInstance();
        $orders = $service->getMenuOrder();

        $disables= $service->getDisabledList();

        foreach ($orders as $orderMenu) {
            if(($key = array_search($orderMenu, $disables)) !== false){
                continue;
            }
            if ($orderMenu =='dashboard' && OW::getPluginManager()->isPluginActive('newsfeed')) {
                $menu = array();
                $menu['title'] = $this->getLableOfMenu($orderMenu);
                $menu['iconUrl'] = $imgSource . 'dashboard.svg';
                if ($type == 'dashboard') {
                    $menu['active'] = true;
                    $menu['iconUrl'] = $imgSource . 'dashboard_select.svg';
                } else {
                    $menu['active'] = false;
                }
                $menu['class'] = 'main_menu_dashboard';
                $menu['url'] = OW::getRouter()->urlForRoute('iismainpage.dashboard');
                $menus[] = $menu;
            }

            if ($orderMenu =='user-groups' && OW::getPluginManager()->isPluginActive('groups')) {
                $menu = array();
                $menu['title'] = $this->getLableOfMenu($orderMenu);
                $menu['iconUrl'] = $imgSource . 'groups.svg';
                if ($type == 'user-groups') {
                    $menu['active'] = true;
                    $menu['iconUrl'] = $imgSource . 'groups_select.svg';
                } else {
                    $menu['active'] = false;
                }
                $menu['class'] = 'main_menu_user_groups';
                $menu['url'] = OW::getRouter()->urlForRoute('iismainpage.user.groups');
                $menus[] = $menu;
            }

            if ($orderMenu =='friends' && OW::getPluginManager()->isPluginActive('friends')) {
                $menu = array();
                $menu['title'] = $this->getLableOfMenu($orderMenu);
                $menu['iconUrl'] = $imgSource . 'friend.svg';
                if ($type == 'friends') {
                    $menu['active'] = true;
                    $menu['iconUrl'] = $imgSource . 'friend_select.svg';
                } else {
                    $menu['active'] = false;
                }
                $menu['class'] = 'main_menu_friends';
                $menu['url'] = OW::getRouter()->urlForRoute('iismainpage.friends');
                $menus[] = $menu;
            }

            if ($orderMenu =='mailbox' && OW::getPluginManager()->isPluginActive('mailbox')) {
                $menu = array();
                $menu['title'] = $this->getLableOfMenu($orderMenu);
                $menu['iconUrl'] = $imgSource . 'chat.svg';
                $menu['class'] = 'menu_messages';
                if ($type == 'mailbox') {
                    $menu['active'] = true;
                    $menu['iconUrl'] = $imgSource . 'chat_select.svg';
                } else {
                    $menu['active'] = false;
                }
                $menu['class'] = 'main_menu_mailbox';
                $menu['url'] = OW::getRouter()->urlForRoute('iismainpage.mailbox');
                $menus[] = $menu;
            }

            if($orderMenu =='settings'){
                $menu = array();
                $menu['title'] = $this->getLableOfMenu($orderMenu);
                $menu['iconUrl'] = $imgSource . 'Settings.svg';
                if ($type == 'settings') {
                    $menu['active'] = true;
                    $menu['iconUrl'] = $imgSource . 'Settings_select.svg';
                } else {
                    $menu['active'] = false;
                }
                $menu['class'] = 'main_menu_settings';
                $menu['url'] = OW::getRouter()->urlForRoute('iismainpage.settings');
                $menus[] = $menu;
           }

            if($orderMenu =='notifications'){
                $menu = array();
                $menu['title'] = $this->getLableOfMenu($orderMenu);
                $menu['iconUrl'] = $imgSource . 'notifications.svg';
                if ($type == 'notifications') {
                    $menu['active'] = true;
                    $menu['iconUrl'] = $imgSource . 'notifications_select.svg';
                } else {
                    $menu['active'] = false;
                }
                $menu['class'] = 'main_menu_notifications';
                $menu['url'] = OW::getRouter()->urlForRoute('iismainpage.notifications');
                $menus[] = $menu;
            }

            if($orderMenu =='photos'){
                $menu = array();
                $menu['title'] = $this->getLableOfMenu($orderMenu);
                $menu['iconUrl'] = $imgSource . 'photos.svg';
                if ($type == 'photos') {
                    $menu['active'] = true;
                    $menu['iconUrl'] = $imgSource . 'photos_select.svg';
                } else {
                    $menu['active'] = false;
                }
                $menu['class'] = 'main_menu_photos';
                $menu['url'] = OW::getRouter()->urlForRoute('iismainpage.photos');
                $menus[] = $menu;
            }

            if($orderMenu =='videos'){
                $menu = array();
                $menu['title'] = $this->getLableOfMenu($orderMenu);
                $menu['iconUrl'] = $imgSource . 'videos.svg';
                if ($type == 'videos') {
                    $menu['active'] = true;
                    $menu['iconUrl'] = $imgSource . 'videos_select.svg';
                } else {
                    $menu['active'] = false;
                }
                $menu['class'] = 'main_menu_videos';
                $menu['url'] = OW::getRouter()->urlForRoute('iismainpage.videos');
                $menus[] = $menu;
            }
        }

        return $menus;
    }

    /***
     * @return array
     */
    public function getMenuOrder(){
        $orders = '';
        if(OW::getConfig()->configExists('iismainpage', 'orders')) {
            $orders = OW::getConfig()->getValue('iismainpage', 'orders');
        }
        $list = $orders!=''?json_decode(OW::getConfig()->getValue('iismainpage', 'orders')):null;
        if($list == null || (is_array($list) && empty($list)) || !is_array($list)){
            $list = $this->getMenuByDefaultOrder();
        }
        $new_list = array_merge($list, $this->getMenuByDefaultOrder());
        $new_list = array_unique($new_list);
        $this->savePageOrdered($new_list);
        return $new_list;
    }
    /***
     * @return array
     */
    public function getMenuByDefaultOrder(){
        $list = array();
        $list[] = 'notifications';
        $list[] = 'user-groups';
        $list[] = 'dashboard';
        $list[] = 'friends';
        $list[] = 'mailbox';
        $list[] = 'photos';
        $list[] = 'videos';
        $list[] = 'settings';
        return $list;
    }

    /***
     * @param $list
     */
    public function savePageOrdered($list){
        if(!OW::getConfig()->configExists('iismainpage','orders'))
        {
            OW::getConfig()->addConfig('iismainpage', 'orders', json_encode($list));
        }
        else {
            OW::getConfig()->saveConfig('iismainpage', 'orders', json_encode($list));
        }
    }

    public function getLableOfMenu($key){
        $languages = OW::getLanguage();
        if($key == 'dashboard'){
            return $languages->text('base', 'console_item_label_dashboard');
        }else if($key == 'user-groups'){
            return $languages->text('groups', 'group_list_menu_item_my');
        }else if($key == 'friends'){
            return $languages->text('friends', 'notification_section_label');
        }else if($key == 'mailbox'){
            return $languages->text('mailbox', 'messages_console_title');
        }else if($key == 'settings'){
            return $languages->text('iismainpage', 'settings');
        }else if($key == 'notifications'){
            return $languages->text('base', 'notifications');
        }else if($key == 'photos'){
            return $languages->text('iismainpage', 'public_photos');
        }else if($key == 'videos'){
            return $languages->text('video', 'video');
        }

        return '';
    }

    public function isPluginExist($key){
        if($key == 'dashboard'){
            return OW::getPluginManager()->isPluginActive('newsfeed');
        }else if($key == 'user-groups'){
            return OW::getPluginManager()->isPluginActive('groups');
        }else if($key == 'friends'){
            return OW::getPluginManager()->isPluginActive($key);
        }else if($key == 'mailbox'){
            return OW::getPluginManager()->isPluginActive($key);
        }else if($key == 'notifications'){
            return OW::getPluginManager()->isPluginActive($key);
        }else if($key == 'photos'){
            return OW::getPluginManager()->isPluginActive('photo');
        }else if($key == 'videos'){
            return OW::getPluginManager()->isPluginActive('video');
        }

        return true;
    }

    public function addToDisableList($id)
    {
        $config = OW::getConfig();
        $disables = array();
        if(!$config->configExists('iismainpage', 'disables'))
        {
            $disables[]=$id;
            OW::getConfig()->addConfig('iismainpage', 'disables', json_encode($disables));
        }
        else {
            $disables = json_decode($config->getValue('iismainpage', 'disables'),true);
            if ( !in_array($id,$disables) ){
                $disables[] = $id;
                $config->saveConfig('iismainpage', 'disables', json_encode($disables));
            }
        }
    }

    public function removeFromDisableList($id)
    {
        $config = OW::getConfig();
        if($config->configExists('iismainpage', 'disables'))
        {
            $disables = json_decode($config->getValue('iismainpage', 'disables'),true);
            if (($key = array_search($id, $disables)) !== false) {
                unset($disables[$key]);
            }
            $config->saveConfig('iismainpage', 'disables', json_encode($disables));
        }
    }

    public function getDisabledList(){
        $allItems = $this->getMenuByDefaultOrder();
        foreach ($allItems as $key){
            if(!$this->isPluginExist($key)){
                $this->addToDisableList($key);
            }
        }

        $disables = array();
        if(OW::getConfig()->configExists('iismainpage', 'disables')){
            $disables =  json_decode(OW::getConfig()->getValue('iismainpage', 'disables'),true);
        }
        return $disables;
    }

    public function isDisabled($id){
        if(OW::getConfig()->configExists('iismainpage', 'disables')){
            $disables =  json_decode(OW::getConfig()->getValue('iismainpage', 'disables'),true);
            if (in_array($id,$disables)){
                return true;
            }
        }
        return false;
    }
}