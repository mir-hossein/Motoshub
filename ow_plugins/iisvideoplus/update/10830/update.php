<?php

$config =  OW::getConfig();
if($config->configExists('iisvideoplus', 'maximum_video_file_upload'))
{
    $config->deleteConfig('iisvideoplus', 'maximum_video_file_upload');
}
try
{
    Updater::getDbo()->update("UPDATE `".OW_DB_PREFIX."base_plugin` SET `adminSettingsRoute`=NULL, `uninstallRoute`=NULL WHERE `module`='iisvideoplus'");
}
catch ( Exception $e ) { $exArr[] = $e; }
