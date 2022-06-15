<?php

class BASE_CTRL_MediaPanel extends OW_ActionController
{
    /**
     * @var BASE_CMP_Menu
     */
    private $menu;
    private $id;

    public function __construct()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            $this->setVisible(false);
            return;
        }

        if ( !OW::getRequest()->isAjax() ) 
        {
            OW::getDocument()->getMasterPage()->setTemplate(OW::getThemeManager()->getMasterPageTemplate(OW_MasterPage::TEMPLATE_BLANK));
            OW::getDocument()->addStyleDeclaration(".ow_footer{display:none;}");
            OW::getEventManager()->trigger(new OW_Event('iismenu.hide.unwanted.element'));
        }

        if(isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'from-url')===false && strpos($_SERVER['REQUEST_URI'], 'fromurl')!==false){
            throw new Redirect404Exception();
        }
    }

    public function ajaxUpload( $params )
    {
        $pluginKey = $params['pluginKey'];
        $result = array();

        if (OW::getRequest()->isPost())
        {
            if ( !empty($_POST['command']) && $_POST['command'] == 'image-upload' )
            {
                if(isset($_FILES['file']['tmp_name']))
                $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event('iisclamav.is_file_clean', array('path' => $_FILES['file']['tmp_name'])));
                if (isset($checkAnotherExtensionEvent->getData()['clean'])) {
                    $isClean = $checkAnotherExtensionEvent->getData()['clean'];
                    if (!$isClean) {
                        $result = array(
                            'error_message' => OW::getLanguage()->text('iisclamav', 'virus_file_found', array('file' => $_FILES['file']['name'])),
                        );
                        die(json_encode($result));
                    }
                }
                $imageId = UploadImageForm::addFile($pluginKey);

                if ( is_numeric($imageId) )
                {
                    $img = BOL_MediaPanelService::getInstance()->findImage($imageId);
                    $filename = isset($img->getData()->filename)?$img->getData()->filename:$img->getData()->name;

                    $result = array(
                        'file_url' => OW::getStorage()->
                                getFileUrl(OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . $img->id . '-' . $filename),
                    );
                }
                else {
                    $result = array(
                        'error_message' => $imageId,
                    );
                }
            }
        }

        die(json_encode($result));
    }

    public function index( $params )
    {
        $pluginKey = $params['pluginKey'];

        $this->initMenu($params);
        $this->addComponent('menu', $this->menu);
        $this->menu->getElement('upload')->setActive(true);

        $form = new UploadImageForm();

        if ( !empty($_POST['command']) && $_POST['command'] == 'image-upload' && $form->isValid($_POST))
        {
            UploadImageForm::process($pluginKey, $params);
        }

        $this->assign('maxSize', OW::getConfig()->getValue('base', 'tf_max_pic_size'));
        
        $this->assign('pageSimple', true);
        $this->addForm($form);
    }

    public function gallery( $params )
    {

        $service = BOL_MediaPanelService::getInstance();
        if ( OW::getRequest()->isPost() )
        {
            $userId = OW::getUser()->getId();
            if ( empty($userId) )
            {
                throw new Exception('Guests can\'t view this page');
            }

            if($_POST['command']=='delete-image') {
                $imgId = intval($_POST['img-id']);

                if ($imgId <= 0) {
                    throw new Redirect404Exception();
                }

                $img = $service->findImage($imgId);
                if(isset($img)) {
                    $service->deleteById($imgId);
                }

                OW::getFeedback()->info(OW::getLanguage()->text('base', 'media_panel_file_deleted'));
                $this->redirect();
            }
        }

        $pluginKey = $params['pluginKey'];
        $this->initMenu($params);
        $this->addComponent('menu', $this->menu);
        $this->menu->getElement('gallery');

        $list = $service->findGalleryImages($pluginKey, OW::getUser()->getId(), 0, 500);
        $list = array_reverse($list);
        $images = array();

        foreach ( $list as $img )
        {
            $filename = isset($img->getData()->filename)?$img->getData()->filename:$img->getData()->name;
            $images[] = array(
                'dto' => $img,
                'data' => $img->getData(),
                'url' => OW::getStorage()->getFileUrl(OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . $img->id . '-' . $filename),
                'sel' => !empty($params['pid']) && $img->getId() == $params['pid'],
            );
        }

        $this->assign('images', $images);
        $this->assign('id', UTIL_HtmlTag::escapeHtml($params['id']));
        $this->assign('pageSimple', true);
    }
    public function fromUrl( $params )
    {
        $this->initMenu($params);
        $this->addComponent('menu', $this->menu);
        $this->assign('elid', UTIL_HtmlTag::escapeHtml($params['id']));
        $this->assign('pageSimple', true);
    }
    private function initMenu( $params )
    {
        $this->menu = BOL_MediaPanelService::getInstance()->initMenu($params);
    }
}

