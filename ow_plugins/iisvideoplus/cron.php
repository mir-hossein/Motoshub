<?php



/**
 * iisvideoplus cron job.
 *
 * @authors Mohammad Agha Abbasloo
 * @package iisvideoplus
 * @since 1.0
 */
class IISVIDEOPLUS_Cron extends OW_Cron
{
    const VIDEO_DELETE_LIMIT = 180;
    public function __construct()
    {
        parent::__construct();

        $this->addJob('videoFileDeleteProcess');
    }

    public function run()
    {
        
    }

    public function videoFileDeleteProcess()
    {
        $config = OW::getConfig();
        
        // check if uninstall is in progress
        if ( !$config->getValue('iisvideoplus', 'uninstall_inprogress') )
        {
            return;
        }
        
        // check if cron queue is not busy
        if ( $config->getValue('iisvideoplus', 'uninstall_cron_busy') )
        {
            return;
        }
        
        $config->saveConfig('iisvideoplus', 'uninstall_cron_busy', 1);
        
        $iisvideoplusService = IISVIDEOPLUS_BOL_Service::getInstance();
        $allFileDeleted=false;
        try
        {
            $allFileDeleted=$iisvideoplusService->deleteAllVideoFiles(self::VIDEO_DELETE_LIMIT);
        }
        catch ( Exception $e )
        {
            OW::getLogger()->addEntry(json_encode($e));
        }

        $config->saveConfig('iisvideoplus', 'uninstall_cron_busy', 0);
        
        if ( $allFileDeleted )
        {
            $config->saveConfig('iisvideoplus', 'uninstall_inprogress', 0);
            IISVIDEOPLUS_BOL_Service::getInstance()->setMaintenanceMode(false);
            BOL_PluginService::getInstance()->uninstall('iisvideoplus');
        } else {
            OW::getEventManager()->trigger(new OW_Event(IISVIDEOPLUS_BOL_Service::EVENT_UNINSTALL_IN_PROGRESS));
        }
    }
}
