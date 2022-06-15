<?php

/**
 * iisvideoplus
 */
/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisvideoplus
 * @since 1.0
 */

$config =  OW::getConfig();
if ( !$config->configExists('iisvideoplus', 'uninstall_inprogress') )
{
    $config->addConfig('iisvideoplus', 'uninstall_inprogress', 0, 'Plugin is being uninstalled');
}

if ( !$config->configExists('iisvideoplus', 'uninstall_cron_busy') )
{
    $config->addConfig('iisvideoplus', 'uninstall_cron_busy', 0, 'Uninstall queue is busy');
}

if ( !$config->configExists('iisvideoplus', 'maintenance_mode_state') )
{
    $state = (int) $config->getValue('base', 'maintenance');
    $config->addConfig('iisvideoplus', 'maintenance_mode_state', $state, 'Stores site maintenance mode config before plugin uninstallation');
}
