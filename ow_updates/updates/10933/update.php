<?php

try
{
    $logger = Updater::getLogger();
    $dbo = Updater::getDbo();


    $query = "ALTER TABLE `".OW_DB_PREFIX."base_component_entity_position` MODIFY `componentPlaceUniqName` VARCHAR(255)";
    $dbo->query($query);

    $query = "ALTER TABLE `".OW_DB_PREFIX."base_component_entity_setting` MODIFY `componentPlaceUniqName` VARCHAR(255)";
    $dbo->query($query);


    $query = "ALTER TABLE `".OW_DB_PREFIX."base_component_position` MODIFY `componentPlaceUniqName` VARCHAR(255)";
    $dbo->query($query);

    $query = "ALTER TABLE `".OW_DB_PREFIX."base_component_setting` MODIFY `componentPlaceUniqName` VARCHAR(255)";
    $dbo->query($query);
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}

