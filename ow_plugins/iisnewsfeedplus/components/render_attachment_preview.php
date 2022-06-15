<?php
/**
 * Created by PhpStorm.
 * User: Milad Heshmati
 * Date: 5/7/2019
 * Time: 10:01 AM
 */

class IISNEWSFEEDPLUS_CMP_RenderAttachmentPreview extends OW_Component
{
    public function __construct($attachmentsList = array())
    {
        parent::__construct();
        $itemBarWidth = 0;
        $mobileMode = false;
        $attachmentItemsParams = null;
        $thumbnailController= OW::getRouter()->urlFor('IISNEWSFEEDPLUS_CTRL_VideoThumbnail', 'index');
        $postAttachmentId = UTIL_String::getRandomString(5);
        $thumbnailControllerActivator = '';

        $attachmentDir = BOL_AttachmentService::getInstance()->getAttachmentsDir();
        $pluginStaticUrl = OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getStaticUrl();
        $audioThumbnailUrl = $pluginStaticUrl . 'img/defualt_audio_thumbnail.png';
        $videoThumbnailUrl = $pluginStaticUrl . 'img/defualt_video_thumbnail.png';
        $audioPreviewUrl = $pluginStaticUrl . 'img/player_background_audio.png';
        $videoPreviewUrl = $pluginStaticUrl . 'img/player_background_video.png';

        $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
        if(isset($mobileEvent->getData()['isMobileVersion'])&& $mobileEvent->getData()['isMobileVersion'] == true) {
            $mobileMode=true;
        }

        $attachmentPreviewNumber = sizeof($attachmentsList);

        if($attachmentPreviewNumber > 6 || ($mobileMode && $attachmentPreviewNumber > 2)){
            /*6 and 2 stand for the suitable items to show in item bar without scroll respectively (6 and 2 are columns count)*/
            if (!$mobileMode) {
                $sizeCalibrator=75-0.3*$attachmentPreviewNumber;
                /* 75 is the container width required for each item in item bar which is calibrated with 0.3 factor according to number of attachments */
                $itemBarWidth = $attachmentPreviewNumber * $sizeCalibrator;
            }
        }
        $idList = array_column($attachmentsList,'id');
        if ($idList != null)
        {
            $thumbnailList = IISNEWSFEEDPLUS_BOL_ThumbnailDao::getInstance()->getThumbnailsByAttachmentIds(array_column($attachmentsList,'id'));
            $thumbnailUrl = OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getUserFilesUrl();
        }
        foreach ($attachmentsList as $attachment) {
            $playerPoster = '';
            $filePath = $attachmentDir . $attachment->fileName;
            $downloadUrl = OW::getStorage()->getFileUrl($filePath);

            $thumbnail = UTIL_File::getFileUrl($filePath, 'userfiles-base-attachments-' . $attachment->fileName, 100, 100, 'min');
            $previewUrl = UTIL_File::getFileUrl($filePath, 'userfiles-base-attachments-' . $attachment->fileName, 600, 600, 'min');
            $itemType = IISNEWSFEEDPLUS_BOL_Service::getInstance()->getItemType($attachment);
            if ($itemType == 'video') {
                if ( isset($thumbnailList) && in_array($attachment->id,array_column($thumbnailList,'attachmentId')))
                {
                    $thumbnail = $thumbnailUrl . $attachment->id . '.png';
                    $playerPoster = 'poster="' . $thumbnail . '"';
                    $previewUrl = $thumbnail;
                } else {
                    $thumbnail = $videoThumbnailUrl;
                    $thumbnailControllerActivator = 'newsfeedVideoThumbnailCreator(\'' . $postAttachmentId . '\',\'' . $attachment->id . '\',\'' . $thumbnailController . '\');';
                    $previewUrl = $videoPreviewUrl;
                    $playerPoster = 'poster="' . $previewUrl . '" posterName="' . $attachment->id . '" posterController="' . $thumbnailController . '"';
                }
            } else if ($itemType == 'audio') {
                $thumbnail =  $audioThumbnailUrl;
                $previewUrl = $audioPreviewUrl;
                $playerPoster = 'poster="' . $previewUrl . '"';
            }

            $itemParams=array();
            $itemParams['itemType'] = $itemType;
            $itemParams['thumbnail'] = $thumbnail;
            $itemParams['previewUrl'] = $previewUrl;
            $itemParams['downloadUrl'] = $downloadUrl;
            $itemParams['playerPoster'] = $playerPoster;
            $itemParams['viewerId'] = $postAttachmentId;
            $itemParams['filename'] = $attachment->getOrigFileName();
            $itemParams['thumbnailCreator'] = $thumbnailControllerActivator;
            $attachmentItemsParams[] = $itemParams;
        }

        $firstItemPoster = '';
        $firstItemPreviewUrl = '';
        $firstItemDownloadUrl = '';
        $fileType = '';
        if (isset($attachmentItemsParams[0])) {
            $firstItemPoster = $attachmentItemsParams[0]['playerPoster'];
            $firstItemPreviewUrl = $attachmentItemsParams[0]['previewUrl'];
            $firstItemDownloadUrl = $attachmentItemsParams[0]['downloadUrl'];
            $fileType = $attachmentItemsParams[0]['itemType'];
        }

        $this->assign('fileType', '' );
        if($fileType == 'audio' || $fileType == 'video'){
            $this->assign('fileType', $fileType );
        }

        $thumbnailItemBarEnable = false;
        $nextPreviousEnable = false;
        $previewBoxEnable = false;

        if ($attachmentPreviewNumber > 0) {
            $previewBoxEnable = true;
        }
        if($attachmentPreviewNumber > 1){
            $thumbnailItemBarEnable = true;
        }
        if($attachmentPreviewNumber > 6){
            $nextPreviousEnable = true;
        }

        $this->assign('thumbnailItemBar', $thumbnailItemBarEnable );
        $this->assign('nextPrevious', $nextPreviousEnable );
        $this->assign('previewBox', $previewBoxEnable );
        $this->assign('firstItemPoster', $firstItemPoster );
        $this->assign('firstItemPreviewUrl', $firstItemPreviewUrl );
        $this->assign('firstItemDownloadUrl', $firstItemDownloadUrl );
        $this->assign('postAttachmentId', $postAttachmentId );
        $this->assign('itemBarWidth', $itemBarWidth );
        $this->assign('attachmentItemsParams', $attachmentItemsParams );
    }
}