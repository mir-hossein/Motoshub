<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisoghat.bol
 * @since 1.0
 */
class IISOGHAT_BOL_Service
{
    CONST CATCH_REQUESTS_KEY = 'iisoghat.catch';

    private static $classInstance;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private $iisOghatDao;

    private function __construct()
    {
        $this->iisOghatDao = IISOGHAT_BOL_CityDao::getInstance();
    }

    public function importingDefaultItems()
    {
        if (OW::getConfig()->configExists('iisoghat', 'importDefaultItem') && !OW::getConfig()->getValue('iisoghat', 'importDefaultItem')) {
            OW::getConfig()->saveConfig('iisoghat', 'importDefaultItem', true);
            $xml = simplexml_load_file(OW::getPluginManager()->getPlugin('iisoghat')->getStaticDir() . 'xml'.DIRECTORY_SEPARATOR.'defaultItems.xml');
            $cities = $xml->xpath("/cities");
            $cities = $cities[0]->xpath('child::city');
            foreach ($cities as $city) {
                $information = explode(',',(string)$city->name);
                $name = $information[2];
                $latitude = $information[0];
                $longitude = $information[1];
                $default = $information[3];
                if(!$this->existCity($name, $longitude, $latitude)) {
                    $this->addCity($name, $longitude, $latitude, $default);
                }
            }
        }
    }

    /***
     * @return array
     */
    public function getAllCity()
    {
        return $this->iisOghatDao->getAllCity();
    }

    /***
     * @param $name
     * @param $logitude
     * @param $latitude
     * @param int $default
     * @return IISOGHAT_BOL_City|void
     */
    public function addCity($name, $logitude, $latitude, $default = 0){
        return $this->iisOghatDao->addCity($name, $logitude, $latitude, $default);
    }


    /***
     * @param $name
     * @param $logitude
     * @param $latitude
     * @return bool
     */
    public function existCity($name, $logitude, $latitude){
        return $this->iisOghatDao->existCity($name, $logitude, $latitude);
    }

    /***
     * @param $name
     */
    public function deleteCity($name){
        $this->iisOghatDao->deleteCity($name);
    }
}
