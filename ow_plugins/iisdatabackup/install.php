<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

$config = OW::getConfig();

if ( !$config->configExists('iisdatabackup', 'deadline') )
{
    $config->addConfig('iisdatabackup', 'deadline', 10);
}
if ( !$config->configExists('iisdatabackup', 'tables') )
{
    $config->addConfig('iisdatabackup', 'tables', 'newsfeed_status');
}
if ( !$config->configExists('iisdatabackup', 'numberOfData') )
{
    $config->addConfig('iisdatabackup', 'numberOfData', 100);
}

