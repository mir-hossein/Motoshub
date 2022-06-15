<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 * 
 *
 * @author Mohammad Aghaabbasloo
 * @package ow_plugins.iisphotoplus
 * @since 1.0
 */
class IISPHOTOPLUS_BOL_Service
{
    private static $PHOTO_FRIENDS = 'photo_friends';

    private static $classInstance;
    public $isPhotoTabActive=false;
    private $statusPhotoDao;
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @return mixed
     */
    public function getIsPhotoTabActive()
    {
        return $this->isPhotoTabActive;
    }

    /**
     * @param mixed $isPhotoTabActive
     */
    public function setIsPhotoTabActive($isPhotoTabActive)
    {
        $this->isPhotoTabActive = $isPhotoTabActive;
    }


    private function __construct()
    {
        $this->statusPhotoDao = IISPHOTOPLUS_BOL_StatusPhotoDao::getInstance();
    }

    public function setTtileHeaderListItemPHOTO( OW_Event $event )
    {
        $params = $event->getParams();
        if (isset($params['listType']) && $params['listType'] == IISPHOTOPLUS_BOL_Service::$PHOTO_FRIENDS) {
            OW::getDocument()->setTitle(OW::getLanguage()->text('iisphotoplus', 'meta_title_photo_add_friends'));
            OW::getDocument()->setDescription(OW::getLanguage()->text('iisphotoplus', 'meta_description_photo_friends'));
        }
    }
    public function getValidListForPhoto( OW_Event $event )
    {
        $params = $event->getParams();
        if(isset($params['validLists'])){
            $validLists = $params['validLists'];
            if(OW::getUser()->isAuthenticated()) {
                $validLists[] = IISPHOTOPLUS_BOL_Service::$PHOTO_FRIENDS;
            }
            $event->setData(array('validLists' => $validLists));
        }
    }

