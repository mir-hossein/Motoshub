<?php

/**
 * Copyright (c) 2016, Milad Heshmati
 * All rights reserved.
 */

/**
 * @author Milad Heshmati <milad.heshmati@gmail.com>
 * @package ow_plugins.iiswidgetplus
 * @since 1.0
 */
class IISWIDGETPLUS_CLASS_EventHandler
{
    private static $classInstance;

    /***
     * @return IISWIDGETPLUS_CLASS_EventHandler
     */
    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /***
     * IISWIDGETPLUS_CLASS_EventHandler constructor.
     */
    private function __construct()
    {
    }

    /***
     *
     */
    public function init()
    {
        $service=IISWIDGETPLUS_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($service, 'addWidgetJS'));
    }

}