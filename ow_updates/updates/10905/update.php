<?php

try
{
    $logger = Updater::getLogger();
    $dbo = Updater::getDbo();
    $query = 'drop table if exists `'.OW_DB_PREFIX.'base_geolocationdata_ipv4`';
    $dbo->query($query);
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}