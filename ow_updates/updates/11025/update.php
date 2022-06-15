<?php
try
{
    $config = OW::getConfig();
    if(!$config->configExists('base', 'file_log_enabled')) {
        $config->addConfig('base', 'file_log_enabled', true);
        $config->addConfig('base', 'stdout_log_enabled', false);
        $config->addConfig('base', 'elastic_log_enabled', false);
    }
}
catch (Exception $e)
{
    OW::getLogger()->writeLog( OW_Log::ERROR, json_encode($e));
}
