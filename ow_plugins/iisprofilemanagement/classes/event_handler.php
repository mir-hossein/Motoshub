<?php



class IISPROFILEMANAGEMENT_CLASS_EventHandler
{
    private static $classInstance;
    
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
        $service = IISPROFILEMANAGEMENT_BOL_Service::getInstance();
        OW::getEventManager()->bind('base.add_profile_action_toolbar', array($service, 'onBeforeProfileEditRenderJsFunction'));

    }

}