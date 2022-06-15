<?php
$query = 'SELECT * FROM `'.OW_DB_PREFIX.'iispasswordchangeinterval_password_validation`';
$allRecords = Updater::getDbo()->queryForList($query);
try{
    Updater::getDbo()->query('DROP TABLE IF EXISTS`' . OW_DB_PREFIX . 'iispasswordchangeinterval_password_validation`');
}catch (Exception $e1){

}
try{
    Updater::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iispasswordchangeinterval_password_validation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `valid` int(1) NOT NULL,
  `token` VARCHAR(128),
  `tokenTime` int(11) NOT NULL,
  `passwordTime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;');
}catch (Exception $e2){
}

$userService = BOL_UserService::getInstance();
foreach ($allRecords as $record){
    if(!isset($record['username'])){
        continue;
    }
    $username= $record['username'] ;
    if($userService->findByUsername($username) !== null){
        $userId = $userService->findByUsername($username)->getId();
        try{
            $query='INSERT INTO`' . OW_DB_PREFIX . 'iispasswordchangeinterval_password_validation` (`id`, `userId`, `valid`, `token`, `tokenTime`, `passwordTime`) VALUES
            ('.$record['id'].','.$userId.','.$record['valid'].',"'.$record['token'].'",'.$record['tokentime'].','.$record['passwordtime'].')';
            Updater::getDbo()->query($query);
        }catch (Exception $e3){

        }
    }
}
