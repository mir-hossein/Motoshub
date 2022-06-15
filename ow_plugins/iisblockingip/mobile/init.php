<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

IISBLOCKINGIP_MCLASS_EventHandler::getInstance()->init();
OW::getRouter()->addRoute(new OW_Route('iisblockingip.authenticate_fail', 'iisblockingip/lock', 'IISBLOCKINGIP_MCTRL_Iisblockingip', 'index'));
