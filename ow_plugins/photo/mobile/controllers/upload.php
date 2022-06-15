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
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.mobile.controllers
 * @since 1.6.0
 */
class PHOTO_MCTRL_Upload extends OW_MobileActionController
{
    /**
     * @var PHOTO_BOL_PhotoService
     */
    private $photoService;

    public function __construct()
    {
        parent::__construct();

        $this->photoService = PHOTO_BOL_PhotoService::getInstance();
    }

    public function photo( array $params = null )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $this->assign('backUrl', (OW::getRouter()->urlForRoute('photo_list_index')));
        $language = OW::getLanguage();

        if ( !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');
            $this->assign('auth_msg', $status['msg']);

            return;
        }

        $config = OW::getConfig();
        $userQuota = (int) $config->getValue('photo', 'user_quota');
        $userId = OW::getUser()->getId();

        if ( !($this->photoService->countUserPhotos($userId) <= $userQuota) )
        {
            $this->assign('auth_msg', $language->text('photo', 'quota_exceeded', array('limit' => $userQuota)));
        }
        else
        {
            $accepted = floatval($config->getValue('photo', 'accepted_filesize') * 1024 * 1024);
            $this->assign('auth_msg', null);

            $form = new PHOTO_MCLASS_UploadForm();
            $this->addForm($form);

            $photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();
            $albums = $photoAlbumService->findUserAlbumList($userId, 1, 100);
            $this->assign('albums', $albums);

            if ( !empty($params['album']) && (int) $params['album'] )
            {
                $albumId = (int) $params['album'];
                $uploadToAlbum = $photoAlbumService->findAlbumById($albumId);
                if ( !$uploadToAlbum || $uploadToAlbum->userId != $userId )
                {
                    $this->redirect(OW::getRouter()->urlForRoute('photo_upload'));
                }
                $form->getElement('album')->setValue($uploadToAlbum->name);
            }

            if ( $albums )
            {
                $script =
                '$("#album_select").change(function(event){
                    $("#album_input").val($(this).val());
                });';
                OW::getDocument()->addOnloadScript($script);
            }

            $script = '$("#upload-file-field").change(function(){
                var img = $("#photo-file-prevew");
                var name = $(".owm_upload_img_name_label span");

                img.hide();
                name.text("");

                if (!this.files || !this.files[0]) return;

                if ( window.FileReader ) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        img.show().attr("src", e.target.result);
                    }
                    reader.readAsDataURL(this.files[0]);
                } else {
                    name.text(this.files[0].name);
                }
                $(".owm_upload_photo_browse_wrap").addClass("owm_upload_photo_attach_wrap");
            });';
            OW::getDocument()->addOnloadScript($script);

            if ( OW::getRequest()->isPost() )
            {
                $form->isValid($_POST);
                $values = $form->getValues();

                // Delete old temporary photos
                $tmpPhotoService = PHOTO_BOL_PhotoTemporaryService::getInstance();
                $photoService = PHOTO_BOL_PhotoService::getInstance();

                $file = $_FILES['photo'];
                $tmpPhotoService->deleteUserTemporaryPhotos($userId);
                if ( strlen($file['tmp_name']) )
                {
                    if ( !UTIL_File::validateImage($file['name']) || $file['size'] > $accepted )
                    {
                        OW::getFeedback()->warning($language->text('photo', 'no_photo_uploaded'));
                        $this->redirect();
                    }

                    $tmpPhotoService->addTemporaryPhoto($file['tmp_name'], $userId, 1);
                    $tmpList = $tmpPhotoService->findUserTemporaryPhotos($userId, 'order');
                    $tmpList = array_reverse($tmpList);

                    // check album exists
                    if ( !($album = $photoAlbumService->findAlbumByName(UTIL_HtmlTag::stripTagsAndJs(trim($values['album'])), $userId)) )
                    {
                        $album = new PHOTO_BOL_PhotoAlbum();
                        $album->name = UTIL_HtmlTag::stripTagsAndJs(trim($values['album']));
                        $album->userId = $userId;
                        $album->createDatetime = time();

                        $photoAlbumService->addAlbum($album);
                    }

                    foreach ( $tmpList as $tmpPhoto )
                    {
                        $photo = $tmpPhotoService->moveTemporaryPhoto($tmpPhoto['dto']->id, $album->id, $values['description']);

                        if ( $photo )
                        {
                            BOL_AuthorizationService::getInstance()->trackAction('photo', 'upload');

                            $photoService->createAlbumCover($album->id, array($photo));
                            $photoService->triggerNewsfeedEventOnSinglePhotoAdd($album, $photo);

                            $photoParams = array('addTimestamp' => $photo->addDatetime, 'photoId' => $photo->id, 'hash' => $photo->hash, 'description' => $photo->description);
                            $event = new OW_Event(PHOTO_CLASS_EventHandler::EVENT_ON_PHOTO_ADD, array($photoParams));
                            OW::getEventManager()->trigger($event);

                            $photo = $this->photoService->findPhotoById($photo->id);

                            if ( $photo->status != PHOTO_BOL_PhotoDao::STATUS_APPROVED )
                            {
                                OW::getFeedback()->info(OW::getLanguage()->text('photo', 'photo_uploaded_pending_approval'));

                                if ( PHOTO_BOL_PhotoAlbumService::getInstance()->countAlbumPhotos($photo->albumId) )
                                {
                                    $this->redirect(OW::getRouter()->urlForRoute('photo_user_album', array(
                                        'user' => BOL_UserService::getInstance()->getUserName($userId),
                                        'album' => $album->id
                                    )));
                                }
                                else
                                {
                                    $this->redirect(OW::getRouter()->urlForRoute('photo_user_albums', array(
                                        'user' => BOL_UserService::getInstance()->getUserName($userId)
                                    )));
                                }
                            }
                            else
                            {
                                OW::getFeedback()->info($language->text('photo', 'photos_uploaded', array('count' => 1)));
                                $this->redirect(OW::getRouter()->urlForRoute('view_photo', array('id' => $photo->id)));
                            }
                        }
                    }
                }
                else
                {
                    OW::getFeedback()->warning($language->text('photo', 'no_photo_uploaded'));
                    $this->redirect();
                }
            }
        }

        OW::getDocument()->setHeading($language->text('photo', 'upload_photos'));
        OW::getDocument()->setTitle($language->text('photo', 'meta_title_photo_upload'));
        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_RENDER));
    }
    public function photos( array $params = null )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $this->assign('backUrl', (OW::getRouter()->urlForRoute('photo_list_index')));
        $language = OW::getLanguage();
        OW::getDocument()->setHeading($language->text('photo', 'upload_photos'));
        OW::getDocument()->setTitle($language->text('photo', 'meta_title_photo_upload'));

        if ( !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');
            $this->assign('auth_msg', $status['msg']);

            return;
        }

        $config = OW::getConfig();
        $userQuota = (int) $config->getValue('photo', 'user_quota');
        $userId = OW::getUser()->getId();

        if ( !($this->photoService->countUserPhotos($userId) <= $userQuota) )
        {
            $this->assign('auth_msg', $language->text('photo', 'quota_exceeded', array('limit' => $userQuota)));
        }
        else
        {
            $this->assign('auth_msg', null);

            $form = new PHOTO_MCLASS_MultipleUploadForm();
            $this->addForm($form);

            $photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();
            $albums = $photoAlbumService->findUserAlbumList($userId, 1, 100);
            $this->assign('albums', $albums);

            if ( !empty($params['album']) && (int) $params['album'] )
            {
                $albumId = (int) $params['album'];
                $uploadToAlbum = $photoAlbumService->findAlbumById($albumId);
                if ( !$uploadToAlbum || $uploadToAlbum->userId != $userId )
                {
                    $this->redirect(OW::getRouter()->urlForRoute('photo_upload'));
                }
                $form->getElement('album')->setValue($uploadToAlbum->name);
            }

            if ( $albums )
            {
                $script =
                    '$("#album_input").hide();
                    $("#album_select").change(function(event){
                     $("#album_input").val($(this).val());
                     var id = $($("#album_select").children(":selected")[0]).data("id")
                     if (id === "create_new_photo_album_in_album_select"){
                         $("#album_input").show();
                     }
                     else{
                        $("#album_input").hide();
                     }

                });';
                OW::getDocument()->addOnloadScript($script);
            }
            OW::getLanguage()->addKeyForJs('photo', 'describe_photo');
            $script = "
            $('#upload-file-field').change(function(){
                var file_data = this.files[0];
                var form_data = new FormData();
                form_data.append('file', file_data);
                $.ajax({
                    url:'".OW::getRouter()->urlFor('PHOTO_MCTRL_Upload','ajaxUpload')."',
                    method:'POST',
                    data:form_data,
                    contentType: false,
                    cache: false,
                    processData:false,
                    success: function(response){
                        var jsonResponse = JSON.parse(response);
                        var status = jsonResponse.status;
                        if(status == 'ok'){
                            var fileUrl = jsonResponse.fileUrl;
                            var tempPhotoId = jsonResponse.tempPhotoId
                            var descName = 'desc'+ tempPhotoId;
                            var placeHolder = OW.getLanguageText('photo', 'describe_photo');
                            var image = '<img src=\"'+fileUrl+'\" />'
                            var input = '<textarea name=\"'+descName+'\"  id=\"'+descName+'\"  placeholder=\"'+placeHolder+'\" />';
                            
                            var wrapper = '<div class=\"owm_photo_upload_item_preview_wrapper\"></div>'
                            var imageContainer = '<div class=\"owm_photo_upload_img\"></div>'
                            var textContainer = '<div class=\"owm_photo_upload_desc\"></div>'
                            
                            imageContainer = $(imageContainer).append(image);
                            textContainer = $(textContainer).append(input);
                            
                            wrapper = $(wrapper).append(imageContainer,textContainer);
                            wrapper.appendTo('#imagesDiv');
                        }
                    },
                    error: function(){
                        console.log('error');
                    }                      
                });
                
            });";
            OW::getDocument()->addOnloadScript($script);

            if ( OW::getRequest()->isPost() )
            {
                if($form->isValid($_POST)){
                    $values = $form->getValues();
                    $userId = OW::getUser()->getId();
                    $photoTmpService = PHOTO_BOL_PhotoTemporaryService::getInstance();
                    if ( !($album = $photoAlbumService->findAlbumByName(UTIL_HtmlTag::stripTagsAndJs(trim($values['album'])), $userId)) )
                    {
                        $album = new PHOTO_BOL_PhotoAlbum();
                        $album->name = UTIL_HtmlTag::stripTagsAndJs(trim($values['album']));
                        $album->userId = $userId;
                        $album->createDatetime = time();

                        $photoAlbumService->addAlbum($album);
                    }
                    $uploadedPhotos = array();
                    $tmpList = $photoTmpService->findUserTemporaryPhotos($userId, 'order');
                    $tmpList = array_reverse($tmpList);
                    foreach ( $tmpList as $tmpPhoto ){
                        $tmpId = $tmpPhoto['dto']->id;
                        $desName = "desc{$tmpId}";
                        $description = $_POST[$desName];
                        $photo = $photoTmpService->moveTemporaryPhoto($tmpId, $album->id, $description);
                        $photoTmpService->deleteTemporaryPhoto($tmpId);
                        if($photo){
                            $uploadedPhotos[]=$photo;
                        }
                    }
                    list($entityType, $entityId) = $this->getEntity($params);
                    $resp = $this->onSubmitComplete($entityType, $entityId, $album, $uploadedPhotos);
                    $redirectUrl = $resp['url'];
                    $photoCount = count($uploadedPhotos);
                    OW::getFeedback()->info(OW::getLanguage()->text('photo', 'photos_uploaded', array('count' => $photoCount)));
                    $this->redirect($redirectUrl);
                }
            }
        }
    }
    public function ajaxUpload($params){
        $file = $_FILES['file'];
        $language = OW::getLanguage();
        $config = OW::getConfig();
        $accepted = floatval($config->getValue('photo', 'accepted_filesize') * 1024 * 1024);
        if ( strlen($file['tmp_name']) ){
            if ( !UTIL_File::validateImage($file['name']) || $file['size'] > $accepted )
            {
                $errorMessage = $language->text('photo', 'no_photo_uploaded');
                OW::getFeedback()->warning($errorMessage);
                $response = array(
                    'status'=>'error',
                    'message' => $errorMessage);
                exit(json_encode($response));
            }
        }
        $order = !empty($_POST['order']) ? (int) $_POST['order'] : 0;
        $id = PHOTO_BOL_PhotoTemporaryService::getInstance()->addTemporaryPhoto($file['tmp_name'], OW::getUser()->getId(), $order);
        $fileUrl = PHOTO_BOL_PhotoTemporaryDao::getInstance()->getTemporaryPhotoUrl($id, 2);
        $response = array(
            'status'=>'ok',
            'description' => 'this is description',
            'fileUrl'=> $fileUrl,
            'tempPhotoId'=>$id);
        exit(json_encode($response));

    }
    protected function onSubmitComplete( $entityType, $entityId, PHOTO_BOL_PhotoAlbum $album, $photos )
    {
        $this->photoService->createAlbumCover($album->id, $photos);

        $userId = OW::getUser()->getId();
        $result = array('result' => TRUE);

        if ( empty($photos) )
        {
            $result['url'] = OW::getRouter()->urlForRoute('photo_user_album', array(
                'user' => BOL_UserService::getInstance()->getUserName($userId),
                'album' => $album->id
            ));

            return $result;
        }

        $movedArray = array();
        foreach ( $photos as $photo )
        {
            $movedArray[] = array(
                'entityType' => $entityType,
                'entityId' => $entityId,
                'addTimestamp' => $photo->addDatetime,
                'photoId' => $photo->id,
                'hash' => $photo->hash,
                'description' => $photo->description
            );
            $userId = OW::getUser()->getId();
            OW::getEventManager()->trigger(new OW_Event('photo.after_desc_change', array('entityType' => 'photo_comments','entityId' => $photo->id, 'text'=>$photo->description,'userId'=>$userId)));

        }

        $event = new OW_Event(PHOTO_CLASS_EventHandler::EVENT_ON_PHOTO_ADD, $movedArray);
        OW::getEventManager()->trigger($event);

        $photoCount = count($photos);
        $photoIdList = array();
        foreach ( $photos as $photo )
        {
            $photoIdList[] = $photo->id;
        };

        $newPhotos = PHOTO_BOL_PhotoDao::getInstance()->findByIdList($photoIdList);
        $approvalPhotos = array();

        foreach ($newPhotos as $photo )
        {
            if ( $photo->status == PHOTO_BOL_PhotoDao::STATUS_APPROVED )
            {
                BOL_AuthorizationService::getInstance()->trackAction('photo', 'upload', array('checkInterval' => false));
            }
            elseif ( $photo->status == PHOTO_BOL_PhotoDao::STATUS_APPROVAL )
            {
                $approvalPhotos[] = $photo;
            }
        };

        if ( ($approvalCount = count($approvalPhotos)) === $photoCount )
        {
            if ( $approvalCount === 1 )
            {
                OW::getFeedback()->info(OW::getLanguage()->text('photo', 'photo_uploaded_pending_approval'));
            }
            else
            {
                OW::getFeedback()->info(OW::getLanguage()->text('photo', 'photos_uploaded_pending_approval', array('count' => $approvalCount)));
            }

            if ( $this->photoAlbumService->countAlbumPhotos($album->id) > 0 )
            {
                $result['url'] = OW::getRouter()->urlForRoute('photo_user_album', array(
                    'user' => BOL_UserService::getInstance()->getUserName($userId),
                    'album' => $album->id
                ));
            }
            else
            {
                $result['url']= OW::getRouter()->urlForRoute('photo_user_albums', array(
                    'user' => BOL_UserService::getInstance()->getUserName($userId)
                ));
            }

            return $result;
        }

        if ( $photoCount == 1 )
        {
            $this->photoService->triggerNewsfeedEventOnSinglePhotoAdd($album, $photos[0]);
        }
        else
        {
            $this->photoService->triggerNewsfeedEventOnMultiplePhotosAdd($album, $photos);
        }

        $result['url'] = OW::getRouter()->urlForRoute('photo_user_album', array(
            'user' => BOL_UserService::getInstance()->getUserName($userId),
            'album' => $album->id
        ));

        return $result;
    }
    protected function getEntity( $params )
    {
        if ( empty($params['entityType']) || empty($params['entityId']) )
        {
            $params['entityType'] = 'user';
            $params['entityId'] = OW::getUser()->getId();
        }

        return array($params['entityType'], $params['entityId']);
    }

}