<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iispasswordstrengthmeter.bol
 * @since 1.0
 */

class IISPASSWORDSTRENGTHMETER_CMP_Securepassword extends OW_Component
{
    public function __construct()
    {
        parent::__construct();

        $label = '';
        $minimumCharacter = OW::getConfig()->getValue('iispasswordstrengthmeter','minimumCharacter');
        $minimumRequirementPasswordStrength = OW::getConfig()->getValue('iispasswordstrengthmeter','minimumRequirementPasswordStrength');
        if($minimumRequirementPasswordStrength==1){
            $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_poor_label');
        }else if($minimumRequirementPasswordStrength==2){
            $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_weak_label');
        }else if($minimumRequirementPasswordStrength==3){
            $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_good_label');
        }else if($minimumRequirementPasswordStrength==4){
            $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_excellent_label');
        }

        $this->assign('description', OW::getLanguage()->text('iispasswordstrengthmeter','secure_password_information_description'));
        $this->assign('rule1', OW::getLanguage()->text('iispasswordstrengthmeter','secure_password_information_rule1'));
        $this->assign('rule2', OW::getLanguage()->text('iispasswordstrengthmeter','secure_password_information_rule2'));
        $this->assign('rule3', OW::getLanguage()->text('iispasswordstrengthmeter','secure_password_information_rule3'));
        $this->assign('rule4', OW::getLanguage()->text('iispasswordstrengthmeter','secure_password_information_rule4'));
        $this->assign('minimumCharacter', OW::getLanguage()->text('iispasswordstrengthmeter','secure_password_information_minimum_character',array('value' => $minimumCharacter, 'value2' => $label)));

        $this->assign('listOfTypes', OW::getLanguage()->text('iispasswordstrengthmeter','secure_password_information_order_of_strength'));
        $this->assign('poor', OW::getLanguage()->text('iispasswordstrengthmeter','strength_poor_label'));
        $this->assign('weak', OW::getLanguage()->text('iispasswordstrengthmeter','strength_weak_label'));
        $this->assign('good', OW::getLanguage()->text('iispasswordstrengthmeter','strength_good_label'));
        $this->assign('excellent', OW::getLanguage()->text('iispasswordstrengthmeter','strength_excellent_label'));

        $cssDir = OW::getPluginManager()->getPlugin("iispasswordstrengthmeter")->getStaticCssUrl();
        OW::getDocument()->addStyleSheet($cssDir . "password_strength_meter.css");
    }
}
