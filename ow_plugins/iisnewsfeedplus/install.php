<?php

try {
    OW::getDbo()->query('CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisnewsfeedplus_thumbnail` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `attachmentId` int(11) NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `userId` int(11) NOT NULL,
    `creationTime` int(11),
    PRIMARY KEY (`id`)
    )DEFAULT CHARSET=utf8');
} catch (Exception $e) {}

$config = OW::getConfig();

if($config->configExists('iisnewsfeedplus', 'newsfeed_list_order')){
    $config->saveConfig('iisnewsfeedplus', 'newsfeed_list_order','activity');
}
else {
    $config->addConfig('iisnewsfeedplus', 'newsfeed_list_order','activity');
}
if ($config->configExists('iisnewsfeedplus', 'allow_sort'))
{
    $config->saveConfig('iisnewsfeedplus', 'allow_sort','1');
}
else{
    $config->addConfig('iisnewsfeedplus', 'allow_sort', '1','Allow sort feeds');
}
