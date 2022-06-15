<?php
$logger = Updater::getLogger();
$tblPrefix = OW_DB_PREFIX;
$query = "ALTER TABLE  `{$tblPrefix}mailbox_message` ADD  `replyId` int(10) DEFAULT NULL";
try
{
    Updater::getDbo()->query($query);
}
catch ( Exception $e )
{
    $logger->addEntry(json_encode($e));
}