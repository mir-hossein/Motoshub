<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisuserlogin.controllers
 * @since 1.0
 */
class IISUSERLOGIN_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    public function index( array $params = array() )
    {
        $language = OW::getLanguage();
        $this->setPageHeading($language->text('iisuserlogin', 'admin_page_heading'));
        $this->setPageTitle($language->text('iisuserlogin', 'admin_page_title'));
        $config = OW::getConfig();
        $configs = $config->getValues('iisuserlogin');
        
        $form = new Form('settings');
        $form->setAjax();
        $form->setAjaxResetOnSuccess(false);
        $form->setAction(OW::getRouter()->urlForRoute('iisuserlogin.admin'));
        $form->bindJsFunction(Form::BIND_SUCCESS, 'function(data){if(data.result){OW.info("'. OW::getLanguage()->text('iisuserlogin', 'setting_saved') .'");}else{OW.error("Parser error");}}');


        $numberOfLastLoginDetails = new TextField('numberOfLastLoginDetails');
        $numberOfLastLoginDetails->setLabel($language->text('iisuserlogin','numberOfLastLoginDetails'));
        $numberOfLastLoginDetails->setRequired();
        $numberOfLastLoginDetails->addValidator(new IntValidator(1));
        $numberOfLastLoginDetails->setValue($configs['numberOfLastLoginDetails']);
        $form->addElement($numberOfLastLoginDetails);

        $expiredTimeOfLoginDetails = new TextField('expiredTimeOfLoginDetails');
        $expiredTimeOfLoginDetails->setLabel($language->text('iisuserlogin','expiredTimeOfLoginDetails'));
        $expiredTimeOfLoginDetails->setRequired();
        $expiredTimeOfLoginDetails->addValidator(new IntValidator(1));
        $expiredTimeOfLoginDetails->setValue($configs['expiredTimeOfLoginDetails']);
        $form->addElement($expiredTimeOfLoginDetails);

        $enableActiveDevices = new CheckboxField('enableActiveDevices');
        $enableActiveDevices->setLabel($language->text('iisuserlogin','enableActiveDevices'));
        $enableActiveDevices->setValue($configs['update_active_details']);
        $form->addElement($enableActiveDevices);

        $submit = new Submit('save');
        $form->addElement($submit);
        
        $this->addForm($form);

        if ( OW::getRequest()->isAjax() )
        {
            if ( $form->isValid($_POST) )
            {
                $config->saveConfig('iisuserlogin', 'numberOfLastLoginDetails', $form->getElement('numberOfLastLoginDetails')->getValue());
                $config->saveConfig('iisuserlogin', 'expiredTimeOfLoginDetails', $form->getElement('expiredTimeOfLoginDetails')->getValue());
                $config->saveConfig('iisuserlogin', 'update_active_details', $form->getElement('enableActiveDevices')->getValue());
                exit(json_encode(array('result' => true)));
            }
        }
    }
}
