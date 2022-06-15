<?php
class IISPHOTOPLUS_MCMP_AjaxUpload extends OW_MobileComponent
{
    private $photoService;
    public function __construct()
    {
        parent::__construct();

        if (!OW::getUser()->isAuthenticated()) {
            throw new AuthenticateException();
        }
        $this->photoService = PHOTO_BOL_PhotoService::getInstance();
    }
    public function onBeforeRender()
    {
        //$this->assign('backUrl', (OW::getRouter()->urlForRoute('photo_list_index')));
        $userId = $userId = OW::getUser()->getId();
        PHOTO_BOL_PhotoTemporaryService::getInstance()->deleteUserTemporaryPhotos($userId);
        $language = OW::getLanguage();
        OW::getDocument()->setHeading($language->text('photo', 'upload_photos'));
        OW::getDocument()->setTitle($language->text('photo', 'meta_title_photo_upload'));

        if (!OW::getUser()->isAuthorized('photo', 'upload')) {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');
            $this->assign('auth_msg', $status['msg']);
            return;
        }

        $config = OW::getConfig();
        $userQuota = (int)$config->getValue('photo', 'user_quota');
        $userId = OW::getUser()->getId();

        if (!($this->photoService->countUserPhotos($userId) <= $userQuota)) {
            $this->assign('auth_msg', $language->text('photo', 'quota_exceeded', array('limit' => $userQuota)));
        } else {
            $this->assign('auth_msg', null);

            $form = new IISPHOTOPLUS_MCLASS_AjaxUploadForm();
            $this->addForm($form);

            $photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();
            $albums = $photoAlbumService->findUserAlbumList($userId, 1, 100);
            $this->assign('albums', $albums);

            if (!empty($params['album']) && (int)$params['album']) {
                $albumId = (int)$params['album'];
                $uploadToAlbum = $photoAlbumService->findAlbumById($albumId);
                if (!$uploadToAlbum || $uploadToAlbum->userId != $userId) {
                    //$this->redirect(OW::getRouter()->urlForRoute('photo_upload'));
                }
                $form->getElement('album')->setValue($uploadToAlbum->name);
            }

            if ($albums) {
                $script =
                    '$("#album_select").change(function(event){
                    $("#album_input").val($(this).val());
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
                    url:'" . OW::getRouter()->urlFor('IISPHOTOPLUS_MCTRL_AjaxUpload', 'ajaxUpload') . "',
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
                            var tempPhotoId = jsonResponse.tempPhotoId;
                            var descName = 'desc'+ tempPhotoId;
                            var placeHolder = OW.getLanguageText('photo', 'describe_photo');
                            var image = '<img src=\"'+fileUrl+'\" />';
                            var input = '<textarea name=\"'+descName+'\"  id=\"'+descName+'\"  placeholder=\"'+placeHolder+'\" />';
                            
                            var wrapper = '<div class=\"owm_photo_upload_item_preview_wrapper\"></div>'
                            var imageContainer = '<div class=\"owm_photo_upload_img\"></div>'
                            var textContainer = '<div class=\"owm_photo_upload_desc\"></div>'
                            
                            imageContainer = $(imageContainer).append(image);
                            textContainer = $(textContainer).append(input);
                            
                            wrapper = $(wrapper).append(imageContainer,textContainer);
                            wrapper.appendTo('#imagesDiv');
                            
                            var res = $('#uploadPhotos').val();
                            if(res == ''){
                                res = [];
                            }else{
                                res = JSON.parse(res);                         
                            }
                            res.push(jsonResponse);
                            var str = JSON.stringify(res);
                            $('#uploadPhotos').val(str);
                            var formElement = new OwFormElement(descName, descName);
                            window.owForms['upload-form'].addElement(formElement);
                        }
                    },
                    error: function(){
                        console.log('error');
                    }                      
                });
                
            });";
            OW::getDocument()->addOnloadScript($script);
        }
    }
}