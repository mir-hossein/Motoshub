<?php

class IISQUESTIONS_CMP_ListMenu extends OW_Component
{

    public function __construct($selected)
    {
        parent::__construct();

        $this->addComponent('menu', $this->getMenu($selected));
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();
    }

    public function getMenu($selected)
    {
        $language = OW::getLanguage();

        $menu = new BASE_CMP_ContentMenu();

        $menuItem = new BASE_MenuItem();
        $menuItem->setKey('all');
        $menuItem->setPrefix('iisquestions');
        $menuItem->setLabel( $language->text('iisquestions', 'list_all_tab') );
        $menuItem->setOrder(1);
        if($selected == 'all')
            $menuItem->setActive(true);
        $menuItem->setUrl(OW::getRouter()->urlForRoute('iisquestions-home',array('type'=>'all')));
        $menuItem->setIconClass('ow_ic_all_questions');

        $menu->addElement($menuItem);

        $menuItem = new BASE_MenuItem();
        $menuItem->setKey('hottest');
        $menuItem->setPrefix('iisquestions');
        $menuItem->setLabel( $language->text('iisquestions', 'feed_order_popular') );
        $menuItem->setOrder(1);
        if($selected == 'hottest')
            $menuItem->setActive(true);
        $menuItem->setUrl(OW::getRouter()->urlForRoute('iisquestions-home',array('type'=>'hottest')));
        $menuItem->setIconClass('ow_ic_most_popular_questions');

        $menu->addElement($menuItem);

        if ( OW::getUser()->isAuthenticated() )
        {

            $menuItem = new BASE_MenuItem();
            $menuItem->setKey('my');
            $menuItem->setPrefix('iisquestions');
            $menuItem->setLabel( $language->text('iisquestions', 'list_my_tab') );
            $menuItem->setOrder(3);
            if($selected == 'my')
                $menuItem->setActive(true);
            $menuItem->setUrl(OW::getRouter()->urlForRoute('iisquestions-home',array('type'=>'my')));
            $menuItem->setIconClass('ow_ic_my_questions');

            $menu->addElement($menuItem);

            if ( OW::getPluginManager()->isPluginActive('friends') )
            {
                $menuItem = new BASE_MenuItem();
                $menuItem->setKey('friends');
                if($selected == 'friends')
                    $menuItem->setActive(true);
                $menuItem->setPrefix('iisquestions');
                $menuItem->setLabel( $language->text('iisquestions', 'list_friends_tab') );
                $menuItem->setOrder(2);
                $menuItem->setUrl(OW::getRouter()->urlForRoute('iisquestions-home',array('type'=>'friend')));
                $menuItem->setIconClass('ow_ic_friends_questions');

                $menu->addElement($menuItem);
            }
        }

        return $menu;
    }
}