<?php

IISNEWSFEEDPLUS_MCLASS_EventHandler::getInstance()->init();
OW::getRouter()->addRoute(new OW_Route('iisnewsfeedplus.edit.post', 'iisnewsfeedplus/edit_post/', 'IISNEWSFEEDPLUS_CTRL_Edit', 'edit'));