<?php
/**
 * @author Mohammad Agha Abbasloo
 * Date: 5/31/2017
 * Time: 3:43 PM
 */

$updateDir = dirname(__FILE__) . DS;
Updater::getLanguageService()->importPrefixFromZip($updateDir . 'langs.zip', 'iiseventplus');


OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iiseventplus_event_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eventId` int(11) NOT NULL,
  `attachmentId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

$widgetService = Updater::getWidgetService();


$widget = $widgetService->addWidget('IISEVENTPLUS_CMP_FileListWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, 'event');

try
{
    $widgetService->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_LEFT);
}
catch ( LogicException $e ) {}