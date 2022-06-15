<?php

/**
 * Copyright (c) 2016, Mohammad Aghaabbasloo
 * All rights reserved.
 */

/**
 *
 *
 * @author Mohammad Aghaabbasloo
 * @package ow_plugins.iisvideoplus
 * @since 1.0
 */
class IISVIDEOPLUS_BOL_Service
{
    const EVENT_AFTER_ADD = 'videoplus.after_add';
    const ON_VIDEO_VIEW_RENDER='videplus.on.video.view.render';
    const ON_BEFORE_VIDEO_ADD = 'videoplus.on.before.video.add';
    const ON_VIDEO_LIST_VIEW_RENDER = 'videplus.on.video.list.view.render';
    const ADD_VIDEO_DOWNLOAD_LINK = 'videoplus.add.video.download.link';
    const ON_USER_UNREGISTER  = 'videplus.on.user.unregister';
    const EVENT_UNINSTALL_IN_PROGRESS = 'iisvideoplus.uninstall_in_progress';
    private static $LATEST_FRIENDS = 'latest_friends';
    private static $LATEST_MYVIDEO = 'latest_myvideo';
    private static $classInstance;
    private $videoFileName;
    private $videoThumbnailFileName;
    private $imageFile;
    private $oldFileName;
    private $oldImageName;
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

    public function setTtileHeaderListItemVideo( OW_Event $event )
    {
        $params = $event->getParams();
        if (isset($params['listType']) && $params['listType'] == IISVIDEOPLUS_BOL_Service::$LATEST_FRIENDS) {
            OW::getDocument()->setTitle(OW::getLanguage()->text('iisvideoplus', 'meta_title_video_add_latest_friends'));
            OW::getDocument()->setDescription(OW::getLanguage()->text('iisvideoplus', 'meta_description_video_latest_friends'));
        }
        if (isset($params['listType']) && $params['listType'] == IISVIDEOPLUS_BOL_Service::$LATEST_MYVIDEO) {
            OW::getDocument()->setTitle(OW::getLanguage()->text('iisvideoplus', 'meta_title_video_add_latest_myvideo'));
            OW::getDocument()->setDescription(OW::getLanguage()->text('iisvideoplus', 'meta_description_video_latest_myvideo'));
        }
    }

    public function addListTypeToVideo( OW_Event $event )
    {
        $params = $event->getParams();
        if(isset($params['validLists'])){
            $validLists = $params['validLists'];
            if(OW::getUser()->isAuthenticated()) {
                $validLists[] = IISVIDEOPLUS_BOL_Service::$LATEST_FRIENDS;
                $validLists[] = IISVIDEOPLUS_BOL_Service::$LATEST_MYVIDEO;
            }
            $event->setData(array('validLists' => $validLists));
        }
        if(isset($params['menuItems']) && OW::getUser()->isAuthenticated()){
            $menuItems = $params['menuItems'];

            //its for my friends videos
            $item = new BASE_MenuItem();
            $item->setLabel(OW::getLanguage()->text('iisvideoplus', IISVIDEOPLUS_BOL_Service::$LATEST_FRIENDS));
            $item->setUrl(OW::getRouter()->urlForRoute('view_list', array('listType' => IISVIDEOPLUS_BOL_Service::$LATEST_FRIENDS)));
            $item->setKey(IISVIDEOPLUS_BOL_Service::$LATEST_FRIENDS);
            $item->setIconClass('ow_ic_friends_video');
            $item->setOrder(sizeof($params['menuItems']));
            array_push($menuItems, $item);

            //its for my videos
            $item = new BASE_MenuItem();
            $item->setLabel(OW::getLanguage()->text('iisvideoplus', IISVIDEOPLUS_BOL_Service::$LATEST_MYVIDEO));
            $item->setUrl(OW::getRouter()->urlForRoute('view_list', array('listType' => IISVIDEOPLUS_BOL_Service::$LATEST_MYVIDEO)));
            $item->setKey(IISVIDEOPLUS_BOL_Service::$LATEST_MYVIDEO);
            $item->setIconClass('ow_ic_my_video');
            $item->setOrder(sizeof($params['menuItems'])+1);
            array_push($menuItems, $item);
            $event->setData(array('menuItems' => $menuItems));
        }
    }

