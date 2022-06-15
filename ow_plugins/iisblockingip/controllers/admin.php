<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisblockingip.controllers
 * @since 1.0
 */
class IISBLOCKINGIP_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    public function index( array $params = array() )
    {
        $language = OW::getLanguage();
        $this->setPageHeading($language->text('iisblockingip', 'admin_page_heading'));
        $this->setPageTitle($language->text('iisblockingip', 'admin_page_title'));
        $config = OW::getConfig();
        $configs = $config->getValues('iisblockingip');
        
        $form = new Form('settings');
        $form->setAjax();
        $form->setAjaxResetOnSuccess(false);
        $form->setAction(OW::getRouter()->urlForRoute('iisblockingip.admin'));
        $form->bindJsFunction(Form::BIND_SUCCESS, 'function(data){if(data.result){OW.info("' . OW::getLanguage()->text("iisblockingip", "settings_successfuly_saved") . '");}else{OW.error("Parser error");}}');

        $loginCaptcha = new CheckboxField('loginCaptcha');
        $loginCaptcha->setValue($configs['loginCaptcha']);
        $form->addElement($loginCaptcha);

        $tryCountCaptcha = new TextField('tryCountCaptcha');
        $tryCountCaptcha->setLabel($language->text('iisblockingip','captcha_try_label'));
        $tryCountCaptcha->setRequired();
        $tryCountCaptcha->addValidator(new IntValidator(1));
        $tryCountCaptcha->setValue($configs['try_count_captcha']);
        $form->addElement($tryCountCaptcha);

        $block = new CheckboxField('block');
        $block->setValue($configs['block']);
        $form->addElement($block);

        $tryCountBlock = new TextField('tryCountBlock');
        $tryCountBlock->setLabel($language->text('iisblockingip','block_try_label'));
        $tryCountBlock->setRequired();
        $tryCountBlock->addValidator(new IntValidator(1));
        $tryCountBlock->setValue($configs['try_count_block']);
        $form->addElement($tryCountBlock);

        $expTime = new TextField('expTime');
        $expTime->setLabel($language->text('iisblockingip','Lock_per_minute_label'));
        $expTime->setRequired();
        $expTime->setValue($configs['expire_time']);
        $expTime->addValidator(new IntValidator(1));
        $form->addElement($expTime);

        $submit = new Submit('save');
        $form->addElement($submit);
        
        $this->addForm($form);

        if ( OW::getRequest()->isAjax() )
        {
            if ( $form->isValid($_POST) )
            {
                $config->saveConfig('iisblockingip', 'loginCaptcha', $form->getElement('loginCaptcha')->getValue());
                $config->saveConfig('iisblockingip', 'try_count_captcha', $form->getElement('tryCountCaptcha')->getValue());
                $config->saveConfig('iisblockingip', 'block', $form->getElement('block')->getValue());
                $config->saveConfig('iisblockingip', 'try_count_block', $form->getElement('tryCountBlock')->getValue());
                $config->saveConfig('iisblockingip', 'expire_time', $form->getElement('expTime')->getValue());

                exit(json_encode(array('result' => true)));
            }
        }
    }
}
