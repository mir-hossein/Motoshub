<?php

/**
 * iismainpage
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisslideshow
 * @since 1.0
 */
OW::getRouter()->addRoute(new OW_Route('iismainpage.index', 'iismainpage/index', 'IISMAINPAGE_MCTRL_Index', 'index'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.dashboard', 'iismainpage/dashboard', 'IISMAINPAGE_MCTRL_Index', 'dashboard'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.user.groups', 'iismainpage/user-groups', 'IISMAINPAGE_MCTRL_Index', 'userGroups'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.friends', 'iismainpage/friends', 'IISMAINPAGE_MCTRL_Index', 'friends'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.mailbox', 'iismainpage/mailbox', 'IISMAINPAGE_MCTRL_Index', 'mailbox'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.mailbox.type', 'iismainpage/mailbox/:type', 'IISMAINPAGE_MCTRL_Index', 'mailbox'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.settings', 'iismainpage/settings', 'IISMAINPAGE_MCTRL_Index', 'settings'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.notifications', 'iismainpage/notifications', 'IISMAINPAGE_MCTRL_Index', 'notifications'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.photos', 'iismainpage/photos', 'IISMAINPAGE_MCTRL_Index', 'photos'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.videos', 'iismainpage/videos', 'IISMAINPAGE_MCTRL_Index', 'videos'));

OW::getRouter()->addRoute(new OW_Route('iismainpage.mailbox_responder', 'iismainpage/mailbox/responder', 'IISMAINPAGE_MCTRL_Index', 'mailbox_responder'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.friends_responder', 'iismainpage/friends/responder', 'IISMAINPAGE_MCTRL_Index', 'friends_responder'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.user.groups_responder', 'iismainpage/user-groups/responder', 'IISMAINPAGE_MCTRL_Index', 'userGroups_responder'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.photos_responder', 'iismainpage/photos/responder', 'IISMAINPAGE_MCTRL_Index', 'photos_responder'));
OW::getRouter()->addRoute(new OW_Route('iismainpage.videos_responder', 'iismainpage/videos/responder', 'IISMAINPAGE_MCTRL_Index', 'videos_responder'));


$eventHandler = new IISMAINPAGE_MCLASS_EventHandler();
$eventHandler->init();
