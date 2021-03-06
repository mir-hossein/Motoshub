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
 * Mobile video event handler
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.video.mobile.classes
 * @since 1.6.0
 */
class VIDEO_MCLASS_EventHandler
{
    /**
     * @var VIDEO_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * @return VIDEO_MCLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct() { }

    public function init()
    {
        VIDEO_CLASS_EventHandler::getInstance()->genericInit();
        OW::getEventManager()->bind('mobile.notifications.on_item_render', array($this, 'onNotificationRender'));
        OW::getEventManager()->bind('base.mobile_top_menu_add_options', array($this, 'onMobileTopMenuAddLink'));
    }

    public function onMobileTopMenuAddLink( BASE_CLASS_EventCollector $event )
    {
        if ( OW::getUser()->isAuthenticated() && OW::getUser()->isAuthorized('video', 'add') )
        {
            $event->add(array(
                'prefix' => 'video',
                'key' => 'video_mobile',
                'url' => OW::getRouter()->urlFor('VIDEO_MCTRL_Add', 'index')
            ));
        }
    }

    public function onNotificationRender( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $params['data'];

        if (empty($params['entityType']) || ($params['entityType'] !== 'video-add_comment' && $params['entityType'] != 'video-add_rate')) {
            return;
        }
        if ($params['entityType'] == 'video-add_rate') {
            $event->setData($data);
            return;
        }
        $commentId = $params['entityId'];
        $comment = BOL_CommentService::getInstance()->findComment($commentId);
        if (!$comment) {
            return;
        }
        $commEntity = BOL_CommentService::getInstance()->findCommentEntityById($comment->commentEntityId);
        if (!$commEntity) {
            return;
        }

        $entityId = $commEntity->entityId;
        $userId = $comment->userId;

        $clipService = VIDEO_BOL_ClipService::getInstance();
        $userService = BOL_UserService::getInstance();

        $clip = $clipService->findClipById($entityId);
        if (OW::getUser()->getId() != $clip->userId)
        {
            $data = $params['data'];
            $event->setData($data);
        }
        else
        {
            $url = OW::getRouter()->urlForRoute('view_clip', array('id' => $entityId));
            $langVars = array(
                'userName' => $userService->getDisplayName($userId),
                'userUrl' => $userService->getUserUrl($userId),
                'videoUrl' => $url,
                'videoTitle' => UTIL_String::truncate(strip_tags($clip->title), 60, '...' ),
                'comment' => UTIL_String::truncate( $comment->getMessage(), 120, '...' )
            );

            $data['string'] = array('key' => 'video+email_notifications_comment', 'vars' => $langVars);
            $data['url'] = $url;
            $event->setData($data);
        }
    }
}