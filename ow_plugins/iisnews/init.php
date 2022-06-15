<?php

$plugin = OW::getPluginManager()->getPlugin('iisnews');

OW::getAutoloader()->addClass('Entry', $plugin->getBolDir() . 'dto' . DS . 'entry.php');
OW::getAutoloader()->addClass('EntryDao', $plugin->getBolDir() . 'dao' . DS . 'entry_dao.php');
OW::getAutoloader()->addClass('EntryService', $plugin->getBolDir() . 'service' . DS . 'entry_service.php');

OW::getRouter()->addRoute(new OW_Route('iisnews-uninstall', 'admin/news/uninstall', 'IISNEWS_CTRL_Admin', 'uninstall'));

OW::getRouter()->addRoute(new OW_Route('entry-save-new', 'news/entry/new', "IISNEWS_CTRL_Save", 'index'));
OW::getRouter()->addRoute(new OW_Route('entry-save-edit', 'news/entry/edit/:id', "IISNEWS_CTRL_Save", 'index'));

OW::getRouter()->addRoute(new OW_Route('entry', 'news/entry/:id', "IISNEWS_CTRL_View", 'index'));
OW::getRouter()->addRoute(new OW_Route('entry-approve', 'news/entry/approve/:id', "IISNEWS_CTRL_View", 'approve'));

OW::getRouter()->addRoute(new OW_Route('entry-part', 'news/entry/:id/:part', "IISNEWS_CTRL_View", 'index'));

OW::getRouter()->addRoute(new OW_Route('user-iisnews', 'news/user/:user', "IISNEWS_CTRL_UserNews", 'index'));

OW::getRouter()->addRoute(new OW_Route('archive-iisnews', 'news/archive', "IISNEWS_CTRL_ArchiveNews", 'index'));

OW::getRouter()->addRoute(new OW_Route('user-entry', 'news/:id', "IISNEWS_CTRL_View", 'index'));

OW::getRouter()->addRoute(new OW_Route('iisnews', 'news', "IISNEWS_CTRL_News", 'index', array('list' => array(OW_Route::PARAM_OPTION_HIDDEN_VAR => 'latest'))));
OW::getRouter()->addRoute(new OW_Route('iisnews.list', 'news/list/:list', "IISNEWS_CTRL_News", 'index'));

OW::getRouter()->addRoute(new OW_Route('iisnews-manage-entrys', 'news/my-published-entrys/', "IISNEWS_CTRL_ManagementEntry", 'index'));
OW::getRouter()->addRoute(new OW_Route('iisnews-manage-drafts', 'news/my-drafts/', "IISNEWS_CTRL_ManagementEntry", 'index'));
OW::getRouter()->addRoute(new OW_Route('iisnews-manage-comments', 'news/my-incoming-comments/', "IISNEWS_CTRL_ManagementComment", 'index'));

OW::getRouter()->addRoute(new OW_Route('iisnews-admin', 'admin/news', "IISNEWS_CTRL_Admin", 'index'));

$service = EntryService::getInstance();
$eventHandler = IISNEWS_CLASS_EventHandler::getInstance();
$eventHandler->init();
IISNEWS_CLASS_ContentProvider::getInstance()->init();

OW::getEventManager()->bind(BASE_CMP_AddNewContent::EVENT_NAME,     array($service, 'onCollectAddNewContentItem'));
OW::getEventManager()->bind(BASE_CMP_QuickLinksWidget::EVENT_NAME,  array($service, 'onCollectQuickLinks'));
OW::getEventManager()->bind('iisadvancesearch.on_collect_search_items',  array($service, 'onCollectSearchItems'));

