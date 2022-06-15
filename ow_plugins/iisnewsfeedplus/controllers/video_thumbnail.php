<?php

class IISNEWSFEEDPLUS_CTRL_VideoThumbnail extends OW_ActionController
{

    public function index()
    {
        $respondArray = array();
        $attachmentId = trim($_POST['videoName']);
        $videoFile = BOL_AttachmentDao::getInstance()->findById($attachmentId);
        $thumbnail = IISNEWSFEEDPLUS_BOL_ThumbnailDao::getInstance()->findById($attachmentId);

        if ( empty($_POST['videoName']) || empty($_POST['canvasData']) || !isset($videoFile) || !empty($thumbnail) )
        {
            $respondArray['messageType'] = 'error';
            $respondArray['message'] = '_ERROR_';
            echo json_encode($respondArray);
            exit;
        }

        $userService = OW::getUser();
        $currentUser = $userService->getUserObject();

        if( $userService != null && $userService->isAuthenticated() && $currentUser)
        {
            $imageName = $attachmentId . '.png';
            $tmpVideoImageFile = IISNEWSFEEDPLUS_BOL_Service::getInstance()->getThumbnailFileDir($imageName);
            $rawData = $_POST['canvasData'];
            $filteredData = explode(',', $rawData);

            $valid = IISSecurityProvider::createFileFromRawData($tmpVideoImageFile, $filteredData[1]);
            if( $valid )
            {
                IISNEWSFEEDPLUS_BOL_ThumbnailDao::getInstance()->addThumbnail($attachmentId,$currentUser->getId());
            }
        }
        exit;
    }

}