<?php

/**
 * iisemoji
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisemoji
 * @since 1.0
 */

OW::getRouter()->addRoute(new OW_Route('iisemoji.admin', 'admin/plugins/iisemoji', "IISEMOJI_CTRL_Admin", 'dept'));
IISEMOJI_CLASS_EventHandler::getInstance()->init();

