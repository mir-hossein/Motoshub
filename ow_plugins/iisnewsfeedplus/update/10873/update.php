<?php

$config = OW::getConfig();

if ($config->configExists('iisnewsfeedplus', 'allow_sort'))
{
    $config->saveConfig('iisnewsfeedplus', 'allow_sort','1');
}
else{
    $config->addConfig('iisnewsfeedplus', 'allow_sort', '1','Allow sort feeds');
}
