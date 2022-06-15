<?php

/**
 * iishashtag
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */

OW::getPluginManager()->addPluginSettingsRouteName('iishashtag', 'iishashtag.admin');
OW::getNavigation()->addMenuItem(OW_Navigation::MAIN, 'iishashtag.page', 'iishashtag', 'main_menu_item', OW_Navigation::VISIBLE_FOR_ALL);
OW::getNavigation()->addMenuItem(OW_Navigation::MOBILE_TOP, 'iishashtag.page', 'iishashtag', 'mobile_main_menu_item', OW_Navigation::VISIBLE_FOR_ALL);


//repopulate tags
if ( !OW::getConfig()->configExists('iishashtag', 'should_be_repopulated') )
    OW::getConfig()->addConfig('iishashtag', 'should_be_repopulated', 'true', 'should_be_repopulated.');
else
    OW::getConfig()->saveConfig('iishashtag', 'should_be_repopulated', 'true');