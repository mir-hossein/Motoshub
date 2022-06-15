<?php

OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisgroupsplus_group_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupId` int(11) NOT NULL,
  `whoCanUploadFile` varchar(100) NOT NULL default "participant",
  `whoCanCreateTopic` varchar(100) NOT NULL default "participant",
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');
