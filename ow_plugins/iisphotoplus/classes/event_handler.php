<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Mohammad Agha Abbasloo <a.mohammad85@gmail.com>
 * @package ow_plugins.iisphotoplus.bol
 * @since 1.0
 */
class IISPHOTOPLUS_CLASS_EventHandler
{
    private static $classInstance;

    const HASH_TOKEN = 'abc';
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
    }

    public function init()
    {
        $masterPlugin = BOL_PluginService::getInstance()->findPluginByKey('photo');
        if( !isset($masterPlugin) || !$masterPlugin->isActive() ){
            return;
        }
        $service = IISPHOTOPLUS_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(IISEventManager::ADD_LIST_TYPE_TO_PHOTO, array($service, 'addListTypeToPhoto'));
        $eventManager->bind(IISEventManager::GET_RESULT_FOR_LIST_ITEM_PHOTO, array($service, 'getResultForListItemPhoto'));
        $eventManager->bind(IISEventManager::SET_TILE_HEADER_LIST_ITEM_PHOTO, array($service, 'setTtileHeaderListItemPhoto'));
        $eventManager->bind(IISEventManager::GET_VALID_LIST_FOR_PHOTO, array($service, 'getValidListForPhoto'));
        $eventManager->bind(IISEventManager::ON_FEED_ITEM_RENDERER, array($service, 'appendPhotosToFeed'));
        $eventManager->bind('notifications.collect_actions', array($this, 'collectNotificationActions'));
        $eventManager->bind('feed.after_like_added', array($this, 'notifyOnNewLike'));
        $eventManager->bind('media.panel.init.menu', array($this, 'onMediaPanelInitMenu'));
        $eventManager->bind('feed.after_like_removed', array($service, 'removeNotification'));

    }


    public function collectNotificationActions( BASE_CLASS_EventCollector $e )
    {
        $e->add(array(
            'section' => 'photo',
            'action' => 'photo-add_like',
            'sectionIcon' => 'ow_ic_picture',
            'sectionLabel' => OW::getLanguage()->text('photo', 'email_notifications_section_label'),
            'description' => OW::getLanguage()->text('iisphotoplus', 'email_notifications_setting_like'),
            'selected' => true
        ));
    }
    public function notifyOnNewLike(OW_Event $event){
        $newsFeedPlugin =BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        $photoPlugin =BOL_PluginDao::getInstance()->findPluginByKey('photo');
        if(isset($newsFeedPlugin) && $newsFeedPlugin->isActive() && isset($photoPlugin) && $photoPlugin->isActive()){
            $params = $event->getParams();
            if ( $params['entityType'] != 'photo_comments' &&  $params['entityType'] != 'multiple_photo_upload' )
            {
                return;
            }
            $photoService = PHOTO_BOL_PhotoService::getInstance();
            $userService = BOL_UserService::getInstance();
            $feedService =NEWSFEED_BOL_Service::getInstance();

            $userId = $params['userId'];
            $userUrl = $userService->getUserUrl($userId);
            $displayName = $userService->getDisplayName($userId);

            $action = $feedService->findAction($params['entityType'],$params['entityId']);
            $actionId = $action->id;
            $url = OW::getRouter()->urlForRoute('newsfeed_view_item',array('actionId'=>$actionId));
            $entityId = $params['entityId'];
            if($params['entityType'] =='multiple_photo_upload'){
                $albumService=PHOTO_BOL_PhotoAlbumService::getInstance();
                $contentImage = '';
                $string = array(
                    'key' => 'iisphotoplus+multiple_photo_liked_notification',
                    'vars' => array(
                        'user' => $displayName,
                        'userUrl' => $userUrl
                    )
                );
                $photoList = $photoService->getPhotoListByUploadKey($entityId);
                if(sizeof($photoList)>0) {
                    $album = $albumService->findAlbumById($photoList[0]->albumId);
                    if(isset($album)) {
                        $albumUrl = OW::getRouter()->urlForRoute('photo_user_album', array(
                            'user' => BOL_UserService::getInstance()->getUserName($album->userId),
                            'album' => $album->id
                        ));

                        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('string' => $albumUrl)));
                        if(isset($stringRenderer->getData()['string'])){
                            $albumUrl = $stringRenderer->getData()['string'];
                        }
                        $string['vars']['albumUrl'] = $albumUrl;
                        $string['vars']['album'] = $album->name;
                        $contentImage = $albumService->getAlbumCover($album->id);
                        $ownerId = $album->userId;
                    }
                    else{
                        return;
                    }
                }else{
                    return;
                }
            }else{
                $contentImage =$photoService->getPhotoUrlByPhotoInfo($entityId, PHOTO_BOL_PhotoService::TYPE_SMALL, array(), true);
                $string = array(
                    'key' => 'iisphotoplus+photo_liked_notification',
                    'vars' => array(
                        'user' => $displayName,
                        'userUrl' => $userUrl
                    )
                );
                $ownerId = $photoService->findPhotoOwner($entityId);
            }




            if ( $ownerId != $userId ){
                $params = array(
                    'pluginKey' => 'photo',
                    'entityType' => 'photo_like',
                    'entityId' => $entityId,
                    'action' => 'photo-add_like',
                    'userId' => $ownerId,
                    'time' => time()
                );

                $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));

                $data = array(
                    'avatar' => $avatars[$userId],
                    'string' => $string,
                    'url' => $url,
                    'contentImage' => $contentImage
                );

                $event = new OW_Event('notifications.add', $params, $data);
                OW::getEventManager()->trigger($event);
            }

        }
    }

    public function onMediaPanelInitMenu(OW_Event $event){
        $params = $event->getParams();
        $menu = $params['menu'];
        $id = $params['id'];
        $pluginKey = $params['pluginKey'];

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iisphotoplus')->getStaticJsUrl() . 'iisphotoplus.js');
        $language = OW::getLanguage();
        $router = OW::getRouter();

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('iisphotoplus', 'photo_gallery'));
        $item->setOrder(1);
        $item->setKey('photos');
        $item->setUrl($router->urlFor('IISPHOTOPLUS_CTRL_PhotoPanel','index',array('id'=>$id,'pluginKey'=>$pluginKey)));
        $menu->addElement($item);
    }

}