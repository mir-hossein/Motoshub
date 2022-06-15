<?php

/**
 * iismainpage
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismainpage
 * @since 1.0
 */
class IISMAINPAGE_MCMP_Menu extends OW_MobileComponent
{

    public function __construct($item)
    {
        parent::__construct();

        $this->assign('menus', IISMAINPAGE_BOL_Service::getInstance()->getMenu($item));

        $this->setTemplate(OW::getPluginManager()->getPlugin('iismainpage')->getMobileCmpViewDir().'menu.html');
    }

}