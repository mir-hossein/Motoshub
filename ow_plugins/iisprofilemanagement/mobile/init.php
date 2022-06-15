<?php

IISPROFILEMANAGEMENT_MCLASS_EventHandler::getInstance()->init();
OW::getRouter()->addRoute(new OW_Route('iisprofilemanagement.edit', 'iisprofilemanagement/edit', 'IISPROFILEMANAGEMENT_MCTRL_Edit', 'index'));
OW::getRouter()->addRoute(new OW_Route('iisprofilemanagement.edit.changepassword', 'iisprofilemanagement/changepassword', 'IISPROFILEMANAGEMENT_MCTRL_Edit', 'changePassword'));
OW::getRouter()->addRoute(new OW_Route('iisprofilemanagement.changepassword', 'iisprofilemanagement/edit/:changepassword', 'IISPROFILEMANAGEMENT_MCTRL_Edit', 'index'));
$eventHandler = IISPROFILEMANAGEMENT_MCLASS_EventHandler::getInstance();
OW::getEventManager()->bind(IISEventManager::ON_MOBILE_ADD_ITEM, array($eventHandler, 'onMobileAddItem'));
OW::getRouter()->addRoute(new OW_Route('iisprofilemanagement.preference_index', 'iisprofilemanagement/preference', 'IISPROFILEMANAGEMENT_MCTRL_Preference', 'index'));
OW::getRouter()->addRoute(new OW_Route('iisprofilemanagement.delete_user', 'profile/delete', 'IISPROFILEMANAGEMENT_MCTRL_DeleteUser', 'index'));