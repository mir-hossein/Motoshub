<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

try 
{
    OW::getAuthorization()->deleteAction('photo', 'delete_comment_by_content_owner');
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

$plugin = OW::getPluginManager()->getPlugin('photo');
$staticDir = OW_DIR_STATIC_PLUGIN . $plugin->getModuleName() . DS . 'js' .DS;

if ( !OW::getStorage()->fileExists($staticDir) )
{
    OW::getStorage()->mkdir($staticDir);
}

OW::getStorage()->copyFile($plugin->getStaticJsDir() . 'album.js', $staticDir . 'album.js', true);
OW::getStorage()->copyFile($plugin->getStaticJsDir() . 'browse_photo.js', $staticDir . 'browse_photo.js', true);
OW::getStorage()->copyFile($plugin->getStaticJsDir() . 'codemirror.min.js', $staticDir . 'codemirror.min.js', true);
OW::getStorage()->copyFile($plugin->getStaticJsDir() . 'jQueryRotate.min.js', $staticDir . 'jQueryRotate.min.js', true);
OW::getStorage()->copyFile($plugin->getStaticJsDir() . 'photo.js', $staticDir . 'photo.js', true);
OW::getStorage()->copyFile($plugin->getStaticJsDir() . 'upload.js', $staticDir . 'upload.js', true);
OW::getStorage()->copyFile($plugin->getStaticJsDir() . 'slider.min.js', $staticDir . 'slider.min.js', true);

try
{
    OW::getNavigation()->deleteMenuItem('photo', 'photo');
    OW::getNavigation()->addMenuItem(OW_Navigation::MAIN, 'view_photo_list', 'photo', 'photo', OW_Navigation::VISIBLE_FOR_ALL);   
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

try
{
    Updater::getDbo()->query('
        ALTER TABLE `' . OW_DB_PREFIX . 'photo`
            ADD `dimension` VARCHAR(128) NULL DEFAULT NULL ;
        ALTER TABLE `' . OW_DB_PREFIX . 'photo_album` ADD `description` TEXT NULL DEFAULT NULL AFTER `name`;
    ');
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

try
{
    Updater::getDbo()->query('
        CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'photo_album_cover` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `albumId` int(10) unsigned NOT NULL,
          `hash` varchar(100) DEFAULT NULL,
          `auto` tinyint(1) NOT NULL DEFAULT 1,
          PRIMARY KEY (`id`),
          UNIQUE KEY `albumId` (`albumId`)
        ) DEFAULT CHARSET=utf8 ;

        CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'photo_cache` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `key` int(11) NOT NULL,
          `data` text NOT NULL,
          `createTimestamp` int(10) unsigned NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `key` (`key`)
        ) DEFAULT CHARSET=utf8 ;

        CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'photo_search_data` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `entityTypeId` int(10) unsigned NOT NULL,
          `entityId` int(10) unsigned NOT NULL,
          `content` text NOT NULL,
          PRIMARY KEY (`id`)
        ) DEFAULT CHARSET=utf8 ;

        CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'photo_search_entity_type` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `entityType` varchar(15) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `entityType` (`entityType`)
        ) DEFAULT CHARSET=utf8 ;

        CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'photo_search_index` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `entityTypeId` int(10) unsigned NOT NULL,
          `entityId` int(10) unsigned NOT NULL,
          `content` text NOT NULL,
          PRIMARY KEY (`id`),
          KEY `entityTypeId` (`entityTypeId`,`entityId`)
        ) DEFAULT CHARSET=utf8 ;
    ');
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

try
{
    UPDATE_Autoload::getInstance()->addPackagePointer('PHOTO_BOL', $plugin->getBolDir());
    PHOTO_BOL_SearchService::getInstance()->addEntityType(PHOTO_BOL_SearchService::ENTITY_TYPE_ALBUM);
    PHOTO_BOL_SearchService::getInstance()->addEntityType(PHOTO_BOL_SearchService::ENTITY_TYPE_PHOTO);
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

try
{
    $entityTypeId = PHOTO_BOL_SearchService::getInstance()->getEntityTypeId(PHOTO_BOL_SearchService::ENTITY_TYPE_PHOTO);
    
    Updater::getDbo()->query('INSERT INTO `' . PHOTO_BOL_SearchDataDao::getInstance()->getTableName() . '` (`entityTypeId`, `entityId`, `content`)
        SELECT ' . $entityTypeId . ', `id`, `description`
        FROM `' . PHOTO_BOL_PhotoDao::getInstance()->getTableName() . '`');
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

$config = Updater::getConfigService();

if ( !$config->configExists('photo', 'photo_list_view_classic') )
{
    $config->addConfig('photo', 'photo_list_view_classic', FALSE);
}

if ( !$config->configExists('photo', 'album_list_view_classic') )
{
    $config->addConfig('photo', 'album_list_view_classic', FALSE);
}

if ( !$config->configExists('photo', 'photo_view_classic') )
{
    $config->addConfig('photo', 'photo_view_classic', FALSE);
}

if ( !$config->configExists('photo', 'download_accept') )
{
    $config->addConfig('photo', 'download_accept', TRUE);
}

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'photo');

Updater::getDbo()->query('DROP TABLE IF EXISTS `' . OW_DB_PREFIX . 'photo_update_tag`;
CREATE TABLE `' . OW_DB_PREFIX . 'photo_update_tag` (
  `entityTagId` int(10) unsigned NOT NULL,
  UNIQUE KEY `entityTagId` (`entityTagId`)
) DEFAULT CHARSET=utf8;');

function updatePhotoTags()
{
    $sql = 'SELECT `et`.`id`, `et`.`entityId`, `et`.`tagId`
            FROM `' . BOL_EntityTagDao::getInstance()->getTableName() . '` AS `et`
                INNER JOIN `'. PHOTO_BOL_PhotoDao::getInstance()->getTableName() . '` AS `p` ON(`et`.`entityId` = `p`.`id`)
            WHERE `et`.`entityType` = :entityType AND
                `et`.`id` NOT IN (SELECT `entityTagId` FROM `' . OW_DB_PREFIX . 'photo_update_tag`) AND
                `p`.`dimension` IS NULL
            LIMIT :limit';

    $tagList = OW::getDbo()->queryForList($sql, array('entityType' => 'photo', 'limit' => 500));

    if ( empty($tagList) )
    {
        return false;
    }

    $photoTagList = array();
    $tagIdList = array();

    foreach ( $tagList as $tag )
    {
        if ( !array_key_exists($tag['entityId'], $photoTagList) )
        {
            $photoTagList[$tag['entityId']] = array();
        }

        $photoTagList[$tag['entityId']][] = $tag['tagId'];
        $tagIdList[] = $tag['id'];
    }

    foreach ( $photoTagList as $photoId => $photoTag )
    {
        $tags = BOL_TagDao::getInstance()->findByIdList($photoTag);

        if ( empty($tags) )
        {
            continue;
        }

        $str = array();

        foreach ( $tags as $tag )
        {
            $str[] = '#' . implode('', array_map('trim', explode(' ', $tag->label)));
        }

        $photo = PHOTO_BOL_PhotoDao::getInstance()->findById($photoId);
        $photo->description .= ' ' . implode(' ', $str);
        PHOTO_BOL_PhotoDao::getInstance()->save($photo);
    }

    OW::getDbo()->query('INSERT IGNORE INTO `' . OW_DB_PREFIX . 'photo_update_tag`(`entityTagId`) VALUES(' . implode('),(', $tagIdList) . ');')  ;

    return true;
}

while (updatePhotoTags()) {
    ;
}
