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
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>, Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow.plugin.photo.mobile.controllers
 * @since 1.6.0
 */
class PHOTO_MCTRL_Photo extends OW_MobileActionController
{
    /**
     * @var PHOTO_BOL_PhotoService
     */
    private $photoService;

    /**
     * @var PHOTO_BOL_PhotoAlbumService
     */
    private $photoAlbumService;

    public function __construct()
    {
        parent::__construct();

        $this->photoService = PHOTO_BOL_PhotoService::getInstance();
        $this->photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();
    }

    public function viewList($params)
    {
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');

        if ( !$modPermissions && !OW::getUser()->isAuthorized('photo', 'view') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'view');
            $this->assign('authError', $status['msg']);

            return;
        }

        $type = !empty($params['listType']) ? $params['listType'] : 'latest' ;
        $limit = 12;
        
        $validLists = array('latest','toprated','featured');
        $resultsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::GET_VALID_LIST_FOR_PHOTO, array('validLists' =>$validLists)));
        if(isset($resultsEvent->getData()['validLists'])){
            $validLists= $resultsEvent->getData()['validLists'];
        }
        if ( !in_array($type, $validLists) )
        {
            $this->redirect(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'latest')));
        }
        
        $menu = $this->getMenu();
        $el = $menu->getElement($type);
        
        $el->setActive(true);
        $this->addComponent('menu', $menu);

        $initialCmp = new PHOTO_MCMP_PhotoList($type, $limit, array());
        $this->addComponent('photos', $initialCmp);

        $checkPrivacy = !OW::getUser()->isAuthorized('photo');
        $total = $this->photoService->countPhotos($type, $checkPrivacy);
        $resultsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::GET_RESULT_FOR_LIST_ITEM_PHOTO, array('listtype' =>$type,'page'=>1,PHOTO_BOL_PhotoService::TYPE_PREVIEW,'onlyCount'=>true)));
        if(isset($resultsEvent->getData()['count'])){
            $total= $resultsEvent->getData()['count'];
        }
        $this->assign('loadMore', $total > $limit);

        $this->assign('isUserAuthenticated', OW::getUser()->isAuthenticated());

        if ( OW::getUser()->isAuthenticated() && !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $id = IISSecurityProvider::generateUniqueId('photo_add');
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');

            OW::getDocument()->addScriptDeclaration(UTIL_JsGenerator::composeJsString(
                ';$("#" + {$btn}).on("click", function()
                {
                    OWM.authorizationLimitedFloatbox({$msg});
                });',
                array(
                    'btn' => $id,
                    'msg' => $status['msg']
                )
            ));

            $this->assign('id', $id);
        }
        else
        {
            $this->assign('uploadUrl', OW::getRouter()->urlForRoute('photo_upload'));
        }

        $script = '
        self.loadMore = true;
        $(document).bind(\'scroll\', function() {
            var diff = $(document).height() - ($(window).scrollTop() + $(window).height());
            if ( diff < 100 && self.loadMore )
            {
                loadMorePhotos();
                self.loadMore = false;
            }
        });
        
        OWM.bind("photo.hide_load_more", function(){
            $("#btn-photo-load-more").hide();
        });

        function loadMorePhotos() {
            var exclude = $("div.owm_photo_list_item").map(function () {
                return $(this).data("ref");
            }).get();
            OWM.loadComponent(
                "PHOTO_MCMP_PhotoList",
                {type: "' . $type . '", count: ' . $limit . ', exclude: exclude},
                {
                    onReady: function (html) {
                        $("#photo-list-cont").append(html);
                        $("#notifications-list").append(html);
                        if (html[0].childElementCount === 0){
                            $("#btn-photo-load-more").hide();
                        }
                        else{
                            self.loadMore = true;
                        }
                    }
                }
            );
        }';

        OW::getDocument()->addOnloadScript($script);

        OW::getDocument()->setHeading(OW::getLanguage()->text('photo', 'page_title_browse_photos'));
        OW::getDocument()->setTitle(OW::getLanguage()->text('photo', 'meta_title_photo_'.$type));
        OW::getDocument()->setDescription(OW::getLanguage()->text('photo', 'meta_description_photo_'.$type));
    }

    public function albums( array $params )
    {
        if ( empty($params['user']) || !mb_strlen($username = trim($params['user'])) )
        {
            throw new Redirect404Exception();
        }

        $user = BOL_UserService::getInstance()->findByUsername($username);
        if ( !$user )
        {
            throw new Redirect404Exception();
        }

        $ownerMode = $user->id == OW::getUser()->getId();

        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');

        if ( !OW::getUser()->isAuthorized('photo', 'view') && !$modPermissions && !$ownerMode )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'view');
            $this->assign('authError', $status['msg']);

            return;
        }

        $lang = OW::getLanguage();
        $limit = 6;

        $initialCmp = new PHOTO_MCMP_AlbumList($user->id, $limit, array());
        $this->addComponent('albums',$initialCmp);

        $total = $this->photoAlbumService->countUserAlbums($user->id);
        $this->assign('loadMore', $total > $limit);
        $this->assign('backUrl', OW::getRouter()->urlForRoute('base_user_profile', array('username' => $user->username)));
        if ( OW::getUser()->isAuthenticated() && !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $id = IISSecurityProvider::generateUniqueId('photo_add');
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');

            OW::getDocument()->addScriptDeclaration(UTIL_JsGenerator::composeJsString(
                ';$("#" + {$btn}).on("click", function()
                {
                    OWM.authorizationLimitedFloatbox({$msg});
                });',
                array(
                    'btn' => $id,
                    'msg' => $status['msg']
                )
            ));

            $this->assign('id', $id);
        }
        else
        {
            $this->assign('uploadUrl', OW::getRouter()->urlForRoute('photo_upload'));
        }

        $script = '
        OWM.bind("photo.hide_load_more", function(){
            $("#btn-photo-load-more").hide();
        });
        
        if ($("#btn-photo-load-more").length){
            var elementTop = $("#btn-photo-load-more").offset().top;
            var viewportBottom = $(window).scrollTop() + $(window).height();
            var load_more_visible =  elementTop < viewportBottom;
            if (load_more_visible)     
                loadMoreAlbums();
        }
         
        self.loadMore = true;
        $(document).bind(\'scroll\', function() {
            var diff = $(document).height() - ($(window).scrollTop() + $(window).height());
            if ( diff < 100 && self.loadMore )
            {
                loadMoreAlbums();
                self.loadMore = false;
            }
        });
        
        function loadMoreAlbums(){
            var exclude = $("li.owm_photo_album_list_item").map(function(){ return $(this).data("ref"); }).get();
            OWM.loadComponent(
                "PHOTO_MCMP_AlbumList",
                {userId: "' . $user->id . '", count:' . $limit . ', exclude: exclude},
                {
                    onReady: function(html){
                        $("#album-list-cont").append(html);
                        if (html[0].childElementCount === 0){
                            $("#btn-photo-load-more").hide();
                        }
                        else{
                            self.loadMore = true;
                        }                    
                    }
                }
            );
        }';

        OW::getDocument()->addOnloadScript($script);

        $displayName = BOL_UserService::getInstance()->getDisplayName($user->id);
        OW::getDocument()->setHeading($lang->text('photo', 'page_title_user_albums', array('user' => $displayName)));
        OW::getDocument()->setTitle($lang->text('photo', 'meta_title_photo_useralbums', array('displayName' => $displayName)));

        OW::getEventManager()->trigger(new OW_Event('iiswidgetplus.general.before.view.render', array('targetPage' => 'userProfile', 'username' => $username)));
    }

    public function album( array $params )
    {
        $albumId=(int) $params['album'];
        $album = $this->photoAlbumService->findAlbumById($albumId);
        $isOwner = false;
        $ownerId = $album->userId;
        $userId = OW::getUser()->getId();
        
        if ( $ownerId == $userId )
        {
            $isOwner=true;
        }

        $this->assign('isOwner', $isOwner);
        
        if ( !isset($params['user']) || !strlen($username = trim($params['user'])) )
        {
            throw new Redirect404Exception();
        }

        if ( !isset($params['album']) || !($albumId = (int) $params['album']) )
        {
            throw new Redirect404Exception();
        }

        // is owner
        $userDto = BOL_UserService::getInstance()->findByUsername($username);

        if ( $userDto )
        {
            $ownerMode = $userDto->id == OW::getUser()->getId();
        }
        else
        {
            $ownerMode = false;
        }

        $lang = OW::getLanguage();

        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');

        if ( !OW::getUser()->isAuthorized('photo', 'view') && !$modPermissions && !$ownerMode )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'view');
            $this->assign('authError', $status['msg']);

            return;
        }

        $album = $this->photoAlbumService->findAlbumById($albumId);
        if ( !$album )
        {
            throw new Redirect404Exception();
        }

        // permissions check
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'photo_view_album', 'ownerId' => $album->userId, 'viewerId' => OW::getUser()->getId());
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);
        }
        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_INIT, array('albumId' => $album->getId(), 'action' => 'check_album_privacy')));
        $type = 'user';
        $limit = 12;

        $initialCmp = new PHOTO_MCMP_PhotoList($type, $limit, array(), $albumId);
        $this->addComponent('photos', $initialCmp);

        $total = $this->photoAlbumService->countAlbumPhotos($albumId);
        $this->assign('loadMore', $total > $limit);
        $this->assign('backUrl', OW::getRouter()->urlForRoute('photo_user_albums', array('user' => $username)));
        if ( OW::getUser()->isAuthenticated() && !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $id = IISSecurityProvider::generateUniqueId('photo_add');
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');

            OW::getDocument()->addScriptDeclaration(UTIL_JsGenerator::composeJsString(
                ';$("#" + {$btn}).on("click", function()
                {
                    OWM.authorizationLimitedFloatbox({$msg});
                });',
                array(
                    'btn' => $id,
                    'msg' => $status['msg']
                )
            ));

            $this->assign('id', $id);
        }
        elseif ($isOwner)
        {
            $this->assign('uploadUrl', OW::getRouter()->urlForRoute('photo_upload_album', array('album' => $albumId)));
        }

        $script = '
        OWM.bind("photo.hide_load_more", function(){
            $("#btn-photo-load-more").hide();
        });

        self.loadMore = true;
        $(document).bind(\'scroll\', function() {
            var diff = $(document).height() - ($(window).scrollTop() + $(window).height());
            if ( diff < 100 && self.loadMore )
            {
                loadMoreAlbumPhotos();
                self.loadMore = false;
            }
        });
        
        function loadMoreAlbumPhotos(){
            var exclude = $("div.owm_photo_list_item").map(function(){ return $(this).data("ref"); }).get();
            OWM.loadComponent(
                "PHOTO_MCMP_PhotoList",
                {type: "' . $type . '", count:' . $limit . ', exclude: exclude, albumId: ' . $albumId . '},
                {
                    onReady: function(html){
                        $("#photo-list-cont").append(html);
                        $("#notifications-list").append(html);
                        if (html[0].childElementCount === 0){
                            $("#btn-photo-load-more").hide();
                        }
                        else{
                            self.loadMore = true;
                        }
                    }
                }
            );
        }';

        OW::getDocument()->addOnloadScript($script);

        $displayName = BOL_UserService::getInstance()->getDisplayName($album->userId);
        OW::getDocument()->setHeading(
            $album->name . ' - ' . $lang->text('photo', 'photos_in_album', array('total' => $total))
        );
        OW::getDocument()->setTitle(
            $lang->text('photo', 'meta_title_photo_useralbum', array('displayName' => $displayName, 'albumName' => $album->name))
        );
        OW::getDocument()->setDescription(
            $lang->text('photo', 'meta_description_photo_useralbum', array('displayName' => $displayName, 'number' => $total))
        );
        $this->assign('photoListUrl',OW::getRouter()->urlForRoute('photo_list_index'));
    }

    public function view( array $params )
    {
        if ( !isset($params['id']) || !($photoId = (int) $params['id']) )
        {
            throw new Redirect404Exception();
        }

        $lang = OW::getLanguage();

        $photo = $this->photoService->findPhotoById($photoId);
        if ( !$photo )
        {
            throw new Redirect404Exception();
        }
        $this->assign('backUrl', (OW::getRouter()->urlForRoute('photo_list_index')));
        $album = $this->photoAlbumService->findAlbumById($photo->albumId);
        $this->assign('album', $album);

        $plugin = BOL_PluginService::getInstance()->findPluginByKey("iismenu");
        if (isset($plugin) && $plugin->isActive())
            $this->assign("iismenu_active", true);

        $ownerName = BOL_UserService::getInstance()->getUserName($album->userId);
        $albumUrl = OW::getRouter()->urlForRoute('photo_user_album', array('album' => $album->id, 'user' => $ownerName));
        $this->assign('albumUrl', $albumUrl);

        // is owner
        $contentOwner = $this->photoService->findPhotoOwner($photo->id);
        $userId = OW::getUser()->getId();
        $ownerMode = $contentOwner == $userId;
        $this->assign('ownerMode', $ownerMode);

        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');
        $this->assign('moderatorMode', $modPermissions);

        if ( !$ownerMode && !$modPermissions && !OW::getUser()->isAuthorized('photo', 'view') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'view');
            $this->assign('authError', $status['msg']);

            return;
        }

        $pluginPhoto = OW::getPluginManager()->getPlugin('photo');
        $this->assign('url', $this->photoService->getPhotoUrl($photo->id, false, $photo->hash));
        OW::getDocument()->addStyleSheet($pluginPhoto->getStaticCssUrl() . 'imageviewer.css');
        OW::getDocument()->addScript($pluginPhoto->getStaticJsUrl() . 'imageviewer.js');

        // permissions check
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'photo_view_album', 'ownerId' => $contentOwner, 'viewerId' => $userId);
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);

            OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_INIT, array('ownerId' => $contentOwner, 'photoId' => $photo->id)));
        }
        $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $photo->description)));
        if(isset($stringRenderer->getData()['string'])){
            $photo->description = ($stringRenderer->getData()['string']);
        }
        $photo->description = UTIL_HtmlTag::autoLink($photo->description);
        $this->assign('photo', $photo);
        if ($contentOwner == $userId)
        {
            $delete = array(
                'label' => OW_Language::getInstance()->text('base', 'delete'),
                'entityId' =>$photo->id,
                'onclick' => 'if ( !confirm(\''.OW::getLanguage()->text('admin', 'confirm_delete').'\') ){ return false;}else{window.location=\''.OW::getRouter()->urlForRoute('delete_photo',array('entityId' => $photo->id)).'\'}'
            );
            $this->assign('delete', $delete);
        }
        $fullsizeUrl = (int) OW::getConfig()->getValue('photo', 'store_fullsize') && $photo->hasFullsize
            ? $this->photoService->getPhotoFullsizeUrl($photo->id, $photo->hash)
            : null;
        $this->assign('fullsizeUrl', $fullsizeUrl);

        $this->assign('nextPhoto', $this->photoService->getNextPhotoId($photo->albumId, $photo->id));
        $this->assign('previousPhoto', $this->photoService->getPreviousPhotoId($photo->albumId, $photo->id));

        $photoCount = $this->photoAlbumService->countAlbumPhotos($photo->albumId);
        $this->assign('photoCount', $photoCount);

        $photoIndex = $this->photoService->getPhotoIndex($photo->albumId, $photo->id);
        $this->assign('photoIndex', $photoIndex);

        $avatar = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($contentOwner), true, true, true, false);
        $this->assign('avatar', $avatar[$contentOwner]);

        $cmtParams = new BASE_CommentsParams('photo', 'photo_comments');
        $cmtParams->setEntityId($photo->id);
        $cmtParams->setOwnerId($contentOwner);
        $cmtParams->setDisplayType(BASE_CommentsParams::DISPLAY_TYPE_BOTTOM_FORM_WITH_FULL_LIST);

        $photoCmts = new BASE_MCMP_Comments($cmtParams);
        $this->addComponent('comments', $photoCmts);

        OW::getDocument()->setHeading($album->name);

        $document = OW::getDocument();
        $document->addScript(OW::getPluginManager()->getPlugin('photo')->getStaticJsUrl() . 'utils.js');

        $service = BOL_RateService::getInstance();
        $rateInfo = new PHOTO_MCMP_Rate('photo', 'photo_rates', $photo->getId(), $contentOwner);

        $this->addComponent('rate', $rateInfo);

        $description = strip_tags($photo->description);
        $description = mb_strlen($description) ? $description : $photo->id;
        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_RENDER, array('this' => $this, 'privacy' => $photo->privacy, 'objectId' => $album->id, 'userId' => $album->userId)));
    }

    private function getMenu()
    {
        $menuItems = array();
        $lang = OW::getLanguage();

        $item = new BASE_MenuItem();
        $item->setLabel($lang->text('photo', 'menu_latest'));
        $item->setUrl(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'latest')));
        $item->setKey('latest');
        $item->setOrder(0);
        array_push($menuItems, $item);
        
        if ( PHOTO_BOL_PhotoService::getInstance()->countPhotos('featured') )
        {
            $item = new BASE_MenuItem();
            $item->setLabel($lang->text('photo', 'menu_featured'));
            $item->setUrl(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'featured')));
            $item->setKey('featured');
            $item->setOrder(1);
            array_push($menuItems, $item);
        }
        
        $item = new BASE_MenuItem();
        $item->setLabel($lang->text('photo', 'menu_toprated'));
        $item->setUrl(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'toprated')));
        $item->setKey('toprated');
        $item->setOrder(2);
