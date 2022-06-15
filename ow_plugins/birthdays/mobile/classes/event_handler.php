<?php
/**
 * Created by PhpStorm.
 * User: atenagh
 * Date: 12/17/2018
 * Time: 12:40 AM
 */

class BIRTHDAYS_MCLASS_EventHandler
{
    /**
     * Singleton instance.
     *
     * @var BIRTHDAYS_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return BIRTHDAYS_MCLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function init()
    {
        OW::getEventManager()->bind('mobile.notifications.on_item_render', array($this, "onNewsfeedItemRender"));
    }


    public function onNewsfeedItemRender( OW_Event $event )
    {
        $params = $event->getParams();
        if ( $params['pluginKey'] != 'birthdays')
        {
            return;
        }
        $data = $params['data'];

        if ( !isset($data['avatar']['urlInfo']['vars']['username']) )
        {
            return;
        }

        $userService = BOL_UserService::getInstance();
        $user = $userService->findByUsername($data['avatar']['urlInfo']['vars']['username']);
        if ( !$user )
        {
            return;
        }
        $event->setData($data);

    }



}