    public function getResultForListItemVideo( OW_Event $event )
    {
        $params = $event->getParams();
        if(isset($params['this']) &&
            isset($params['listtype']) &&
            isset($params['cacheLifeTime']) &&
            isset($params['cacheTags']) &&
            isset($params['first']) &&
            isset($params['limit']) &&
            $params['listtype'] == IISVIDEOPLUS_BOL_Service::$LATEST_FRIENDS){

            $friendsOfCurrentUser = array();
            if(OW::getUser()->isAuthenticated()){
                $friendsOfCurrentUser = OW::getEventManager()->call('plugin.friends.get_friend_list', array('userId' => OW::getUser()->getId()));
            }
            if(class_exists('PRIVACY_BOL_ActionService')) {
                $friendsOfCurrentUserFiltered = array();
                $videoPrivacyKey = 'video_view_video';
                $userPrivacy = PRIVACY_BOL_ActionService::getInstance()->getActionValueListByUserIdList(array($videoPrivacyKey), $friendsOfCurrentUser);
                foreach ($friendsOfCurrentUser as $userFriendId) {
                    if (key_exists($userFriendId, $userPrivacy) && $userPrivacy[$userFriendId][$videoPrivacyKey] != 'only_for_me') {
                        $friendsOfCurrentUserFiltered[] = $userFriendId;
                    }
                }
                $friendsOfCurrentUser = $friendsOfCurrentUserFiltered;
            }
            if(!empty($friendsOfCurrentUser)) {

                $example = new OW_Example();

                $example->andFieldEqual('status', 'approved');
                $example->andFieldInArray('userId', $friendsOfCurrentUser);
                $example->andFieldNotEqual('privacy', 'only_for_me');
                $example->setOrder('`addDatetime` DESC');
                $example->setLimitClause($params['first'], $params['limit']);

                $result = $params['this']->findListByExample($example, $params['cacheLifeTime'], $params['cacheTags']);
                $event->setData(array('result' => $result));
            }
        }
        /*
         * add my list video result
         */
        if(isset($params['this']) &&
            isset($params['listtype']) &&
            isset($params['cacheLifeTime']) &&
            isset($params['cacheTags']) &&
            isset($params['first']) &&
            isset($params['limit']) &&

            $params['listtype'] == IISVIDEOPLUS_BOL_Service::$LATEST_MYVIDEO){

            if(OW::getUser()->isAuthenticated()) {
                $example = new OW_Example();
                $example->andFieldEqual('status', 'approved');
                $example->andFieldEqual('userId', OW::getUser()->getId());
                $example->setOrder('`addDatetime` DESC');
                $example->setLimitClause($params['first'], $params['limit']);
                $result = $params['this']->findListByExample($example, $params['cacheLifeTime'], $params['cacheTags']);
                $event->setData(array('result' => $result));
            }
        }
    }

    public function getResultForCountItemVideo( OW_Event $event ){
        $params = $event->getParams();
        if(isset($params['this']) && isset($params['listType']) && OW::getUser()->isAuthenticated() ){
            switch ( $params['listType'] ) {
                case IISVIDEOPLUS_BOL_Service::$LATEST_MYVIDEO:
                    $result = $params['this']->countUserClips(OW::getUser()->getId());
                    $event->setData(array('result' => $result));
                    return;
                case IISVIDEOPLUS_BOL_Service::$LATEST_FRIENDS:
                    $friendsOfCurrentUser = OW::getEventManager()->call('plugin.friends.get_friend_list', array('userId' => OW::getUser()->getId()));
                    if(class_exists('PRIVACY_BOL_ActionService')) {
                        $friendsOfCurrentUserFiltered = array();
                        $videoPrivacyKey = 'video_view_video';
                        $userPrivacy = PRIVACY_BOL_ActionService::getInstance()->getActionValueListByUserIdList(array($videoPrivacyKey), $friendsOfCurrentUser);
                        foreach ($friendsOfCurrentUser as $userFriendId) {
                            if (key_exists($userFriendId, $userPrivacy) && $userPrivacy[$userFriendId][$videoPrivacyKey] != 'only_for_me') {
                                $friendsOfCurrentUserFiltered[] = $userFriendId;
                            }
                        }
                        $friendsOfCurrentUser = $friendsOfCurrentUserFiltered;
                    }
                    if(!empty($friendsOfCurrentUser)) {

                        $example = new OW_Example();

                        $example->andFieldEqual('status', 'approved');
                        $example->andFieldInArray('userId', $friendsOfCurrentUser);
                        $example->andFieldNotEqual('privacy', 'only_for_me');
                        $result = $params['this']->countByExample($example);
                        $event->setData(array('result' => $result));
                    }
                    return;
                default :

            }
        }

    }
    /*
 * show video thumb image after video rendered in main page
 * @param OW_Event $event
 */
    public static function onAfterVideoRendered(OW_Event $event)
    {
        $js = UTIL_JsGenerator::newInstance();
        $params = $event->getParams();
        if(isset($params['uniqId'])) {
            $js->addScript('$(".ow_oembed_video_cover", "#" + {$uniqId}).trigger( "click" );', array(
                "uniqId" => $params['uniqId']
            ));
        }
        OW::getDocument()->addOnloadScript($js);
    }

