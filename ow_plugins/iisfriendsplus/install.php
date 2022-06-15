<?php

$config = OW::getConfig();
if (!$config->configExists('iisfriendsplus', 'selected_roles')) {
    $config->addConfig('iisfriendsplus', 'selected_roles', null);
}
