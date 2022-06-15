<?php

$updateDir = dirname(__FILE__) . DS;
Updater::getLanguageService()->importPrefixFromZip($updateDir . 'langs.zip', 'iisgroupsplus');

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisgroupsplus_group_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupId` int(11) NOT NULL,
  `attachmentId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

$widgetService = Updater::getWidgetService();


$widget = $widgetService->addWidget('IISGROUPSPLUS_CMP_FileListWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, 'group');

try
{
    $widgetService->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_LEFT);
}
catch ( LogicException $e ) {}