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

/**
 * @author Kairat Bakitow <kainisoft@gmail.com>
 * @package ow_plugins.photo.bol
 * @since 1.6.1
 */
class PHOTO_BOL_PhotoCacheDao extends OW_BaseDao
{
    CONST KEY = 'key';
    CONST CREATE_TIMESTAMP = 'createTimestamp';
    
    CONST CACHE_LIFETIME = 10;
    
    private static $classInstance;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    public function getTableName()
    {
        return OW_DB_PREFIX . 'photo_cache';
    }
    
    public function getDtoClassName()
    {
        return 'PHOTO_BOL_PhotoCache';
    }
    
    public function getKey( $searchVal )
    {
        return crc32(OW::getUser()->getId() . $searchVal);
    }
    
    public function getKeyAll( $searchVal )
    {
        return crc32(OW::getUser()->getId() . $searchVal . 'all');
    }

    public function findCacheByKey( $key )
    {
        if ( empty($key) )
        {
            return NULL;
        }
        
        $sql = 'SELECT *
            FROM `' . $this->getTableName() . '`
            WHERE `' . self::KEY . '` = :key
            LIMIT 1';

        $result = $this->dbo->queryForObject($sql, $this->getDtoClassName(), array('key' => $key));

        if ($result !== null && $result->createTimestamp <= time() - self::CACHE_LIFETIME * 60) {
            $this->delete($result);
            return null;
        }

        return $result;
    }
    
    public function cleareCache()
    {
        return $this->dbo->query('DELETE FROM `' . $this->getTableName() . '`
            WHERE `' . self::CREATE_TIMESTAMP . '` <= :time', array('time' => time() - self::CACHE_LIFETIME * 60));
    }
    public function invalidateCacheItem($key){
        if ( empty($key) )
        {
            return null;
        }
        $sql = 'DELETE
            FROM `' . $this->getTableName() . '`
            WHERE `' . self::KEY . '` = :key';

        return $this->dbo->query($sql,array('key'=> $key));
    }
}
