<?php

/**
 * Copyright (c) 2018, Milad Heshmati
 * All rights reserved.
 */

/**
 *
 *
 * @author Milad Heshmati <milad.heshmati@gmail.com>
 * @package ow_plugins.iismobilesupport.bol
 * @since 1.0
 */
class IISMOBILESUPPORT_BOL_WebServiceFlag
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

    public function checkFlagsList($entityType)
    {
        $event = OW::getEventManager()->trigger(new BASE_CLASS_EventCollector('content.collect_types'));
        $items = OW::getEventManager()->trigger($event);

        $list = array();
        foreach ($items->getData() as $item) {
            $list[] = $item['entityType'];
        }
        if (in_array($entityType, $list)) {
            return true;
        } else {
            return false;
        }
    }


    public function flagItem()
    {
        $reasonsSet = array('spam', 'offence', 'illegal');
        if (!isset($_GET['entityType']) ||
            !isset($_GET['entityId']) ||
            !isset($_GET['reason']) ||
            !in_array($_GET['reason'], $reasonsSet)
        ) {
            return array('valid' => false, 'message' => 'input_error');
        } else {
            $entityType = $_GET['entityType'];
            $entityId = $_GET['entityId'];
            $reason = $_GET['reason'];
        }
        if (!$this->checkFlagsList($entityType)) {
            return array('valid' => false, 'message' => 'flag_is_not_available');
        }
        if (OW::getUser()->isAuthenticated()) {
            $userId = OW::getUser()->getId();
        }else{
            return array('valid' => false, 'message' => 'authentication_error');
        }
        $data = BOL_ContentService::getInstance()->getContent($entityType, $entityId);
        if(isset($data) && $data!=null){
            BOL_FlagService::getInstance()->addFlag($entityType, $entityId, $reason, $userId);
            return array('valid' => true, 'message' => 'flag_submitted');
        }else{
            return array('valid' => false, 'message' => 'flag_not_valid');
        }

    }


}