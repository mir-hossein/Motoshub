<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iispasswordchangeinterval.bol
 * @since 1.0
 */
class IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidationDao extends OW_BaseDao
{
    private static $classInstance;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function getDtoClassName()
    {
        return 'IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidation';
    }

    public function getTableName()
    {
        return OW_DB_PREFIX . 'iispasswordchangeinterval_password_validation';
    }

    public function resendLinkToUserByUserId($regenerate, $userId)
    {
        $user = BOL_UserService::getInstance()->findUserById($userId);
        $passwordValidation = $this->getUserByUserId($user->getId());
        if ($regenerate || empty($passwordValidation->token)) {
            $passwordValidation->setToken(md5(UTIL_String::getRandomString(8, 5)));
            $passwordValidation->setTokenTime(time());
            $this->save($passwordValidation);
        }
        OW::getMailer()->addToQueue($this->createEmailForChangingPassword($user->getEmail(), $passwordValidation->token, $user->username));
    }

    public function setAllUsersPasswordInvalid($sendEmail)
    {
        $this->deleteAllUsersFromPasswordValidation();

        $numberOfUsers = BOL_UserService::getInstance()->count(true);
        $users = BOL_UserService::getInstance()->findList(0, $numberOfUsers, true);
        $savedPasswordValidation = array();

        foreach ($users as $key => $user) {
            $token = md5(UTIL_String::getRandomString(8, 5));
            $savedPasswordValidation[] = $this->createPasswordValidationObject($user->id, false, $token);
            IISPASSWORDCHANGEINTERVAL_BOL_Service::getInstance()->sendNotificationToCurrentUserForChangingPassword($user->getId());
        }

        if($sendEmail) {
            $this->sendEmailForChangingPassword($savedPasswordValidation);
        }
    }

    /***
     * @return mixed
     */
    public function setAllUsersPasswordExpire() {
        $numberOfUsers = BOL_UserService::getInstance()->count(true);
        $users = BOL_UserService::getInstance()->findList(0, $numberOfUsers, true);
        $savedPasswordValidation = array();

        foreach ($users as $key => $user) {
            $ex = new OW_Example();
            $ex->andFieldEqual('userid', $user->id);
            $old_row = $this->findObjectByExample($ex);
            if(!isset($old_row)){
                $token = md5(UTIL_String::getRandomString(8, 5));
                $savedPasswordValidation[] = $this->createPasswordValidationObject($user->id, true, $token);
            }
        }

        //set time to zero
        $q = 'UPDATE '.$this->getTableName().' SET `passwordTime` = 0;';
        return $this->dbo->query($q);
    }

    /**
     * @param $userId
     * @param $valid
     * @param $token
     * @param $time
     * @return IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidation
     */
    public function createPasswordValidationObject($userId, $valid, $token, $time = null)
    {
        if($time==null){
            $time = time();
        }
        $passwordValidation = new IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidation();
        $passwordValidation->setUserId($userId);
        $passwordValidation->setValid($valid);
        $passwordValidation->setToken($token);
        $passwordValidation->setTokenTime(time());
        $passwordValidation->setPasswordTime($time);
        $ex = new OW_Example();
        $ex->andFieldEqual('userId', $userId);
        $this->deleteByExample($ex);
        $this->save($passwordValidation);
        return $passwordValidation;
    }

    /**
     * @param $passwordValidations
     */
    public function sendEmailForChangingPassword($passwordValidations)
    {
        $mails = array();

        if (is_array($passwordValidations)) {
            foreach ($passwordValidations as $key => $passwordValidation) {
                $user = BOL_UserService::getInstance()->findUserById($passwordValidation->userId);
                $mails[] = $this->createEmailForChangingPassword($user->getEmail(), $passwordValidation->token, $user->getUsername());
            }
        } else {
            $user = BOL_UserService::getInstance()->findUserById($passwordValidations->userId);
            $mails[] = $this->createEmailForChangingPassword($user->getEmail(), $passwordValidations->token, $user->getUsername());
        }

        OW::getMailer()->addListToQueue($mails);
    }

