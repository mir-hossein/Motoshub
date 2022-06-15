<?php

class IISPROFILEMANAGEMENT_MCTRL_Edit extends OW_MobileActionController
{
    const EDIT_SYNCHRONIZE_HOOK = 'edit_synchronize_hook';
    const PREFERENCE_LIST_OF_CHANGES = 'base_questions_changes_list';

    private $questionService;
    private $userService;

    public function __construct()
    {
        parent::__construct();

        $this->questionService = BOL_QuestionService::getInstance();
        $this->userService = BOL_UserService::getInstance();

        $preference = BOL_PreferenceService::getInstance()->findPreference(self::PREFERENCE_LIST_OF_CHANGES);

        if ( empty($preference) )
        {
            $preference = new BOL_Preference();
        }

        $preference->key = self::PREFERENCE_LIST_OF_CHANGES;
        $preference->defaultValue = json_encode(array());
        $preference->sectionName = 'general';
        $preference->sortOrder = 100;

        BOL_PreferenceService::getInstance()->savePreference($preference);
    }

    public function index( $params )
    {
        $adminMode = false;
        $viewerId = OW::getUser()->getId();
        if (!OW::getUser()->isAuthenticated() || $viewerId === null) {
            throw new AuthenticateException();
        }

        if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=null)
        {
            $this->assign('backUrl',$_SERVER['HTTP_REFERER']);
        }

        if (!empty($params['userId']) && $params['userId'] != $viewerId) {

            if (OW::getUser()->isAdmin() || OW::getUser()->isAuthorized('base')) {
                $adminMode = true;
                $userId = (int)$params['userId'];
                $user = BOL_UserService::getInstance()->findUserById($userId);

                if (empty($user) || BOL_AuthorizationService::getInstance()->isSuperModerator($userId)) {
                    throw new Redirect404Exception();
                }

                $editUserId = $userId;
            } else {
                throw new Redirect403Exception();
            }
        } else {
            $editUserId = $viewerId;
            $this->assign('changePasswordUrl', OW::getRouter()->urlForRoute('iisprofilemanagement.edit.changepassword'));

            $user = OW::getUser()->getUserObject(); //BOL_UserService::getInstance()->findUserById($editUserId);
        }

        $changeList = BOL_PreferenceService::getInstance()->getPreferenceValue(self::PREFERENCE_LIST_OF_CHANGES, $editUserId);

        if (empty($changeList)) {
            $changeList = '[]';
        }

        $this->assign('changeList', json_decode($changeList, true));

        $isEditedUserModerator = BOL_AuthorizationService::getInstance()->isModerator($editUserId) || BOL_AuthorizationService::getInstance()->isSuperModerator($editUserId);

        $accountType = $user->accountType;

        // dispaly account type
        if (OW::getUser()->isAdmin() || OW::getUser()->isAuthorized('base')) {
            $accountType = !empty($_GET['accountType']) ? $_GET['accountType'] : $user->accountType;

            // get available account types from DB
            $accountTypes = BOL_QuestionService::getInstance()->findAllAccountTypes();

            $accounts = array();

            if (count($accountTypes) > 1) {
                /* @var $value BOL_QuestionAccountType */
                foreach ($accountTypes as $key => $value) {
                    $accounts[$value->name] = OW::getLanguage()->text('base', 'questions_account_type_' . $value->name);
                }

                if (!in_array($accountType, array_keys($accounts))) {
                    if (in_array($user->accountType, array_keys($accounts))) {
                        $accountType = $user->accountType;
                    } else {
                        $accountType = BOL_QuestionService::getInstance()->getDefaultAccountType()->name;
                    }
                }

                $editAccountType = new Selectbox('accountType');
                $editAccountType->setId('accountType');
                $editAccountType->setLabel(OW::getLanguage()->text('base', 'questions_question_account_type_label'));
                $editAccountType->setRequired();
                $editAccountType->setOptions($accounts);
                $editAccountType->setHasInvitation(false);
            } else {
                $accountType = BOL_QuestionService::getInstance()->getDefaultAccountType()->name;
            }
        }

