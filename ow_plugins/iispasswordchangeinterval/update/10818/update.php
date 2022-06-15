<?php
$query = 'SELECT * FROM `'.OW_DB_PREFIX.'iispasswordchangeinterval_password_validation`';
$allRecords = Updater::getDbo()->queryForList($query);

$userService = BOL_UserService::getInstance();
foreach ($allRecords as $record){
    if(!isset($record['userId'])){
        $query = 'DELETE FROM `'.OW_DB_PREFIX.'iispasswordchangeinterval_password_validation` WHERE `id`='. $record['id'] .';';
        Updater::getDbo()->query($query);
    }
}
