<?php

try
{
    $sql = "ALTER TABLE iisbckp_".OW_DB_PREFIX."iismobilesupport_app_version MODIFY COLUMN `message` VARCHAR(400) AFTER `deprecated`;";
    Updater::getDbo()->query($sql);
}
catch ( Exception $e ){ }