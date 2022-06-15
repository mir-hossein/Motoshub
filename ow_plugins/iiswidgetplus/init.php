<?php

/**
 * @author Milad Heshmati <milad.heshmati@gmail.com>
 * @package ow_plugins.iiswidgetplus
 * @since 1.0
 */

IISWIDGETPLUS_CLASS_EventHandler::getInstance()->init();
OW::getRouter()->addRoute(new OW_Route('iiswidgetplus_admin_setting', 'admin/plugins/iiswidgetplus', 'IISWIDGETPLUS_CTRL_Admin', 'index'));