<?php

try {

    OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iismobilesupport_app_version` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(30) NOT NULL,
  `versionName` varchar(100) NOT NULL,
  `versionCode` int(100),
  `url` varchar(400) NOT NULL,
  `timestamp` int(11),
  `deprecated` BOOL NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

    Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'iismobilesupport');

} catch (Exception $e) {
    Updater::getLogger()->addEntry(json_encode($e));
}