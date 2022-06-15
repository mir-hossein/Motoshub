<?php


/**
 * Data Access Object for `iiseventplus_category` table.
 *
 * @author Mohammad Agha Abbasloo
 * @package ow_plugins.iiseventplus.bol
 * @since 1.0
 */
class IISEVENTPLUS_BOL_CategoryDao extends OW_BaseDao
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
     * @var IISEVENTPLUS_BOL_CategoryDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return IISEVENTPLUS_BOL_CategoryDao
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
        return 'IISEVENTPLUS_BOL_Category';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'iiseventplus_category';
    }

    public function findIsExistLabel($label)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('label', $label);
        return $this->findObjectByExample($ex);
    }
}