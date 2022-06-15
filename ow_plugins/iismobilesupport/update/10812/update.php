<?php
/**
 * @author Yaser Alimardany
 * Date: 7/13/2017
 */

try
{
    $sql = "ALTER TABLE `".OW_DB_PREFIX."iismobilesupport_device` ADD `type` varchar(30) NOT NULL default '1';";
    Updater::getDbo()->query($sql);
}
catch ( Exception $e ){ }