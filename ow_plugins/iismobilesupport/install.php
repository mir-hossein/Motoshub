<?php

if (!OW::getConfig()->configExists('iismobilesupport', 'fcm_api_key')){
    OW::getConfig()->addConfig('iismobilesupport', 'fcm_api_key', '');
}

if (!OW::getConfig()->configExists('iismobilesupport', 'fcm_api_url')){
    OW::getConfig()->addConfig('iismobilesupport', 'fcm_api_url', 'https://fcm.googleapis.com/fcm/send');
}

if (!OW::getConfig()->configExists('iismobilesupport', 'constraint_user_device')){
    OW::getConfig()->addConfig('iismobilesupport', 'constraint_user_device', '10');
}

if (!OW::getConfig()->configExists('iismobilesupport', 'disable_notification_content')){
    OW::getConfig()->addConfig('iismobilesupport', 'disable_notification_content', false);
}

if (!OW::getConfig()->configExists('iismobilesupport', 'custom_download_link_code')){
    OW::getConfig()->addConfig('iismobilesupport', 'custom_download_link_code', '<a class="app_download_link android" href="" target="_blank"></a>');
}

if (!OW::getConfig()->configExists('iismobilesupport', 'custom_download_link_activation')){
    OW::getConfig()->addConfig('iismobilesupport', 'custom_download_link_activation', false);
}

if (!OW::getConfig()->configExists('iismobilesupport', 'last_firebase_send_notifications_time')){
    OW::getConfig()->addConfig('iismobilesupport', 'last_firebase_send_notifications_time', '0');
}

try
{
    $authorization = OW::getAuthorization();
    $groupName = 'iismobilesupport';
    $authorization->addGroup($groupName);
    $authorization->addAction($groupName, 'show-desktop-version');
}
catch ( LogicException $e ) {}

OW::getDbo()->query("
DROP TABLE IF EXISTS  `" . OW_DB_PREFIX . "iismobilesupport_device`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iismobilesupport_device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `token` longtext NOT NULL,
  `time` int(1),
  `type` varchar(30) NOT NULL,
  `cookie` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

OW::getDbo()->query("
DROP TABLE IF EXISTS  `" . OW_DB_PREFIX . "iismobilesupport_app_version`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iismobilesupport_app_version` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(30) NOT NULL,
  `versionName` varchar(100) NOT NULL,
  `versionCode` int(100),
  `url` varchar(400) NOT NULL,
  `timestamp` int(11),
  `deprecated` BOOL NOT NULL DEFAULT \'0\',
  `message` varchar(400),
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

OW::getDbo()->query("
DROP TABLE IF EXISTS  `" . OW_DB_PREFIX . "iismobilesupport_notifications`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iismobilesupport_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data` longtext NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');
