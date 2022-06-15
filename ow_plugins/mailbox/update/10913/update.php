<?php
$dbo = Updater::getDbo();
$query = "DELETE FROM `".OW_DB_PREFIX."notifications_rule` WHERE `action` IN ('mailbox-new_message','mailbox-new_chat_message')";
$dbo->query($query);