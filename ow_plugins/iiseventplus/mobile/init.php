<?php

/**
 * iiseventplus
 */
$plugin = OW::getPluginManager()->getPlugin('iiseventplus');
IISEVENTPLUS_MCLASS_EventHandler::getInstance()->init();
$router = OW::getRouter();
$router->addRoute(new OW_Route('iiseventplus.leave', 'iiseventplus/leave/:eventId', 'IISEVENTPLUS_MCTRL_Base', 'leave'));
$router->addRoute(new OW_Route('iiseventplus.file-list', 'event/:eventId/files', 'IISEVENTPLUS_MCTRL_Base', 'fileList'));
$router->addRoute(new OW_Route('iiseventplus.addFile', 'event/:eventId/addFile', 'IISEVENTPLUS_MCTRL_Base', 'addFile'));
$router->addRoute(new OW_Route('iiseventplus.deleteFile', 'event/:eventId/attachmentId/:attachmentId/deleteFile', 'IISEVENTPLUS_MCTRL_Base', 'deleteFile'));