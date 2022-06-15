<?php

$widgetService = BOL_ComponentAdminService::getInstance();
$widget = $widgetService->addWidget('IISADVANCESEARCH_MCMP_UsersSearchWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, BOL_MobileWidgetService::PLACE_MOBILE_INDEX);
$widgetService->addWidgetToPosition($placeWidget, BOL_MobileWidgetService::SECTION_MOBILE_MAIN);

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__).DS.'langs.zip', 'iisadvancesearch');