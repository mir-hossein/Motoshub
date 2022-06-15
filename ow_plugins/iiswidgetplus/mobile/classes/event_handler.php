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
class IISWIDGETPLUS_MCLASS_EventHandler
{
    private static $classInstance;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }


    private function __construct()
    {
    }

    public function init()
    {
        $service=IISWIDGETPLUS_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($service, 'addWidgetJS'));
        $eventManager->bind(IISEventManager::ON_BEFORE_GROUP_LIST_VIEW_RENDER, array($service, 'beforeGroupListViewRender'));
        $eventManager->bind('iis.on.before.news.list.view.render', array($service, 'beforeNewsListViewRender'));
        $eventManager->bind(IISEventManager::ON_BEFORE_GROUP_VIEW_RENDER, array($service, 'beforeGroupViewRender'));
    }
}