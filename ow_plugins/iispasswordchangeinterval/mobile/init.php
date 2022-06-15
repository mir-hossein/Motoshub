<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.change-password', 'iispasswordchangeinterval/changepassword', 'IISPASSWORDCHANGEINTERVAL_MCTRL_Iispasswordchangeinterval', 'index'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.check-validate-password', 'iispasswordchangeinterval/checkvalidatepassword/:token', 'IISPASSWORDCHANGEINTERVAL_MCTRL_Iispasswordchangeinterval', 'checkValidatePassword'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.invalid-password', 'iispasswordchangeinterval/invalidpassword/:userId', 'IISPASSWORDCHANGEINTERVAL_MCTRL_Iispasswordchangeinterval', 'invalidPassword'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.resend-link', 'iispasswordchangeinterval/resendlLink', 'IISPASSWORDCHANGEINTERVAL_MCTRL_Iispasswordchangeinterval', 'resendlLink'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.resend-link-generate-token', 'iispasswordchangeinterval/resendlLinkGenerateToken/:userId', 'IISPASSWORDCHANGEINTERVAL_MCTRL_Iispasswordchangeinterval', 'resendlLinkGenerateToken'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.change-user-password', 'iispasswordchangeinterval/changeuserpassword/:token', 'IISPASSWORDCHANGEINTERVAL_MCTRL_Iispasswordchangeinterval', 'changeUserPassword'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.change-user-password-with-userId', 'iispasswordchangeinterval/changeuserpasswordwithuserid/:userId', 'IISPASSWORDCHANGEINTERVAL_MCTRL_Iispasswordchangeinterval', 'changeUserPasswordWithUserId'));

IISPASSWORDCHANGEINTERVAL_MCLASS_EventHandler::getInstance()->init();