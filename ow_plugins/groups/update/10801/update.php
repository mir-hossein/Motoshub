<?php
if(BOL_NavigationService::getInstance()->findMenuItem('groups', 'mobile_main_menu_list')==null) {
    Updater::getNavigationService()->addMenuItem(OW_Navigation::MOBILE_TOP, 'groups-index', 'groups', 'mobile_main_menu_list', OW_Navigation::VISIBLE_FOR_ALL);
}
Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'groups');

