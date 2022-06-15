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

/**
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow.ow_plugins.blogs.classes
 * @since 1.6.0
 */
class BLOGS_MCLASS_EventHandler
{
    /**
     * Singleton instance.
     *
     * @var BLOGS_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return BLOGS_MCLASS_EventHandler
     */
    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function init()
    {
        $service = PostService::getInstance();
        OW::getEventManager()->bind('feed.on_item_render', array($this, "onFeedItemRenderDisableActions"));
        OW::getEventManager()->bind('base.mobile_top_menu_add_options', array($this, 'onMobileTopMenuAddLink'));
        OW::getEventManager()->bind('mobile.notifications.on_item_render', array($this, 'onNotificationRender'));
    }

    public function onFeedItemRenderDisableActions(OW_Event $event)
    {
        $params = $event->getParams();

        if (!in_array($params["action"]["entityType"], array('blog-post'))) {
            return;
        }

        $data = $event->getData();

        if (isset($data['content']['vars']['description'])) {
            $data['content']['vars']['description'] = trim(preg_replace('/\s+/', ' ', $data['content']['vars']['description']));
            $data['content']['vars']['description'] = preg_replace('/^(<br\s*\/?>)*|(<br\s*\/?>)*$/i', '', $data['content']['vars']['description']);
            $data['content']['vars']['description'] = preg_replace("/^(<br \/>)/", '', trim($data['content']['vars']['description']));
        }
        $data["disabled"] = false;
        if (isset($data["string"]["key"]) && $data["string"]["key"] == "blogs+feed_add_item_label"
        && isset($data["respond"]["text"]) && isset($params["lastActivity"]["data"]["string"]["key"]) ) {
            $userName = BOL_UserService::getInstance()->getDisplayName($data["ownerId"]);
            $userUrl = BOL_UserService::getInstance()->getUserUrl($data["ownerId"]);
            if ($params["lastActivity"]["data"]["string"]["key"] == "blogs+feed_activity_post_string")
                $data["respond"]["text"] = OW::getLanguage()->text('blogs', 'feed_activity_post_string', array('user' => '<a href="' . $userUrl . '">' . $userName . '</a>'));
            elseif ($params["lastActivity"]["data"]["string"]["key"] == "blogs+feed_activity_post_string_like")
                $data["respond"]["text"] = OW::getLanguage()->text('blogs', 'feed_activity_post_string_like', array('user' => '<a href="' . $userUrl . '">' . $userName . '</a>'));
        }
        $event->setData($data);
    }

    public function onMobileTopMenuAddLink(BASE_CLASS_EventCollector $event)
    {
        if (OW::getUser()->isAuthenticated() && OW::getUser()->isAuthorized('blogs', 'add')) {
            $event->add(array(
                'prefix' => 'blogs',
                'key' => 'mobile_main_menu_list',
                'url' => OW::getRouter()->urlForRoute('post-save-new')
            ));
        }
    }

    public function onNotificationRender(OW_Event $event)
    {
        $params = $event->getParams();
        $data = $params['data'];

        if (empty($params['entityType']) || ($params['entityType'] !== 'blogs-add_comment')) {
            return;
        }

        $data = $params['data'];
        $event->setData($data);
    }
}