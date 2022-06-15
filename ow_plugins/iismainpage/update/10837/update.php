<?php


$config = OW::getConfig();
if($config->configExists('iismainpage', 'disables'))
{
    $disables = json_decode($config->getValue('iismainpage', 'disables'),true);
    $disables = array_unique($disables);
    $config->saveConfig('iismainpage', 'disables', json_encode($disables));
}
