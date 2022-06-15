<?php

/**
 * IIS Advance Search
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisadvancesearch
 * @since 1.0
 */

BOL_ComponentAdminService::getInstance()->deleteWidget('IISADVANCESEARCH_MCMP_UsersSearchWidget');
BOL_ComponentAdminService::getInstance()->deleteWidget('IISADVANCESEARCH_MCMP_FriendsSearchWidget');


OW::getNavigation()->deleteMenuItem('iisadvancesearch', 'mobile_main_menu_item');