<?php

$tblPrefix = OW_DB_PREFIX;

$dbo = Updater::getDbo();
$logger = Updater::getLogger();


try
{
    $query = "ALTER TABLE `".OW_DB_PREFIX."base_login_cookie` ADD `timestamp` int(11) NOT NULL default '".time()."';";
    $row = $dbo->query($query);
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}