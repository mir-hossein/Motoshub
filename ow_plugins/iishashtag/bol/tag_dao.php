<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_BOL_TagDao extends OW_BaseDao
{
    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }
    /**
     * Class instance
     *
     * @var IISHASHTAG_BOL_TagDao
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return IISHASHTAG_BOL_TagDao
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
        return 'IISHASHTAG_BOL_Tag';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'iishashtag_tag';
    }

    public function getItemByTagText($tag){
        $ex = new OW_Example();
        $ex->andFieldEqual('tag', $tag);

        return $this->findListByExample($ex);
    }
    public function itemExists($tag)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('tag', $tag);

        return ($this->countByExample($ex)>0);
    }

    public function countTags( )
    {
        $ex = new OW_Example();

        return $this->countByExample($ex);
    }

    public function findTagList($tag,$count)
    {
        $ex = new OW_Example();
        if(strlen($tag)>0)
            $ex->andFieldLike('tag', '%'.$tag.'%');
        $ex->setOrder('`count` DESC, `id` DESC');
        $ex->setLimitClause(0, intval($count));

        return $this->findListByExample($ex);
    }
    public function findTagListInAdvanceSearchPlugin($tag,$first,$count)
    {
        $ex = new OW_Example();
        if(strlen($tag)>0)
            $ex->andFieldLike('tag', '%'.$tag.'%');
        $ex->setOrder('`count` DESC, `id` DESC');
        $ex->setLimitClause($first, intval($count));

        return $this->findListByExample($ex);
    }
}
