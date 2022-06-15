<?php

IISAPARATSUPPORT_CLASS_EventHandler::getInstance()->init();

OW::getRouter()->addRoute(new OW_Route('iisaparatsupport.load', 'iisaparatsupport/load/:vid',"IISAPARATSUPPORT_CTRL_Load", 'get_aparat_info'));
