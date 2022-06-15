<?php
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 */
try {
    $tableName = OW_DB_PREFIX . 'iismobilesupport_notifications';
    $q = 'DROP TABLE IF EXISTS `' . $tableName . '`';
    Updater::getDbo()->query($q);
    $tableName = OW_DB_PREFIX . 'iismobilesupport_notifications';
    $q = 'DROP TABLE IF EXISTS `' . IISSecurityProvider::getTableBackupName($tableName) . '`';
    Updater::getDbo()->query($q);
    $dropRemoveTriggerOfTableDontNeedBackupQuery = 'DROP TRIGGER IF EXISTS ' . IISSecurityProvider::$removeTriggerNameBackupTable . $tableName;
    Updater::getDbo()->query($dropRemoveTriggerOfTableDontNeedBackupQuery);
    $dropUpdateTriggerOfTableDontNeedBackupQuery = 'DROP TRIGGER IF EXISTS ' . IISSecurityProvider::$updateTriggerNameBackupTable . $tableName;
    Updater::getDbo()->query($dropUpdateTriggerOfTableDontNeedBackupQuery);
}catch (Exception $ex){}