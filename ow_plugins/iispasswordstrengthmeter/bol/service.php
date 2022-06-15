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
class IISPASSWORDSTRENGTHMETER_BOL_Service
{

    private static $classInstance;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }


    private function __construct()
    {
    }


    /**
     * @param OW_Event $event
     */
    public function getMinimumReqirementPasswordStrengthInformation(OW_Event $event){
        $event->setData(array('label' => $this->getMinimumRequirementPasswordStrength(), 'minimumCharacter' => OW::getConfig()->getValue('iispasswordstrengthmeter','minimumCharacter')));
    }


    /**
     * @param OW_Event $event
     */
    public function onPasswordValidationInJoinForm(OW_Event $event){
        $params = $event->getParams();
        if($params['value']) {
            $minimumRequirementPasswordStrength = OW::getConfig()->getValue('iispasswordstrengthmeter','minimumRequirementPasswordStrength');
            $minimumCharacter = OW::getConfig()->getValue('iispasswordstrengthmeter','minimumCharacter');
            if(!$this->isPasswordSecure($params['value'], $minimumRequirementPasswordStrength, $minimumCharacter)){
                $label = '';
                if($minimumRequirementPasswordStrength==1){
                    $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_poor_label');
                }else if($minimumRequirementPasswordStrength==2){
                    $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_weak_label');
                }else if($minimumRequirementPasswordStrength==3){
                    $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_good_label');
                }else if($minimumRequirementPasswordStrength==4){
                    $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_excellent_label');
                }

                if ( OW::getRequest()->isAjax() )
                {
                    echo json_encode( array( 'result' => false , 'errorText' => OW::getLanguage()->text('iispasswordstrengthmeter','strength_password_validate_error',array('value' => $label))) );
                    exit;
                }
                $event->setData(array('error' => OW::getLanguage()->text('iispasswordstrengthmeter','strength_password_validate_error',array('value' => $label))));
            }
        }
    }

    /**
     * @param $value
     * @param $minimumRequirementPasswordStrength
     * @param $minimumCharacter
     * @return bool
     */
    public function isPasswordSecure($value, $minimumRequirementPasswordStrength, $minimumCharacter){
        $poorValue = 1;
        $weakValue = 2;
        $goodValue = 3;
        $excellentValue = 4;

        $hasNumber = 0;
        $hasUpperCase = 0;
        $hasLowerCase = 0;
        $hasSpecialCharacter = 0;

        $passwordValue = $value;

        $specialCharacterList = [" ","!","\"","#","$","%","&","'","(",")","*","+","\,","-",".","/",":",";","<","=",">","?","@","[","\\","]","^","_","`","{","|","}","~"];
        $passwordValueArray = str_split($passwordValue, 1);
        foreach ($passwordValueArray as &$value) {
            if(in_array($value,$specialCharacterList)){
                $hasSpecialCharacter = 1;
            }else if(is_numeric($value)){
                $hasNumber = 1;
            }else if($value == strtoupper($value)){
                $hasUpperCase = 1;
            }else if($value == strtolower($value)){
                $hasLowerCase = 1;
            }
        }

        if(strlen($passwordValue) < $minimumCharacter && $minimumRequirementPasswordStrength > $poorValue){//Type of password is poor
            return false;
        }else if(strlen($passwordValue) < 2*$minimumCharacter && $hasNumber+$hasUpperCase+$hasSpecialCharacter == 0  && $minimumRequirementPasswordStrength<=$poorValue){//Type of password is poor
            return true;
        }else if(strlen($passwordValue) < 2*$minimumCharacter && $hasNumber+$hasUpperCase+$hasSpecialCharacter == 0  && $minimumRequirementPasswordStrength>$poorValue){//Type of password is poor
            return false;
        }else if($hasNumber+$hasUpperCase+$hasSpecialCharacter+$hasLowerCase==1 && $minimumRequirementPasswordStrength<=$weakValue){//Type of password is weak
            return true;
        }else if($hasNumber+$hasUpperCase+$hasSpecialCharacter+$hasLowerCase==2 && $minimumRequirementPasswordStrength<=$goodValue){//Type of password is good
            return true;
        }else if($hasNumber+$hasUpperCase+$hasSpecialCharacter+$hasLowerCase >= 3 && $minimumRequirementPasswordStrength<=$excellentValue){//Type of password is excellent
            return true;
        }

        return false;
    }

    /**
     * @param OW_Event $event
     */
    public function onAfterDocumentRenderer(OW_Event $event){
        $jsDir = OW::getPluginManager()->getPlugin("iispasswordstrengthmeter")->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir . "password_strength_meter.js");

        $cssDir = OW::getPluginManager()->getPlugin("iispasswordstrengthmeter")->getStaticCssUrl();
        OW::getDocument()->addStyleSheet($cssDir . "password_strength_meter.css");

        $minimumCharacter = OW::getConfig()->getValue('iispasswordstrengthmeter','minimumCharacter');
        OW::getLanguage()->addKeyForJs('iispasswordstrengthmeter', 'strength_poor_label');
        OW::getLanguage()->addKeyForJs('iispasswordstrengthmeter', 'strength_weak_label');
        OW::getLanguage()->addKeyForJs('iispasswordstrengthmeter', 'strength_good_label');
        OW::getLanguage()->addKeyForJs('iispasswordstrengthmeter', 'strength_excellent_label');
        OW::getLanguage()->addKeyForJs('iispasswordstrengthmeter', 'secure_password_information_title');
        OW::getLanguage()->addKeyForJs('iispasswordstrengthmeter', 'secure_password_information_minimum_strength_type');
        OW::getLanguage()->addKeyForJs('iispasswordstrengthmeter', 'password_repeatpassword_compare_error');

        $onLoadJs = "passwordStrengthMeter('".$minimumCharacter."','".$this->getMinimumRequirementPasswordStrength()."');";
        OW::getDocument()->addOnloadScript($onLoadJs);
    }

    public function getMinimumRequirementPasswordStrength(){
        $minimumRequirementPasswordStrength = OW::getConfig()->getValue('iispasswordstrengthmeter','minimumRequirementPasswordStrength');
        $label = '';
        if($minimumRequirementPasswordStrength==1){
            $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_poor_label');
        }else if($minimumRequirementPasswordStrength==2){
            $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_weak_label');
        }else if($minimumRequirementPasswordStrength==3){
            $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_good_label');
        }else if($minimumRequirementPasswordStrength==4){
            $label = OW::getLanguage()->text('iispasswordstrengthmeter','strength_excellent_label');
        }

        return $label;
    }

}
