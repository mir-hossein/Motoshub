<?php
/**
 * iismainpage
 */
/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iismainpage
 * @since 1.0
 */

OW::getPluginManager()->addPluginSettingsRouteName('iismainpage', 'iismainpage.admin');

OW::getNavigation()->addMenuItem(OW_Navigation::MOBILE_TOP, 'iismainpage.index', 'iismainpage', 'mobile_main_menu_list', OW_Navigation::VISIBLE_FOR_MEMBER);

OW::getNavigation()->addMenuItem(OW_Navigation::MOBILE_BOTTOM, 'iismainpage.settings', 'iismainpage', 'settings', OW_Navigation::VISIBLE_FOR_MEMBER);