<?php
try {
    $query = "ALTER TABLE `" . OW_DB_PREFIX . "base_attachment` MODIFY `fileName` VARCHAR (256)";
    Updater::getDbo()->query($query);

    $query = "ALTER TABLE `" . OW_DB_PREFIX . "base_attachment` MODIFY `origFileName` VARCHAR (256)";
    Updater::getDbo()->query($query);

}catch (Exception $e)
{
    $exArr[] = $e;
}
