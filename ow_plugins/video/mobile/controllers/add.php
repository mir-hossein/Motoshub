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
 * Video add action controller
 *
 * @since 1.0
 */
class VIDEO_MCTRL_Add extends OW_MobileActionController
{
    /**
     * Default action
     */
    public function index()
    {
        $language = OW::getLanguage();
        OW::getDocument()->setHeading($language->text('video', 'page_title_add_video'));
        OW::getDocument()->setHeadingIconClass('ow_ic_video');
        OW::getDocument()->setTitle($language->text('video', 'meta_title_video_add'));
        OW::getDocument()->setDescription($language->text('video', 'meta_description_video_add'));

        $clipService = VIDEO_BOL_ClipService::getInstance();
        $userId = OW::getUser()->getId();
        $this->assign('backUrl', OW::getRouter()->urlForRoute('video_view_list'));
        if ( !OW::getUser()->isAuthorized('video', 'add') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('video', 'add');
            throw new AuthorizationException($status['msg']);
        }

        if ( !($clipService->findUserClipsCount($userId) <= $clipService->getUserQuotaConfig()) )
        {
            $this->assign('auth_msg', $language->text('video', 'quota_exceeded', array('limit' => $clipService->getUserQuotaConfig())));
        }
        else
        {
            $this->assign('auth_msg', null);

            $videoAddForm = new videoAddForm();
            $this->addForm($videoAddForm);
            OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_VIDEO_UPLOAD_COMPONENT_RENDERER,array('form' => $videoAddForm, 'component' => $this)));
            if(isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH']>0 && $videoAddForm->getElement('videoUpload')!=null) {
                $maxUploadSize = OW::getConfig()->getValue('base', 'attch_file_max_size_mb');
                $bundleSize = floor($_SERVER['CONTENT_LENGTH'] / 1024);

                if ($maxUploadSize > 0 && $bundleSize > ($maxUploadSize * 1024)) {
                    OW::getFeedback()->error(OW::getLanguage()->text('iisvideoplus', 'upload_file_max_upload_filesize_error', array('videofilesize' => $maxUploadSize)));
                    return false;
                }
            }
            if ( OW::getRequest()->isPost() )
            {
                $videoAddForm->getElement('code')->addAttribute(Form::SAFE_RENDERING,true);
                if(isset($_POST['input_type'])){
                    $this->assign('input_type', $_POST['input_type']);
                }
                if ($videoAddForm->isValid($_POST)) {
                    $values = $videoAddForm->getValues();
                    if ( (isset($_POST['input_type']) && $_POST['input_type']=="aparat") && !empty($values['aparatURL'])) {
                        $aparat_video_ID = explode('/', $values['aparatURL'])[4];
                        $aparat_code = '
<style>.h_iframe-aparat_embed_frame{position:relative;} .h_iframe-aparat_embed_frame .ratio {display:block;width:100%;height:auto;} .h_iframe-aparat_embed_frame iframe {position:absolute;top:0;left:0;width:100%; height:100%;}</style>
<div class="h_iframe-aparat_embed_frame"> <span style="display: block;padding-top: 57%"></span>
<iframe src="https://www.aparat.com/video/video/embed/videohash/'.$aparat_video_ID.'/vt/frame" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" ></iframe></div>';
                        $values['code'] = $aparat_code;
                    }
                    if ( (isset($_POST['input_type']) && $_POST['input_type']=="code") || !isset($values['videoUpload'])) {
                        $code = $clipService->validateClipCode($values['code']);
                        if (!mb_strlen($code)) {
                            OW::getFeedback()->warning($language->text('video', 'resource_not_allowed'));
                            return false;
                        }
                        $videoAddForm->setValues($values);
                    }
                    $res = $videoAddForm->process();
                    OW::getFeedback()->info($language->text('video', 'clip_added'));
                    $this->redirect(OW::getRouter()->urlForRoute('view_clip', array('id' => $res['id'])));
                }
            }
        }

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'video', 'video');
        }

        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_VIDEO_RENDER));
    }
}

/**
 * Video add form class
 */
class videoAddForm extends Form
{

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
        parent::__construct('videoAddForm');

        $language = OW::getLanguage();

        // title Field
        $titleField = new TextField('title');
        $titleField->setRequired(true);
        $this->addElement($titleField->setLabel($language->text('video', 'title')));

        // description Field
        $buttons = array(
            BOL_TextFormatService::WS_BTN_BOLD,
            BOL_TextFormatService::WS_BTN_ITALIC,
            BOL_TextFormatService::WS_BTN_UNDERLINE,
            BOL_TextFormatService::WS_BTN_LINK);
        $descField = new MobileWysiwygTextarea('description','video', $buttons);
        $this->addElement($descField->setLabel($language->text('video', 'description')));

        // code Field
        $codeField = new Textarea('code');
        $codeField->setRequired(true);
        $this->addElement($codeField->setLabel($language->text('video', 'code')));

        $tagsField = new TagsInputField('tags');
        $this->addElement($tagsField->setLabel($language->text('video', 'tags')));

        $submit = new Submit('add');
        $submit->setValue($language->text('video', 'btn_add'));
        $this->addElement($submit);

        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_VIDEO_UPLOAD_FORM_RENDERER,array('form' => $this)));
    }

    /**
     * Adds video clip
     *
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();

        $addClipParams = array(
            'userId' => OW::getUser()->getId(),
            'title' => UTIL_HtmlTag::stripTagsAndJs($values['title']),
            'description' => $values['description'],
            'code' => $values['code'],
            'tags' => $values['tags']
        );
        if(isset($values['videoUpload']) ) {
            $addClipParams['videoUpload']=$values['videoUpload'];
            $addClipParams['code']='videoUpload';
        }
        $event = new OW_Event(VIDEO_CLASS_EventHandler::EVENT_VIDEO_ADD, $addClipParams);
        OW::getEventManager()->trigger($event);

        $addClipData = $event->getData();

        if ( !empty($addClipData['id']) )
        {
            if(isset($values['videoUpload']) ) {
                $event = new OW_Event('videoplus.after_add', array('videoUpload'=>$values['videoUpload'],'videoUploadThumbnail'=>$values['videoUploadThumbnail'],'videoId'=>$addClipData['id'] ));
                OW::getEventManager()->trigger($event);
            }
            return array('result' => true, 'id' => $addClipData['id']);
        }

        return false;
    }
}