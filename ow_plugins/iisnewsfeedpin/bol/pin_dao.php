<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisoghat.bol
 * @since 1.0
 */
class IISNEWSFEEDPIN_BOL_PinDao extends OW_BaseDao
{
    private static $classInstance;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function getDtoClassName()
    {
        return 'IISNEWSFEEDPIN_BOL_Pin';
    }

    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisnewsfeedpin_pin';
    }

    public function findMostRecentPins($maxNum){
        $example = new OW_Example();
        $example->setLimitClause(0,$maxNum);
        $example->setOrder('createDate desc');
        return $this->findListByExample($example);
    }

    public function findByEntityIdAndEntityType($entityId,$entityType){
        $example = new OW_Example();
        $example->andFieldEqual('entityId',(int) $entityId);
        $example->andFieldEqual('entityType',$entityType);
        return $this->findObjectByExample($example);
    }

    public function deleteByEntityIdAndEntityType($entityId,$entityType){
        $example = new OW_Example();
        $example->andFieldEqual('entityId',(int) $entityId);
        $example->andFieldEqual('entityType',$entityType);
        $this->deleteByExample($example);
    }
}