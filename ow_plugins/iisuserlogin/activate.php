<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

OW::getPluginManager()->addPluginSettingsRouteName('iisuserlogin', 'iisuserlogin.admin');

OW::getNavigation()->addMenuItem(OW_Navigation::BOTTOM, 'iisuserlogin.index', 'iisuserlogin', 'bottom_menu_item', OW_Navigation::VISIBLE_FOR_MEMBER);
OW::getNavigation()->addMenuItem(OW_Navigation::MOBILE_BOTTOM, 'iisuserlogin.index', 'iisuserlogin', 'mobile_bottom_menu_item', OW_Navigation::VISIBLE_FOR_MEMBER);