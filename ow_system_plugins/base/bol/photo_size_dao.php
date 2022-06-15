<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_system_plugins.base.bol
 */
class BOL_PhotoSizeDao extends OW_BaseDao
{
    /**
     *
     * @var BOL_PhotoSizeDao
     */
    private static $classInstance;

    /**
     * @return BOL_PhotoSizeDao
     */
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
        return OW_DB_PREFIX . 'base_photo_size';
    }

    public function getDtoClassName()
    {
        return 'BOL_PhotoSizeDao';
    }

    public function findPhoto($path, $width, $height) {
        $example = new OW_Example();

        $example->andFieldEqual('originalPath', $path);
        $example->andFieldEqual('width', $width);
        $example->andFieldEqual('height', $height);

        return $this->findIdByExample($example);
    }
}