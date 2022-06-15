<?php

IISUSERLOGIN_MCLASS_EventHandler::getInstance()->init();
OW::getRouter()->addRoute(new OW_Route('iisuserlogin.index', 'iisuserlogin/default', 'IISUSERLOGIN_MCTRL_Iisuserlogin', 'index'));
OW::getRouter()->addRoute(new OW_Route('iisuserlogin.login', 'latest-logins', 'IISUSERLOGIN_MCTRL_Iisuserlogin', 'login'));
OW::getRouter()->addRoute(new OW_Route('iisuserlogin.active', 'active-sessions', 'IISUSERLOGIN_MCTRL_Iisuserlogin', 'active'));
OW::getRouter()->addRoute(new OW_Route('iisuserlogin.terminate_device', 'iisuserlogin/terminate-device', 'IISUSERLOGIN_CTRL_Iisuserlogin', 'terminateDevice'));