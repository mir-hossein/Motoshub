<?php

/**
 * iisoghat
 */
/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisoghat
 * @since 1.0
 */

$config = OW::getConfig();
if ( !$config->configExists('iisoghat', 'importDefaultItem') )
{
    $config->addConfig('iisoghat', 'importDefaultItem', false);
}

OW::getDbo()->query("
DROP TABLE IF EXISTS `" . OW_DB_PREFIX . "iisoghat_city`;"."
CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "iisoghat_city` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `longitude` varchar(40),
  `latitude` varchar(40),
  `default` int(1),
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8;");
