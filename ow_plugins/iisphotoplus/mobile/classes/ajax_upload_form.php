<?php
class IISPHOTOPLUS_MCLASS_AjaxUploadForm extends Form
{
    public function __construct( )
    {
        parent::__construct('upload-form');
        $this->setAjax();
        $this->setAjaxResetOnSuccess(false);
        $this->bindJsFunction(Form::BIND_SUCCESS,'function(data)
        {
            var res = data;
            photoplus_mcmp_ajaxUpload.close();
            var jsonString = JSON.stringify(data);
            $("#attachment_information_json").val(jsonString);
            
            var imageList = data.tmpList;
            $(".ow_photo_attachment_preview").empty();
            var attachmentElement = $(".ow_photo_attachment_preview");
            
            for (var image in imageList)
            {
                console.log(image);
                var fileUrl = imageList[image].imageSrc;
                console.log(fileUrl);                
                var image = \'<img class="owm_float_left" width="100px" src="\'+fileUrl+\'" />\';
                $(attachmentElement).append(image);
            }
        }
        ');
        $language = OW::getLanguage();

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);
        $this->setAction(OW::getRouter()->urlForRoute('iisphotoplus.ajax_upload_submit'));

        $fileField = new FileField('photo');
        //$fileField->setRequired(true);
        $this->addElement($fileField);

        // album Field
        $albumField = new TextField('album');
        $albumField->setRequired(true);
        $albumField->setHasInvitation(true);
        $albumField->setId('album_input');
        $albumField->setInvitation($language->text('photo', 'album_name'));
        $this->addElement($albumField);

        $uploadPhotos = new HiddenField('uploadPhotos');
        $uploadPhotos->addAttribute("id", "uploadPhotos");
        $this->addElement($uploadPhotos);

        $cancel = new Submit('cancel', false);
        $cancel->setValue($language->text('base', 'cancel_button'));
        $this->addElement($cancel);

        $submit = new Submit('submit', false);
        $this->addElement($submit);
    }
}
