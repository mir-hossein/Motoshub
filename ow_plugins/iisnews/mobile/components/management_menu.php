<?php

class IISNEWS_MCMP_ManagementMenu extends OW_Component
{

    public function __construct()
    {
        parent::__construct();

        $language = OW::getLanguage()->getInstance();

        $item[0] = new BASE_MenuItem();

        $item[0]->setLabel($language->text('iisnews', 'manage_page_menu_published'))
            ->setOrder(0)
            ->setKey(0)
            ->setUrl(OW::getRouter()->urlForRoute('news-manage-entrys'))
            ->setActive(OW::getRequest()->getRequestUri() == OW::getRouter()->uriForRoute('news-manage-entrys'))
            ->setIconClass('ow_ic_clock');

        $item[1] = new BASE_MenuItem();

        $item[1]->setLabel($language->text('iisnews', 'manage_page_menu_drafts'))
            ->setOrder(1)
            ->setKey(1)
            ->setUrl(OW::getRouter()->urlForRoute('news-manage-drafts'))
            ->setActive(OW::getRequest()->getRequestUri() == OW::getRouter()->uriForRoute('news-manage-drafts'))
            ->setIconClass('ow_ic_geer_wheel');

        $item[2] = new BASE_MenuItem();

        $item[2]->setLabel($language->text('iisnews', 'manage_page_menu_comments'))
            ->setOrder(2)
            ->setKey(2)
            ->setUrl(OW::getRouter()->urlForRoute('news-manage-comments'))
            ->setActive(OW::getRequest()->getRequestUri() == OW::getRouter()->uriForRoute('news-manage-comments'))
            ->setIconClass('ow_ic_comment');

        $menu = new BASE_MCMP_ContentMenu($item);

        $this->addComponent('menu', $menu);
    }
}