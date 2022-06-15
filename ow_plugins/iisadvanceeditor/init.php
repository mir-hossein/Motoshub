<?php
OW::getRouter()->addRoute(new OW_Route('iisadvanceeditor.admin_config','iisadvanceeditor/admin','IISADVANCEEDITOR_CTRL_Admin','index'));

IISADVANCEEDITOR_CLASS_EventHandler::getInstance()->init();