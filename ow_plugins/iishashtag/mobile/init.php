<?php

/**
 * iishashtag
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
IISHASHTAG_MCLASS_EventHandler::getInstance()->genericInit();

OW::getRouter()->addRoute(new OW_Route('iishashtag.load_tags', 'iishashtag/tags', "IISHASHTAG_CTRL_Load", 'loadTags'));
OW::getRouter()->addRoute(new OW_Route('iishashtag.load_tags_filled', 'iishashtag/tags/:tag', "IISHASHTAG_CTRL_Load", 'loadTags'));

OW::getRouter()->addRoute(new OW_Route('iishashtag.page', 'hashtag/', "IISHASHTAG_MCTRL_Load", 'index'));
OW::getRouter()->addRoute(new OW_Route('iishashtag.tag', 'hashtag/:tag', "IISHASHTAG_MCTRL_Load", 'index'));
OW::getRouter()->addRoute(new OW_Route('iishashtag.tag.tab', 'hashtag/:tag/:tab', "IISHASHTAG_MCTRL_Load", 'index'));