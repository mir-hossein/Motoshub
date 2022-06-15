<?php
IISNEWSFEEDPLUS_CLASS_EventHandler::getInstance()->init();
OW::getRouter()->addRoute(new OW_Route('iisnewsfeedplus.edit.post', 'iisnewsfeedplus/edit_post/', 'IISNEWSFEEDPLUS_CTRL_Edit', 'edit'));
OW::getRouter()->addRoute(new OW_Route('iisnewsfeedplus.admin_config', 'iisnewsfeedplus/admin', 'IISNEWSFEEDPLUS_CTRL_Admin', 'index'));