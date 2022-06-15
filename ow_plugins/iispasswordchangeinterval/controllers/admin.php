<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iispasswordchangeinterval.controllers
 * @since 1.0
 */
class IISPASSWORDCHANGEINTERVAL_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    public function index(array $params = array())
    {
        $language = OW::getLanguage();
        $this->setPageHeading($language->text('iispasswordchangeinterval', 'admin_page_heading'));
        $this->setPageTitle($language->text('iispasswordchangeinterval', 'admin_page_title'));
        $this->setPageHeadingIconClass('ow_ic_comment');

        $sectionId = IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_INFORMATION;
        if(isset($params['sectionId'])){
            $sectionId = $params['sectionId'];
        }

        $config = OW::getConfig();
        $configs = $config->getValues('iispasswordchangeinterval');

        $formSetting = new Form('settings');
        $formSetting->setAjax();
        $formSetting->setAjaxResetOnSuccess(false);
        $formSetting->setAction(OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin'));
        $formSetting->bindJsFunction(Form::BIND_SUCCESS, 'function(data){if(data.result){OW.info("' . OW::getLanguage()->text("iispasswordchangeinterval", "settings_successfuly_saved") . '");}else{OW.error("Parser error");}}');

        $expTime = new TextField('expTime');
        $expTime->setRequired();
        $expTime->setLabel($language->text('iispasswordchangeinterval','change_password_per_day_label'));
        $expTime->setValue($configs['expire_time']);
        $expTime->addValidator(new IntValidator(1));
        $formSetting->addElement($expTime);

        $dealWithExpiredPassword = new Selectbox('dealWithExpiredPassword');
        $options = array();
        $options[IISPASSWORDCHANGEINTERVAL_BOL_Service::DEAL_WITH_EXPIRED_PASSWORD_NORMAL_WITHOUT_NOTIF] = OW::getLanguage()->text("iispasswordchangeinterval", "deal_with_expired_password_normal_without_notif");
        $options[IISPASSWORDCHANGEINTERVAL_BOL_Service::DEAL_WITH_EXPIRED_PASSWORD_NORMAL_WITH_NOTIF] = OW::getLanguage()->text("iispasswordchangeinterval", "deal_with_expired_password_normal_with_notif");
        $options[IISPASSWORDCHANGEINTERVAL_BOL_Service::DEAL_WITH_EXPIRED_PASSWORD_FORCE_WITH_NOTIF] = OW::getLanguage()->text("iispasswordchangeinterval", "deal_with_expired_password_force_with_notif");
        $dealWithExpiredPassword->setHasInvitation(false);
        $dealWithExpiredPassword->setOptions($options);
        $dealWithExpiredPassword->setRequired();
        $dealWithExpiredPassword->setValue($configs['dealWithExpiredPassword']);
        $formSetting->addElement($dealWithExpiredPassword);

        $submit = new Submit('save');
        $formSetting->addElement($submit);

        $this->addForm($formSetting);

        $formSearch = new Form('search');
        $formSearch->setAction(OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.section-id', array('sectionId' => $sectionId)));

        $searchUsername = new TextField('username');
        $searchUsername->setRequired();
        $searchUsername->setInvitation(OW::getLanguage()->text('iispasswordchangeinterval', 'find_users_placeholder'));
        $searchUsername->setHasInvitation(true);
        $formSearch->addElement($searchUsername);

        $searchType = new HiddenField('type');
        if($sectionId==2){
            $searchType->setValue(2);
        }else if($sectionId==3){
            $searchType->setValue(3);
        }
        $formSearch->addElement($searchType);

        $submit = new Submit('save');
        $formSearch->addElement($submit);
        $this->addForm($formSearch);


        if (OW::getRequest()->isAjax()) {
            if ($formSetting->isValid($_POST)) {
                $config->saveConfig('iispasswordchangeinterval', 'expire_time', $formSetting->getElement('expTime')->getValue());
                $config->saveConfig('iispasswordchangeinterval', 'dealWithExpiredPassword', $formSetting->getElement('dealWithExpiredPassword')->getValue());
                exit(json_encode(array('result' => true)));
            }
        }

        $searchValue = null;
        if(OW::getRequest()->isPost()){
            if($formSearch->isValid($_POST)){
                $searchType = $formSearch->getElement('type')->getValue();
                if(!empty($searchType)){
                    $searchValue = $formSearch->getElement('username')->getValue();
                }
            }
        }

        $usersValidation = array();
        if($sectionId!=IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_INFORMATION) {
            if ($sectionId == IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_VALID_USERS) {
                $this->assign('changeStatusClass', 'ow_red');
                $this->assign('changeStatusLabel', OW::getLanguage()->text('iispasswordchangeinterval', 'invalidate_status_label'));
            } else if ($sectionId == IISPASSWORDCHANGEINTERVAL_BOL_Service::SECTION_PASSWORD_VALIDATION_INVALID_USERS) {
                $this->assign('changeStatusClass', 'ow_green');
                $this->assign('changeStatusLabel', OW::getLanguage()->text('iispasswordchangeinterval', 'validate_status_label'));
            }
            $users = $this->getService()->getUsersBySectionId($sectionId, $searchValue);
            foreach ($users as $user) {
                $usersValidation[] = array(
                    'username' => $user->username,
                    'email' => $user->email,
                    'changeStatusUrl' => $this->getService()->getChangeStatusUrl($user->id, $sectionId)
                );
            }
        }else{
            $this->assign('invalidateAllPasswordsUrl', OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.invalidate-all-password', array('sectionId' => $sectionId)));
            $this->assign('invalidateAllPasswordsLabel', OW::getLanguage()->text('iispasswordchangeinterval', 'set_all_password_invalid'));
            $this->assign('expireAllPasswordsUrl', OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.expire-all-password', array('sectionId' => $sectionId)));
            $this->assign('expireAllPasswordsLabel', OW::getLanguage()->text('iispasswordchangeinterval', 'set_all_password_expire'));
        }

        if($sectionId==2){
            $this->assign('invalidUserImgUrl', OW_PluginManager::getInstance()->getPlugin('iispasswordchangeinterval')->getStaticUrl().'/images/stop.png');
        }else if($sectionId==3){
            $this->assign('validUserImgUrl', OW_PluginManager::getInstance()->getPlugin('iispasswordchangeinterval')->getStaticUrl().'/images/allow.png');
        }
        $this->assign('sectionId', $sectionId);
        $this->assign('users', $usersValidation);
        $this->assign('sections', $this->getService()->getSections($sectionId));
    }

    public function invalidatePassword($params)
    {
        if(!isset($params['userId'])){
            throw new Redirect404Exception();
        }
        $user = BOL_UserService::getInstance()->findUserById($params['userId']);
        $this->getService()->setUserPasswordInvalid($user->getId());
        OW::getFeedback()->info(OW::getLanguage()->text('iispasswordchangeinterval', 'database_record_invalidate'));
        $this->redirect(OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.section-id', array('sectionId' => $params['sectionId'])));
    }

    public function validatePassword($params){
        if(!isset($params['userId'])){
            throw new Redirect404Exception();
        }
        $user = BOL_UserService::getInstance()->findUserById($params['userId']);
        $this->getService()->setUserPasswordValid($user->getId());
        OW::getFeedback()->info(OW::getLanguage()->text('iispasswordchangeinterval', 'database_record_validate'));
        $this->redirect(OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.section-id', array('sectionId' => $params['sectionId'])));
    }

    public function invalidateAllPassword($params)
    {
        $this->getService()->setAllUsersPasswordInvalid(true);
        OW::getFeedback()->info(OW::getLanguage()->text('iispasswordchangeinterval', 'database_all_record_invalidate'));
        $this->redirect(OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.section-id', array('sectionId' => $params['sectionId'])));
    }

    public function expireAllPassword($params)
    {
        $this->getService()->setAllUsersPasswordExpire();
        OW::getFeedback()->info(OW::getLanguage()->text('iispasswordchangeinterval', 'database_all_record_invalidate'));
        $this->redirect(OW::getRouter()->urlForRoute('iispasswordchangeinterval.admin.section-id', array('sectionId' => $params['sectionId'])));
    }

    public function getService()
    {
        return IISPASSWORDCHANGEINTERVAL_BOL_Service::getInstance();
    }

}
