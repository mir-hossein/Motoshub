<?php


class IISPROFILEMANAGEMENT_MCMP_PreferenceContentMenu extends BASE_MCMP_ContentMenu
{

    public function __construct()
    {
        $event = new BASE_CLASS_EventCollector('base.preference_menu_items');

        OW::getEventManager()->trigger($event);

        $menuItems = $event->getData();

        parent::__construct($menuItems);
    }
}
