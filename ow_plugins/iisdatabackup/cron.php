<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany
 * @package ow_plugins.iisdatabackup
 * @since 1.0
 */
class IISDATABACKUP_Cron extends OW_Cron
{
    public function __construct()
    {
        parent::__construct();

        $this->addJob('deleteBackupData', 60*24); //Checking for removing backup data per day
    }
    
    public function run()
    {

    }
    
    public function deleteBackupData()
    {
        $deadline = OW::getConfig()->getValue('iisdatabackup','deadline');
        if($deadline!=5){
            $deadlinePerMonth = $deadline*6;
            $deadlinePerDay = $deadlinePerMonth*30;
            $deadlinePerHour = $deadlinePerDay*24;
            $deadlinePerMinute = $deadlinePerHour*60;
            $timestamp = $deadlinePerMinute*60;
            OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_DATA_BACKUP_DELETE, array('timestamp' => $timestamp)));
        }


    }
}
