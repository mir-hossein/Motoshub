<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

OW::getRouter()->addRoute(new OW_Route('iispasswordstrengthmeter.admin', 'iispasswordstrengthmeter/admin', 'IISPASSWORDSTRENGTHMETER_CTRL_Admin', 'index'));

IISPASSWORDSTRENGTHMETER_CLASS_EventHandler::getInstance()->init();