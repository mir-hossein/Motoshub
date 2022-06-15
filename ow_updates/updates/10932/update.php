<?php

try
{
    $logger = Updater::getLogger();
    $dbo = Updater::getDbo();
    $query = "ALTER TABLE `".OW_DB_PREFIX."base_component_place` MODIFY `uniqName` VARCHAR(255)";
    $dbo->query($query);
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}