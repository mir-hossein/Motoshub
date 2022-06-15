<?php

/**
 * iisgroupsplus
 */
/**
 * @author Mohammad Agha Abbasloo <a.mohammad85@gmail.com>
 * @package ow_plugins.iisgroupsplus
 * @since 1.0
 */

$widgetService = BOL_ComponentAdminService::getInstance();
$widget = $widgetService->addWidget('IISGROUPSPLUS_CMP_FileListWidget', false);
$placeWidget = $widgetService->addWidgetToPlace($widget, 'group');
$widgetService->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_LEFT);

if ( OW::getConfig()->getValue('groups', 'is_iisgroupsplus_connected') ) {
    try {
        $widgetService = BOL_ComponentAdminService::getInstance();
        $widget = $widgetService->addWidget('IISGROUPSPLUS_CMP_PendingInvitation', false);
        $widgetUniqID = 'group' . '-' . $widget->className;

        //*remove if exists
        $widgets = $widgetService->findPlaceComponentList('group');
        foreach ($widgets as $w) {
            if ($w['uniqName'] == $widgetUniqID)
                $widgetService->deleteWidgetPlace($widgetUniqID);
        }
        //----------*/

        //add
        $placeWidget = $widgetService->addWidgetToPlace($widget, 'group', $widgetUniqID);
        $widgetService->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_LEFT, -1);
    } catch (Exception $e) {
    }
}

try {
    $authorization = OW::getAuthorization();
    $groupName = 'iisgroupsplus';
    $authorization->addGroup($groupName);
    $authorization->addAction($groupName, 'all-search');
    $authorization->addAction($groupName, 'direct-add');
    $authorization->addAction($groupName, 'add-forced-groups');
}catch (Exception $e){}

/*if (!OW::getConfig()->configExists('iisgroupsplus', 'groupFileAndJoinFeed')){
    OW::getConfig()->addConfig('iisgroupsplus', 'groupFileAndJoinFeed', '["fileFeed","joinFeed"]');
}*/
$config = OW::getConfig();
if (!$config->configExists('iisgroupsplus', 'groupFileAndJoinAndLeaveFeed')) {
    $config->addConfig('iisgroupsplus', 'groupFileAndJoinAndLeaveFeed', '["fileFeed","joinFeed","leaveFeed"]');
}

if (!$config->configExists('iisgroupsplus', 'showFileUploadSettings')) {
    $config->addConfig('iisgroupsplus', 'showFileUploadSettings', 1);
}
if (!$config->configExists('iisgroupsplus', 'showAddTopic')) {
    $config->addConfig('iisgroupsplus', 'showAddTopic', 1);
}

$dbPrefix = OW_DB_PREFIX;

OW::getDbo()->query("
DROP TABLE IF EXISTS  `" . $dbPrefix . "iisgroupsplus_category`;");

$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "iisgroupsplus_category` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`label` VARCHAR(200) NOT NULL,
	 UNIQUE KEY `label` (`label`),
	PRIMARY KEY (`id`)
)
CHARSET=utf8 AUTO_INCREMENT=1;";
//installing database
OW::getDbo()->query($sql);

OW::getDbo()->query("
DROP TABLE IF EXISTS  `" . $dbPrefix . "iisgroupsplus_group_information`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisgroupsplus_group_information` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

OW::getDbo()->query("
DROP TABLE IF EXISTS  `" . $dbPrefix . "iisgroupsplus_group_managers`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisgroupsplus_group_managers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

OW::getDbo()->query("
DROP TABLE IF EXISTS  `" . $dbPrefix . "iisgroupsplus_group_files`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisgroupsplus_group_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupId` int(11) NOT NULL,
  `attachmentId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

OW::getDbo()->query("
DROP TABLE IF EXISTS  `" . $dbPrefix . "iisgroupsplus_channel`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisgroupsplus_channel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupId` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisgroupsplus_group_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupId` int(11) NOT NULL,
  `whoCanUploadFile` varchar(100) NOT NULL default "participant",
  `whoCanCreateTopic` varchar(100) NOT NULL default "participant",
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

