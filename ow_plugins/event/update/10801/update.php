<?php
if(BOL_NavigationService::getInstance()->findMenuItem('event', 'event_mobile')==null) {
    Updater::getNavigationService()->addMenuItem(OW_Navigation::MOBILE_TOP, 'event.main_menu_route', 'event', 'event_mobile', OW_Navigation::VISIBLE_FOR_ALL);
}
Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'event');

