<?php
$logger = Updater::getLogger();
$tblPrefix = OW_DB_PREFIX;
$query = "ALTER TABLE  `{$tblPrefix}mailbox_message` ADD  `changed` TINYINT NOT NULL DEFAULT '0'";
try
{
    Updater::getDbo()->query($query);
    $sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "mailbox_deleted_message` (
  `id` int(10) NOT NULL auto_increment,
  `deletedId` int(10) NOT NULL,
  `conversationId` int(10) NOT NULL default '0',
  `time` bigint(10) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

    OW::getDbo()->query($sql);
}
catch ( Exception $e )
{
    $logger->addEntry(json_encode($e));
}
