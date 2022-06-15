<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Update Status Component
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.mobile.components
 * @since 1.0
 */
class NEWSFEED_MCMP_UpdateStatus extends NEWSFEED_CMP_UpdateStatus
{
    public function __construct( $feedAutoId, $feedType, $feedId, $actionVisibility = null )
    {
        parent::__construct($feedAutoId, $feedType, $feedId, $actionVisibility);
        $this->assign('statusMessage',(IISSecurityProvider::getStatusMessage()!=null)? IISSecurityProvider::getStatusMessage() : OW::getLanguage()->text('newsfeed', 'status_field_invintation'));
        $tpl = OW::getPluginManager()->getPlugin("newsfeed")->getMobileCmpViewDir() . "update_status.html";
        $this->setTemplate($tpl);
        $iisnewsfeedplusPlugin = BOL_PluginDao::getInstance()->findPluginByKey('iisnewsfeedplus');
        if(!isset($iisnewsfeedplusPlugin) || !$iisnewsfeedplusPlugin->isActive()) {
            $this->assign('hideDefaultAttachment',false);
        }else{
            $this->assign('hideDefaultAttachment',true);
        }
    }
    
    public function initAttachments($feedAutoId, Form $form) 
    {
        OW::getDocument()->addOnloadScript(UTIL_JsGenerator::composeJsString(
            'window.onStatusUpdate_' . $feedAutoId . ' = function( r ) {
                $("#newsfeed_status_input").val("");
                $("#newsfeed-att-file").val("");
                $("#newsfeed-att-file-prevew img").hide();
                $("#newsfeed-att-file-prevew span").empty();
                
                $("#newsfeed_status_save_btn_c").removeClass("owm_preloader_circle");

                if ( r.error ) {
                    OWM.error(r.error); return;
                }

                if ( r.item ) {
                    window.ow_newsfeed_feed_list[{$autoId}].loadNewItem(r.item, false);
                }
                
                if ( r.message ) {
                    OWM.info(r.message);
                }

                OWM.getActiveFloatBox().close();
                $(".owm_nav_menu.owm_nav_back").click();
                
                if (typeof refreshAttachClass === "function") { 
                    refreshAttachClass();
                }
            }',
        array(
            'autoId' => $feedAutoId
        )));
    }
    
    protected function setFocusOnInput()
    {
        $this->assign("focused", true);
    }

        /**
     * 
     * @param int $feedAutoId
     * @param string $feedType
     * @param int $feedId
     * @param int $actionVisibility
     * @return Form
     */
    public function createForm( $feedAutoId, $feedType, $feedId, $actionVisibility )
    {
        return new NEWSFEED_MStatusForm($feedAutoId, $feedType, $feedId, $actionVisibility);
    }
}

class NEWSFEED_MStatusForm extends Form
{
    public function __construct( $feedAutoId, $feedType, $feedId, $actionVisibility = null )
    {
        parent::__construct('newsfeed_update_status');
        
        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);
        
        $field = new Textarea('status');
        $field->setId('newsfeed_update_status_info_id');
        $field->setHasInvitation(true);
        $field->setInvitation( OW::getLanguage()->text('newsfeed', 'status_field_invintation') );
        $this->addElement($field);

        $field = new HiddenField('attachment');
        $this->addElement($field);

        $field = new HiddenField('feedType');
        $field->setValue($feedType);
        $this->addElement($field);

        $field = new HiddenField('feedId');
        $field->setValue($feedId);
        $this->addElement($field);
        
        $field = new HiddenField('feedAutoId');
        $field->setValue($feedAutoId);
        $this->addElement($field);

        $field = new HiddenField('visibility');
        $field->setValue($actionVisibility);
        $this->addElement($field);

        $submit = new Submit('save');
        $submit->setId('updatestatus_submit_button');
        $submit->setValue(OW::getLanguage()->text('newsfeed', 'status_btn_label'));
        $this->addElement($submit);
        if ( !OW::getRequest()->isAjax() )
        {
            $js = UTIL_JsGenerator::composeJsString('
            
            owForms["newsfeed_update_status"].bind( "submit", function( r )
            {
                $("#newsfeed_status_save_btn_c").addClass("owm_preloader_circle");
            });
            
            owForms["newsfeed_update_status"].bind( "success", function( r )
            {
                OW.trigger("clear.attachment.inProgress",{"pluginKey": "iisnewsfeedplus"});
            });   
            ');

            OW::getDocument()->addOnloadScript( $js );
        }

        $this->setAction( OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('NEWSFEED_MCTRL_Feed', 'statusUpdate')) );
        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_UPDATE_STATUS_FORM_RENDERER, array('form' => $this)));
    }
}