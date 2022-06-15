<?php
if(BOL_NavigationService::getInstance()->findMenuItem('iisnews', 'iisnews_mobile')==null) {
    Updater::getNavigationService()->addMenuItem(OW_Navigation::MOBILE_TOP, 'iisnews-default', 'iisnews', 'iisnews_mobile', OW_Navigation::VISIBLE_FOR_ALL);
}
Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'iisnews');

