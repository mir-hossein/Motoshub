<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

$config = OW::getConfig();

if ( !$config->configExists('iispasswordchangeinterval', 'expire_time') )
{
    $config->addConfig('iispasswordchangeinterval', 'expire_time', 90);
}
if ( !$config->configExists('iispasswordchangeinterval', 'dealWithExpiredPassword') )
{
    $config->addConfig('iispasswordchangeinterval', 'dealWithExpiredPassword', 'normal');
}

OW::getDbo()->query("
DROP TABLE IF EXISTS  `" . OW_DB_PREFIX . "iispasswordchangeinterval_password_validation`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iispasswordchangeinterval_password_validation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `valid` int(1) NOT NULL,
  `token` VARCHAR(128),
  `tokenTime` int(11) NOT NULL,
  `passwordTime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');
