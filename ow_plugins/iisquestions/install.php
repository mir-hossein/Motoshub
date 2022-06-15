<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/25/18
 * Time: 2:50 PM
 */

$authorization = OW::getAuthorization();
$groupName = 'iisquestions';
$authorization->addGroup($groupName);

$authorization->addAction($groupName, 'answer');
$authorization->addAction($groupName, 'add_answer');

if (!OW::getConfig()->configExists('iisquestions', 'allow_comments'))
    OW::getConfig()->addConfig('iisquestions', 'allow_comments', true);
if (!OW::getConfig()->configExists('iisquestions', 'enable_follow'))
    OW::getConfig()->addConfig('iisquestions', 'enable_follow', true);

$sql = array();

OW::getDbo()->query("
    DROP TABLE IF EXISTS `" . OW_DB_PREFIX . "iisquestions_question`;");

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisquestions_question` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `owner` int(11) NOT NULL,
            `privacy` VARCHAR(15),
            `addOption` VARCHAR(15) NOT NULL,
            `context` VARCHAR(20) NOT NULL,
            `contextId` int(11) NOT NULL,
            `isMultiple` TINYINT(1) NOT NULL,
            `timeStamp` int(11) NOT NULL,
            `entityType` VARCHAR(30),
            `entityId` int(11),
            PRIMARY KEY (`id`)
        ) DEFAULT CHARSET=utf8 ;';

OW::getDbo()->query("
    DROP TABLE IF EXISTS `" . OW_DB_PREFIX . "iisquestions_option`;");

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisquestions_option` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `questionId` int(11) NOT NULL,
            `userId` int(11) NOT NULL,
            `text` text CHARACTER SET utf8 NOT NULL,
            `timeStamp` int(11) NOT NULL,
            PRIMARY KEY (`id`)
        ) CHARSET=utf8 ;';

OW::getDbo()->query("
    DROP TABLE IF EXISTS  `" . OW_DB_PREFIX . "iisquestions_answer`;");

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisquestions_answer` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `userId` INT NOT NULL ,
            `questionId` int(11) NOT NULL,
            `optionId` INT NOT NULL ,
            `timeStamp` INT NOT NULL ,
            PRIMARY KEY (`id`),
            UNIQUE KEY `userId` (`userId`,`optionId`)
        ) CHARSET=utf8 ;';

OW::getDbo()->query("
    DROP TABLE IF EXISTS  `" . OW_DB_PREFIX . "iisquestions_subscribe`;");

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisquestions_subscribe` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `userId` int(11) NOT NULL,
            `questionId` int(11) NOT NULL,
            `timeStamp` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `userId` (`userId`,`questionId`)
        ) DEFAULT CHARSET=utf8 ;';

foreach ($sql as $q) {
    OW::getDbo()->query($q);
}
