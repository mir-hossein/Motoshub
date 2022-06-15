<?php

/**
 * iisoghat
 */

IISOGHAT_BOL_Service::getInstance()->importingDefaultItems();
OW::getRouter()->addRoute(new OW_Route('iisoghat.get.time', 'iisoghat/get-time/', 'IISOGHAT_CTRL_Time', 'getTime'));