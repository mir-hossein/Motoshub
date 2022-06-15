<?php

/**
 * iisgroupsplus
 */
/**
 * @author Mohammad Agha Abbasloo <a.mohammad85@gmail.com>
 * @package ow_plugins.iisgroupsplus
 * @since 1.0
 */

BOL_ComponentAdminService::getInstance()->deleteWidget('IISGROUPSPLUS_CMP_FileListWidget');
try
{
    BOL_ComponentAdminService::getInstance()->deleteWidget('IISGROUPSPLUS_CMP_PendingInvitation');
}
catch(Exception $e)
{

}