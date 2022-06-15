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
 * @author Zarif Safiullin <zaph.work@gmail.com>
 * @package ow_plugin.mailbox.components
 * @since 1.6.1
 * */
class MAILBOX_CMP_OembedAttachment extends OW_Component
{
    protected $oembed = array();
    protected $message = "";

    public function __construct($message, $oembed )
    {
        parent::__construct();

        $this->message = $message;
        $this->oembed = $oembed;
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        if (!empty($this->oembed['title']))
        {
            $this->oembed['title'] = UTIL_String::truncate($this->oembed['title'], 23, '...');
        }

        if (!empty($this->oembed['description']))
        {
            $this->oembed['description'] = UTIL_String::truncate($this->oembed['description'], 40, '...');
        }

        $message_information = MAILBOX_BOL_MessageDao::getInstance()->findById($this->oembed['messageId']);
        $message_time = strftime("%H:%M", (int) $message_information->timeStamp);
        $message_seen = ($message_information->recipientRead === "1" and $message_information->senderId === OW::getUser()->getId()) ? true : false;

        $this->assign('message', $this->message);
        $this->assign('data', $this->oembed);
        $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION, array('check' => true)));
        if (isset($mobileEvent->getData()['isMobileVersion']) && $mobileEvent->getData()['isMobileVersion'] == false) {
            $this->assign('message_info', array("message_time"=> $message_time, "message_seen"=>$message_seen));
        }
    }
}
