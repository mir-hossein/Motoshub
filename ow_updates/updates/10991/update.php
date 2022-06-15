<?php

try {
    $tableName = OW_DB_PREFIX . 'iismobilesupport_notifications';
    $q = 'DROP TABLE IF EXISTS `' . IISSecurityProvider::getTableBackupName($tableName) . '`';
    Updater::getDbo()->query($q);
}
catch (Exception $ex) {}

try {
    $tableName = OW_DB_PREFIX . 'iissecurityessentials_request_manager';
    $q = 'DROP TABLE IF EXISTS `' . IISSecurityProvider::getTableBackupName($tableName) . '`';
    Updater::getDbo()->query($q);
}
catch (Exception $ex) {}

try {
    $tableName = OW_DB_PREFIX . 'base_db_cache';
    $q = 'DROP TABLE IF EXISTS `' . IISSecurityProvider::getTableBackupName($tableName) . '`';
    Updater::getDbo()->query($q);
}
catch (Exception $ex) {}

try {
    $tableName = OW_DB_PREFIX . 'iishashtag_tag';
    $q = 'DROP TABLE IF EXISTS `' . IISSecurityProvider::getTableBackupName($tableName) . '`';
    Updater::getDbo()->query($q);
}
catch (Exception $ex) {}