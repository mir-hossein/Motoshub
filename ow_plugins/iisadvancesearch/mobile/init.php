<?php

/**
 * IIS Advance Search
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisadvancesearch
 * @since 1.0
 */

OW::getRouter()->addRoute(new OW_Route('iisadvancesearch.search_users_empty', 'ajax-search-users/:type', 'IISADVANCESEARCH_CTRL_Search', 'searchUsers'));
OW::getRouter()->addRoute(new OW_Route('iisadvancesearch.search_users', 'ajax-search-users/:type/:key', 'IISADVANCESEARCH_CTRL_Search', 'searchUsers'));
OW::getRouter()->addRoute(new OW_Route('iisadvancesearch.search_friends', 'search/friends/:key', 'IISADVANCESEARCH_CTRL_Search', 'searchFriends'));

OW::getRouter()->addRoute(new OW_Route('iisadvancesearch.search_users.ctrl', 'search-users', 'IISADVANCESEARCH_MCTRL_Container', 'index'));
OW::getRouter()->addRoute(new OW_Route('iisadvancesearch.list.users', 'search/users/:type', 'IISADVANCESEARCH_MCTRL_AllUsersList', 'index'));
OW::getRouter()->addRoute(new OW_Route('iisadvancesearch.all_users.search', 'search/users/all/:search', 'IISADVANCESEARCH_MCTRL_AllUsersList', 'index'));
OW::getRouter()->addRoute(new OW_Route('iisadvancesearch.all_users.responder', 'search/users/all-responder', 'IISADVANCESEARCH_MCTRL_AllUsersList', 'responder'));