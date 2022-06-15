<?php
/**
 * Created by PhpStorm.
 * User: atenagh
 * Date: 1/21/2019
 * Time: 2:40 AM
 */
class IISEMOJI_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    public function dept()
    {
        $this->setPageTitle(OW::getLanguage()->text('iisemoji', 'admin_dept_title'));
        $this->setPageHeading(OW::getLanguage()->text('iisemoji', 'admin_dept_title'));
        $form = new Form('select_emoji_image');
        $this->addForm($form);
        $Type = new Selectbox('emoji_image');
        $Type->addOption('emojione','emojione');
        $Type->addOption('apple','apple');
        $Type->setRequired(true);
        $form->addElement($Type);

        $submit = new Submit('save');
        $submit->setValue(OW::getLanguage()->text('iisemoji', 'form_add_dept_submit'));
        $form->addElement($submit);

        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                $emojiType = $_POST['emoji_image'];
                try{
                OW::getConfig()->saveConfig('iisemoji', 'emojiType', $emojiType);
                }
                catch(Exception $e)
                {
                OW::getConfig()->addConfig('iisemoji', 'emojiType', $emojiType);
                }
                OW::getFeedback()->info(OW::getLanguage()->text('iisemoji', 'save_successful_message'));
            }
        }
    }
}