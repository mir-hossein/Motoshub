<?php

$plugin = OW::getPluginManager()->getPlugin('iisnews');

OW::getAutoloader()->addClass('Entry', $plugin->getBolDir() . 'dto' . DS . 'entry.php');
OW::getAutoloader()->addClass('EntryDao', $plugin->getBolDir() . 'dao' . DS . 'entry_dao.php');
OW::getAutoloader()->addClass('EntryService', $plugin->getBolDir() . 'service' . DS . 'entry_service.php');
OW::getRouter()->addRoute(new OW_Route('event.user_list', 'event/:eventId/users/:list', 'EVENT_CTRL_Base', 'eventUserLists'));
OW::getRouter()->addRoute(new OW_Route('iisnews', 'news', "IISNEWS_MCTRL_News", 'index', array('list' => array(OW_Route::PARAM_OPTION_HIDDEN_VAR => 'latest'))));
OW::getRouter()->addRoute(new OW_Route('iisnews-default', 'news', 'IISNEWS_MCTRL_News', 'index'));
OW::getRouter()->addRoute(new OW_Route('user-entry', 'news/:id', "IISNEWS_MCTRL_View", 'index'));
OW::getRouter()->addRoute(new OW_Route('iisnews.list', 'news/list/:list', "IISNEWS_MCTRL_News", 'index'));
OW::getRouter()->addRoute(new OW_Route('entry', 'news/entry/:id', "IISNEWS_MCTRL_View", 'index'));
OW::getRouter()->addRoute(new OW_Route('entry-part', 'news/entry/:id/:part', "IISNEWS_MCTRL_View", 'index'));
OW::getRouter()->addRoute(new OW_Route('entry-save-new', 'news/entry/new', "IISNEWS_MCTRL_Save", 'index'));
OW::getRouter()->addRoute(new OW_Route('entry-save-edit', 'news/entry/edit/:id', "IISNEWS_MCTRL_Save", 'index'));
OW::getRouter()->addRoute(new OW_Route('news-manage-drafts', 'news/my-drafts/', "IISNEWS_MCTRL_ManagementEntry", 'index'));
OW::getRouter()->addRoute(new OW_Route('news-manage-entrys', 'news/my-published-entrys/', "IISNEWS_MCTRL_ManagementEntry", 'index'));
OW::getRouter()->addRoute(new OW_Route('news-manage-comments', 'news/my-incoming-comments/', "IISNEWS_MCTRL_ManagementComment", 'index'));
$eventHandler = IISNEWS_CLASS_EventHandler::getInstance();
$eventHandler->genericInit();

$mobileEventHandler = IISNEWS_MCLASS_EventHandler::getInstance();
$mobileEventHandler->init();

IISNEWS_CLASS_ContentProvider::getInstance()->init();
