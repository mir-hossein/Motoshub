<?php


IISFRIENDSPLUS_CLASS_EventHandler::getInstance()->init();
OW::getRouter()->addRoute(new OW_Route('iisfriendsplus_admin_config', 'iisfriendsplus/admin', 'IISFRIENDSPLUS_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('iisfriendsplus_admin_config_all_users', 'iisfriendsplus/admin/all_users', 'IISFRIENDSPLUS_CTRL_Admin', 'allUsers'));
