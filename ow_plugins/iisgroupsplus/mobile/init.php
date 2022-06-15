<?php

/**
 * iisgroupsplus
 */
$plugin = OW::getPluginManager()->getPlugin('iisgroupsplus');
IISGROUPSPLUS_MCLASS_EventHandler::getInstance()->init();
OW::getRouter()->addRoute(new OW_Route('iisgroupsplus.file-list', 'groups/:groupId/files', 'IISGROUPSPLUS_MCTRL_Groups', 'fileList'));
OW::getRouter()->addRoute(new OW_Route('iisgroupsplus.addFile', 'groups/:groupId/addFile', 'IISGROUPSPLUS_MCTRL_Groups', 'addFile'));
OW::getRouter()->addRoute(new OW_Route('iisgroupsplus.deleteFile', 'groups/:groupId/attachmentId/:attachmentId/deleteFile', 'IISGROUPSPLUS_MCTRL_Groups', 'deleteFile'));