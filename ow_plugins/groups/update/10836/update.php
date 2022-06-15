<?php

try {
    Updater::getDbo()->update("ALTER TABLE `".OW_DB_PREFIX."groups_group` ADD `lastActivityTimeStamp` int(11) default '0'");
}catch(Exception $e){

}