    public function onBeforeVideoUploadFormRenderer(OW_Event $event)
    {
        $this->oldFileName=null;
        $params = $event->getParams();
        if(isset($params['form']) && isset($params['component']) && isset($params['code'])){
            $form = $params['form'];
            /* @var $form Form */
            $form->addElement($this->addVideoUploader());
            $form->addElement($this->addVideoThumbnailInput());
            $videoDir= $this->getVideoFileDir($params['code']);
            if(OW::getStorage()->fileExists($videoDir)) {
                if ($form->getElement('code') != null) {
                    $this->oldFileName=$form->getElement('code')->getValue();
                    $videId=$form->getElement('id')->getValue();
                    $video = VIDEO_BOL_ClipService::getInstance()->findClipById($videId);
                    $this->oldImageName=$video->thumbUrl;
                    $form->deleteElement("code");
                    $codeField = new Textarea('code');
                    $codeField->setLabel(OW::getLanguage()->text('video', 'code'));
                    $form->addElement($codeField);
                }
            }
            else{
                $codeValue = $form->getElement('code')->getValue();
                $form->deleteElement("code");
                $codeField = new Textarea('code');
                $codeField->setLabel(OW::getLanguage()->text('video', 'code'));
                $codeField->setValue($codeValue);
                $form->addElement($codeField);
            }
            $params['component']->assign('videoUploadField', true);
            $form->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);
        }
        else if(isset($params['form'])){
            $form = $params['form'];
            /* @var $form Form */
            $form->addElement($this->addVideoUploader());
            $form->addElement($this->addVideoThumbnailInput());
            if($form->getElement('code')!=null) {
                $form->getElement('code')->setRequired(false)->setLabel(OW::getLanguage()->text('video', 'code'));
            }
            $form->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);
        }
    }

    public function onBeforeVideoUploadComponentRenderer(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['form']) && isset($params['component'])){
            $form = $params['form'];
            if($form->getElement('videoUpload')!=null){
                $params['component']->assign('videoUploadField',true);
                OW::getLanguage()->addKeyForJs('iisvideoplus', 'upload_file_extension_is_not_allowed');
                OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iisvideoplus')->getStaticJsUrl().'iisvideoplus.js');
            }
        }
    }

    public function addVideoUploader(){
        $videoUpload = new IISVIDEOPLUS_File('videoUpload');
        $videoUpload->setId('videoUpload');
        $videoUpload->setLabel(OW::getLanguage()->text('iisvideoplus', 'create_video_upload_label'));
        $videoUpload->addValidator(new IISVIDEOPLUS_CMP_Validserviceproviders());
        return $videoUpload;
    }

    public function addVideoThumbnailInput(){
        $videoUploadThumbnail = new HiddenField('videoUploadThumbnail');
        $videoUploadThumbnail->setId('videoUploadThumbnail');
        return $videoUploadThumbnail;
    }

    public function getValue()
    {
        return empty($_FILES[$this->getName()]['tmp_name']) ? null : $_FILES[$this->getName()];
    }

    public function onAfterEntryAdd(OW_Event $event){
        $params = $event->getParams();
        if(isset($params['videoId'])  && isset($params['code'])
            && isset($params['forUpdate']) && $params['forUpdate']==true){
            if(isset($this->oldFileName) && $this->oldFileName!=null && $this->oldFileName!=$params['code']) {
                $videoDir = $this->getVideoFileDir($this->oldFileName);
                if (OW::getStorage()->fileExists($videoDir)) {
                    OW::getStorage()->removeFile($videoDir);
                }
            }
            if(isset($this->oldImageName) && $this->oldImageName!=null && $this->oldFileName!=$params['code']) {
                $imageDir = $this->getVideoFileDir($this->oldImageName);
                if (OW::getStorage()->fileExists($imageDir)) {
                    OW::getStorage()->removeFile($imageDir);
                }
            }
        }
        if(isset($params['videoUpload']) && isset($params['videoId'])){
            $this->saveVideoFile($params['videoUpload']);//, $params['videoId']);
            if (!OW::getConfig()->configExists('iisvideoplus', 'totalSize')){
                OW::getConfig()->addConfig('iisvideoplus', 'totalSize', $params['videoUpload']['size']);
            }else{
                $totalSize=OW::getConfig()->getValue('iisvideoplus', 'totalSize');
                $totalSize=$totalSize+$params['videoUpload']['size'];
                OW::getConfig()->saveConfig('iisvideoplus', 'totalSize', $totalSize);
            }
        }
        if(isset($params['videoUploadThumbnail']))
        {
         $this->saveVideoThumbnailFile($params['videoId'],$params['videoUploadThumbnail']);

        }
    }

    public function saveVideoThumbnailFile($videoId,$convasData)
    {
        if(!isset($convasData))
        {
            return;
        }
        $videoService = VIDEO_BOL_ClipService::getInstance();
        $video = $videoService->findClipById($videoId);

        if(!OW::getUser()->isAuthenticated() || $video->getUserId()!= OW::getUser()->getId())
        {
            return;
        }
        try {
            $videoNameParts = explode('.', $video->code);
            $imageName = "";
            foreach ($videoNameParts as $videoNamePart) {
                if ($videoNamePart != end($videoNameParts)) {
                    $imageName = $imageName . $videoNamePart;
                }
            }
            $imageName = $imageName . '.png';
            $tmpDir = OW::getPluginManager()->getPlugin('video')->getPluginFilesDir();
            $tmpVideoImageFile = $tmpDir . $imageName;
            $rawData = $convasData;
            $filteredData = explode(',', $rawData);
            $valid = IISSecurityProvider::createFileFromRawData($tmpVideoImageFile, $filteredData[1]);
            if ($valid) {
                $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event('iisclamav.is_file_clean', array('path' => $tmpVideoImageFile)));
                if(isset($checkAnotherExtensionEvent->getData()['clean'])) {
                    $isClean = $checkAnotherExtensionEvent->getData()['clean'];
                    if (!$isClean) {
                        return false;
                    }
                }
                $imageFile = $this->getVideoFileDir($imageName);
                OW::getStorage()->copyFile($tmpVideoImageFile, $imageFile);
                $video->thumbUrl = $imageName;
                $videoService->saveClip($video);
                OW::getStorage()->removeFile($tmpVideoImageFile);
            }
        } catch (Exception $e) {
            OW::getLogger()->writeLog(OW_Log::ERROR, 'save_video_thumbnail', ['actionType'=>OW_Log::UPDATE, 'enType'=>'video', 'enId'=>$videoId]);
        }
    }
    protected function saveVideoFile( $postFile )
    {
        $videoFile = $this->getVideoFileDir( $this->videoFileName);
        $tmpDir = OW::getPluginManager()->getPlugin('video')->getPluginFilesDir();
        $tmpVideoFile = $tmpDir . $this->videoFileName;
        if(OW::getStorage()->moveFile($postFile["tmp_name"], $tmpVideoFile)) {
            try {
                OW::getStorage()->copyFile($tmpVideoFile, $videoFile);
            } catch (Exception $e) {
            }
        }
        OW::getStorage()->removeFile($tmpVideoFile);
    }

    public function getVideoFileDir($FileName)
    {
        return OW::getPluginManager()->getPlugin('video')->getUserFilesDir() . $FileName;
    }

    public function getVideoFilePath($FileName)
    {
        return OW::getStorage()->getFileUrl($this->getVideoFileDir($FileName));
    }

    public function onBeforeVideoAdded(OW_Event $event){
        $params = $event->getParams();
        if(isset($params['videoUpload'])){
            $fileName = OW::getUser()->getId() . "_" . UTIL_String::getRandomString(16);
            $this->videoThumbnailFileName = $fileName.".jpg";
            $fileName = $fileName.'.'.UTIL_File::getExtension($params['videoUpload']['name']);
            $this->videoFileName=$fileName;
            $event->setData(array('fileName'=>$fileName,'newFile'=>true));
        }
        else if(!isset($params['code'])|| $params['code']==null){
            $event->setData(array('fileName'=>$this->oldFileName));
        }
        else if (isset($params['code']) && isset($params['oldCode'])){
            $videoDir = $this->getVideoFileDir($params['oldCode']);
            if (OW::getStorage()->fileExists($videoDir)) {
                OW::getStorage()->removeFile($videoDir);
            }
            $videoName = explode('.', $params['oldCode']);
            $imageDir = $this->getVideoFileDir($videoName[0].'.jpg');
            if (OW::getStorage()->fileExists($imageDir)) {
                OW::getStorage()->removeFile($imageDir);
            }
        }
    }
    public function onVideoViewRender(OW_Event $event){
        $params = $event->getParams();
        if(isset($params['code']) && isset($params['videoId'])){
            $videoDir= $this->getVideoFileDir($params['code']);
            if(OW::getStorage()->fileExists($videoDir)) {
                $video = VIDEO_BOL_ClipService::getInstance()->findClipById($params['videoId']);
                $videoFile = $this->getVideoFilePath($params['code']);
                $this->videoPlayerRenderScript();
                $thumbUrl=null;
                if(isset($video->thumbUrl)) {
                    $thumbUrl=$this->getVideoFilePath($video->thumbUrl);
                }
                $event->setData(array('source' => $videoFile,'thumbUrl'=>$thumbUrl));
            }
        }
    }

    public function videoPlayerRenderScript(){
        $script = "$('video,audio').mediaelementplayer(/* Options */);";
        OW::getDocument()->addOnloadScript($script);
    }

    public function onVideoListViewRender(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['clips'])) {
            $clips=$params['clips'];
            $newClips = array();
            foreach ( $clips as $clip )
            {
                $video = VIDEO_BOL_ClipService::getInstance()->findClipById($clip['id']);
                if(isset($video->code))
                {
                    if (!isset($video->thumbUrl) || $video->thumbUrl == "") {
                        $clip['thumb'] = $this->getClipDefaultThumbUrl();
                    } else {
                        if (substr($video->code, 0, 7) == "<iframe")
                            $clip['thumb'] = $video->thumbUrl;
                        else
                            $clip['thumb'] = $this->getVideoFilePath($video->thumbUrl);
                    }
                }
                $newClips[]=$clip;
            }
            $event->setData(array('clips' => $newClips));
        }else if(isset($params['clip'])){
            $clip = $params['clip'];
            $video = VIDEO_BOL_ClipService::getInstance()->findClipById($clip->id);
            if(isset($this->videoThumbnailFileName)) {
                $clip->thumbUrl=$this->getVideoFilePath($video->thumbUrl);
            }
            $event->setData(array('clip' => $clip));
        }else if(isset($params['getThumb']) && $params['getThumb']==true && isset($params['clipId'])){
            $video = VIDEO_BOL_ClipService::getInstance()->findClipById($params['clipId']);
            if(isset($video->thumbUrl)) {
                if (filter_var($video->thumbUrl, FILTER_VALIDATE_URL)) {
                    $event->setData(array('thumbUrl' =>$video->thumbUrl));
                }else{
                    $thumbUrl=$this->getVideoFilePath($video->thumbUrl);
                    $event->setData(array('thumbUrl' =>$thumbUrl));
                }

            }
        }else if(isset($params['forNewsFeed']) && $params['forNewsFeed']==true && isset($params['videoId'])){
            $video = VIDEO_BOL_ClipService::getInstance()->findClipById($params['videoId']);
            $eventVideo = new OW_Event('videplus.on.video.view.render', array('code'=>$video->code,'videoId'=>$video->id));
            OW::getEventManager()->trigger($eventVideo);
            if(isset($eventVideo->getData()['source'])) {
                $config = OW::getConfig();
                $playerWidth = $config->getValue('video', 'player_width');
                $playerHeight = $config->getValue('video', 'player_height');
                $thumbUrl=null;
                if(isset($video->thumbUrl)) {
                    $thumbUrl=$this->getVideoFilePath($video->thumbUrl);
                }
                $event->setData(array('width' => $playerWidth,'height'=> $playerHeight,'source'=>$eventVideo->getData()['source'],'thumbUrl'=>$thumbUrl));
            }
        }else if(isset($params['getThumb']) && $params['getThumb']==true && isset($params['thumbUrl'])){
            if (substr($params['thumbUrl'], 0, 4) == "http"){
                $thumbUrl = $params['thumbUrl'];
            }
            else{
                $thumbUrl = $this->getVideoFilePath($params['thumbUrl']);
            }
            $event->setData(array('thumbUrl' => $thumbUrl));
        }
    }
    public function setMaintenanceMode( $mode = true )
    {
        $config = OW::getConfig();

        if ( $mode )
        {
            $state = (int) $config->getValue('base', 'maintenance');
            $config->saveConfig('iisvideoplus', 'maintenance_mode_state', $state);
            OW::getApplication()->setMaintenanceMode($mode);
        }
        else
        {
            $state = (int) $config->getValue('iisvideoplus', 'maintenance_mode_state');
            $config->saveConfig('base', 'maintenance', $state);
        }
    }

    public function deleteVideoFileByCode(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['code'])) {
            $videoDir = $this->getVideoFileDir($params['code']);
            if (OW::getStorage()->fileExists($videoDir)) {
                $videSizeFile=filesize($videoDir);
                if (OW::getConfig()->configExists('iisvideoplus', 'totalSize')){
                    $totalSize=OW::getConfig()->getValue('iisvideoplus', 'totalSize');
                    $totalSize=$totalSize-$videSizeFile;
                    OW::getConfig()->saveConfig('iisvideoplus', 'totalSize', $totalSize);
                }

            }
            $videoName = explode('.', $params['code']);
            $imageDir = $this->getVideoFileDir($videoName[0].'.png');
            if (OW::getStorage()->fileExists($imageDir)) {
                OW::getStorage()->removeFile($imageDir);
            }
        }
    }
    public function deleteAllVideoFiles($limit){
        $files = glob(OW::getPluginManager()->getPlugin('video')->getUserFilesDir().'/*');
        $videoDao = VIDEO_BOL_ClipDao::getInstance();
        $videoService = VIDEO_BOL_ClipService::getInstance();
        $videoSizeFile=0;
        foreach($files as $file){ // iterate files
            if(OW::getStorage()->isFile($file)) {
                if(UTIL_File::getExtension($file)=="mp4") {
                    $videoSizeFile = $videoSizeFile+filesize($file);
                }
                $file = basename($file);
                $example = new OW_Example();
                $example->andFieldEqual('code', $file);
                $res=$videoDao->findIdByExample($example);
                if (count($res) != 0) {
                    $videoService->deleteClip($res);
                }
                OW::getStorage()->removeFile($this->getVideoFileDir($file)); // delete file
            }
        }
        if (OW::getConfig()->configExists('iisvideoplus', 'totalSize')) {
            $totalSize = OW::getConfig()->getValue('iisvideoplus', 'totalSize');
            $totalSize = $totalSize - $videoSizeFile;
            OW::getConfig()->saveConfig('iisvideoplus', 'totalSize', $totalSize);
        }
        return true;
    }

    public function addVideoDownloadLink(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['code']))
        {
            $videoDir = $this->getVideoFileDir($params['code']);
            if (OW::getStorage()->fileExists($videoDir)) {
                $fileToolbar= array(
                    'href' => $this->getVideoFilePath($params['code']),
                    'id' => 'btn-video-download',
                    'label' => OW::getLanguage()->text('iisvideoplus', 'download_file')
                );
                $event->setData(array('fileToolbar'=>$fileToolbar));
            }
        }
    }

    public function getVideoThumbnail(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['clipId']))
        {
            $responseData=array();
            $video = VIDEO_BOL_ClipService::getInstance()->findClipById($params['clipId']);
            $videoFile = $this->getVideoFilePath($video->code);
            if(isset($videoFile))
            {
                $responseData['videoFileUrl']=$videoFile;
            }
            if(isset($video->thumbUrl)) {
                $thumbnailUrl=$this->getVideoFilePath($video->thumbUrl);
                $responseData['thumbnailUrl']=$thumbnailUrl;
                $event->setData($responseData);
            }
        }
    }
    public function getClipDefaultThumbUrl() {
        return OW::getThemeManager()->getCurrentTheme()->getStaticImagesUrl() . 'video-no-video.png';
    }
}

class IISVIDEOPLUS_File extends FileField
{

    public function getValue()
    {
        return empty($_FILES[$this->getName()]['tmp_name']) ? null : $_FILES[$this->getName()];
    }
}

