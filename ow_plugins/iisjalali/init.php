<?php
OW::getRouter()->addRoute(new OW_Route('iisjalali_admin_config', 'iisjalali/admin', 'IISJALALI_CTRL_Admin', 'index'));
IISJALALI_CLASS_EventHandler::getInstance()->init();
