<?php
$config = OW::getConfig();
if ($config->configExists('iisgroupsplus', 'groupFileAndJoinFeed')){
    $config->deleteConfig('iisgroupsplus', 'groupFileAndJoinFeed');
}
if (!$config->configExists('iisgroupsplus', 'groupFileAndJoinAndLeaveFeed')) {
    $config->addConfig('iisgroupsplus', 'groupFileAndJoinAndLeaveFeed', '["fileFeed","joinFeed","leaveFeed"]');
}