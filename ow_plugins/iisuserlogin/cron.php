<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany
 * @package ow_plugins.iisuserlogin
 * @since 1.0
 */
class IISUSERLOGIN_Cron extends OW_Cron
{
    public function __construct()
    {
        parent::__construct();

        $this->addJob('deleteUsersLoginDetails', 24*60);
    }
    
    public function run()
    {

    }
    
    public function deleteUsersLoginDetails()
    {
        IISUSERLOGIN_BOL_Service::getInstance()->deleteLoginDetails();
        IISUSERLOGIN_BOL_Service::getInstance()->deleteActiveLoginDetails();
    }
}
