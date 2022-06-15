<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

OW::getRouter()->addRoute(new OW_Route('iisuserlogin.admin', 'iisuserlogin/admin', 'IISUSERLOGIN_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('iisuserlogin.index', 'iisuserlogin/default', 'IISUSERLOGIN_CTRL_Iisuserlogin', 'index'));
OW::getRouter()->addRoute(new OW_Route('iisuserlogin.login', 'latest-logins', 'IISUSERLOGIN_CTRL_Iisuserlogin', 'login'));
OW::getRouter()->addRoute(new OW_Route('iisuserlogin.active', 'active-sessions', 'IISUSERLOGIN_CTRL_Iisuserlogin', 'active'));
OW::getRouter()->addRoute(new OW_Route('iisuserlogin.terminate_device', 'iisuserlogin/terminate-device', 'IISUSERLOGIN_CTRL_Iisuserlogin', 'terminateDevice'));

IISUSERLOGIN_CLASS_EventHandler::getInstance()->init();