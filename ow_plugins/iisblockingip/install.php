<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

$config = OW::getConfig();

if ( !$config->configExists('iisblockingip', 'loginCaptcha') )
{
    $config->addConfig('iisblockingip', 'loginCaptcha', true);
}

if ( !$config->configExists('iisblockingip', 'try_count_captcha') )
{
    $config->addConfig('iisblockingip', 'try_count_captcha', 1);
};

if ( !$config->configExists('iisblockingip', 'block') )
{
    $config->addConfig('iisblockingip', 'block', true);
}

if ( !$config->configExists('iisblockingip', 'try_count_block') )
{
    $config->addConfig('iisblockingip', 'try_count_block', 5);
};

if ( !$config->configExists('iisblockingip', 'expire_time') )
{
    $config->addConfig('iisblockingip', 'expire_time', 15);
}
OW::getDbo()->query("
DROP TABLE IF EXISTS  `" . OW_DB_PREFIX . "iisblockingip_block_ip`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisblockingip_block_ip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(40) NOT NULL,
  `time` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');
