<?php

class IISNEWS_Cron extends OW_Cron
{
    const IMAGES_DELETE_LIMIT = 10;

    public function __construct()
    {
        parent::__construct();

        $this->addJob('imagesDeleteProcess', 1);
    }

    public function run()
    {

    }

    public function imagesDeleteProcess()
    {
        $config = OW::getConfig();

        // check if uninstall is in progress
        if ( !$config->getValue('iisnews', 'uninstall_inprogress') )
        {
            return;
        }

        // check if cron queue is not busy
        if ( $config->getValue('iisnews', 'uninstall_cron_busy') )
        {
            return;
        }

        $config->saveConfig('iisnews', 'uninstall_cron_busy', 1);

        $mediaPanelService = BOL_MediaPanelService::getInstance();

        $mediaPanelService->deleteImages('iisnews', self::IMAGES_DELETE_LIMIT);

        $config->saveConfig('iisnews', 'uninstall_cron_busy', 0);

        if ( !$mediaPanelService->countGalleryImages('iisnews') )
        {
            $config->saveConfig('iisnews', 'uninstall_inprogress', 0);
            BOL_PluginService::getInstance()->uninstall('iisnews');
        } else {
            OW::getEventManager()->trigger(new OW_Event(EntryService::EVENT_UNINSTALL_IN_PROGRESS));
        }
    }
}