<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 3/4/18
 * Time: 8:57 AM
 */
class MAILBOX_CLASS_EditMessageForm extends Form
{
    const FORM_NAME = 'edit_message_form';

    /**
     * @param MAILBOX_BOL_Message $message
     */
    public function __construct($message)
    {
        parent::__construct(self::FORM_NAME);
        $language = OW::getLanguage();

        $this->setAjax();
        $this->setAjaxResetOnSuccess(false);
        $this->setAction(OW::getRouter()->urlForRoute('mailbox_edit_message'));
        $this->bindJsFunction(self::BIND_SUCCESS, 'function(data){window.messageEdited(data);}');

        $field = new Textarea('message');
        $field->setRequired();
        $field->setLabel(OW::getLanguage()->text('mailbox','text_message_invitation'));
        $field->setValue(str_replace('<br />','',MAILBOX_BOL_ConversationService::getInstance()->decodeMessage($message)));
        $field->setHasInvitation(true);
        $field->setInvitation($language->text('mailbox', 'text_message_invitation'));
        $this->addElement($field);

        $field = new HiddenField('messageId');
        $field->setValue($message->getId());
        $this->addElement($field);

        $submit = new Submit('save');
        $submit->setValue($language->text('mailbox', 'label_save'));
        $this->addElement($submit);
    }
}