<?php

$dbo = Updater::getDbo();
$query = "ALTER TABLE `".OW_DB_PREFIX."forum_post_attachment` MODIFY `hash` VARCHAR(64)";
$dbo->query($query);
