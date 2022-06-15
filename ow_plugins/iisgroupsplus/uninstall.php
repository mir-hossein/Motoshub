<?php

/**
 * iisgroupsplus
 */

BOL_ComponentAdminService::getInstance()->deleteWidget('IISGROUPSPLUS_CMP_FileListWidget');

$eventIisGroupsPlusFiles = new OW_Event('iisgroupsplus.delete.files', array('allFiles'=>true));
OW::getEventManager()->trigger($eventIisGroupsPlusFiles);
$config = OW::getConfig();

if($config->configExists('iisgroupsplus', 'groupFileAndJoinAndLeaveFeed'))
    $config->deleteConfig('iisgroupsplus', 'groupFileAndJoinAndLeaveFeed');

if (!$config->configExists('iisgroupsplus', 'showFileUploadSettings')) {
    $config->deleteConfig('iisgroupsplus', 'showFileUploadSettings');
}
if (!$config->configExists('iisgroupsplus', 'showAddTopic')) {
    $config->deleteConfig('iisgroupsplus', 'showAddTopic');
}
try
{
    BOL_ComponentAdminService::getInstance()->deleteWidget('IISGROUPSPLUS_CMP_PendingInvitation');
}
catch(Exception $e)
{

}