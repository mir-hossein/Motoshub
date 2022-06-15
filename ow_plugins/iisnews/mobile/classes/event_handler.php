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
 * @author Mohammad Aghaabbasloo
 * @package ow.ow_plugins.iisnews.classes
 * @since 1.6.0
 */
class IISNEWS_MCLASS_EventHandler
{
    /**
     * Singleton instance.
     *
     * @var IISNEWS_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return IISNEWS_MCLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function init()
    {
        $service = EntryService::getInstance();
        OW::getEventManager()->bind('feed.on_item_render', array($service, 'feedOnItemRenderActivity'));
        OW::getEventManager()->bind('mobile.notifications.on_item_render', array($this, 'onNotificationRender'));
    }

    public function onNotificationRender( OW_Event $e )
    {
        $params = $e->getParams();

        if ($params['pluginKey'] != 'iisnews' || ($params['entityType'] != 'news-add_comment' && $params['entityType'] != 'news-add_news')) {
            return;
        }

        $data = $params['data'];

        if (!isset($data['avatar']['urlInfo']['vars']['username'])) {
            return;
        }

        $userService = BOL_UserService::getInstance();
        $user = $userService->findByUsername($data['avatar']['urlInfo']['vars']['username']);
        if (!$user) {
            return;
        }
        $entryService = EntryService::getInstance();

        if ($params['entityType'] == 'news-add_comment') {
            $commentId = $params['entityId'];
            $comment = BOL_CommentService::getInstance()->findComment($commentId);
            if (!$comment) {
                return;
            }
            $commEntity = BOL_CommentService::getInstance()->findCommentEntityById($comment->commentEntityId);
            if (!$commEntity) {
                return;
            }
            $entry = $entryService->findById($commEntity->entityId);
            $stringKey = 'iisnews+comment_notification_string';
        }
        if ($params['entityType'] == 'news-add_news') {
            $entry = EntryService::getInstance()->findById($params['entityId']);
            $stringKey = 'iisnews+news_notification_string';
        }
        if ($entry == null) {
            return;
        }
        if (OW::getUser()->getId() != $entry->authorId) {
            $data = $params['data'];
            $e->setData($data);
        } else {
            $langVars = array(
                'actorUrl' => $userService->getUserUrl($user->id),
                'actor' => $userService->getDisplayName($user->id),
                'url' => OW::getRouter()->urlForRoute('entry', array('id' => $entry->getId())),
                'title' => UTIL_String::truncate( $entry->getTitle(), 60, '...' )
            );
            if ($params['entityType'] == 'news-add_comment') {
                $langVars['comment'] =  UTIL_String::truncate( $comment->getMessage(), 120, '...' );
            }

            $data['string'] = array('key' => $stringKey, 'vars' => $langVars);

            $e->setData($data);
        }
    }
}