<?php

/**
 * @author Issa Annamoradnejad
 */

class IISAPARATSUPPORT_MCLASS_EventHandler
{
    /**
     * @var IISAPARATSUPPORT_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * @return IISAPARATSUPPORT_MCLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
    }

    public function init()
    {
        $service = IISAPARATSUPPORT_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(IISEventManager::ON_BEFORE_VIDEO_UPLOAD_FORM_RENDERER, array($service, 'onBeforeVideoUploadFormRenderer'));
        $eventManager->bind(IISEventManager::ON_BEFORE_VIDEO_UPLOAD_COMPONENT_RENDERER, array($service, 'onBeforeVideoUploadComponentRenderer'));
    }
}