        $language = OW::getLanguage();

        $this->setPageHeading($language->text('base', 'edit_index'));
        $this->setPageHeadingIconClass('ow_ic_user');
        // -- Edit form --

        $editForm = new EditQuestionForm('editForm', $editUserId);
        $editForm->setId('editForm');

        $this->assign('displayAccountType', false);

        // display account type
        if (!empty($editAccountType)) {
            $editAccountType->setValue($accountType);
            $editForm->addElement($editAccountType);

            OW::getDocument()->addOnloadScript(" $('#accountType').change(function() {
                
                var form = $(\"<form method='get'><input type='text' name='accountType' value='\" + $(this).val() + \"' /></form>\");
                $('body').append(form);
                $(form).submit();

            }  ); ");

            $this->assign('displayAccountType', true);
        }

        $userId = !empty($params['userId']) ? $params['userId'] : $viewerId;

        // add avatar field
        $editAvatar = OW::getClassInstance("BASE_CLASS_AvatarField", 'avatar', false);
        $editAvatar->setLabel(OW::getLanguage()->text('base', 'questions_question_user_photo_label'));
        $editAvatar->setValue(BOL_AvatarService::getInstance()->getAvatarUrl($userId, 1, null, true, false));
        $displayPhotoUpload = OW::getConfig()->getValue('base', 'join_display_photo_upload');

        // add the required avatar validator
        if ($displayPhotoUpload == BOL_UserService::CONFIG_JOIN_DISPLAY_AND_SET_REQUIRED_PHOTO_UPLOAD) {
            $avatarValidator = OW::getClassInstance("BASE_CLASS_AvatarFieldValidator", true, $userId);
            $editAvatar->addValidator($avatarValidator);
        }

        $editForm->addElement($editAvatar);

        $isUserApproved = BOL_UserService::getInstance()->isApproved($editUserId);
        $this->assign('isUserApproved', $isUserApproved);

        // password required
        $this->assign('passwordRequiredProfile', false);
        if(OW::getConfig()->configExists('iissecurityessentials','passwordRequiredProfile')){
            $passwordRequiredProfile=OW::getConfig()->getValue('iissecurityessentials','passwordRequiredProfile');
            if($passwordRequiredProfile){
                $password = new PasswordField('oldPasswordCheck');
                $password->setLabel(OW::getLanguage()->text("iissecurityessentials", "password"));
                $password->setRequired(true);
                $editForm->addElement($password);
                $this->assign('passwordRequiredProfile', true );
            }
        }

        // add submit button
        $editSubmit = new Submit('editSubmit');
        $editSubmit->addAttribute('class', 'ow_button ow_ic_save');

        $editSubmit->setValue($language->text('base', 'edit_button'));

        if ($adminMode && !$isUserApproved) {
            $editSubmit->setName('saveAndApprove');
            $editSubmit->setValue($language->text('base', 'save_and_approve'));

            // TODO: remove
            if (!$isEditedUserModerator) {
                // add delete button
                $script = UTIL_JsGenerator::newInstance()->jQueryEvent('input.delete_user_by_moderator', 'click', 'OW.Users.deleteUser(e.data.userId, e.data.callbackUrl, false);'
                    , array('e'), array('userId' => $userId, 'callbackUrl' => OW::getRouter()->urlForRoute('base_member_dashboard')));
                OW::getDocument()->addOnloadScript($script);
            }
        }

        $editForm->addElement($editSubmit);

        // prepare question list
        $questions = $this->questionService->findEditQuestionsForAccountType($accountType);

        $section = null;
        $questionArray = array();
        $questionNameList = array();

        foreach ($questions as $sort => $question) {
            if ($section !== $question['sectionName']) {
                $section = $question['sectionName'];
            }

            $questionArray[$section][$sort] = $questions[$sort];
            $questionNameList[] = $questions[$sort]['name'];
        }

