<?php

$logger = Updater::getLogger();
try
{
    $query =  "CREATE TABLE IF NOT EXISTS `".OW_DB_PREFIX."base_photo_size` (
        id INT(11) NOT NULL AUTO_INCREMENT,
        originalPath VARCHAR(255) NOT NULL,
        width INT(11) NOT NULL,
        height INT(11) NOT NULL,
        PRIMARY KEY (id)
    )
    CHARACTER SET utf8 COLLATE utf8_general_ci";
    Updater::getDbo()->query($query);
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
