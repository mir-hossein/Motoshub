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

OW::getRouter()->addRoute(new OW_Route('groups-view', 'groups/:groupId', 'GROUPS_MCTRL_Groups', 'view'));

GROUPS_CLASS_EventHandler::getInstance()->genericInit();
$eventHandler = GROUPS_MCLASS_EventHandler::getInstance();
$eventHandler->genericInit();

OW::getEventManager()->bind('mobile.invitations.on_item_render', array($eventHandler, 'onInvitationsItemRender'));
OW::getEventManager()->bind('feed.on_item_render', array($eventHandler, "onFeedItemRenderDisableActions"));

GROUPS_CLASS_ConsoleBridge::getInstance()->genericInit();


OW::getEventManager()->bind('feed.collect_widgets', array(GROUPS_CLASS_EventHandler::getInstance(), "onFeedCollectWidgets"));
OW::getEventManager()->bind('feed.on_widget_construct', array(GROUPS_CLASS_EventHandler::getInstance(), "onFeedWidgetConstruct"));
OW::getEventManager()->bind('feed.on_item_render', array($eventHandler, "onFeedItemRender"));

//Frontend Routs
OW::getRouter()->addRoute(new OW_Route('groups-create', 'groups/create', 'GROUPS_MCTRL_Groups', 'create'));
OW::getRouter()->addRoute(new OW_Route('groups-edit', 'groups/:groupId/edit', 'GROUPS_MCTRL_Groups', 'edit'));
OW::getRouter()->addRoute(new OW_Route('groups-join', 'groups/:groupId/join', 'GROUPS_MCTRL_Groups', 'join'));
OW::getRouter()->addRoute(new OW_Route('groups-customize', 'groups/:groupId/customize', 'GROUPS_MCTRL_Groups', 'customize'));
OW::getRouter()->addRoute(new OW_Route('groups-most-popular', 'groups/most-popular', 'GROUPS_MCTRL_Groups', 'mostPopularList'));
OW::getRouter()->addRoute(new OW_Route('groups-latest', 'groups/latest', 'GROUPS_MCTRL_Groups', 'latestList'));
OW::getRouter()->addRoute(new OW_Route('groups-invite-list', 'groups/invitations', 'GROUPS_MCTRL_Groups', 'inviteList'));
OW::getRouter()->addRoute(new OW_Route('groups-my-list', 'groups/my', 'GROUPS_MCTRL_Groups', 'myGroupList'));

OW::getRouter()->addRoute(new OW_Route('groups-index', 'groups', 'GROUPS_MCTRL_Groups', 'index'));
OW::getRouter()->addRoute(new OW_Route('groups-user-groups', 'users/:user/groups', 'GROUPS_MCTRL_Groups', 'userGroupList'));
OW::getRouter()->addRoute(new OW_Route('groups-leave', 'groups/:groupId/leave', 'GROUPS_MCTRL_Groups', 'leave'));

OW::getRouter()->addRoute(new OW_Route('groups-user-list', 'groups/:groupId/users', 'GROUPS_MCTRL_Groups', 'userList'));
//OW::getRouter()->addRoute(new OW_Route('groups-private-group', 'groups/:groupId/private', 'GROUPS_MCTRL_Groups', 'privateGroup'));

OW::getRegistry()->addToArray(BASE_CMP_AddNewContent::REGISTRY_DATA_KEY,
    array(
        BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_comment',
        BASE_CMP_AddNewContent::DATA_KEY_URL => OW::getRouter()->urlForRoute('groups-create'),
        BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('groups', 'add_new_label')
    ));

GROUPS_CLASS_ConsoleBridge::getInstance()->init();
GROUPS_CLASS_ContentProvider::getInstance()->init();