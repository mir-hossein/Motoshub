<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisblockingip.controllers
 * @since 1.0
 */
class IISBLOCKINGIP_MCTRL_Iisblockingip extends OW_MobileActionController
{
    private $service;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->service = IISBLOCKINGIP_BOL_Service::getInstance();
    }
    
    public function index( $params = NULL )
    {
        if ( !$this->service->isLocked() )
        {
            $this->redirect(OW_URL_HOME);
        }

        $userBlockedTime = $this->service->getCurrentUser()->getTime();

        $this->setPageTitle(OW::getLanguage()->text("iisblockingip", "title_locked"));
        $release_time =  $userBlockedTime + (int)OW::getConfig()->getValue('iisblockingip', IISBLOCKINGIP_BOL_Service::EXPIRE_TIME) * 60;
        $release_time =  UTIL_DateTime::formatSimpleDate($release_time,false);
        $this->assign("release_time", $release_time);
        OW::getDocument()->getMasterPage()->setRButtonData(array('extraString' => ' style="display:none;"'));
    }
}
