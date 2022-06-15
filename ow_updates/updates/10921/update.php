<?php

try {
    $dbo = Updater::getDbo();
    $logger = Updater::getLogger();
    $query = "alter table `" . OW_DB_PREFIX . "base_user` add column `salt` varchar(64) NOT NULL DEFAULT ''";
    $dbo->query($query);
}catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
