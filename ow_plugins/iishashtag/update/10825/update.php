<?php
/**
 * User: Ismail Mirvakili
 */

OW::getDbo()->query("ALTER TABLE `" . OW_DB_PREFIX . "iishashtag_entity` ADD `context` VARCHAR(100) DEFAULT NULL");
