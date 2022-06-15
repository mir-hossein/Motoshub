<?php
IISNEWSFEEDPIN_CLASS_EventHandler::getInstance()->init();

OW::getRouter()->addRoute(new OW_Route('iisnewsfeedpin.add_pin_by_entity', 'iisnewsfeedpin/add_pin_entity', 'IISNEWSFEEDPIN_CTRL_Pin', 'addPinByEntity'));
OW::getRouter()->addRoute(new OW_Route('iisnewsfeedpin.pin_delete', 'iisnewsfeedpin/delete', 'IISNEWSFEEDPIN_CTRL_Pin', 'deletePin'));
