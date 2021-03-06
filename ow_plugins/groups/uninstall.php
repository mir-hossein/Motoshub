<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
$widgetService = BOL_ComponentAdminService::getInstance();

try
{
    $widgets = $widgetService->findPlaceComponentList(GROUPS_BOL_Service::WIDGET_PANEL_NAME);
    foreach ( $widgets as $widget )
    {
	$widgetService->deleteWidgetPlace($widget['uniqName']);
    }
}
catch ( Exception $e ) {}

BOL_ComponentAdminService::getInstance()->deleteWidget('GROUPS_CMP_JoinButtonWidget');
BOL_ComponentAdminService::getInstance()->deleteWidget('GROUPS_CMP_BriefInfoWidget');
BOL_ComponentAdminService::getInstance()->deleteWidget('GROUPS_CMP_UserListWidget');
BOL_ComponentAdminService::getInstance()->deleteWidget('GROUPS_CMP_WallWidget');
BOL_ComponentAdminService::getInstance()->deleteWidget('GROUPS_CMP_LeaveButtonWidget');

if ( OW::getConfig()->getValue('groups', 'is_forum_connected') )
{
    $event = new OW_Event('forum.delete_section', array('entity' => 'groups'));
    OW::getEventManager()->trigger($event);

    $event = new OW_Event('forum.delete_widget');
    OW::getEventManager()->trigger($event);
}

if ( OW::getConfig()->getValue('groups', 'is_iisgroupsplus_connected') )
{
    $event = new OW_Event('iisgroupsplus.delete_widget');
    OW::getEventManager()->trigger($event);
    OW::getConfig()->deleteConfig('groups', 'is_iisgroupsplus_connected');
}

if(OW::getConfig()->configExists('groups', 'is_telegram_connected'))
{
    $event = new OW_Event('iistelegram.delete_widget');
    OW::getEventManager()->trigger($event);
    OW::getConfig()->deleteConfig('groups', 'is_telegram_connected');
}

if ( OW::getConfig()->getValue('groups', 'is_instagram_connected') )
{
    $event = new OW_Event('iisinstagram.delete_widget');
    OW::getEventManager()->trigger($event);
    OW::getConfig()->deleteConfig('groups', 'is_instagram_connected');
}

$dbPrefix = OW_DB_PREFIX;

$sql =
    <<<EOT
DELETE FROM `{$dbPrefix}base_place` WHERE `name`='group';
EOT;

OW::getDbo()->query($sql);