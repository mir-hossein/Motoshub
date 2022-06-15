<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iispasswordchangeinterval.bol
 * @since 1.0
 */
class IISPASSWORDCHANGEINTERVAL_BOL_Service
{
    CONST CATCH_REQUESTS_KEY = 'iispasswordchangeinterval.catch';
    CONST SECTION_PASSWORD_VALIDATION_INFORMATION = 1;
    CONST SECTION_PASSWORD_VALIDATION_VALID_USERS = 2;
    CONST SECTION_PASSWORD_VALIDATION_INVALID_USERS = 3;
    CONST EXPIRED_TIME_OF_TOKEN = 864000; //5 day

    CONST DEAL_WITH_EXPIRED_PASSWORD_NORMAL_WITHOUT_NOTIF = 'normal';
    CONST DEAL_WITH_EXPIRED_PASSWORD_NORMAL_WITH_NOTIF = 'normal_notif';
    CONST DEAL_WITH_EXPIRED_PASSWORD_FORCE_WITH_NOTIF = 'force_notif';

    private static $classInstance;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private $passwordValidationDao;

    private function __construct()
    {
        $this->passwordValidationDao = IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidationDao::getInstance();
    }

    /**
     * @param $time
     * @param $userId
     * @return IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidation
     */
    public function updateTimePasswordChanged($time = null, $userId = null)
    {
        return $this->passwordValidationDao->updateTimePasswordChanged($time, $userId);
    }

    public function deleteAllUsersFromPasswordValidation()
    {
        $this->passwordValidationDao->deleteAllUsersFromPasswordValidation();
    }

    /**
     * @return IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidation
     */
    public function getCurrentUser()
    {
        return $this->passwordValidationDao->getCurrentUser();
    }

    /**
     * @param $passwordValidation
     * @return bool
     */
    public function isChangable($passwordValidation)
    {
        $dealWithExpiredPassword = OW::getConfig()->getValue('iispasswordchangeinterval', 'dealWithExpiredPassword');
        if($this->isUserPasswordExpired($passwordValidation) || ($passwordValidation!=null && !$passwordValidation->valid)){
            if($dealWithExpiredPassword==IISPASSWORDCHANGEINTERVAL_BOL_Service::DEAL_WITH_EXPIRED_PASSWORD_NORMAL_WITHOUT_NOTIF){
                return false;
            }else if($dealWithExpiredPassword==IISPASSWORDCHANGEINTERVAL_BOL_Service::DEAL_WITH_EXPIRED_PASSWORD_NORMAL_WITH_NOTIF){
                $this->sendNotificationToCurrentUserForChangingPassword(OW::getUser()->getId());
                return false;
            }else if($dealWithExpiredPassword==IISPASSWORDCHANGEINTERVAL_BOL_Service::DEAL_WITH_EXPIRED_PASSWORD_FORCE_WITH_NOTIF){
                $this->sendNotificationToCurrentUserForChangingPassword(OW::getUser()->getId());
                return true;
            }
        }
        return false;
    }


