<?php
$dbo = Updater::getDbo();
$query = "ALTER TABLE `".OW_DB_PREFIX."mailbox_attachment` MODIFY `hash` VARCHAR(64)";
$dbo->query($query);