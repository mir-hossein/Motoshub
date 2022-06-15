<?php

/**
 * iiseventplus
 */
/**
 * @author Mohammad Agha Abbasloo <a.mohammad85@gmail.com>
 * @package ow_plugins.iiseventplus
 * @since 1.0
 */

OW::getPluginManager()->addPluginSettingsRouteName('iiseventplus', 'iiseventplus.admin');

try {
    $widgetService = BOL_ComponentAdminService::getInstance();
    $widget = $widgetService->addWidget('IISEVENTPLUS_CMP_FileListWidget', false);
    $placeWidget = $widgetService->addWidgetToPlace($widget, 'event');
    $widgetService->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_LEFT);
} catch(Exception $e){}