    /**
     * @param $email
     * @param $token
     * @param $username
     * @return BASE_CLASS_Mail
     */
    public function createEmailForChangingPassword($email, $token, $username)
    {
        $mail = OW::getMailer()->createMail();
        $mail->addRecipientEmail($email);
        $mail->setSubject(OW::getLanguage()->text('iispasswordchangeinterval', 'email_for_changing_password_subject'));
        $mail->setHtmlContent($this->getEmailHTMLContent($token, $username));
        $mail->setTextContent($this->getEmailHTMLContent($token, $username));
        return $mail;
    }

    /**
     * @param $token
     * @param $username
     * @return string
     */
    public function getEmailHTMLContent($token, $username)
    {
        $html = '<p>' . OW::getLanguage()->text('iispasswordchangeinterval', 'email_for_changing_password_description', array('username' => $username)) . '</p>';
        $html .= '<br/>';
        $html .= OW::getRouter()->urlForRoute('iispasswordchangeinterval.check-validate-password', array('token' => $token));
        return $html;
    }

    public function deleteAllUsersFromPasswordValidation()
    {
        $this->dbo->delete('TRUNCATE TABLE ' . $this->getTableName());
    }

    /**
     * @param $userId
     */
    public function setUserPasswordValid($userId)
    {

        $ex = new OW_Example();
        $ex->andFieldEqual('userid', $userId);
        $passwordValidation = $this->findObjectByExample($ex);
        $passwordValidation->setValid(true);
        $passwordValidation->setToken(null);
        $this->save($passwordValidation);
    }

    /**
     * @param $userId
     */
    public function setUserPasswordInvalid($userId)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('userid', $userId);
        $passwordValidation = $this->findObjectByExample($ex);
        if($passwordValidation == null){
            $user = BOL_UserService::getInstance()->findUserById($userId);
            $passwordValidation = $this->createPasswordValidationObject($user->id, false, md5(UTIL_String::getRandomString(8, 5)));
        }else {
            $passwordValidation->setValid(false);
            $passwordValidation->setToken(md5(UTIL_String::getRandomString(8, 5)));
            $passwordValidation->setTokentime(time());
            $this->save($passwordValidation);
        }