        $this->assign('questionArray', $questionArray);

        $questionData = $this->questionService->getQuestionData(array($editUserId), $questionNameList);

        $questionValues = $this->questionService->findQuestionsValuesByQuestionNameList($questionNameList);
        // add question to form
        $editForm->addQuestions($questions, $questionValues, !empty($questionData[$editUserId]) ? $questionData[$editUserId] : array());
        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_JOIN_FORM_RENDER, array('form' => $editForm, 'controller' => $this,'forEditProfile' =>true)));
        // process form
        if (OW::getRequest()->isPost()) {
            if (isset($_POST['editSubmit']) || isset($_POST['saveAndApprove'])) {
                $this->process($editForm, $user->id, $questionArray, $adminMode);
            }
        }

        $this->addForm($editForm);

        $deleteUrl = OW::getRouter()->urlForRoute('iisprofilemanagement.delete_user');

        $this->assign('unregisterProfileUrl', $deleteUrl);

        // add langs to js
        $language->addKeyForJs('base', 'join_error_username_not_valid');
        $language->addKeyForJs('base', 'join_error_username_already_exist');
        $language->addKeyForJs('base', 'join_error_email_not_valid');
        $language->addKeyForJs('base', 'join_error_email_already_exist');
        $language->addKeyForJs('base', 'join_error_password_not_valid');
        $language->addKeyForJs('base', 'join_error_password_too_short');
        $language->addKeyForJs('base', 'join_error_password_too_long');
        $language->addKeyForJs('base', 'reset_password_not_equal_error_message');

        //include js
        $onLoadJs = " window.edit = new OW_BaseFieldValidators( " .
            json_encode(array(
                'formName' => $editForm->getName(),
                'responderUrl' => OW::getRouter()->urlFor("BASE_CTRL_Edit", "ajaxResponder"))) . ",
                                                        " . UTIL_Validator::EMAIL_PATTERN . ", " . UTIL_Validator::USER_NAME_PATTERN . ", " . $editUserId . " ); ";

        $this->assign('isAdmin', OW::getUser()->isAdmin());
        $this->assign('isEditedUserModerator', $isEditedUserModerator);
        $this->assign('adminMode', $adminMode);
        $approveEnabled = OW::getConfig()->getValue('base', 'mandatory_user_approve');
        $userDisapprove = new OW_Event(IISEventManager::ON_BEFORE_USER_DISAPPROVE_AFTER_EDIT_PROFILE, array('checkApproveEnabled' => true));
        OW::getEventManager()->trigger($userDisapprove);
        if(isset($userDisapprove->getData()['approveEnabled'])){
            $approveEnabled = $userDisapprove->getData()['approveEnabled'];
        }
        $this->assign('approveEnabled', $approveEnabled);

        OW::getDocument()->addOnloadScript('
            $("input.write_message_button").click( function() {
                    OW.ajaxFloatBox("BASE_CMP_SendMessageToEmail", [' . ((int)$editUserId) . '],
                    {
                        title: ' . json_encode($language->text('base', 'send_message_to_email')) . ',
                        width:600
                    });
                }
            );
        ');

        OW::getDocument()->addOnloadScript($onLoadJs);

        $jsDir = OW::getPluginManager()->getPlugin("base")->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir . "base_field_validators.js");

        if (!$adminMode) {
            $editSynchronizeHook = OW::getRegistry()->getArray(self::EDIT_SYNCHRONIZE_HOOK);

            if (!empty($editSynchronizeHook)) {
                $content = array();

                foreach ($editSynchronizeHook as $function) {
                    $result = call_user_func($function);

                    if (trim($result)) {
                        $content[] = $result;
                    }
                }

                $content = array_filter($content, 'trim');

                if (!empty($content)) {
                    $this->assign('editSynchronizeHook', $content);
                }
            }
        }
        OW::getEventManager()->trigger(new OW_Event('iis.on.before.profile.pages.view.render', array('pageType' => "editProfile")));
    }

    /***
     * @param EditQuestionForm $editForm
     * @param $userId
     * @param $questionArray
     * @param $adminMode
     */
    private function process($editForm, $userId, $questionArray, $adminMode)
    {
        if ( $editForm->isValid($_POST) )
        {
            $language = OW::getLanguage();
            $data = $editForm->getValues();
            $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array('userId' => $userId, 'method' => 'native', 'params' => $data,'forEditProfile'=>true));
            OW::getEventManager()->trigger($event);
            foreach ( $questionArray as $section )
            {
                foreach ( $section as $key => $question )
                {
                    switch ( $question['presentation'] )
                    {
                        case 'multicheckbox':

                            if ( is_array($data[$question['name']]) )
                            {
                                $answer = array();
                                foreach ($data[$question['name']] as $key => $value )
                                {
                                    $answer[] = (int)$value;
                                }
                                $data[$question['name']] = json_encode($answer);
                                //$data[$question['name']] = array_sum($data[$question['name']]);
                            }
                            else
                            {
                                $data[$question['name']] = 0;
                            }

                            break;
                    }
                }
            }

            // save user data
            if ( !empty($userId) )
            {
                if(OW::getConfig()->configExists('iissecurityessentials','passwordRequiredProfile')) {
                    $passwordRequiredProfile = OW::getConfig()->getValue('iissecurityessentials', 'passwordRequiredProfile');
                    if ($passwordRequiredProfile) {
                        $auth = false;
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

                $changesList = $this->questionService->getChangedQuestionList($data, $userId);
                if ( $this->questionService->saveQuestionsData($data, $userId) )
                {
                    // delete avatar
                    if ( empty($data['avatar']) )
                    {
                        if ( empty($_POST['avatarPreloaded']) )
                        {
                            BOL_AvatarService::getInstance()->deleteUserAvatar($userId);
                        }
                    }else{
                        $data['avatar']=htmlspecialchars($data['avatar']);
                    }
                    if(isset($_POST['avatarUpdated']) && $_POST['avatarUpdated'] == 1)
                    {
                        // update user avatar
                        BOL_AvatarService::getInstance()->createAvatar($userId);
                    }

                    if ( !$adminMode )
                    {
                        $isNeedToModerate = $this->questionService->isNeedToModerate($changesList);
                        $event = new OW_Event(OW_EventManager::ON_USER_EDIT, array('userId' => $userId, 'method' => 'native', 'moderate' => $isNeedToModerate));
                        OW::getEventManager()->trigger($event);

                        // saving changed fields
                        if ( BOL_UserService::getInstance()->isApproved($userId) )
                        {
                            $changesList = array();
                        }

                        BOL_PreferenceService::getInstance()->savePreferenceValue(self::PREFERENCE_LIST_OF_CHANGES, json_encode($changesList), $userId);
                        // ----
                        if ( OW::getConfig()->getValue('base', 'mandatory_user_approve') && OW::getUser()->isAuthenticated() && OW::getConfig()->getValue('iissecurityessentials', 'approveUserAfterEditProfile') == null &&  !OW::getUser()->isAdmin()) {
                            OW::getFeedback()->error(OW::getLanguage()->text('base', 'wait_for_approval'));
                            OW_User::getInstance()->logout();
                            $this->redirect(OW_URL_HOME);
                        }
                        else {
                            OW::getFeedback()->info($language->text('base', 'edit_successfull_edit'));
                            $this->redirect();
                        }
                    }
                    else
                    {
                        $event = new OW_Event(OW_EventManager::ON_USER_EDIT_BY_ADMIN, array('userId' => $userId));
                        OW::getEventManager()->trigger($event);

                        BOL_PreferenceService::getInstance()->savePreferenceValue(self::PREFERENCE_LIST_OF_CHANGES, json_encode(array()), $userId);

                        if ( !BOL_UserService::getInstance()->isApproved($userId) )
                        {
                            BOL_UserService::getInstance()->approve($userId);
                        }

                        OW::getFeedback()->info($language->text('base', 'edit_successfull_edit'));
                        $this->redirect(OW::getRouter()->urlForRoute('base_user_profile', array('username' => BOL_UserService::getInstance()->getUserName($userId))));
                    }
                }
                else
                {
                    OW::getFeedback()->info($language->text('base', 'edit_edit_error'));
                }
            }
            else
            {
                OW::getFeedback()->info($language->text('base', 'edit_edit_error'));
            }
        }
    }

    public function ajaxResponder()
    {
        $adminMode = false;

        if ( empty($_POST["command"]) || !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        $editorId = OW::getUser()->getId();

        if ( !OW::getUser()->isAuthenticated() || $editorId === null )
        {
            throw new AuthenticateException(); // TODO: Redirect to login page
        }

        $editedUserId = $editorId;
        if ( !empty($_POST["userId"]) )
        {
            $adminMode = true;

            $userId = (int) $_POST["userId"];
            $user = $this->userService->findUserById($userId);

            if ( empty($user) )
            {
                echo json_encode(array('result' => false));
                exit;
            }

            if ( !OW::getUser()->isAdmin() && ( !OW::getUser()->isAuthenticated() || OW::getUser()->getId() != $userId ) )
            {
                echo json_encode(array('result' => false));
                exit;
            }

            $editedUserId = $user->id;
        }

        $command = (string) $_POST["command"];

        switch ( $command )
        {
            case 'isExistEmail':

                $email = $_POST["value"];

                $validator = new editEmailValidator($editedUserId);
                $result = $validator->isValid($email);

                echo json_encode(array('result' => $result));

                break;

            case 'validatePassword':

                $result = false;

                if ( !$adminMode )
                {
                    $password = $_POST["value"];

                    $result = $this->userService->isValidPassword(OW::getUser()->getId(), $password);
                }

                echo json_encode(array('result' => $result));

                break;

            case 'isExistUserName':
                $username = $_POST["value"];

                $validator = new editUserNameValidator($editedUserId);
                $result = $validator->isValid($username);

                echo json_encode(array('result' => $result));

                break;

            default:
        }
        exit();
    }

    public function changePassword($params){
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

class editUserNameValidator extends OW_Validator
{
    private $userId = null;

    /**
     * editUserNameValidator constructor.
     * @param null $userId
     */
    public function __construct( $userId = null )
    {
        $this->userId = $userId;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function isValid( $value )
    {
        $language = OW::getLanguage();

        if ( !UTIL_Validator::isUserNameValid($value) )
        {
            $this->setErrorMessage($language->text('base', 'join_error_username_not_valid'));
            return false;
        }

        if ( BOL_UserService::getInstance()->isExistUserName($value) )
        {
            $userId = OW::getUser()->getId();

            if ( !empty($this->userId) )
            {
                $userId = $this->userId;
            }

            $user = BOL_UserService::getInstance()->findUserById($userId);

            if ( $value !== $user->username )
            {
                $this->setErrorMessage($language->text('base', 'join_error_username_already_exist'));
                return false;
            }
        }

        if ( BOL_UserService::getInstance()->isRestrictedUsername($value) && !OW::getUser()->isAdmin() )
        {
            $this->setErrorMessage($language->text('base', 'join_error_username_restricted'));
            return false;
        }

        return true;
    }

    /**
     * @see Validator::getJsValidator()
     *
     * @return string
     */
    public function getJsValidator()
    {
        return "{
                validate : function( value )
                {
                    // window.edit.validateUsername(false);
                    if( window.edit.errors['username']['error'] !== undefined )
                    {
                        throw window.edit.errors['username']['error'];
                    }
                },
                getErrorMessage : function(){
                    if( window.edit.errors['username']['error'] !== undefined ){ return window.edit.errors['username']['error']; }
                    else{ return " . json_encode($this->getError()) . " }
                }
        }";
    }
}

class editEmailValidator extends OW_Validator
{
    private $userId = null;

    /**
     * editEmailValidator constructor.
     * @param $userId
     */
    public function __construct( $userId )
    {
        if ( empty($userId) )
        {
            throw new InvalidArgumentException(" Invalid parameter userId ");
        }

        $this->userId = $userId;
    }

    /***
     * @param mixed $value
     * @return bool
     */
    public function isValid( $value )
    {
        $language = OW::getLanguage();

        if ( !UTIL_Validator::isEmailValid($value) )
        {
            $this->setErrorMessage($language->text('base', 'join_error_email_not_valid'));
            return false;
        }

        if ( BOL_UserService::getInstance()->isExistEmail($value) )
        {
            $userId = $this->userId;
            $user = BOL_UserService::getInstance()->findUserById($userId);

            if ( !$user || $value !== $user->email )
            {
                $this->setErrorMessage($language->text('base', 'join_error_email_already_exist'));
                return false;
            }
        }

        return true;
    }

    /**
     * @see Validator::getJsValidator()
     *
     * @return string
     */
    public function getJsValidator()
    {
        return "{
        	validate : function( value )
                {
                    // window.edit.validateEmail(false);
                    if( window.edit.errors['email']['error'] !== undefined )
                    {
                        throw window.edit.errors['email']['error'];
                    }
                },
        	getErrorMessage : function(){
                    if( window.edit.errors['email']['error'] !== undefined ){ return window.edit.errors['email']['error']; }
                    else{ return " . json_encode($this->getError()) . " }
                }
        }";
    }
}

class EditQuestionForm extends BASE_CLASS_UserQuestionForm
{
    private $userId = null;

    public function __construct( $name, $userId = null )
    {
        parent::__construct($name);

        if ( $userId != null )
        {
            $this->userId = $userId;
        }
    }

    /**
     * @param FormElement $formField
     * @param array $question
     * @return FormElement
     */
    protected function addFieldValidator( $formField, $question )
    {
        if ( (string) $question['base'] === '1' )
        {
            if ( $question['name'] === 'email' )
            {
                $formField->addValidator(new editEmailValidator($this->userId));
            }
            else if ( $question['name'] === 'username' )
            {
                $formField->addValidator(new editUserNameValidator($this->userId));
            }
        }

        return $formField;
    }
}

class NewPasswordValidator extends BASE_CLASS_PasswordValidator
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @see Validator::getJsValidator()
     *
     * @return string
     */
    public function getJsValidator()
    {
        return "{
                validate : function( value )
                {
                    if( !window.changePassword.validatePassword() )
                    {
                        throw window.changePassword.errors['password']['error'];
                    }
                },
                getErrorMessage : function()
                {
                       if( window.changePassword.errors['password']['error'] !== undefined ){ return window.changePassword.errors['password']['error'] }
                       else{ return ".json_encode($this->getError())." }
                }
        }";
    }
}

class OldPasswordValidator extends OW_Validator
{
    public function __construct()
    {
        $language = OW::getLanguage();
        $this->setErrorMessage($language->text('base', 'password_protection_error_message'));
    }

    public function isValid( $value )
    {
        $result = BOL_UserService::getInstance()->isValidPassword( OW::getUser()->getId(), $value );

        return $result;
    }

    /**
     * @see Validator::getJsValidator()
     *
     * @return string
     */
    public function getJsValidator()
    {
        return "{
                validate : function( value )
                {
                    if( !window.oldPassword.validatePassword() )
                    {
                        throw window.oldPassword.errors['password']['error'];
                    }
                },
                getErrorMessage : function()
                {
                       if( window.oldPassword.errors['password']['error'] !== undefined ){ return window.oldPassword.errors['password']['error'] }
                       else{ return ".json_encode($this->getError())." }
                }
        }";
    }
}