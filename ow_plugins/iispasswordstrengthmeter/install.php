<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

$config = OW::getConfig();

if ( !$config->configExists('iispasswordstrengthmeter', 'minimumCharacter') )
{
    $config->addConfig('iispasswordstrengthmeter', 'minimumCharacter', 8);
}
if ( !$config->configExists('iispasswordstrengthmeter', 'minimumRequirementPasswordStrength') )
{
    $config->addConfig('iispasswordstrengthmeter', 'minimumRequirementPasswordStrength', 3);
}
