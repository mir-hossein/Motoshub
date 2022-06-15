<?php

try
{
    $query = "DROP TABLE IF EXISTS `" . OW_DB_PREFIX . "base_log`";
    Updater::getDbo()->query($query);

    $query = "DROP TABLE IF EXISTS `" . IISSecurityProvider::getTableBackupName(OW_DB_PREFIX . 'base_log')  . "`";
    Updater::getDbo()->query($query);
}
catch (Exception $e)
{
    Updater::getLogger()->addEntry(json_encode($e));
}
