<?php

if(!OW::getConfig()->configExists('iisnewsfeedpin','max_pin_number')){
    OW::getConfig()->addConfig('iisnewsfeedpin','max_pin_number',10);
}

OW::getDbo()->query("
DROP TABLE IF EXISTS  `" . OW_DB_PREFIX . "iisnewsfeedpin_pin`;");

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisnewsfeedpin_pin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entityId` int(11),
  `entityType` VARCHAR(128),
  `createDate` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');
