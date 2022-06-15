<?php

/***
 * Class IISPROFILEMANAGEMENT_MCMP_ChangePassword
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 */

class IISPROFILEMANAGEMENT_MCMP_ChangePassword extends OW_MobileComponent
{
    public function __construct($showChangePasswordFirst=false)
    {
        if($showChangePasswordFirst){
            $this->assign('showChangePasswordFirst',true);
        }
        parent::__construct();

        $language = OW::getLanguage();

        $form = new Form("change-user-password");
        $form->setId("change-user-password");

        $oldPassword = new PasswordField('oldPassword');
        $oldPassword->setLabel($language->text('base', 'change_password_old_password'));
        $oldPassword->addValidator(new OldPasswordValidator());
        $oldPassword->setRequired();
        
        $form->addElement( $oldPassword );

        $newPassword = new PasswordField('password');
        $newPassword->setLabel($language->text('base', 'change_password_new_password'));
        $newPassword->setRequired();
        $newPassword->addValidator( new NewPasswordValidator() );

        $form->addElement( $newPassword );

        $repeatPassword = new PasswordField('repeatPassword');
        $repeatPassword->setLabel($language->text('base', 'change_password_repeat_password'));
        $repeatPassword->setRequired();
        
        $form->addElement( $repeatPassword );

        $submit = new Submit("change");
        $submit->setLabel($language->text('base', 'change_password_submit'));

        $form->setAjax(true);
        $form->setAjaxResetOnSuccess(false);

        $form->addElement($submit);

        if ( OW::getRequest()->isAjax() )
        {
            $result = false;
            
            if ( $form->isValid($_POST) )
            {
                $data = $form->getValues();
                
                BOL_UserService::getInstance()->updatePassword( OW::getUser()->getId(), $data['password'] );

                $result = true;
            }
            
            echo json_encode( array( 'result' => $result ) );
            exit;
        }
        else
        {
            $messageError = $language->text('base', 'change_password_error');
            $messageSuccess = $language->text('base', 'change_password_success');
            $eventData = OW::getEventManager()->trigger(new OW_Event(IISEventManager::GET_PASSWORD_REQUIREMENT_PASSWORD_STRENGTH_INFORMATION));
            $labelPasswordStrength = '';
            $minimumCharacterPasswordStrength = '';
            if(isset($eventData->getData()['label']) && isset($eventData->getData()['minimumCharacter'])){
                $labelPasswordStrength = $eventData->getData()['label'];
                $minimumCharacterPasswordStrength = $eventData->getData()['minimumCharacter'];
            }
            $form->bindJsFunction(Form::BIND_SUCCESS, "function( json )
            {
            	if( json.result )
            	{
            	    var floatbox = OW.getActiveFloatBox();

                    if ( floatbox )
                    {
                        floatbox.close();
                    }

            	    OW.info(".json_encode($messageSuccess).");
                }
                else if(json.errorText){
                    OW.error(json.errorText);
                    if(typeof passwordStrengthMeter == 'function'){
                        passwordStrengthMeter('".$minimumCharacterPasswordStrength."', '".$labelPasswordStrength."');
                    }
                }
                else
                {
                    OW.error(".json_encode($messageError).");
                }

            } " );

            $this->addForm($form);

            $language->addKeyForJs('base', 'join_error_password_not_valid');
            $language->addKeyForJs('base', 'join_error_password_too_short');
            $language->addKeyForJs('base', 'join_error_password_too_long');
            $language->addKeyForJs('base', 'reset_password_not_equal_error_message');
            $language->addKeyForJs('base', 'password_protection_error_message');

            //include js
            $onLoadJs = " window.changePassword = new OW_BaseFieldValidators( " .
                                                    json_encode( array (
                                                            'formName' => $form->getName(),
                                                            'responderUrl' => OW::getRouter()->urlFor("BASE_CTRL_Join", "ajaxResponder"),
                                                            'passwordMaxLength' => UTIL_Validator::PASSWORD_MAX_LENGTH,
                                                            'passwordMinLength' => UTIL_Validator::PASSWORD_MIN_LENGTH ) ) . ",
                                                            " . UTIL_Validator::EMAIL_PATTERN . ", " . UTIL_Validator::USER_NAME_PATTERN . " ); ";


            $onLoadJs .= " window.oldPassword = new OW_ChangePassword( " .
                                                    json_encode( array (
                                                            'formName' => $form->getName(),
                                                            'responderUrl' => OW::getRouter()->urlFor("BASE_CTRL_Edit", "ajaxResponder") ) ) ." ); ";

            OW::getDocument()->addOnloadScript($onLoadJs);

            $jsDir = OW::getPluginManager()->getPlugin("base")->getStaticJsUrl();
            OW::getDocument()->addScript($jsDir . "base_field_validators.js");
            OW::getDocument()->addScript($jsDir . "change_password.js");
            OW::getEventManager()->trigger(new OW_Event('iis.on.before.profile.pages.view.render', array('pageType' => "editProfile")));
        }
    }
}