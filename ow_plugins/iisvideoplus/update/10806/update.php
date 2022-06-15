<?php
/**
 * @author Seyed Ismail Mirvakili
 * Date: 6/11/2017
 * Time: 11:41 AM
 */

$maxUploadMaxFilesize = BOL_FileService::getInstance()->getUploadMaxFilesize();
$config = OW::getConfig();
if ( !$config->configExists('iisvideoplus', 'maximum_video_file_upload'))
{
    $config->addConfig('iisvideoplus', 'maximum_video_file_upload',$maxUploadMaxFilesize);
}