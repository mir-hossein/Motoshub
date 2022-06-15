<?php
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 */

$sql = "ALTER TABLE `".OW_DB_PREFIX."notifications_notification` 
   DROP INDEX entityType, 
   ADD UNIQUE KEY `entityType` (`userId`, `entityId`, `entityType`)";

Updater::getDbo()->query($sql);