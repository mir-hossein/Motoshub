<?php
/**
 * Created by PhpStorm.
 * User: Milad Heshmati
 * Date: 5/7/2019
 * Time: 10:01 AM
 */

/***
 * Class IISNEWSFEEDPLUS_CMP_RenderAttachmentNoPreview
 */
class IISNEWSFEEDPLUS_CMP_RenderAttachmentNoPreview extends OW_Component
{
    public function __construct($attachmentsList = array())
    {
        parent::__construct();
        $attachmentService = BOL_AttachmentService::getInstance();
        $attachmentDir = $attachmentService->getAttachmentsDir();
        $AttachmentItemsParams = array();

        foreach ($attachmentsList as $attachment) {
            $filePath = $attachmentDir . $attachment->fileName;
            $downloadUrl = OW::getStorage()->getFileUrl($filePath);
            $itemParams = array();
            $itemParams['downloadUrl'] = $downloadUrl;
            $itemParams['filename'] = $attachment->getOrigFileName();
            $itemParams['extension'] = strtolower(pathinfo($attachment->getOrigFileName())['extension']);
            $AttachmentItemsParams[] = $itemParams;
        }

        $this->assign('attachmentItemsParams',$AttachmentItemsParams);
        $this->assign('attachmentBox',true);
    }
}


