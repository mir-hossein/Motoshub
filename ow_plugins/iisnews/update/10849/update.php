<?php
OW::getDbo()->query("ALTER TABLE `" . OW_DB_PREFIX . "iisnews_entry` MODIFY entry mediumtext Not Null");