<?php
$query = "ALTER TABLE `" . OW_DB_PREFIX . "base_language_value` add column  `original_value` text default NULL";
try
{
    Updater::getDbo()->query($query);
}
catch (Exception $ex) {}