    public function addListTypeToPhoto( OW_Event $event )
    {
        $params = $event->getParams();
        if(isset($params['menu']) && OW::getUser()->isAuthenticated()){
            $menu = $params['menu'];
            if(isset($menu->sortItems[IISPHOTOPLUS_BOL_Service::$PHOTO_FRIENDS]) && isset($menu->sortItems[IISPHOTOPLUS_BOL_Service::$PHOTO_FRIENDS]['isActive']) && $menu->sortItems[IISPHOTOPLUS_BOL_Service::$PHOTO_FRIENDS]['isActive']==true) {
                $this->setIsPhotoTabActive(true);
                $sortItems = array();
                $menu->setSortItems($sortItems);
                $event->setData(array('menu' => $menu));
            }
        }
        if(isset($params['validLists'])){
            $validLists = $params['validLists'];
            if(OW::getUser()->isAuthenticated()) {
                $validLists[] = IISPHOTOPLUS_BOL_Service::$PHOTO_FRIENDS;
            }
            $event->setData(array('validLists' => $validLists));
        }
        if(isset($params['menuItems']) && OW::getUser()->isAuthenticated() && isset($params['isCmp']) && $params['isCmp']==true
        && isset($params['uniqId'])){
            $menuItems = $params['menuItems'];
            $menuItems['photo_friends'] = array(
                    'label' => OW::getLanguage()->text('iisphotoplus', IISPHOTOPLUS_BOL_Service::$PHOTO_FRIENDS),
                    'id' => 'photo-cmp-menu-photo_friends-'.$params['uniqId'],
                    'contId' => 'photo-cmp-photo_friends-'.$params['uniqId'],
                    'active' => false,
                    'visibility' => true
                );
            $event->setData(array('menuItems' => $menuItems));
        }
        else if(isset($params['menuItems']) && OW::getUser()->isAuthenticated()){
            $menuItems = $params['menuItems'];
            if($this->isPhotoTabActive==true){
                foreach($menuItems as $item){
                    $item->setActive(false);
                }
            }
            $item = new BASE_MenuItem();
            $item->setLabel(OW::getLanguage()->text('iisphotoplus', IISPHOTOPLUS_BOL_Service::$PHOTO_FRIENDS));
            $item->setUrl(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => IISPHOTOPLUS_BOL_Service::$PHOTO_FRIENDS)));
            $item->setKey(IISPHOTOPLUS_BOL_Service::$PHOTO_FRIENDS);
            $item->setIconClass('ow_ic_friends_photos');
            $item->setOrder(sizeof($params['menuItems']));
            $item->setActive($this->isPhotoTabActive) ;
            array_push($menuItems, $item);
            $event->setData(array('menuItems' => $menuItems));
        }
    }

    public function getResultForListItemPhoto( OW_Event $event )
    {
        $params = $event->getParams();
        if(isset($params['listtype']) &&
            $params['listtype'] == IISPHOTOPLUS_BOL_Service::$PHOTO_FRIENDS){
            $exclude = array();
            if(isset($params['exclude'])){
                $exclude=$params['exclude'];
            }
            $friendsOfCurrentUser = array();
            if(OW::getUser()->isAuthenticated()){
                $friendsOfCurrentUser = OW::getEventManager()->call('plugin.friends.get_friend_list', array('userId' => OW::getUser()->getId()));
            }
            if(!empty($friendsOfCurrentUser)) {
                if(isset($params['onlyCount'])){
                    $count = PHOTO_BOL_PhotoService::getInstance()->findPhotoListByUserIdListCount($friendsOfCurrentUser,$exclude);
                    $event->setData(array('count' => $count));
                }else{
                    $count = PHOTO_BOL_PhotoService::getInstance()->findPhotoListByUserIdListCount($friendsOfCurrentUser,$exclude);
                    $photos = PHOTO_BOL_PhotoService::getInstance()->findPhotoListByUserIdList($friendsOfCurrentUser, $params['page'], $params['photosPerPage'],$exclude);
                    $event->setData(array('count' => $count,'result' => $photos));
                }
            }
        }
    }

    public function addStatusPhoto($photoId, $userId){
        $this->statusPhotoDao->addStatusPhoto($photoId,$userId);
    }

    public function getStatusPhotosByUserId($userId){
        return $this->statusPhotoDao->getStatusPhotosByUserId($userId);
    }
    public function deleteStatusPhotosByUserId($userId){
        $this->statusPhotoDao->deleteStatusPhotosByUserId($userId);
    }

    public function appendPhotosToFeed(OW_Event $event)
    {
        $data = $event->getData();
        $params = $event->getParams();
        if (isset($params["data"]["album-name"]) &&isset($params["data"]["photo-userId"]) )
        {
            $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION, array('check' => true)));
            if (isset($mobileEvent->getData()['isMobileVersion']) && $mobileEvent->getData()['isMobileVersion'] == true) {
                // MOBILE VIEW
                $dataPhotoHtml = '';
                if (sizeof($params["data"]["photoIdList"]) > 0) {
                    foreach ($params["data"]["photoIdList"] as $photoId) {
                        $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);
                        if (!isset($photo)) {
                            continue;
                        }
                        $imageUrl = PHOTO_BOL_PhotoService::getInstance()->getPhotoUrl($photo->getId(), true, $photo->hash);
                        $imageLink = OW::getRouter()->urlForRoute('view_photo', array('id' => $photo->getId()));
                        $photoString = '
                    <div class="owm_newsfeed_imglist_item">
                        <a href="' . $imageLink . '" data-image="' . $imageUrl . '"  style="background-image: url(' . $imageUrl . ')">
                            <img width="100%" alt="" src="data:image/png;base64,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"/>
                        </a>
                    </div>';
                        $dataPhotoHtml = $dataPhotoHtml . $photoString;
                    }

                    $dataPhotoHtmlParent = '
<div class="owm_newsfeed_body_cont">
    <div class="owm_newsfeed_imglist_scroller owm_newsfeed_imglist_scroller_'.sizeof($params["data"]["photoIdList"]).'">
        <div class="owm_newsfeed_imglist_wrap">
            <div class="owm_newsfeed_imglist clearfix">
                <div class="owm_newsfeed_imglist_section clearfix">
                    ' . $dataPhotoHtml . '
                </div>
                <div class="owm_newsfeed_imglist_section clearfix">
                </div>
            </div>
        </div>
    </div>
</div>';

                    $data["content"] = $data["content"] . $dataPhotoHtmlParent;
                    $event->setData($data);
                }
            }
            else
            {
                //DESKTOP VIEW
                $dataPhotoHtml = '';
                if (sizeof($params["data"]["photoIdList"]) > 1) {
                    foreach ($params["data"]["photoIdList"] as $photoId) {
                        $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);
                        if (!isset($photo)) {
                            continue;
                        }
                        $imageUrl = PHOTO_BOL_PhotoService::getInstance()->getPhotoUrl($photo->getId(), true, $photo->hash);
                        $imageLink = OW::getRouter()->urlForRoute('view_photo', array('id' => $photo->getId()));
                        $photoString = '<div class="clearfix ow_newsfeed_photo_grid_item">
                                    <a href="' . $imageLink . '" data-image="' . $imageUrl . '"  style="background-image: url(' . $imageUrl . ')">
                                       <img width="100%" alt="" src="data:image/png;base64,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"/>
                                    </a>
                                </div>';
                        $dataPhotoHtml = $dataPhotoHtml . $photoString;
                    }
                }
                else if (sizeof($params["data"]["photoIdList"]) == 1) {
                    $photoId = $params["data"]["photoIdList"][0];
                    $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);
                    if (!isset($photo)) {
                        return;
                    }
                    $imageUrl = PHOTO_BOL_PhotoService::getInstance()->getPhotoUrl($photo->getId(), true, $photo->hash);
                    $imageLink = OW::getRouter()->urlForRoute('view_photo', array('id' => $photo->getId()));
                    $photoString = '<div class="ow_newsfeed_large_image clearfix"><div class="ow_newsfeed_item_picture">
                                    <a href="' . $imageLink . '">
                                       <img width="100%" alt="" src="'.$imageUrl.'"/>
                                    </a>
                                </div></div>';
                    $dataPhotoHtml = $dataPhotoHtml . $photoString;
                }

                $dataPhotoHtmlParent = '';
                if (sizeof($params["data"]["photoIdList"]) > 1) {
                    $dataPhotoHtmlParent = '<div class="ow_newsfeed_content ow_smallmargin"><div class="ow_newsfeed_photo_grid clearfix">' . $dataPhotoHtml . '</div></div>';
                } else if (sizeof($params["data"]["photoIdList"]) == 1) {
                    $dataPhotoHtmlParent = '<div class="ow_newsfeed_content ow_smallmargin">' . $dataPhotoHtml . '</div>';
                }

                $data["photoHTML"] = $dataPhotoHtmlParent;
                $event->setData($data);
            }
        }
    }
    public function removeNotification(OW_Event $event)
    {
        $params = $event->getParams();
        if(!isset($params['entityType']) || !isset($params['entityId']) || $params['entityType']!='multiple_photo_upload')
        {
            return;
        }
        OW::getEventManager()->call('notifications.remove', array(
            'entityType' => 'photo_like',
            'entityId' => $params['entityId']
        ));
    }

}
