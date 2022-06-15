<?php

/**
 * iisvideoplus
 */
$service = IISVIDEOPLUS_BOL_Service::getInstance();
OW::getEventManager()->bind(IISEventManager::ON_AFTER_VIDEO_RENDERED, array($service, 'onAfterVideoRendered'));

IISVIDEOPLUS_CLASS_EventHandler::getInstance()->init();

OW::getRouter()->addRoute(new OW_Route('iisvideoplus_uninstall', 'iisvideoplus/admin/uninstall', 'IISVIDEOPLUS_CTRL_Admin', 'uninstall'));
