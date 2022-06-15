<?php
/**
 * @author Zarif Safiullin <zaph.work@gmail.com>
 * @package ow.ow_plugins.mailbox
 * @since 1.7.2
 */

Updater::getConfigService()->addConfig('mailbox', 'send_message_interval', 60);

$sql = "ALTER TABLE  `".OW_DB_PREFIX."mailbox_user_last_data` ADD INDEX  `userId` (  `userId` )";
Updater::getDbo()->query($sql);