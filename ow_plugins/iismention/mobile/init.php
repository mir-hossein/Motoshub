<?php
/**
 * iismention
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismention
 * @since 1.0
 */
IISMENTION_MCLASS_EventHandler::getInstance()->genericInit();

OW::getRouter()->addRoute(new OW_Route('iismention.load_usernames', 'iismention/usernames', "IISMENTION_CTRL_Load", 'loadUsernames'));
OW::getRouter()->addRoute(new OW_Route('iismention.load_usernames_filled', 'iismention/usernames/:username', "IISMENTION_CTRL_Load", 'loadUsernames'));
