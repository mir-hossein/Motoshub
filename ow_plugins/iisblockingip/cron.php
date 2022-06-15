<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany
 * @package ow_plugins.iisblockingip
 * @since 1.0
 */
class IISBLOCKINGIP_Cron extends OW_Cron
{
    public function __construct()
    {
        parent::__construct();

        $this->addJob('deleteBlockIp', 1);
    }
    
    public function run()
    {

    }
    
    public function deleteBlockIp()
    {
        IISBLOCKINGIP_BOL_Service::getInstance()->deleteBlockIp();
    }
}
