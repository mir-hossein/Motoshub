<?php


$widgetService = BOL_MobileWidgetService::getInstance();

try
{
    $widget = $widgetService->addWidget("NEWSFEED_MCMP_MyFeedWidget", false);
    $place = $widgetService->addWidgetToPlace($widget, BOL_MobileWidgetService::PLACE_MOBILE_DASHBOARD);
    $widgetService->addWidgetToPosition($place, BOL_MobileWidgetService::SECTION_MOBILE_MAIN);
}
catch ( Exception $e )
{
    $logger->addEntry(json_encode($e));
}

