<?php


class IISPHOTOPLUS_CTRL_AjaxUpload extends OW_ActionController
{
    CONST STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    private $photoService;
    private $photoAlbumService;
    
    public function __construct()
    {
        parent::__construct();

        $this->photoService = PHOTO_BOL_PhotoService::getInstance();
        $this->photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();
    }
    
    public function init()
    {
        parent::init();
        
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }
        
        if ( !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $this->returnResponse(array(
                'status' => self::STATUS_ERROR,
                'result' => false,
                'msg' => OW::getLanguage()->text('photo', 'auth_upload_permissions')
            ));
        }
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

    private function isAvailableFile( $file )
    {
        return !empty($file['file']) && 
            $file['file']['error'] === UPLOAD_ERR_OK && 
            in_array($file['file']['type'], array('image/jpeg', 'image/png', 'image/gif'), true) &&
            $_FILES['file']['size'] <= $this->photoService->getMaxUploadFileSize() && 
            is_uploaded_file($file['file']['tmp_name']);
    }
    
    private function getErrorMsg( $file )
    {
        if ( $this->isAvailableFile($file) )
        {
            return null;
        }
        
        if ( !empty($file['file']['error']) )
        {
            switch ( $file['file']['error'] )
            {
                case UPLOAD_ERR_INI_SIZE:
                    return OW::getLanguage()->text('photo', 'error_ini_size');
                case UPLOAD_ERR_FORM_SIZE:
                    return OW::getLanguage()->text('photo', 'error_form_size');
                case UPLOAD_ERR_PARTIAL:
                    return OW::getLanguage()->text('photo', 'error_partial');
                case UPLOAD_ERR_NO_FILE:
                    return OW::getLanguage()->text('photo', 'error_no_file');
                case UPLOAD_ERR_NO_TMP_DIR:
                    return OW::getLanguage()->text('photo', 'error_no_tmp_dir');
                case UPLOAD_ERR_CANT_WRITE:
                    return OW::getLanguage()->text('photo', 'error_cant_write');
                case UPLOAD_ERR_EXTENSION:
                    return OW::getLanguage()->text('photo', 'error_extension');
                default:
                    return OW::getLanguage()->text('photo', 'no_photo_uploaded');                        
            }
        }
        else
        {
            return OW::getLanguage()->text('photo', 'no_photo_uploaded');
        }
    }
    
    public function ajaxSubmitPhotos( $params )
    {
        $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');
        // Check balance photo count == balanse count. Delete other photo
        if ( $status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE )
        {
            $this->returnResponse(array('result' => FALSE, 'msg' => $status['msg']));
        }
        
        $userId = OW::getUser()->getId();
        $photoTmpService = PHOTO_BOL_PhotoTemporaryService::getInstance();
        $tmpList = [];
        if ( (!strlen($albumName = htmlspecialchars(trim($_POST['album']))) || !strlen($albumName = htmlspecialchars(trim($_POST['album-name'])))) || count($tmpList = $photoTmpService->findUserTemporaryPhotos($userId, 'order')) === 0 )
        {
            $resp = array('result' => FALSE, 'msg' => OW::getLanguage()->text('photo', 'photo_upload_error'));
            
            $this->returnResponse($resp);
        }
        $form = new IISPHOTOPLUS_CLASS_AjaxUploadForm('user', $userId);
        if ( !$form->isValid($_POST))
        {
            $error = $form->getErrors();
            $resp = array('result' => FALSE);
                
            if ( !empty($error['album-name'][0]) )
            {
                $resp['msg'] = $error['album-name'][0];
            }
            else
            {
                $resp['msg'] = OW::getLanguage()->text('photo', 'photo_upload_error');
            }
            
            $this->returnResponse($resp);
        }

        $information = json_encode(
            array(
                'entityType' => (!isset($params['entityType']))?null:$params['entityType'],
                'entityId' => (!isset($params['entityId']))?null:$params['entityId'],
                'album-name' => htmlspecialchars(trim($_POST['album-name'])),
                'album' => $_POST['album'],
                'description' => $_POST['description'],
                'userId' => $userId,
                'tmpList'=> $tmpList,
                'desc' => $_POST['desc'],
		        'rotate' => $_POST['rotate'],
                'infoId' =>(!isset($_POST['infoId']))?null:$_POST['infoId']
            )
        );
       // IISPHOTOPLUS_BOL_Service::getInstance()->addStatusPhoto($information,$_POST['infoId'],$userId,time());
        $result['information'] =$information;
        $this->returnResponse($result);
    }

    public function upload( array $params = array() )
    {
        if ( $this->isAvailableFile($_FILES) )
        {
            $order = !empty($_POST['order']) ? (int) $_POST['order'] : 0;

            if ( ($id = PHOTO_BOL_PhotoTemporaryService::getInstance()->addTemporaryPhoto($_FILES['file']['tmp_name'], OW::getUser()->getId(), $order)) )
            {
                $fileUrl = PHOTO_BOL_PhotoTemporaryDao::getInstance()->getTemporaryPhotoUrl($id, 2);
                
                $this->returnResponse(array(
                    'status' => self::STATUS_SUCCESS,
                    'fileUrl' => $fileUrl . '?' . IISSecurityProvider::generateUniqueId(),
                    'id' => $id
                ));
            }
            else
            {
                $this->returnResponse(array(
                    'status' => self::STATUS_ERROR,
                    'msg' => OW::getLanguage()->text('photo', 'no_photo_uploaded')
                ));
            }
        }
        else
        {
            $msg = $this->getErrorMsg($_FILES);

            $this->returnResponse(array(
                'status' => self::STATUS_ERROR,
                'msg' => $msg
            ));
        }
    }
    
    public function delete( array $params = array() )
    {
        if ( !empty($_POST['id']) )
        {
            PHOTO_BOL_PhotoTemporaryService::getInstance()->deleteTemporaryPhoto((int)$_POST['id']);
        }
        
        exit();
    }
    
    private function returnResponse( $response )
    {
        if ( !OW_DEBUG_MODE )
        {
            ob_end_clean();
        }

        exit(json_encode($response));
    }

    public function checkFakeAlbumData( array $params = array() )
    {
        $form = new PHOTO_CLASS_CreateFakeAlbumForm();

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $this->returnResponse(array(
                'result' => true,
                'data' => array_merge($_POST, $form->getValues())
            ));
        }
        else
        {
            $this->returnResponse(array(
                'result' => false,
                'errors' => $form->getErrors()
            ));
        }
    }
}
