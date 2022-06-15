<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 1:10 PM
 */
class MAILBOX_CMP_EditMessage extends OW_Component
{
    /**
     * Constructor.
     *
     * @param $messageId
     */
    public function __construct( $messageId )
    {
        parent::__construct();

        $message = MAILBOX_BOL_MessageDao::getInstance()->findById($messageId);
        $form = new MAILBOX_CLASS_EditMessageForm($message);
        $this->addForm($form);
    }
}