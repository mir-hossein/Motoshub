<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * User console component class.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.components
 * @since 1.0
 */
class BASE_MCMP_SignIn extends OW_MobileComponent
{

    /**
     * Constructor.
     */
    public function __construct( $ajax = true )
    {
        parent::__construct();

        if ( OW::getUser()->isAuthenticated() )
        {
            $this->setVisible(false);
        }
        OW::getDocument()->setHeading(OW::getLanguage()->text('base','sign_in_submit_label'));
        $form = BOL_UserService::getInstance()->getSignInForm('sign-in', false);

        if ( $ajax )
        {
            $form->setAction(OW::getRouter()->urlFor('BASE_MCTRL_User', 'signIn'));
            $form->setAjax();
            $form->bindJsFunction(Form::BIND_SUBMIT, 'function(data){$("#console_preloader").fadeIn(300);}');
            $form->bindJsFunction(Form::BIND_SUCCESS, 'function(data){$("#console_preloader").fadeOut(300);if( data.result ){OWM.info(data.message);setTimeout(function(){window.location.reload();}, 1000);}else{OWM.error(data.message);}}');
        }
        $eventData= OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_FORM_SIGNIN_RENDER, array('form' => $form,'BASE_CMP_SignIn' => $this,'ajax' => $ajax)));
        if(isset($eventData->getData()['ssoForm']))
        {
            $this->addForm($eventData->getData()['ssoForm']);
            $this->assign('sso',true);
            if(isset($eventData->getData()['joinButton']))
            {
                $this->addComponent('joinButton',$eventData->getData()['joinButton']);
            }
            return;
        }
        OW::getDocument()->addOnloadScript("$('.owm_login_username input').focus()");
        $this->addForm($form);
    }
}