<?php


class IISGROUPSPLUS_BOL_ChannelDao extends OW_BaseDao
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
     * @var IISGROUPSPLUS_BOL_ChannelDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return IISGROUPSPLUS_BOL_ChannelDao
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
        return 'IISGROUPSPLUS_BOL_Channel';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisgroupsplus_channel';
    }

    public function addChannel($groupId)
    {
        $channel = new IISGROUPSPLUS_BOL_Channel();
        $channel->setGroupId($groupId);
            $this->save($channel);

    }
    public function deleteByGroupId( $groupId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('groupId', $groupId);
        return $this->deleteByExample($ex);
    }

    public function findIsExistGroupId($groupId)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('groupId', $groupId);
        return $this->findObjectByExample($ex);
    }
}