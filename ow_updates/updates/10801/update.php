<?php

$tblPrefix = OW_DB_PREFIX;

$dbo = Updater::getDbo();
$logger = Updater::getLogger();


try
{
    // add absent items
    $query = "select * FROM `{$tblPrefix}base_menu_item` where id=479";
    $row = $dbo->queryForRow($query);
    if (!$row){
        $query = "INSERT INTO `{$tblPrefix}base_menu_item` VALUES (479,'base','mobile_version_menu_item','','bottom',5,'base.mobile_version',NULL,NULL,3)";
        $dbo->query($query);
    }


    $query = "select * FROM `{$tblPrefix}base_menu_item` where id=469";
    $row = $dbo->queryForRow($query);
    if (!$row){
        $query = "INSERT INTO `{$tblPrefix}base_menu_item` VALUES (469,'mobile','mobile_admin_navigation','','admin_mobile',1,'mobile.admin.navigation',NULL,NULL,3)";
        $dbo->query($query);
    }

    $query = "select * FROM `{$tblPrefix}base_menu_item` where id=470";
    $row = $dbo->queryForRow($query);
    if (!$row){
        $query = "INSERT INTO `{$tblPrefix}base_menu_item` VALUES (470,'mobile','mobile_admin_pages_index','','admin_mobile',2,'mobile.admin.pages.index',NULL,NULL,3)";
        $dbo->query($query);
    }


    $query = "select * FROM `{$tblPrefix}base_menu_item` where id=471";
    $row = $dbo->queryForRow($query);
    if (!$row){
        $query = "INSERT INTO `{$tblPrefix}base_menu_item` VALUES (471,'mobile','mobile_admin_pages_dashboard','','admin_mobile',3,'mobile.admin.pages.dashboard',NULL,NULL,3)";
        $dbo->query($query);
    }

    $query = "select * FROM `{$tblPrefix}base_menu_item` where id=485";
    $row = $dbo->queryForRow($query);
    if (!$row){
        $query = "INSERT INTO `{$tblPrefix}base_menu_item` VALUES (485,'mobile','mobile_pages_dashboard','','mobile_top',1,'base_member_dashboard',NULL,NULL,2)";
        $dbo->query($query);
    }

    $query = "select * FROM `{$tblPrefix}base_menu_item` where id=481";
    $row = $dbo->queryForRow($query);
    if (!$row){
        $query = "INSERT INTO `{$tblPrefix}base_menu_item` VALUES (481,'mobile','mobile_admin_settings','','admin_mobile',4,'mobile.admin_settings',NULL,NULL,2)";
        $dbo->query($query);
    }

    //Remove default widget in mobile
    IISSecurityProvider::deleteWidgetUsingComponentPlaceUniqueName("admin-5295f2e03ec8a");

    //Remove default widget in mobile
    IISSecurityProvider::deleteWidgetUsingComponentPlaceUniqueName("admin-5295f2e40db5c");

    BOL_ComponentAdminService::getInstance()->clearAllCache();
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}