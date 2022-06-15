<?php

try
{
    $dbo = Updater::getDbo();
    $logger = Updater::getLogger();
    $query = 'drop index `content` on `'.OW_DB_PREFIX.'photo_search_index`';
    $dbo->query($query);
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
