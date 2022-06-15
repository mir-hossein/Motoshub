<?php


class IISPROFILEMANAGEMENT_MCTRL_DeleteUser extends OW_MobileActionController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index( $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        if ( OW::getUser()->isAdmin() )
        {
            throw new Redirect404Exception();
        }

        $newDeletePath = OW::getEventManager()->trigger(new OW_Event('base.before.action_user_delete', array('href' => '', 'userId' => 'me')));
        if(isset($newDeletePath->getData()['href'])){
            $href = $newDeletePath->getData()['href'];
            OW::getApplication()->redirect($href);
            exit();
        }

        $form = new Form('deleteUser');
        $form->setMethod(Form::METHOD_POST);

        $submit = new Submit('submit');
        $submit->setValue(OW::getLanguage()->text('base','delete_user_delete_button'));
        $submit->addAttribute('class', 'ow_button ow_ic_delete ow_red');
        $form->addElement($submit);

        $cancel = new Submit('cancel');
        $cancel->setValue(OW::getLanguage()->text('base','delete_user_cancel_button'));
        $form->addElement($cancel);

        $this->assign('passwordRequiredProfile', false);
        if(OW::getConfig()->configExists('iissecurityessentials','passwordRequiredProfile')){
            $passwordRequiredProfile=OW::getConfig()->getValue('iissecurityessentials','passwordRequiredProfile');
            if($passwordRequiredProfile){
                $password = new PasswordField('oldPasswordCheck');
                $password->setLabel(OW::getLanguage()->text("iissecurityessentials", "password"));
                $password->setRequired(true);
                $form->addElement($password);
                $this->assign('passwordRequiredProfile', true );
            }
        }

        $fieldCaptcha = new CaptchaField('captcha');
        $fieldCaptcha->setLabel(OW::getLanguage()->text('base', 'form_label_captcha'));
        $form->addElement($fieldCaptcha);
        $this->assign('captcha_present', 'true');

        $this->assign('returnUrl', OW::getRouter()->urlForRoute('iisprofilemanagement.edit'));
        $this->addForm($form);
        $language = OW::getLanguage();

        $this->setPageHeading($language->text('base', 'delete_user_index'));

        $userId = OW::getUser()->getId();

        if ( OW::getRequest()->isPost() && !(OW::getRequest()->isAjax()) && $form->isValid($_POST))
        {
            if ( isset( $_POST['submit'] ) )
            {
                if(OW::getConfig()->configExists('iissecurityessentials','passwordRequiredProfile')) {
                    $passwordRequiredProfile = OW::getConfig()->getValue('iissecurityessentials', 'passwordRequiredProfile');
                    if ($passwordRequiredProfile) {
                        $auth = false;
                        $data = $form->getValues();
                        if ( !empty($data['oldPasswordCheck']) )
                        {
                            $auth = BOL_UserService::getInstance()->isValidPassword( OW::getUser()->getId(), $data['oldPasswordCheck'] );
                        }
                        if(!$auth){
                            OW::getFeedback()->error($language->text('base', 'password_protection_error_message'));
                            return;
                        }
                    }
                }
                OW::getUser()->logout();

                BOL_UserService::getInstance()->deleteUser($userId, true);

                $this->redirect( OW::getRouter()->urlForRoute('base_index') );
            }

            if ( isset( $_POST['cancel'] ) )
            {
                $this->redirect( OW::getRouter()->urlForRoute('iisprofilemanagement.edit') );
            }
        }
    }
}
