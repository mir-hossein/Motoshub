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
class IISOGHAT_BOL_CityDao extends OW_BaseDao
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
        return 'IISOGHAT_BOL_City';
    }

    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisoghat_city';
    }

    /***
     * @return array
     */
    public function getAllCity()
    {
        $ex = new OW_Example();
        return $this->findAll();
    }

    /***
     * @param $name
     * @param $longitude
     * @param $latitude
     * @param int $default
     * @return IISOGHAT_BOL_City
     */
    public function addCity($name, $longitude, $latitude, $default = 0)
    {
        $city = new IISOGHAT_BOL_City;
        $city->name = $name;
        $city->latitude = $latitude;
        $city->longitude = $longitude;
        $city->default = $default;
        $this->save($city);
        return $city;
    }

    /***
     * @param $name
     * @param $longitude
     * @param $latitude
     * @return bool
     */
    public function existCity($name, $longitude, $latitude)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('name', $name);
        $ex->andFieldEqual('longitude', $longitude);
        $ex->andFieldEqual('latitude', $latitude);
        $res = $this->findObjectByExample($ex);
        if ($res != null) {
            return true;
        }

        return false;
    }

    /***
     * @param $name
     */
    public function deleteCity($name)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('name', $name);
        $res = $this->deleteByExample($ex);
    }

}