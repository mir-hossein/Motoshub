<?php

/**
 * iiseventplus
 */

BOL_ComponentAdminService::getInstance()->deleteWidget('IISEVENTPLUS_CMP_FileListWidget');

$eventIisEventsPlusFiles = new OW_Event('iiseventplus.delete.files', array('allFiles'=>true));
OW::getEventManager()->trigger($eventIisEventsPlusFiles);