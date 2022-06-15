<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.admin', 'iispasswordchangeinterval/admin', 'IISPASSWORDCHANGEINTERVAL_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.admin.section-id', 'iispasswordchangeinterval/admin/:sectionId', 'IISPASSWORDCHANGEINTERVAL_CTRL_Admin', 'index'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.admin.invalidate-password', 'iispasswordchangeinterval/admin/invalidatepassword/:userId/:sectionId', 'IISPASSWORDCHANGEINTERVAL_CTRL_Admin', 'invalidatePassword'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.admin.validate-password', 'iispasswordchangeinterval/admin/validatepassword/:userId/:sectionId', 'IISPASSWORDCHANGEINTERVAL_CTRL_Admin', 'validatePassword'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.admin.invalidate-all-password', 'iispasswordchangeinterval/admin/invalidateallpassword/:sectionId', 'IISPASSWORDCHANGEINTERVAL_CTRL_Admin', 'invalidateAllPassword'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.admin.expire-all-password', 'iispasswordchangeinterval/admin/expireallpassword/:sectionId', 'IISPASSWORDCHANGEINTERVAL_CTRL_Admin', 'expireAllPassword'));

OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.change-password', 'iispasswordchangeinterval/changepassword', 'IISPASSWORDCHANGEINTERVAL_CTRL_Iispasswordchangeinterval', 'index'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.check-validate-password', 'iispasswordchangeinterval/checkvalidatepassword/:token', 'IISPASSWORDCHANGEINTERVAL_CTRL_Iispasswordchangeinterval', 'checkValidatePassword'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.invalid-password', 'iispasswordchangeinterval/invalidpassword/:userId', 'IISPASSWORDCHANGEINTERVAL_CTRL_Iispasswordchangeinterval', 'invalidPassword'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.resend-link', 'iispasswordchangeinterval/resendlLink', 'IISPASSWORDCHANGEINTERVAL_CTRL_Iispasswordchangeinterval', 'resendlLink'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.resend-link-generate-token', 'iispasswordchangeinterval/resendlLinkGenerateToken/:userId', 'IISPASSWORDCHANGEINTERVAL_CTRL_Iispasswordchangeinterval', 'resendlLinkGenerateToken'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.change-user-password', 'iispasswordchangeinterval/changeuserpassword/:token', 'IISPASSWORDCHANGEINTERVAL_CTRL_Iispasswordchangeinterval', 'changeUserPassword'));
OW::getRouter()->addRoute(new OW_Route('iispasswordchangeinterval.change-user-password-with-userId', 'iispasswordchangeinterval/changeuserpasswordwithuserid/:userId', 'IISPASSWORDCHANGEINTERVAL_CTRL_Iispasswordchangeinterval', 'changeUserPasswordWithUserId'));

IISPASSWORDCHANGEINTERVAL_CLASS_EventHandler::getInstance()->init();