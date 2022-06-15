<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * Console notifications section items component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.notifications.mobile.components
 * @since 1.6.0
 */
class NOTIFICATIONS_MCMP_ConsoleItems extends OW_MobileComponent
{
    /**
     * Constructor.
     */
    public function __construct(  $limit, $exclude = null )
    {
        parent::__construct();

        $service = NOTIFICATIONS_BOL_Service::getInstance();
        $userId = OW::getUser()->getId();
        $addToExclude = $service->findIgnoreNotifications();
        if(sizeof($addToExclude) > 0)
        {
            if(isset($exclude)) {
                $exclude = array_merge($exclude, $addToExclude);
            } else{
                $exclude = $addToExclude;
            }
        }
        $notifications = $service->findNotificationList($userId, time(), $exclude, $limit);
        $items = self::prepareData($notifications);
        $this->assign('items', $items);

        $notificationIdList = array();
        foreach ( $items as $id => $item )
        {
            $notificationIdList[] = $id;
        }

        // Mark as viewed
        $service->markNotificationsViewedByUserId($userId);

        $exclude = is_array($exclude) ? array_merge($exclude, $notificationIdList) : $notificationIdList;
        $loadMore = (bool) $service->findNotificationCount($userId, null, $exclude);
        if ( !$loadMore )
        {
            $script = "OWM.trigger('mobile.console_hide_notifications_load_more', {});";
            OW::getDocument()->addOnloadScript($script);
        }
    }

    public static function prepareData( $notifications )
    {
        if ( !$notifications )
        {
            return array();
        }

        $avatars = array();
        $router = OW::getRouter();
        foreach ( $notifications as $notification )
        {
            $data = json_decode($notification->data, true);

            if(isset($data['url'])) {
                $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ, array('string' => $data['url'])));
                if (isset($stringRenderer->getData()['string'])) {
                    $data['url'] = $stringRenderer->getData()['string'];
                }
            }
            if(isset($data['avatar']['src'])) {
                $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ, array('string' => $data['avatar']['src'])));
                if (isset($stringRenderer->getData()['string'])) {
                    $data['avatar']['src'] = $stringRenderer->getData()['string'];
                }
            }
            if(isset($data['avatar']['url'])) {
                $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ, array('string' => $data['avatar']['url'])));
                if (isset($stringRenderer->getData()['string'])) {
                    $data['avatar']['url'] = $stringRenderer->getData()['string'];
                }
            }
            $avatar = empty($data['avatar']) ? array() : $data['avatar'];

            if ( !empty($data["avatar"]["userId"]) )
            {
                $avatarData = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($data["avatar"]["userId"]));
                $avatar = $avatarData[$data["avatar"]["userId"]];
            }
            $userDeleted = isset($avatar['urlInfo']['routeName'])
                && $avatar['urlInfo']['routeName']=='base_user_profile'
                && (!isset($avatar['urlInfo']['vars']['username']));

            $avatars[$notification->id] = array(
                'src' => isset($avatar['src']) ? $avatar['src'] : null,
                'title' => isset($avatar['title']) ? $avatar['title'] : null,
                'url' => isset($data['avatar']['urlInfo']['routeName']) && !$userDeleted ?
                    $router->urlForRoute($avatar['urlInfo']['routeName'], $avatar['urlInfo']['vars']) : null
            );
        }

        $items = array();
        foreach ( $notifications as $notification )
        {
            $disabled = false;
            $notificationData=NOTIFICATIONS_CLASS_ConsoleBridge::getInstance()->getEditedData($notification->pluginKey,$notification->entityId,$notification->entityType, $notification->getData());
            if(isset($notificationData["string"]["vars"]["status"]))
                $notificationData["string"]["vars"]["status"] = UTIL_String::truncate(UTIL_HtmlTag::stripTags($notificationData["string"]["vars"]["status"]), 200, '...');

            /** @var $notification NOTIFICATIONS_BOL_Notification */
            /*$notifData = $notification->getData();

            if ( isset($notifData['url']) )
            {
                if ( is_array($notifData['url']) && !empty($notifData['url']['routeName']) )
                {
                    $routeVars = isset($notifData['url']['routeVars']) ? $notifData['url']['routeVars'] : array();
                    $notifData['url'] = $router->urlForRoute($notifData['url']['routeName'], $routeVars);
                }
            }*/

            $itemEvent = new OW_Event('mobile.notifications.on_item_render', array(
                'entityType' => $notification->entityType,
                'entityId' => $notification->entityId,
                'pluginKey' => $notification->pluginKey,
                'userId' => $notification->userId,
                'data' =>$notificationData
            ));

            OW::getEventManager()->trigger($itemEvent);
            $item = $itemEvent->getData();
            /*
             * In order to repetitive notifications, some notifications must be shown only in desktop version like friend request
             * because in Desktop version friend request and invitations are in different parts but in mobile version all of the are in one list
             */
            if(isset($item) && isset($item['ignoreNotification']) && $item['ignoreNotification']==true)
            {
                continue;
            }
            if ( !$item ) // backward compatibility: row will be not clickable
            {
                $item = $notificationData;
                $disabled = true;

                if ( isset($item['url']) && strpos($item['url'], OW_URL_HOME) === 0 )
                {
                    $permalinkUri = str_replace(OW_URL_HOME, "", $item['url']);

                    $item['url'] = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute("base.desktop_version"), array(
                        "back-uri" => urlencode($permalinkUri)
                    ));
                }
            }

            $item['avatar'] = $avatars[$notification->id];

            if ( !empty($item['string']) && is_array($item['string']) )
            {
                $key = explode('+', $item['string']['key']);
                $vars = empty($item['string']['vars']) ? array() : $item['string']['vars'];
                $item['string'] = OW::getLanguage()->text($key[0], $key[1], $vars);
            }

            if ( !empty($item['string'])) {
                $item['string'] = UTIL_HtmlTag::stripTags($item['string']);
            }

            if ( !empty($item['contentImage']) )
            {
                $item['contentImage'] = is_string($item['contentImage'])
                    ? array( 'src' => $item['contentImage'] )
                    : $item['contentImage'];
            }
            else
            {
                $item['contentImage'] = null;
            }

            $item['viewed'] = (bool) $notification->viewed;
            $item['disabled'] = $disabled;
            $item['createTime'] = UTIL_DateTime::formatDate($notification->timeStamp);
            $items[$notification->id] = $item;
        }

        return $items;
    }
}