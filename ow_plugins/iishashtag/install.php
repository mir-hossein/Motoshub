<?php

/**
 * iishashtag
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */

if ( !OW::getConfig()->configExists('iishashtag', 'max_count') )
{
    OW::getConfig()->addConfig('iishashtag', 'max_count', 13, 'Hashtag Max Count');
}

$authorization = OW::getAuthorization();
$groupName = 'iishashtag';
$authorization->addGroup($groupName);
$authorization->addAction($groupName, 'view_newsfeed', true);

OW::getDbo()->query("
DROP TABLE IF EXISTS `" . OW_DB_PREFIX . "iishashtag_tag`;
CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "iishashtag_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` VARCHAR(256) NOT NULL DEFAULT '',
  `count` int(7) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
)DEFAULT CHARSET=utf8;");

OW::getDbo()->query("
DROP TABLE IF EXISTS `" . OW_DB_PREFIX . "iishashtag_entity`;
CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "iishashtag_entity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tagId` int(11) NOT NULL,
  `entityId` VARCHAR(100)  NOT NULL,
  `entityType` VARCHAR(100) NOT NULL,
  `context` VARCHAR(100),
  PRIMARY KEY (`id`)
)DEFAULT CHARSET=utf8;");
