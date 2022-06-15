<?php
try
{
    $config = OW::getConfig();
    $config->addConfig('base', 'file_log_enabled', true);
    $config->addConfig('base', 'stdout_log_enabled', false);
    $config->addConfig('base', 'elastic_log_enabled', false);
    $config->addConfig('base', 'elastic_host', 'localhost');
    $config->addConfig('base', 'elastic_port', 9200);
    if($config->configExists('base','log_level')){
        $val1 = $config->getValue('base','log_level');
        $config->deleteConfig('base', 'log_level');
        $config->addConfig('base', 'file_log_level', $val1);
        $config->addConfig('base', 'stdout_log_level', $val1);
        $config->addConfig('base', 'elastic_log_level', $val1);
    }
    if($config->configExists('base','log_output_format')){
        $val1 = $config->getValue('base','log_output_format');
        $config->deleteConfig('base', 'log_output_format');
        $config->addConfig('base', 'file_output_format', $val1);
        $config->addConfig('base', 'stdout_output_format', $val1);
    }
    if($config->configExists('base','log_output_handler')){
        $config->deleteConfig('base', 'log_output_handler');
    }
}
catch (Exception $e)
{
    OW::getLogger()->writeLog( OW_Log::ERROR, json_encode($e));
}

try
{
    $dto = BOL_NavigationService::getInstance()->findMenuItem('admin', 'sidebar_menu_item_log');
    if(!isset($dto)) {
        $dto = new BOL_MenuItem();
        $dto->setType(BOL_NavigationService::MENU_TYPE_SETTINGS)
            ->setPrefix('admin')
            ->setKey('sidebar_menu_item_log')
            ->setRoutePath('admin_settings_log')
            ->setOrder(5)
            ->setVisibleFor(3);
        BOL_NavigationService::getInstance()->saveMenuItem($dto);
    }
}
catch (Exception $e)
{
    OW::getLogger()->writeLog( OW_Log::ERROR, json_encode($e));
}
