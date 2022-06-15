<?php
class PHOTO_MCLASS_MultipleUploadForm extends Form
{
    public function __construct( )
    {
        parent::__construct('upload-form');

        $language = OW::getLanguage();

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

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

        $cancel = new Submit('cancel', false);
        $cancel->setValue($language->text('base', 'cancel_button'));
        $this->addElement($cancel);

        $submit = new Submit('submit', false);
        $this->addElement($submit);
    }
}