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
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.controllers
 * @since 1.0
 */
class BASE_CTRL_Rate extends OW_ActionController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function updateRate()
    {
        if ( empty($_POST['entityId']) || empty($_POST['entityType']) || empty($_POST['rate']) || empty($_POST['ownerId']) )
        {
            exit(json_encode(array('errorMessage' => 'Invalid request')));
        }

        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            $code =$_GET['code'];
            if(!isset($code)){
                throw new Redirect404Exception();
            }
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'update_rate')));
        }
        $service = BOL_RateService::getInstance();

        $entityId = (int) $_POST['entityId'];
        $entityType = trim($_POST['entityType']);
        $rate = (int) $_POST['rate'];
        $ownerId = (int) $_POST['ownerId'];
        $userId = OW::getUser()->getId();

        if ( !OW::getUser()->isAuthenticated() )
        {
            exit(json_encode(array('errorMessage' => OW::getLanguage()->text('base', 'rate_cmp_auth_error_message'))));
        }

        if ( $userId === $ownerId )
        {
            exit(json_encode(array('errorMessage' => OW::getLanguage()->text('base', 'rate_cmp_owner_cant_rate_error_message'))));
        }

        if ( false )
        {
            //TODO add authorization error
            exit(json_encode(array('errorMessage' => 'Auth error')));
        }

        if ( BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $ownerId) )
        {
            exit(json_encode(array('errorMessage' => OW::getLanguage()->text('base', 'user_block_message'))));
        }

        $rateItem = $service->findRate($entityId, $entityType, $userId);

        if ( $rateItem === null )
        {
            $rateItem = new BOL_Rate();
            $rateItem->setEntityId($entityId)->setEntityType($entityType)->setUserId($userId)->setActive(true);
        }

        $rateItem->setScore($rate)->setTimeStamp(time());

        $service->saveRate($rateItem);
        $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
        if(isset($mobileEvent->getData()['isMobileVersion'])&& $mobileEvent->getData()['isMobileVersion']==true && ($entityType=='photo_rates' || $entityType=='blog-post')) {
            $totalScoreCmp = new PHOTO_MCMP_TotalScore($entityId, $entityType);
        }else {
            $totalScoreCmp = new BASE_CMP_TotalScore($entityId, $entityType);
        }
     $this->addToNotificationList($entityId,$entityType);
        exit(json_encode(array('totalScoreCmp' => $totalScoreCmp->render(), 'message' => OW::getLanguage()->text('base', 'rate_cmp_success_message'))));
    }
    public function addToNotificationList($entityId,$entityType)
    {
        $userId = OW::getUser()->getId();
        $ownerId = (int) $_POST['ownerId'];
        $userService = BOL_UserService::getInstance();
        if($entityType=='photo_rates')
        {
            $photoService = PHOTO_BOL_PhotoService::getInstance();
            $url = OW::getRouter()->urlForRoute('view_photo', array('id' => $entityId));
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));
            $e = new OW_Event('notifications.add', array(
                'pluginKey' => 'photo',
                'entityType' => 'photo-add_rate',
                'entityId' => $entityId,
                'action' => 'photo-add_rate',
                'userId' => $ownerId,
                'time' => time()
            ), array(
                'avatar' => $avatars[$userId],
                'string' => array(
                    'key' => 'photo+email_notifications_rate',
                    'vars' => array(
                        'userName' => $userService->getDisplayName($userId),
                        'userUrl' => $userService->getUserUrl($userId),
                        'photoUrl' => $url
                    )
                ),
                'url' => $url,
                'contentImage' => $photoService->getPhotoUrlByPhotoInfo($entityId, PHOTO_BOL_PhotoService::TYPE_SMALL, array(), true)
            ));
            OW::getEventManager()->trigger($e);
        }
        else if($entityType=='video_rates')
        {
            $clipService = VIDEO_BOL_ClipService::getInstance();
            $clip = $clipService->findClipById($entityId);
            $url = OW::getRouter()->urlForRoute('view_clip', array('id' => $entityId));
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));
            $event = new OW_Event('notifications.add', array(
                'pluginKey' => 'video',
                'entityType' => 'video-add_rate',
                'entityId' => $entityId,
                'action' => 'video-add_rate',
                'userId' => $ownerId,
                'time' => time()
            ), array(
                'avatar' => $avatars[$userId],
                'string' => array(
                    'key' => 'video+email_notifications_rate',
                    'vars' => array(
                        'userName' => $userService->getDisplayName($userId),
                        'userUrl' => $userService->getUserUrl($userId),
                        'videoUrl' => $url,
                        'videoTitle' => strip_tags($clip->title)
                    )
                ),
                'url' => $url
            ));
            OW::getEventManager()->trigger($event);

        }
    else
        return;

    }
    public static function displayRate( array $params )
    {
        $service = BOL_RateService::getInstance();

        $minRate = 1;
        $maxRate = $service->getConfig(BOL_RateService::CONFIG_MAX_RATE);

        if ( !isset($params['avg_rate']) || (float) $params['avg_rate'] < $minRate || (float) $params['avg_rate'] > $maxRate )
        {
            return '_INVALID_RATE_PARAM_';
        }

        $width = (int) floor((float) $params['avg_rate'] / $maxRate * 100);

        return '<div class="inactive_rate_list"><div class="active_rate_list" style="width:' . $width . '%;"></div></div>';
    }
}