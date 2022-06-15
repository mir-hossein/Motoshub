<?php

$widget = BOL_ComponentAdminService::getInstance()->addWidget('FRIENDS_MCMP_FriendsListWidget', false);
$placeWidget = BOL_ComponentAdminService::getInstance()->addWidgetToPlace($widget, BOL_MobileWidgetService::PLACE_MOBILE_INDEX);
BOL_ComponentAdminService::getInstance()->addWidgetToPosition($placeWidget, BOL_MobileWidgetService::SECTION_MOBILE_MAIN);

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__).DS.'langs.zip', 'friends');