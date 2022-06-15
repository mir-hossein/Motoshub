<?php

/**
 * IIS Advance Search
 */

IISADVANCESEARCH_CLASS_EventHandler::getInstance()->init();
OW::getRouter()->addRoute(new OW_Route('iisadvancesearch.search', 'search/all', 'IISADVANCESEARCH_CTRL_Search', 'searchAll'));

OW::getRouter()->addRoute(new OW_Route('iisadvancesearch.admin', 'iisadvancesearch/admin', "IISADVANCESEARCH_CTRL_Admin", 'index'));
