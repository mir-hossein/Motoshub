<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iismobilesupport.bol
 * @since 1.0
 */
class IISMOBILESUPPORT_BOL_AppVersionDao extends OW_BaseDao
{
    private static $classInstance;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function getDtoClassName()
    {
        return 'IISMOBILESUPPORT_BOL_AppVersion';
    }
    
    public function getTableName()
    {
        return OW_DB_PREFIX . 'iismobilesupport_app_version';
    }

    /***
     * @param $type
     * @return array
     */
    public function getAllVersions($type){
        $ex = new OW_Example();
        $ex->andFieldEqual('type', $type);
        $ex->setOrder('`versionCode` DESC');
        return $this->findListByExample($ex);
    }

    /***
     * @param $type
     * @return mixed
     */
    public function getLastVersions($type){
        $ex = new OW_Example();
        $ex->andFieldEqual('type', $type);
        $ex->setOrder('`versionCode` DESC');
        $allVersions = $this->findListByExample($ex);
        if($allVersions != null && sizeof($allVersions)>0){
            return $allVersions[0];
        }
        return null;
    }

    /***
     * @param $id
     */
    public function deleteVersion($id){
        $item = $this->findById($id);
        $type = $item->type;

        $ex = new OW_Example();
        $ex->andFieldEqual('id', $id);
        $this->deleteByExample($ex);

        //Last version should not deprecated
        $lastVersion = $this->getLastVersions($type);
        if($lastVersion != null) {
            $lastVersion->deprecated = false;
            $this->save($lastVersion);
        }
    }

    /***
     * @param $type
     * @param $versionName
     * @param $versionCode
     * @return bool
     */
    public function hasVersion($type, $versionName, $versionCode){
        $ex = new OW_Example();
        $ex->andFieldEqual('type', $type);
        $ex->andFieldEqual('versionName', $versionName);
        $ex->andFieldEqual('versionCode', $versionCode);
        return $this->findObjectByExample($ex);
    }

    /***
     * @param $type
     * @param $versionCode
     * @return mixed
     */
    public function getVersionUsingCode($type, $versionCode){
        $ex = new OW_Example();
        $ex->andFieldEqual('type', $type);
        $ex->andFieldEqual('versionCode', $versionCode);
        return $this->findObjectByExample($ex);
    }


    /***
     * @param $type
     * @param $versionName
     * @param $versionCode
     * @param $url
     * @param $message
     * @return bool|IISMOBILESUPPORT_BOL_AppVersion
     */
    public function saveVersion($type, $versionName, $versionCode, $url, $message){
        $oldVersion = $this->hasVersion($type, $versionName, $versionCode);
        if($oldVersion != null){
            return $oldVersion;
        }
        $version = new IISMOBILESUPPORT_BOL_AppVersion();
        $version->type = $type;
        $version->versionName = $versionName;
        $version->versionCode = $versionCode;
        $version->url = $url;
        $version->message = $message;
        $version->timestamp = time();
        $version->deprecated = false;
        $this->save($version);
        return $version;
    }

    /***
     * @param $id
     * @return mixed
     */
    public function deprecateVersion($id){
        $ex = new OW_Example();
        $ex->andFieldEqual('id', $id);
        $version = $this->findObjectByExample($ex);
        $version->deprecated = true;
        $this->save($version);
        return $version;
    }

    /***
     * @param $id
     * @return mixed
     */
    public function approveVersion($id){
        $ex = new OW_Example();
        $ex->andFieldEqual('id', $id);
        $version = $this->findObjectByExample($ex);
        $version->deprecated = false;
        $this->save($version);
        return $version;
    }
}
