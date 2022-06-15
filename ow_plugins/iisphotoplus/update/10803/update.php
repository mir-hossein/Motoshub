<?php

try {
        OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisphotoplus_status_photo` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `photoId`int(11) NOT NULL,
              `userId` int(11) NOT NULL,
              PRIMARY KEY (`id`)
        ) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');

} catch (Exception $e) {
    Updater::getLogger()->addEntry(json_encode($e));
}