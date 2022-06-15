<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow.ow_system_plugins.base.controllers
 * @since 1.0
 */
class BASE_CTRL_Notifications extends OW_ActionController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(){
        if (!OW::getUser()->isAuthenticated())
        {
            throw new Redirect404Exception();
        }
        OW::getDocument()->setHeading(OW::getLanguage()->text('base', 'notifications'));

        $js = "load_notifications('.ow_notifications_page', '".OW::getRouter()->urlFor('BASE_CTRL_Console', 'listRsp')."');";
        OW::getDocument()->addOnloadScript($js);
    }
}
