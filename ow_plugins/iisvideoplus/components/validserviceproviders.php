<?php

class IISVIDEOPLUS_CMP_Validserviceproviders extends OW_Validator
{

    /***
     * Constructor.
     *
     */
    public function __construct()
    {

    }

    /***
     * @param mixed $value
     * @return bool
     */
    public function isValid( $value )
    {
        if (isset($_POST['input_type']) && $_POST['input_type']=="aparat"){
            return true;
        }
        if ( empty($value) )
        {
            if(isset($_POST['id'])){
                $video = VIDEO_BOL_ClipService::getInstance()->findClipById($_POST['id']);
                $service = IISVIDEOPLUS_BOL_Service::getInstance();
                $videoDir = $service->getVideoFileDir($video->code);
                if(!OW::getStorage()->fileExists($videoDir) && (!isset($_POST['code']) || $_POST['code']==null)){
                    $this->setErrorMessage(OW::getLanguage()->text('iisvideoplus', 'error_both_fields_empty'));
                    return false;
                }
            }
            else if(!isset($_POST['code']) || $_POST['code']==null){
                $this->setErrorMessage(OW::getLanguage()->text('iisvideoplus', 'error_both_fields_empty'));
                return false;
            }
            return true;
        }

        $realName = $value['name'];
        $tmpName = $value['tmp_name'];
        switch ( false )
        {
            case is_uploaded_file($tmpName):
                $this->setErrorMessage(OW::getLanguage()->text('iisvideoplus', 'errors_video_upload'));
                return false;
        }

        $validFileExtensions = array('ogv','mp4','webm');
        $maxUploadSize = OW::getConfig()->getValue('base', 'attch_file_max_size_mb');

        if ( !empty($validFileExtensions) && !in_array(UTIL_File::getExtension($realName), $validFileExtensions) )
        {
            $this->setErrorMessage(OW::getLanguage()->text('iisvideoplus', 'upload_file_extension_is_not_allowed'));
            return false;
        }

        // get all bundle upload size
        $bundleSize = floor($value['size'] / 1024);

        if ( $maxUploadSize > 0 && $bundleSize > ($maxUploadSize * 1024) )
        {
            $this->setErrorMessage(OW::getLanguage()->text('iisvideoplus', 'upload_file_max_upload_filesize_error',array('videofilesize' => $maxUploadSize)));
            return false;
        }
        return true;
    }

}
