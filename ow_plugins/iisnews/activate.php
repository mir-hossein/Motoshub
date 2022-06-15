<?php

OW::getPluginManager()->addPluginSettingsRouteName('iisnews', 'iisnews-admin');
OW::getPluginManager()->addUninstallRouteName('iisnews', 'iisnews-uninstall');

$widget = array();

//--
$widget['dashboard'] = BOL_ComponentAdminService::getInstance()->addWidget('IISNEWS_CMP_NewsWidget', false);

$placeWidget = BOL_ComponentAdminService::getInstance()->addWidgetToPlace($widget['dashboard'], BOL_ComponentAdminService::PLACE_DASHBOARD);

BOL_ComponentAdminService::getInstance()->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_LEFT );

//--
$widget['site'] = BOL_ComponentAdminService::getInstance()->addWidget('IISNEWS_CMP_NewsWidget', false);

$placeWidget = BOL_ComponentAdminService::getInstance()->addWidgetToPlace($widget['site'], BOL_ComponentAdminService::PLACE_INDEX);

BOL_ComponentAdminService::getInstance()->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_LEFT );

//--
$widget['tags'] = BOL_ComponentAdminService::getInstance()->addWidget('IISNEWS_CMP_TagsWidget', true);

$placeWidget = BOL_ComponentAdminService::getInstance()->addWidgetToPlace($widget['tags'], BOL_ComponentAdminService::PLACE_INDEX);

//BOL_ComponentAdminService::getInstance()->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_RIGHT );

//--
OW::getNavigation()->addMenuItem(OW_Navigation::MAIN, 'iisnews', 'iisnews', 'main_menu_item', OW_Navigation::VISIBLE_FOR_ALL);

// Mobile activation
OW::getNavigation()->addMenuItem(OW_Navigation::MOBILE_TOP, 'iisnews-default', 'iisnews', 'iisnews_mobile', OW_Navigation::VISIBLE_FOR_ALL);

require_once dirname(__FILE__) . DS .  'classes' . DS . 'credits.php';
$credits = new IISNEWS_CLASS_Credits();
$credits->triggerCreditActionsAdd();
// register sitemap entities
BOL_SeoService::getInstance()->addSitemapEntity('iisnews', 'iisnews_sitemap', 'iisnews', array(
    'news'
));