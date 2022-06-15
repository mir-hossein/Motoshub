<?php



/**
 * Data Access Object for `iisgroupsplus_group_managers` table.
 *
 * @author Mohammad
 * @package ow_plugins.iisgroupsplus.bol
 * @since 1.0
 */
class IISGROUPSPLUS_BOL_GroupManagersDao extends OW_BaseDao
{

    /**
     * Constructor.
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }
    /**
     * Singleton instance.
     *
     * @var IISGROUPSPLUS_BOL_GroupManagersDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return IISGROUPSPLUS_BOL_GroupManagersDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'IISGROUPSPLUS_BOL_GroupManagers';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisgroupsplus_group_managers';
    }

    public function getGroupManagersByGroupId($groupId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('groupId', $groupId);
        return $this->findListByExample($example);
    }

    public function getGroupManagerByUidAndGid($groupId,$userId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('groupId', $groupId);
        $example->andFieldEqual('userId', $userId);
        return $this->findObjectByExample($example);
    }

    public function deleteGroupManagerByUidAndGid($groupId, $userId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        $example->andFieldEqual('groupId', $groupId);
        return $this->deleteByExample($example);
    }

    public function deleteGroupManagerByGroupId($groupId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('groupId', $groupId);
        return $this->deleteByExample($example);
    }
    public function deleteGroupManagerByUserId($userId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        return $this->deleteByExample($example);
    }

    public function addUserAsManager($groupId, $userId)
    {
        $groupManagers = new IISGROUPSPLUS_BOL_GroupManagers();
        $groupManagers->setGroupId($groupId);
        $groupManagers->setUserId($userId);
        $this->save($groupManagers);
    }

}