class UploadImageForm extends Form
{

    public function __construct()
    {
        parent::__construct('image-upload');

        $this->setEnctype('multipart/form-data');

        $hidden = new HiddenField('command');

        $hidden->setValue('image-upload');

        $this->addElement($hidden);

        $hiddenMaxSize = new HiddenField('MAX_FILE_SIZE');

        $hiddenMaxSize->setValue(intval(OW::getConfig()->getValue('base', 'tf_max_pic_size')) * 1000000);

        $fileInput = new FileField('file');

        $fileInput->setLabel(OW::getLanguage()->text('base', 'tf_img_choose_file'))->setRequired(true);

        $this->addElement($fileInput);

        $titleTextField = new TextField('title');
        $titleTextField->setLabel(OW::getLanguage()->text('base', 'subject_label'));
        $this->addElement($titleTextField);

        $submit = new Submit('submit');

        $submit->setValue(OW::getLanguage()->text('base', 'upload'));

        $this->addElement($submit);

        return $this;
    }

    /**
     * Add file
     * 
     * @param string $plugin
     * @return integer|string
     */
    public static function addFile( $plugin )
    {
        $uploaddir = OW::getPluginManager()->getPlugin('base')->getUserFilesDir();
        $name = $_FILES['file']['name'];

        if ( !UTIL_File::validateImage($name) )
        {
            return OW::getLanguage()->text('base', 'invalid_file_type_acceptable_file_types_jpg_png_gif');
        }

        $tmpname = $_FILES['file']['tmp_name'];

        if ( (int) $_FILES['file']['size'] > (float) OW::getConfig()->getValue('base', 'tf_max_pic_size') * 1024 * 1024 )
        {
            return OW::getLanguage()->text('base', 'upload_file_max_upload_filesize_error');
        }

        $image = new UTIL_Image($tmpname);
        $height = $image->getHeight();
        $width = $image->getWidth();

        $file_name = IISSecurityProvider::generateUniqueId() . '.' . UTIL_File::getExtension($name);

        if(isset($_POST['title']) && strlen($_POST['title'])>0){
            $name = UTIL_HtmlTag::stripTagsAndJs( $_POST['title']);
        }

        $id = BOL_MediaPanelService::getInstance()->add($plugin, 'image', OW::getUser()->getId(), array('name' => $name, 'filename' => $file_name, 'height' => $height, 'width' => $width));
        $new_path = $uploaddir . $id . '-' . $file_name;


        $fileInfo = $_FILES['file'];
        if ( in_array(UTIL_File::getExtension($fileInfo['name']), array('jpg', 'jpeg', 'png', 'bmp')) )
        {
            try
            {
                $image->resizeImage(UTIL_Image::DIM_FULLSCREEN_WIDTH, UTIL_Image::DIM_FULLSCREEN_HEIGHT)
                    ->orientateImage()
                    ->saveImage($new_path);
                $image->destroy();
            }
            catch ( Exception $e )
            {
                throw new InvalidArgumentException(OW::getLanguage()->text('base', 'upload_file_fail'));
            }
        }
        else
        {
            OW::getStorage()->copyFile($tmpname, $new_path);
        }
        OW::getStorage()->removeFile($tmpname, true);
        
        return $id;
    }

    public static function process( $plugin, $params )
    {
        $imageId = self::addFile($plugin);

        if (!is_numeric($imageId)) {
            OW::getFeedback()->error($imageId);
            OW::getApplication()->redirect();
        }

        $params['pid'] = $imageId;
        OW::getApplication()->redirect(OW::getRouter()->urlFor('BASE_CTRL_MediaPanel', 'gallery', $params) . '#bottom');
    }
}
