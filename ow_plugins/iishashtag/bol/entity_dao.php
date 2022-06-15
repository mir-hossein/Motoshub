<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_BOL_EntityDao extends OW_BaseDao
{
    protected function __construct()
    {
        parent::__construct();
    }
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
        return 'IISHASHTAG_BOL_Entity';
    }

    public function getTableName()
    {
        return OW_DB_PREFIX . 'iishashtag_entity';
    }

    public function getItemsByTagId($tagId){
        $ex = new OW_Example();
        $ex->andFieldEqual('tagId', $tagId);
        return $this->findListByExample($ex);
    }
    public function itemExists($tagId,$entityId,$entityType)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('tagId', $tagId);
        $ex->andFieldEqual('entityId', $entityId);
        $ex->andFieldEqual('entityType', $entityType);
        return ($this->countByExample($ex)>0);
    }

    public function countEntitiesForTagId($tagId)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('tagId', $tagId);
        return $this->countByExample($ex);
    }

    public function findEntityList($tagId, $entityType)
    {
        $sql = 'SELECT * FROM `' . $this->getTableName() . '`
         WHERE `tagId` = :tagId AND ((`entityType` = :entityType AND `context` IS NULL) OR `context` = :entityType)
         ORDER BY `id` DESC LIMIT 0,400';
        $params = array(
            'tagId' => $tagId,
            'entityType' => $entityType
        );
        return $this->dbo->queryForObjectList($sql,$this->getDtoClassName(),$params);
    }

    /**
     * @param $tagId
     * @return array
     */
    public function findGroupEntityList($tagId)
    {
        $sql = 'SELECT * FROM `' . $this->getTableName() . '`
         WHERE `tagId` = :tagId AND (((`entityType` = :groupStatus OR `entityType` = :groupFeed) AND `context` IS NULL) OR (`context` = :groupStatus OR `context` = :groupFeed))
         ORDER BY `id` DESC LIMIT 0,400';
        $params = array(
            'tagId' => $tagId,
            'groupStatus' => 'groups-status',
            'groupFeed' => 'groups-feed'
        );
        return $this->dbo->queryForObjectList($sql,$this->getDtoClassName(),$params);
    }

    public function findEntities($entityId,$entityType)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('entityId', $entityId);
        $ex->andFieldEqual('entityType', $entityType);
        return $this->findIdListByExample($ex);
    }
}