    public function userPasswordUpdate(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['user'])) {
            $user = $params['user'];
            $userId = $user->id;
            $passwordValidation = $this->getUserByUserId($userId);

            if ($passwordValidation == null) {
                $token = md5(UTIL_String::getRandomString(8, 5));
                $this->passwordValidationDao->createPasswordValidationObject($userId, true, $token, time());
            } else {
                $passwordValidation->setPasswordTime(time());
                $passwordValidation->setValid(true);
                $passwordValidation->setToken(null);
                IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidationDao::getInstance()->save($passwordValidation);

                OW::getEventManager()->call('notifications.remove', array(
                    'entityType' => 'iispasswordchangeinterval',
                    'entityId' => $userId
                ));

            }

        }
    }


    /**
     * @return bool
     */
    public function isForceChangable()
    {
        $dealWithExpiredPassword = OW::getConfig()->getValue('iispasswordchangeinterval', 'dealWithExpiredPassword');
        if($dealWithExpiredPassword==IISPASSWORDCHANGEINTERVAL_BOL_Service::DEAL_WITH_EXPIRED_PASSWORD_NORMAL_WITHOUT_NOTIF){
            return false;
        }else if($dealWithExpiredPassword==IISPASSWORDCHANGEINTERVAL_BOL_Service::DEAL_WITH_EXPIRED_PASSWORD_NORMAL_WITH_NOTIF){
            return false;
        }else if($dealWithExpiredPassword==IISPASSWORDCHANGEINTERVAL_BOL_Service::DEAL_WITH_EXPIRED_PASSWORD_FORCE_WITH_NOTIF){
            return true;
        }
        return false;
    }

    /**
     * @param $userId
     */
    public function sendNotificationToCurrentUserForChangingPassword($userId)
    {
        $adminId = BOL_AuthorizationService::getInstance()->getSuperModeratorUserId();

        $notificationParams = array(
            'pluginKey' => 'iispasswordchangeinterval',
            'action' => 'change-password',
            'entityType' => 'iispasswordchangeinterval',
            'entityId' => $userId,
            'userId' => $userId,
            'time' => time(),
            'mobile_notification' => false,
        );
        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($adminId));

        $notificationData = array(
            'string' => array(
                'key' => 'iispasswordchangeinterval+description_change_password',
                'vars' => array('value' => $this->getUserProfileEditUrl())
            ),
            'avatar' => $avatars[$adminId]
        );
        $event = new OW_Event('notifications.add', $notificationParams, $notificationData);
        OW::getEventManager()->trigger($event);
    }

    /**
     * @param $passwordValidation
     * @return bool
     */
    public function isUserPasswordExpired($passwordValidation){
        $expired_time = OW::getConfig()->getValue('iispasswordchangeinterval', 'expire_time') * 60 * 60 * 24;
        if ($passwordValidation == null) {
            if (time() - OW::getUser()->getUserObject()->getJoinStamp() > $expired_time) {
                return true;
            }
        } else {
            if (time() - $passwordValidation->passwordTime > $expired_time) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $tokenTime
     * @return boolean
     */
    public function isTokenExpired($tokenTime){
        $expired_time = IISPASSWORDCHANGEINTERVAL_BOL_Service::EXPIRED_TIME_OF_TOKEN;
        if (time() - $tokenTime > $expired_time) {
            return true;
        } else {
            return false;
        }
    }

    public function setAllUsersPasswordInvalid($sendEmail)
    {

        $this->passwordValidationDao->setAllUsersPasswordInvalid($sendEmail);
    }

    public function setAllUsersPasswordExpire()
    {
        $this->passwordValidationDao->setAllUsersPasswordExpire();
    }

    /**
     * @param $userId
     */
    public function setUserPasswordValid($userId)
    {
        $this->passwordValidationDao->setUserPasswordValid($userId);
    }

    /**
     * @param $userId
     */
    public function setUserPasswordInvalid($userId)
    {
        $this->passwordValidationDao->setUserPasswordInvalid($userId);
    }

    /***
     * @param null $searchValue
     * @param int $count
     * @return array
     */
    public function getAllUsersValid($searchValue = null, $count = 20)
    {
        return $this->passwordValidationDao->getAllUsersValid($searchValue, $count);
    }

    /**
     * @param $regenerate
     * @param userId
     */
    public function resendLinkToUserByUserId($regenerate, $userId){
        $this->passwordValidationDao->resendLinkToUserByUserId($regenerate, $userId);
    }

    /***
     * @param null $searchValue
     * @param int $count
     * @return array
     */
    public function getAllUsersInvalid($searchValue = null, $count = 20)
    {
        return $this->passwordValidationDao->getAllUsersInvalid($searchValue, $count);
    }

    /**
     * @param int $sectionId
     * @return array
     */
    public function getSections($sectionId)
    {
        $sections = array();
        $sections[] = array(
            'sectionId' => IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_INFORMATION,
            'active' => $sectionId == IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_INFORMATION ? true : false,
            'url' => OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.section-id', array('sectionId' => IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_INFORMATION)),
            'label' => OW::getLanguage()->text('iispasswordchangeinterval','password_validation_header')
        );
        $sections[] = array(
            'sectionId' => IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_VALID_USERS,
            'active' => $sectionId == IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_VALID_USERS ? true : false,
            'url' => OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.section-id', array('sectionId' => IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_VALID_USERS)),
            'label' => OW::getLanguage()->text('iispasswordchangeinterval','valid_users_header')
        );
        $sections[] = array(
            'sectionId' => IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_INVALID_USERS,
            'active' => $sectionId == IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_INVALID_USERS ? true : false,
            'url' => OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.section-id', array('sectionId' => IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_INVALID_USERS)),
            'label' => OW::getLanguage()->text('iispasswordchangeinterval','invalid_users_header')
        );
        return $sections;
    }

    /**
     * @param $sectionId
     * @param null $searchValue
     * @return array
     */
    public function getUsersBySectionId($sectionId, $searchValue = null){
        if($sectionId==IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_VALID_USERS){
            return $this->getAllUsersValid($searchValue);
        }else if($sectionId==IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_INVALID_USERS){
            return $this->getAllUsersInvalid($searchValue);
        }
    }


    /**
     * @param $userId
     * @param $sectionId
     * @return string
     */
    public function getChangeStatusUrl($userId, $sectionId){
        if($sectionId==IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_VALID_USERS){
            return "javascript:if(confirm('".OW::getLanguage()->text('iispasswordchangeinterval','invalidate_user_password_warning')."')){location.href='" . OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.invalidate-password', array('userId' => $userId, 'sectionId' => $sectionId)) . "';}";
        }else if($sectionId==IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_INVALID_USERS){
            return "javascript:if(confirm('".OW::getLanguage()->text('iispasswordchangeinterval','validate_user_password_warning')."')){location.href='" . OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.validate-password', array('userId' => $userId, 'sectionId' => $sectionId)) . "';}";
        }
        return "";
    }

    /**
     * @param $token
     * @return IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidation
     */
    public function getUserByToken($token){
        return $this->passwordValidationDao->getUserByToken($token);
    }

    /**
     * @param $userId
     * @return IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidation
     */
    public function getUserByUserId($userId){
        return $this->passwordValidationDao->getUserByUserId($userId);
    }

    /**
     * @param OW_Event $event
     */
    public function onUserRegistered(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['forEditProfile']) && $params['forEditProfile']==true){
            return;
        }
        if(isset($params['userId'])){
            $user = BOL_UserService::getInstance()->findUserById($params['userId']);
            if($user != null){
                $userInformation = $this->getUserByUserId($user->id);
                if($userInformation != null) {
                    $this->setUserPasswordValid($user->getId());
                }
            }
        }
    }

    public function catchAllRequestsExceptions( BASE_CLASS_EventCollector $event )
    {
        $event->add(array(
            OW_RequestHandler::ATTRS_KEY_CTRL => 'IISPASSWORDCHANGEINTERVAL_CTRL_Iispasswordchangeinterval',
            OW_RequestHandler::ATTRS_KEY_ACTION => 'changeUserPassword'
        ));

        $event->add(array(
            OW_RequestHandler::ATTRS_KEY_CTRL => 'IISPASSWORDCHANGEINTERVAL_CTRL_Iispasswordchangeinterval',
            OW_RequestHandler::ATTRS_KEY_ACTION => 'checkValidatePassword'
        ));

    }

    /**
     * @param OW_Event $event
     */
    public function onBeforeResetPasswordFormRenderer(OW_Event $event)
    {
        $params = $event->getParams();
        if ($params['user']) {
            $user = $params['user'];
            $passwordValidation = $this->getUserByUserId($user->id);
            if ($this->isForceChangable() && $passwordValidation != null && (!$passwordValidation->valid || ($passwordValidation->token != null && $this->isTokenExpired($passwordValidation->tokenTime)))) {
                UTIL_Url::redirect(OW::getRouter()->urlForRoute('iispasswordchangeinterval.invalid-password', array('userId' => $passwordValidation->userId)));
            }
        }
    }

    /**
     * @param OW_Event $event
     */
    public function onAfterPasswordUpdate(OW_Event $event)
    {
        $params = $event->getParams();
        if ($params['userId'] != null) {
            $this->updateTimePasswordChanged(null, $params['userId']);
            if (!OW::getRequest()->isAjax()) {
                OW::getFeedback()->info(OW::getLanguage()->text('iispasswordchangeinterval', 'password_changed_successfully'));
            }
        }
    }

    /**
     * @param OW_Event $event
     */
    public function onAfterRoute(OW_Event $event)
    {
        $checkUriEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::BEFORE_CHECK_URI_REQUEST));
        if(isset($checkUriEvent->getData()['ignore']) && $checkUriEvent->getData()['ignore']){
            return;
        }
        if (OW::getRequest()->isAjax() || $this->isUrlInWhitelist() || $this->isUserInWhitelist()) {
            return;
        }
        $passwordValidation = $this->getCurrentUser();
        if($this->isChangable($passwordValidation)){
            $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
            if(!isset($event->getData()['isMobileVersion']) || $event->getData()['isMobileVersion']==false) {
                $attributeKey='IISPASSWORDCHANGEINTERVAL_CTRL_Iispasswordchangeinterval';
            }else{
                $attributeKey='IISPASSWORDCHANGEINTERVAL_MCTRL_Iispasswordchangeinterval';
            }
            OW::getRequestHandler()->setCatchAllRequestsAttributes(IISPASSWORDCHANGEINTERVAL_BOL_Service::CATCH_REQUESTS_KEY, array(
                OW_RequestHandler::ATTRS_KEY_CTRL => $attributeKey,
                OW_RequestHandler::ATTRS_KEY_ACTION => 'index'
            ));
            OW::getRequestHandler()->addCatchAllRequestsExclude(IISPASSWORDCHANGEINTERVAL_BOL_Service::CATCH_REQUESTS_KEY, 'IISPASSWORDCHANGEINTERVAL_CTRL_Iispasswordchangeinterval', 'index');
        }
    }

    /**
     * @return bool
     */
    public function isUserInWhitelist()
    {
        if (!OW::getUser()->isAuthenticated() || OW::getUser()->isAdmin()) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isUrlInWhitelist()
    {
        if (OW::getRequest()->getRequestUri() == 'sign-out' || strpos(OW::getRequest()->getRequestUri(), 'changeuserpassword') > -1 || strpos(OW::getRequest()->getRequestUri(), 'changeuserpasswordwithuserid') > -1 || strpos(OW::getRequest()->getRequestUri(), 'checkvalidatepassword') > -1 || strpos(OW::getRequest()->getRequestUri(), 'resendlLink') > -1) {
            return true;
        }

        return false;
    }

    /**
     * @param BASE_CLASS_EventCollector $event
     */
    function on_notify_actions(BASE_CLASS_EventCollector $event)
    {
        $event->add(array(
            'section' => 'iispasswordchangeinterval',
            'action' => 'change-password',
            'description' => OW::getLanguage()->text('iispasswordchangeinterval', 'description_change_password', array('value' => $this->getUserProfileEditUrl())),
            'selected' => true,
            'sectionLabel' => OW::getLanguage()->text('iispasswordchangeinterval', 'title_change_password'),
            'sectionIcon' => 'ow_ic_clock'
        ));
    }

    function getUserProfileEditUrl(){
        $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
        if(!isset($event->getData()['isMobileVersion']) || $event->getData()['isMobileVersion']==false) {
            return OW::getRouter()->urlForRoute('base_edit');
        }else{
            $plugin = BOL_PluginDao::getInstance()->findPluginByKey('iisprofilemanagement');
            if (isset($plugin) && $plugin->isActive()) {
                return OW::getRouter()->urlForRoute('iisprofilemanagement.edit');
            }
            return OW::getRouter()->urlForRoute('base_user_profile', array('username'=> OW::getUser()->getUserObject()->username));
        }
    }

}
