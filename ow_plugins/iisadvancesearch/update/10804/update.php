<?php

$widgetService = BOL_ComponentAdminService::getInstance();
$widget = $widgetService->addWidget('IISADVANCESEARCH_MCMP_UsersSearchWidget', true);
$placeWidget = $widgetService->addWidgetToPlace($widget, 'iisadvancesearch');
$widgetService->addWidgetToPosition($placeWidget, BOL_MobileWidgetService::SECTION_MOBILE_MAIN);

OW::getNavigation()->addMenuItem(OW_Navigation::MOBILE_TOP, 'iisadvancesearch.search_users.ctrl', 'iisadvancesearch', 'mobile_main_menu_item', OW_Navigation::VISIBLE_FOR_ALL);



Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__).DS.'langs.zip', 'iisadvancesearch');