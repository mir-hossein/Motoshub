<?php

$config = OW::getConfig();
if ( !$config->configExists('iisjalali', 'dateLocale') )
{
    $config->addConfig('iisjalali', 'dateLocale',1);
}
