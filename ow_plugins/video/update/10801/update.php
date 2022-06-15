<?php

if(BOL_NavigationService::getInstance()->findMenuItem('video', 'video_mobile')==null) {
    Updater::getNavigationService()->addMenuItem(OW_Navigation::MOBILE_TOP, 'video_list_index', 'video', 'video_mobile', OW_Navigation::VISIBLE_FOR_ALL);
}
Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'video');

