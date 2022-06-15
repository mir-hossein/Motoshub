<?php
IISNEWSFEEDPIN_MCLASS_EventHandler::getInstance()->init();

OW::getRouter()->addRoute(new OW_Route('iisnewsfeedpin.add_pin_by_entity', 'iisnewsfeedpin/add_pin_entity', 'IISNEWSFEEDPIN_MCTRL_Pin', 'addPinByEntity'));
OW::getRouter()->addRoute(new OW_Route('iisnewsfeedpin.pin_delete', 'iisnewsfeedpin/delete', 'IISNEWSFEEDPIN_MCTRL_Pin', 'deletePin'));
