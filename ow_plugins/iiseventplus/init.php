<?php

/**
 * iiseventplus
 */
$plugin = OW::getPluginManager()->getPlugin('iiseventplus');
IISEVENTPLUS_CLASS_EventHandler::getInstance()->init();
$router = OW::getRouter();
$router->addRoute(new OW_Route('iiseventplus.leave', 'iiseventplus/leave/:eventId', 'IISEVENTPLUS_CTRL_Base', 'leave'));
$router->addRoute(new OW_Route('iiseventplus.admin', 'admin/plugins/iiseventplus', "IISEVENTPLUS_CTRL_Admin", 'eventCategory'));
$router->addRoute(new OW_Route('iiseventplus.admin.edit.item', 'iiseventplus/admin/edit-item', 'IISEVENTPLUS_CTRL_Admin', 'editItem'));
$router->addRoute(new OW_Route('iiseventplus.file-list', 'event/:eventId/files', 'IISEVENTPLUS_CTRL_Base', 'fileList'));
$router->addRoute(new OW_Route('iiseventplus.addFile', 'event/:eventId/addFile', 'IISEVENTPLUS_CTRL_Base', 'addFile'));
$router->addRoute(new OW_Route('iiseventplus.deleteFile', 'event/:eventId/attachmentId/:attachmentId/deleteFile', 'IISEVENTPLUS_CTRL_Base', 'deleteFile'));