        $this->sendEmailForChangingPassword($passwordValidation);
    }

    /***
     * @param null $searchValue
     * @param int $count
     * @return array
     */
    public function getAllUsersValid($searchValue = null, $count = 20)
    {
        $markedUserIDs = array();
        $result = array();

        if(empty($searchValue)) {
            $ex = new OW_Example();
            $ex->andFieldEqual('valid', true);
            if($count != false) {
                $ex->setLimitClause(0, $count);
            }
            $activatedUsers = $this->findListByExample($ex);
            foreach($activatedUsers as $activatedUser){
                if(!in_array($activatedUser->userId, $markedUserIDs)){
                    $markedUserIDs[] = $activatedUser->userId;
                    $result[] = BOL_UserService::getInstance()->findUserById($activatedUser->userId);
                }
            }

            if($count != false) {
                $remainedCount = $count - sizeof($activatedUsers);
                $activeUsersNotInvalidatedYet = $this->getValidUsersFromUserTable($remainedCount);
                foreach ($activeUsersNotInvalidatedYet as $activeUserNotInvalidatedYet) {
                    if (!in_array($activeUserNotInvalidatedYet->id, $markedUserIDs)) {
                        $markedUserIDs[] = $activeUserNotInvalidatedYet->id;
                        $result[] = $activeUserNotInvalidatedYet;
                    }
                }
            }

            return $result;
        }

        $ex = new OW_Example();
        $ex->andFieldEqual('valid', true);
        if($count != false) {
            $ex->setLimitClause(0, $count);
        }
        $resultsWithoutUsername = $this->findListByExample($ex);
        $resultsByUsername = array();
        foreach ($resultsWithoutUsername as $resultWithoutUsername){
            $username = BOL_UserService::getInstance()->findUserById($resultWithoutUsername->userId)->getUsername();
            if (strpos($username, $searchValue) !== false) {
                $resultsByUsername[] = $resultWithoutUsername;
            }
        }
        foreach($resultsByUsername as $resultByUsername){
            if(!in_array($resultByUsername->userId, $markedUserIDs)){
                $markedUserIDs[] = $resultByUsername->userId;
                $result[] = BOL_UserService::getInstance()->findUserById($resultByUsername->userId);
            }
        }

        if($count != false) {
            $resultsWithoutEmail = array();
            $remainedCount = $count - sizeof($resultsByUsername);
            if ($remainedCount > 0) {
                $ex = new OW_Example();
                //$ex->andFieldLike('email', '%' . $searchValue . '%');
                $ex->andFieldEqual('valid', true);
                $ex->setLimitClause(0, $remainedCount);
                $resultsWithoutEmail = $this->findListByExample($ex);
            }
            $resultsByEmail = array();
            foreach ($resultsWithoutEmail as $resultWithoutEmail){
                $email = BOL_UserService::getInstance()->findUserById($resultWithoutEmail->userId)->getEmail();
                if (strpos($email, $searchValue) !== false) {
                    $resultsByEmail[] = $resultWithoutEmail;
                }
            }
            foreach ($resultsByEmail as $resultByEmail) {
                if (!in_array($resultByEmail->userId, $markedUserIDs)) {
                    $markedUserIDs[] = $resultByEmail->userId;
                    $result[] = BOL_UserService::getInstance()->findUserById($resultByEmail->userId);
                }
            }

            $remainedCount = $count - sizeof($result);
            $activeUsersNotInvalidatedYet = $this->getValidUsersFromUserTable($remainedCount, $searchValue);
            foreach($activeUsersNotInvalidatedYet as $activeUserNotInvalidatedYet){
                if(!in_array($activeUserNotInvalidatedYet->id, $markedUserIDs)){
                    $markedUserIDs[] = $activeUserNotInvalidatedYet->id;
                    $result[] = $activeUserNotInvalidatedYet;
                }
            }
        }

        return $result;
    }

    /***
     * @param $remainedCount
     * @param $searchValue
     * @return array
     */
    public function getValidUsersFromUserTable($remainedCount, $searchValue = null){
        $activeUsersNotInvalidatedYet = array();
        if($remainedCount>0) {
            $invalidatedUsersUserId = $this->getAllUsersInvalidUserIds();
            if (empty($searchValue)) {
                $ex = new OW_Example();
                if(!empty($invalidatedUsersUserId)) {
                    $ex->andFieldNotInArray('id', $invalidatedUsersUserId);
                }
                $ex->andFieldEqual('emailVerify', 1);
                $ex->setOrder('`joinIp` DESC');
                $ex->setLimitClause(0, $remainedCount);
                $activeUsersNotInvalidatedYet = BOL_UserDao::getInstance()->findListByExample($ex);
                return $activeUsersNotInvalidatedYet;
            }

            $ex = new OW_Example();
            $ex->andFieldLike('username', '%' . $searchValue . '%');
            if(!empty($invalidatedUsersUserId)) {
                $ex->andFieldNotInArray('id', $invalidatedUsersUserId);
            }
            $ex->andFieldEqual('emailVerify', 1);
            $ex->setOrder('`joinIp` DESC');
            $ex->setLimitClause(0, $remainedCount);
            $resultsByUsername = BOL_UserDao::getInstance()->findListByExample($ex);

            $resultsByEmail = array();
            $remainedCount = $remainedCount - sizeof($resultsByUsername);
            if ($remainedCount > 0) {
                $ex = new OW_Example();
                $ex->andFieldLike('email', '%' . $searchValue . '%');
                if(!empty($invalidatedUsersUserId)) {
                    $ex->andFieldNotInArray('id', $invalidatedUsersUserId);
                }
                $ex->andFieldEqual('emailVerify', 1);
                $ex->setOrder('`joinIp` DESC');
                $ex->setLimitClause(0, $remainedCount);
                $resultsByEmail = BOL_UserDao::getInstance()->findListByExample($ex);
            }

            $markedUsername = array();
            foreach($resultsByUsername as $resultByUsername){
                if(!in_array($resultByUsername->username, $markedUsername)){
                    $markedUsername[] = $resultByUsername->username;
                    $activeUsersNotInvalidatedYet[] = $resultByUsername;
                }
            }

            foreach($resultsByEmail as $resultByEmail){
                if(!in_array($resultByEmail->username, $markedUsername)){
                    $markedUsername[] = $resultByEmail->username;
                    $activeUsersNotInvalidatedYet[] = $resultByEmail;
                }
            }
        }

        return $activeUsersNotInvalidatedYet;
    }

    /***
     * @return array
     */
    public function getAllUsersInvalidUserIds(){
        $result = array();
        $allUsersInvalid = $this->getAllUsersInvalid(null, false);
        foreach($allUsersInvalid as $allUserInvalid){
            $result[] = $allUserInvalid->id;
        }

        return $result;
    }

    /**
     * @param $token
     * @return IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidation
     */
    public function getUserByToken($token)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('token', $token);
        return $this->findObjectByExample($ex);
    }

    /***
     * @param null $searchValue
     * @param int $count
     * @return array
     */
    public function getAllUsersInvalid($searchValue = null, $count = 20)
    {
        if(empty($searchValue)) {
            $result = array();
            $ex = new OW_Example();
            $ex->andFieldEqual('valid', false);
            if($count!=false) {
                $ex->setLimitClause(0, $count);
            }
            $ex->setOrder('`tokenTime` DESC');
            $invalidatedUsers = $this->findListByExample($ex);
            foreach ($invalidatedUsers as $invalidatedUser){
                $invalidUser = BOL_UserService::getInstance()->findUserById($invalidatedUser->userId);
                $result[]=$invalidUser;
            }
            return $result;
        }

        $markedId = array();
        $result = array();

        $ex = new OW_Example();
        $ex->andFieldEqual('valid', false);
        if($count!=false) {
            $ex->setLimitClause(0, $count);
        }
        $resultsWithoutUsername = $this->findListByExample($ex);
        $resultsByUsername = array();
        foreach ($resultsWithoutUsername as $resultWithoutUsername){
            $username = BOL_UserService::getInstance()->findUserById($resultWithoutUsername->userId)->getUsername();
            if (strpos($username, $searchValue) !== false) {
                $resultsByUsername[] = $resultWithoutUsername;
            }
        }
        foreach($resultsByUsername as $resultByUsername){
            if(!in_array($resultByUsername->userId, $markedId)){
                $markedId[] = $resultByUsername->userId;
                $result[] = BOL_UserService::getInstance()->findUserById($resultByUsername->userId);
            }
        }

        if($count!=false) {
            //$resultsByEmail = array();
            $resultsWithoutEmail = array();
            $remainedCount = $count - sizeof($resultsByUsername);
            if ($remainedCount > 0) {
                $ex = new OW_Example();
                //$ex->andFieldLike('email', '%' . $searchValue . '%');
                $ex->andFieldEqual('valid', false);
                $ex->setLimitClause(0, $remainedCount);
                $resultsWithoutEmail = $this->findListByExample($ex);
            }
            $resultsByEmail = array();
            foreach ($resultsWithoutEmail as $resultWithoutEmail){
                $email = BOL_UserService::getInstance()->findUserById($resultWithoutEmail->userId)->getEmail();
                if (strpos($email, $searchValue) !== false) {
                    $resultsByEmail[] = $resultWithoutEmail;
                }
            }
            foreach($resultsByEmail as $resultByEmail){
                if(!in_array($resultByEmail->userId, $markedId)){
                    $markedId[] = $resultByEmail->userId;
                    $result[] = BOL_UserService::getInstance()->findUserById($resultByEmail->userId);
                }
            }
        }

        return $result;
    }

    /**
     * @param $userId
     * @return IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidation
     */
    public function getUserByUserId($userId)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('userId', $userId);
        return $this->findObjectByExample($ex);
    }

    /**
     * @return IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidation
     */
    public function getCurrentUser($userId = null)
    {
        if ($userId == null && OW::getUser()->isAuthenticated()) {
            $userId = OW::getUser()->getId();
        }
        return $this->getUserByUserId($userId);
    }

    /**
     * @param $time
     * @param $userId
     * @return IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidation
     */
    public function updateTimePasswordChanged($time = null, $userId = null)
    {
        if ($userId == null && OW::getUser()->isAuthenticated()) {
            $userId = OW::getUser()->getId();
        }
        $passwordValidation = $this->getCurrentUser($userId);
        if ($passwordValidation == null) {
            $passwordValidation = $this->createPasswordValidationObject($userId, true, null, $time);
        } else {
            $passwordValidation->setPasswordTime(time());
            $passwordValidation->setValid(true);
            $passwordValidation->setToken(null);
            $this->save($passwordValidation);
        }
        return $passwordValidation;
    }
}
