<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

OW::getRouter()->addRoute(new OW_Route('iisblockingip.admin', 'iisblockingip/admin', 'IISBLOCKINGIP_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('iisblockingip.authenticate_fail', 'iisblockingip/lock', 'IISBLOCKINGIP_CTRL_Iisblockingip', 'index'));

IISBLOCKINGIP_CLASS_EventHandler::getInstance()->init();