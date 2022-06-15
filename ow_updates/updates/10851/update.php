<?php

$tblPrefix = OW_DB_PREFIX;
$prefixBackuplabel = 'iisbckp_';
$prefixRemovedlabel = 'removed_';
$updateTriggerNameBackupTable = 'uiistrig_';
$dbo = Updater::getDbo();
$logger = Updater::getLogger();

try
{
    if(!defined('BACKUP_TABLES_USING_TRIGGER') || BACKUP_TABLES_USING_TRIGGER == true) {
        $queryGetAllTables = 'select * from information_schema.tables WHERE TABLE_SCHEMA = \'' . OW_DB_NAME . '\'';
        $allTables = $dbo->queryForList($queryGetAllTables);
        $tablesWithCustomTriggerForUpdate = array(OW_DB_PREFIX . 'base_user');
        foreach ($allTables as $table) {

            //Main table name
            $tableName = $table['TABLE_NAME'];
            //Table name for backup updated or removed data
            $backupTableName = $prefixBackuplabel . $tableName;
            //Check backup table exists or not
            $backupTable = $dbo->queryForRow('show tables like :tableName', array('tableName' => $backupTableName));
            if (!empty($backupTable)) {
                /*
                 * remove incorrect update Triggers
                 */
                $updateTriggerName = $updateTriggerNameBackupTable . $tableName;
                $triggerQueryForDoinBackupBeforeRemove = 'DROP TRIGGER IF EXISTS ' . $updateTriggerName . ';';
                $dbo->query($triggerQueryForDoinBackupBeforeRemove);

                if (!in_array($tableName, $tablesWithCustomTriggerForUpdate)) {
                    //Add trigger for updated data
                    $triggerQueryForDoinBackupBeforUpdate = 'DROP TRIGGER IF EXISTS ' . $updateTriggerName . '; CREATE TRIGGER ' . $updateTriggerName . ' Before UPDATE ON ' . $tableName . ' FOR EACH ROW  BEGIN INSERT INTO ' . $backupTableName . ' (select tbl.*, UNIX_TIMESTAMP(NOW()) as backup_timestamp, \'u\' as backup_action, NULL as backup_pk_id from ' . $tableName . ' tbl where tbl.id = OLD.id); END;';
                    $dbo->query($triggerQueryForDoinBackupBeforUpdate);
                }
            }
        }
        $updateTriggerName = OW_DB_PREFIX . 'base_user_uiistrig';
        $tableName = OW_DB_PREFIX . 'base_user';
        $backupTableName = 'iisbckp_' . OW_DB_PREFIX . 'base_user';
        $columnDrop = 'backup_userId';
        //Add trigger for updated data
        $triggerQueryForDoinBackupBeforeUpdate = 'DROP TRIGGER IF EXISTS ' . $updateTriggerName . '; CREATE TRIGGER ' . $updateTriggerName . ' Before UPDATE ON ' . $tableName . ' FOR EACH ROW  BEGIN  IF NEW.email <> OLD.email  OR NEW.password <> OLD.password OR NEW.accountType <> OLD.accountType OR NEW.username <> OLD.username THEN  INSERT INTO ' . $backupTableName . ' (select tbl.*, UNIX_TIMESTAMP(NOW()) as backup_timestamp, \'u\' as backup_action, NULL as backup_pk_id from ' . $tableName . ' tbl where tbl.id = OLD.id); END IF; END;';
        $dbo->query($triggerQueryForDoinBackupBeforeUpdate);
    }
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
