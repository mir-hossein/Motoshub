<?php

try
{
    $sql = "ALTER TABLE `".OW_DB_PREFIX."iismobilesupport_device` ADD `cookie` varchar(255) NOT NULL;";
    Updater::getDbo()->query($sql);
}
catch ( Exception $e ){ }