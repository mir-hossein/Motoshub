<?php
class IISPHOTOPLUS_MCTRL_AjaxUpload extends OW_MobileActionController
{

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
            'fileUrl'=> $fileUrl,
            'tempPhotoId'=>$id);
        exit(json_encode($response));
    }
    public function submitPhotos($params){
        $form = new IISPHOTOPLUS_MCLASS_AjaxUploadForm();
        if($form->isValid($_POST) && OW::getUser()->isAuthenticated()) {
            $values = $form->getValues();
            $userId = OW::getUser()->getId();
            $photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();
            $photoTmpService = PHOTO_BOL_PhotoTemporaryService::getInstance();
            if (!($album = $photoAlbumService->findAlbumByName(UTIL_HtmlTag::stripTagsAndJs(trim($values['album'])), $userId))) {
                $album = new PHOTO_BOL_PhotoAlbum();
                $album->name = UTIL_HtmlTag::stripTagsAndJs(trim($values['album']));
                $album->userId = $userId;
                $album->createDatetime = time();

                $photoAlbumService->addAlbum($album);
            }
            $tmpList = $photoTmpService->findUserTemporaryPhotos($userId, 'order');
            $tmpList = array_reverse($tmpList);
            $temporaryPhotos = [];
            $photoDescriptions = [];
            $photoRotates =[];
            foreach ($tmpList as $tmpPhoto) {
                $tmpId = $tmpPhoto['dto']->id;
                $desName = "desc{$tmpId}";
                $description = isset($_POST[$desName]) ? $_POST[$desName] : '';
//                $photo = $photoTmpService->moveTemporaryPhoto($tmpId, $album->id, $description);
//                $photoTmpService->deleteTemporaryPhoto($tmpId);
//                if($photo){
//                    $uploadedPhotos[]=$photo;
//                }
                $temporaryPhotos[] = $tmpPhoto['dto'];
                $photoDescriptions[] = array("{$tmpId}" => $description);
                $photoRotates[] = array("{$tmpId}" => "undefined");
            }
            list($entityType, $entityId) = $this->getEntity($params);
            //$response = $this->onSubmitComplete($entityType, $entityId, $album, $temporaryPhotos, $photoDescriptions);
            $response = array(
                'entityType' => (!isset($params['entityType']))?null:$params['entityType'],
                'entityId' => (!isset($params['entityId']))?null:$params['entityId'],
                'album-name' => $album->name,
                'album' => $album->name,
                'description' => $description,
                'userId' => $userId,
                'tmpList'=> $tmpList,
                'desc' => $photoDescriptions,
                'rotate' => $photoRotates,
                'infoId' =>(!isset($_POST['infoId']))?null:$_POST['infoId']
            );
            $this->returnResponse($response);
        }
    }
    protected function onSubmitComplete( $entityType, $entityId, PHOTO_BOL_PhotoAlbum $album, $photos,$descriptions)
    {
//        $photoService=PHOTO_BOL_PhotoService::getInstance();
//        $photoService->createAlbumCover($album->id, $photos);

        $userId = OW::getUser()->getId();
        $result = array(
                'result'=> true,
                'entityType' => $entityType,
                'entityId' => $entityId,
                'album-name' => $album->name,
                'albumId' =>$album->id,
                'userId' => $userId,
                'photos' => $photos,
                'descriptions' =>$descriptions
            );
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
    private function returnResponse( $response )
    {
        if ( !OW_DEBUG_MODE )
        {
            ob_end_clean();
        }

        exit(json_encode($response));
    }
}
