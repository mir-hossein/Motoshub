<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iispasswordstrengthmeter.controllers
 * @since 1.0
 */
class IISPASSWORDSTRENGTHMETER_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    public function index( array $params = array() )
    {
        $language = OW::getLanguage();
        $this->setPageHeading($language->text('iispasswordstrengthmeter', 'admin_page_heading'));
        $this->setPageTitle($language->text('iispasswordstrengthmeter', 'admin_page_title'));
        $config = OW::getConfig();
        $configs = $config->getValues('iispasswordstrengthmeter');
        
        $form = new Form('settings');
        $form->setAjax();
        $form->setAjaxResetOnSuccess(false);
        $form->setAction(OW::getRouter()->urlForRoute('iispasswordstrengthmeter.admin'));
        $form->bindJsFunction(Form::BIND_SUCCESS, 'function(data){if(data.result){OW.info("'.OW::getLanguage()->text("iispasswordstrengthmeter", "settings_successfuly_saved").'");}else{OW.error("Parser error");}}');

        $minimumCharacter = new TextField('minimumCharacter');
        $minimumCharacter->setLabel($language->text('iispasswordstrengthmeter','minimum_length_label'));
        $minimumCharacter->setRequired();
        $minimumCharacter->setValue($configs['minimumCharacter']);
        $minimumCharacter->addValidator(new IntValidator(1));
        $form->addElement($minimumCharacter);

        $minimumRequirementPasswordStrength = new Selectbox('minimumRequirementPasswordStrength');
        $options = array();
        $options[1] = OW::getLanguage()->text("iispasswordstrengthmeter", "strength_poor_label");
        $options[2] = OW::getLanguage()->text("iispasswordstrengthmeter", "strength_weak_label");
        $options[3] = OW::getLanguage()->text("iispasswordstrengthmeter", "strength_good_label");
        $options[4] = OW::getLanguage()->text("iispasswordstrengthmeter", "strength_excellent_label");
        $minimumRequirementPasswordStrength->setHasInvitation(false);
        $minimumRequirementPasswordStrength->setOptions($options);
        $minimumRequirementPasswordStrength->setRequired();
        $minimumRequirementPasswordStrength->setValue($configs['minimumRequirementPasswordStrength']);
        $form->addElement($minimumRequirementPasswordStrength);

        $submit = new Submit('save');
        $form->addElement($submit);
        
        $this->addForm($form);

        if ( OW::getRequest()->isAjax() )
        {
            if ( $form->isValid($_POST) )
            {
                $config->saveConfig('iispasswordstrengthmeter', 'minimumCharacter', $form->getElement('minimumCharacter')->getValue());
                $config->saveConfig('iispasswordstrengthmeter', 'minimumRequirementPasswordStrength', $form->getElement('minimumRequirementPasswordStrength')->getValue());
                exit(json_encode(array('result' => true)));
            }
        }
    }
}