//        array_push($menuItems, $item);

        $validListsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ADD_LIST_TYPE_TO_PHOTO,array('menuItems' => $menuItems)));
        if(isset($validListsEvent->getData()['menuItems'])){
            $menuItems = $validListsEvent->getData()['menuItems'];
        }
        return new BASE_MCMP_ContentMenu($menuItems);
    }
    public function deletePhoto( array $params )
    {
        $return = array();

        if ( !empty($params['entityId']) && ($photo = $this->photoService->findPhotoById($params['entityId'])) !== NULL )
        {
            $ownerId = $this->photoService->findPhotoOwner($photo->id);
            $isOwner = $ownerId == OW::getUser()->getId();
            $isModerator = OW::getUser()->isAuthorized('photo');

            if ( !$isOwner && !$isModerator )
            {
                throw new Redirect404Exception();
            }

            if ( $this->photoService->deletePhoto($photo->id) )
            {
                OW::getEventManager()->call('notifications.remove', array(
                    'entityType' => 'photo-add_rate',
                    'entityId' => $photo->id
                ));
                $cover = PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->findByAlbumId($photo->albumId);

                if ( $cover === NULL || (int)$cover->auto )
                {
                    PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->deleteCoverByAlbumId($photo->albumId);

                    $this->photoService->createAlbumCover($photo->albumId, array_reverse(PHOTO_BOL_PhotoDao::getInstance()->getAlbumAllPhotos($photo->albumId)));
                }

                OW::getFeedback()->info(OW::getLanguage()->text('photo', 'photo_deleted'));
            }
            else
            {
                OW::getFeedback()->error(OW::getLanguage()->text('photo', 'photo_not_deleted'));
            }
            $this->redirect( OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'latest')) );
        }

        return $return;
    }
}

