<?php
$config = OW::getConfig();
if (!$config->configExists('iisgroupsplus', 'showFileUploadSettings')) {
    $config->addConfig('iisgroupsplus', 'showFileUploadSettings', 1);
}
if (!$config->configExists('iisgroupsplus', 'showAddTopic')) {
    $config->addConfig('iisgroupsplus', 'showAddTopic', 1);
}