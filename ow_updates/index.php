<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
define('_OW_', true);
define('DS', DIRECTORY_SEPARATOR);
define('OW_DIR_ROOT', dirname(dirname(__FILE__)) . DS);
define('UPDATE_DIR_ROOT', OW_DIR_ROOT . 'ow_updates' . DS);

require_once(OW_DIR_ROOT . 'ow_includes' . DS . 'init.php');
require_once OW_DIR_UTIL . 'file.php';
require_once UPDATE_DIR_ROOT . 'classes' . DS . 'autoload.php';
require_once UPDATE_DIR_ROOT . 'classes' . DS . 'error_manager.php';
require_once UPDATE_DIR_ROOT . 'classes' . DS . 'updater.php';
spl_autoload_register(array('UPDATE_Autoload', 'autoload'));

UPDATE_ErrorManager::getInstance(true);

$autoloader = UPDATE_Autoload::getInstance();
$autoloader->addPackagePointer('BASE_CLASS', OW_DIR_SYSTEM_PLUGIN . 'base' . DS . 'classes' . DS);
$autoloader->addPackagePointer('UPDATE', UPDATE_DIR_ROOT . 'classes' . DS);

//---------check admin or valid posts
OW_Auth::getInstance()->setAuthenticator(new OW_SessionAuthenticator());
OW::getSession()->start();
if (!OW::getUser()->isAuthenticated() || !OW::getUser()->isAdmin())
{
    if(!isPostValid() && !isScriptRequest()) {
        header('Location: ' . OW_URL_HOME . '404');
        exit();
    }
}

//---------END OF check admin

/* ------------------- Initialize all plugins ------------------------ */
OW::getPluginManager()->initPlugins();
$event = new OW_Event(OW_EventManager::ON_PLUGINS_INIT);
OW::getEventManager()->trigger($event);
/* ------------------- End Initialize all plugins ------------------------ */

/* ------------------- Files and backups UPDATE ------------------------ */
IISSecurityProvider::createBackupTables(new OW_Event(''));
/* ------------------- End Files and backups UPDATE END ------------------------ */

$db = Updater::getDbo();
$showResult = !isScriptRequest();

/* ------------------- CORE UPDATE  ------------------------ */
$version = IISSecurityProvider::updateCore($db);
/* ----------------- CORE UPDATE END ------------------------ */

/* ------------------- Install Plugins ------------------------ */
if((isset($_GET['install_plugins']) || isset($_POST['install_plugins'])) || isScriptNeedInstallPlugins()) {
    $plugins = getPluginsForInstall();
    IISSecurityProvider::installPlugins($plugins);
}
/* ------------------- End Install Plugins END ------------------------ */


/* ------------------- Language UPDATE ------------------------ */
if((isset($_GET['update_languages']) && $_GET['update_languages']) || (isset($_POST['update_languages']) && $_POST['update_languages']) || isScriptNeedLanguageUpdate() || ($version != null && !empty($version))) {
    IISSecurityProvider::updateLanguages(true);
}
/* ------------------- End Language UPDATE END ------------------------ */

/* ----------------- PLUGIN UPDATE ------------------------ */
if((isset($_GET['update_all']) && $_GET['update_all']) ||
    (isset($_POST['update_all']) && $_POST['update_all']) ||
    isScriptAllPluginsToUpdate()){
    //Update static files of all plugins
    $updateStaticFiles = true;
    if((isset($_GET['do_not_update_statics']) && $_GET['do_not_update_statics']) ||
        (isset($_POST['do_not_update_statics']) && $_POST['do_not_update_statics']) ||
        isScriptHasNoUpdateStatics()) {
        $updateStaticFiles = false;
    }

    if($updateStaticFiles){
        IISSecurityProvider::updateStaticFiles();
    }
    IISSecurityProvider::updateAllPlugins($db, $showResult);
}else if(isset($_GET['plugin'])) {
    //Update static files of requested plugin
    IISSecurityProvider::updatePluginStaticFilesWithPluginKey($_GET['plugin']);
    IISSecurityProvider::updatePlugin($db, $_GET['plugin'], $showResult);
}
/* ------------------ PLUGIN UPDATE END -------------------- */

/* ----------------- THEME UPDATE ------------------------ */
if(isset($_GET['theme'])) {
    IISSecurityProvider::updateTheme($db, $_GET['theme'], $showResult);
}
/* ------------------ THEME UPDATE END -------------------- */

if(!$showResult){
    exit();
}else{
    $urlToRedirect = OW::getRouter()->urlForRoute('admin_plugins_installed');
    if (!empty($_GET['back-uri'])) {
        $urlToRedirect = urldecode($_GET['back-uri']);
    }
    OW::getApplication()->redirect($urlToRedirect, OW::CONTEXT_DESKTOP);
}

IISSecurityProvider::showCoreUpdateResult($version);

/* functions */

function isScriptAllPluginsToUpdate()
{
    if(!isScriptRequest()){
        return false;
    }
    return sizeof($_SERVER['argv'])>1 && in_array('update_all', $_SERVER['argv']);
}

function isScriptHasNoUpdateStatics()
{
    if(!isScriptRequest()){
        return false;
    }
    return sizeof($_SERVER['argv'])>1 && in_array('do_not_update_statics', $_SERVER['argv']);
}

function isScriptNeedInstallPlugins(){
    if(!isScriptRequest()){
        return false;
    }
    foreach ($_SERVER['argv'] as $value) {
        if (strpos($value, 'install_plugins') !== false) {
            return true;
        }
    }
    return false;
}

function isScriptNeedLanguageUpdate(){
    if(!isScriptRequest()){
        return false;
    }
    return sizeof($_SERVER['argv'])>1 && in_array('update_languages', $_SERVER['argv']);
}

function isScriptRequest()
{
    return php_sapi_name() === 'cli';
}

function getPluginsForInstall(){
    $plugins = null;
    $pluginsString = "";
    if(isset($_GET['install_plugins'])){
        $pluginsString = $_GET['install_plugins'];
    }else if(isset($_POST['install_plugins'])){
        $pluginsString = $_POST['install_plugins'];
    }else if(isScriptNeedInstallPlugins()){
        foreach ($_SERVER['argv'] as $value){
            if(strpos($value ,'install_plugins') !== false){
                $pluginsString = $value;
                $pluginsString = str_replace('install_plugins', '', $pluginsString);
                $pluginsString = str_replace('=', '', $pluginsString);
            }
        }
    }

    $pluginsString = trim($pluginsString);
    $plugins = explode(',', $pluginsString);
    return $plugins;
}

function isPostValid(){
    return isset($_POST['OW_AUTHENTICATE']) && defined('OW_AUTHENTICATE_COMMAND') && OW_AUTHENTICATE_COMMAND != null && OW_AUTHENTICATE_COMMAND == $_POST['OW_AUTHENTICATE'];
}