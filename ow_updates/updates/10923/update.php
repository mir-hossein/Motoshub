<?php

try
{
    $logger = Updater::getLogger();
    $dbo = Updater::getDbo();
    $query = "delete from `".OW_DB_PREFIX."base_theme_content` where `type`='decorator'";
    $dbo->query($query);
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}