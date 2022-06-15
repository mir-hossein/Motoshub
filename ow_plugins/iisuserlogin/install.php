<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

$config = OW::getConfig();

if ( !$config->configExists('iisuserlogin', 'numberOfLastLoginDetails') )
{
    $config->addConfig('iisuserlogin', 'numberOfLastLoginDetails', 5);
}

if ( !$config->configExists('iisuserlogin', 'expiredTimeOfLoginDetails') )
{
    $config->addConfig('iisuserlogin', 'expiredTimeOfLoginDetails', 1);
}

if( !$config->configExists('iisuserlogin','update_active_details'))
{
    $config->addConfig('iisuserlogin', 'update_active_details', true);
}

$preference = BOL_PreferenceService::getInstance()->findPreference('iisuserlogin_login_detail_subscribe');
if ( empty($preference) )
{
    $preference = new BOL_Preference();
}

$preference->key = 'iisuserlogin_login_detail_subscribe';
$preference->sectionName = 'general';
$preference->defaultValue = false;
$preference->sortOrder = 100;

BOL_PreferenceService::getInstance()->savePreference($preference);

OW::getDbo()->query("
    DROP TABLE IF EXISTS  `" . OW_DB_PREFIX . "iisuserlogin_login_details`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisuserlogin_login_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `browser` longtext NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

OW::getDbo()->query("
    DROP TABLE IF EXISTS  `" . OW_DB_PREFIX . "iisuserlogin_active_details`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisuserlogin_active_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `browser` longtext NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `sessionId` longtext NOT NULL,
  `loginCookie` longtext NOT NULL,
  `delete` TINYINT(1) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ;');
