<?php

try {
    $tableName = OW_DB_PREFIX . 'iismobilesupport_notifications';
    $q = 'DROP TRIGGER IF EXISTS `' . IISSecurityProvider::$removeTriggerNameBackupTable . $tableName;
    Updater::getDbo()->query($q);
    $q = 'DROP TRIGGER IF EXISTS `' . IISSecurityProvider::$updateTriggerNameBackupTable . $tableName;
    Updater::getDbo()->query($q);
}
catch (Exception $ex) {}

try {
    $tableName = OW_DB_PREFIX . 'iissecurityessentials_request_manager';
    $q = 'DROP TRIGGER IF EXISTS `' . IISSecurityProvider::$removeTriggerNameBackupTable . $tableName;
    Updater::getDbo()->query($q);
    $q = 'DROP TRIGGER IF EXISTS `' . IISSecurityProvider::$updateTriggerNameBackupTable . $tableName;
    Updater::getDbo()->query($q);
}
catch (Exception $ex) {}

try {
    $tableName = OW_DB_PREFIX . 'base_db_cache';
    $q = 'DROP TRIGGER IF EXISTS `' . IISSecurityProvider::$removeTriggerNameBackupTable . $tableName;
    Updater::getDbo()->query($q);
    $q = 'DROP TRIGGER IF EXISTS `' . IISSecurityProvider::$updateTriggerNameBackupTable . $tableName;
    Updater::getDbo()->query($q);
}
catch (Exception $ex) {}

try {
    $tableName = OW_DB_PREFIX . 'iishashtag_tag';
    $q = 'DROP TRIGGER IF EXISTS `' . IISSecurityProvider::$removeTriggerNameBackupTable . $tableName;
    Updater::getDbo()->query($q);
    $q = 'DROP TRIGGER IF EXISTS `' . IISSecurityProvider::$updateTriggerNameBackupTable . $tableName;
    Updater::getDbo()->query($q);
}
catch (Exception $ex) {}