<?php

try
{
    $dbo = Updater::getDbo();
    $logger = Updater::getLogger();
    $query = 'drop index `entityText` on `'.OW_DB_PREFIX.'base_search_entity`';
    $dbo->query($query);
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
