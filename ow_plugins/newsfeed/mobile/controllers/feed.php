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
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.newsfeed.controllers
 * @since 1.0
 */
class NEWSFEED_MCTRL_Feed extends NEWSFEED_CTRL_Feed
{
    /**
     * 
     * @param NEWSFEED_CLASS_Driver $driver
     * @param string $feedType
     * @param string $feedId
     * @return NEWSFEED_MCMP_Feed
     */
    protected function getFeed( NEWSFEED_CLASS_Driver $driver, $feedType, $feedId )
    {
        return OW::getClassInstance("NEWSFEED_MCMP_Feed", $driver, $feedType, $feedId);
    }
    
    public function viewItem( $params )
    {
        parent::viewItem($params);
        if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=null){
            $this->assign('backUrl',$_SERVER['HTTP_REFERER']);
        }else{
            $this->assign('backUrl', (OW::getRouter()->urlForRoute('newsfeed_view_feed')));
        }
        $viewDir = OW::getPluginManager()->getPlugin("newsfeed")->getMobileCtrlViewDir();
        $this->setTemplate($viewDir . "feed_view_item.html");
    }
    
    public function feed()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }
        
        $write = !empty($_GET["write"]);
        
        $viewDir = OW::getPluginManager()->getPlugin("newsfeed")->getMobileCtrlViewDir();
        $this->setTemplate($viewDir . "feed_feed.html");
        
        $driver = OW::getClassInstance("NEWSFEED_CLASS_UserDriver");
        $feed = OW::getClassInstance("NEWSFEED_MCMP_Feed", $driver, 'my', OW::getUser()->getId());
        
        $feed->setDisplayType(NEWSFEED_CMP_Feed::DISPLAY_TYPE_ACTIVITY);
        
        if ( OW::getUser()->isAuthorized('newsfeed', 'allow_status_update') )
        {
            $feed->addStatusForm('user', OW::getUser()->getId());
        }

        $feed->setup(array(
            "displayCount" => 20,
            "customizeMode" => false,
            "viewMore" => true
        ));
        
        
        $feed->focusOnInput(isset($_GET["write"]));
        
        $this->addComponent("feed", $feed);
    }
    
    private function echoOut( $feedAutoId, $out )
    {
        $feedAutoId = preg_replace("/[^A-Za-z0-9 ]/", "", $feedAutoId);
        echo '<script>window.parent.onStatusUpdate_' . $feedAutoId . '(' . json_encode($out) . ');</script>';
        exit;
    }
    
    public function statusUpdate()
    {

        if ( empty($_POST['status']) && empty($_FILES['attachment']["tmp_name"]) )
        {
            $this->echoOut($_POST['feedAutoId'], array(
                "error" => OW::getLanguage()->text('base', 'form_validate_common_error_message')
            ));
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            $this->echoOut($_POST['feedAutoId'], array(
                "error" => "You need to sign in to post."
            ));
        }

        if (!preg_match("/^[A-Za-z0-9]+$/", $_POST['feedAutoId'])) {
            $this->echoOut($_POST['feedAutoId'], array(
                "error" => OW::getLanguage()->text('base', 'form_validate_common_error_message')
            ));
        }
        $status = empty($_POST['status']) ? '' : strip_tags($_POST['status']);
        $content = array();

        if ( !empty($_FILES['attachment']["tmp_name"]) )
        {
            try 
            {
                $attachment = BOL_AttachmentService::getInstance()->processPhotoAttachment("newsfeed", $_FILES['attachment']);
            } 
            catch (InvalidArgumentException $ex) 
            {
                $this->echoOut($_POST['feedAutoId'], array(
                    "error" => $ex->getMessage()
                ));
            }
            
            $content = array(
                "type" => "photo",
                "url" => $attachment["url"]
            );
        }
        
        $userId = OW::getUser()->getId();
        $visibility=$_POST['visibility'];
        if($_POST['feedType']=='groups') {
            $groupService = GROUPS_BOL_Service::getInstance();
            $group = $groupService->findGroupById($_POST['feedId']);
            $private = $group->whoCanView == GROUPS_BOL_Service::WCV_INVITE;
            $visibility = $private
                ? 14 // VISIBILITY_FOLLOW + VISIBILITY_AUTHOR + VISIBILITY_FEED
                : 15; // Visible for all (15)
        }
        $event = new OW_Event("feed.before_content_add", array(
            "feedType" => $_POST['feedType'],
            "feedId" => $_POST['feedId'],
            "visibility" => $visibility,
            "userId" => $userId,
            "status" => $status,
            "type" => empty($content["type"]) ? "text" : $content["type"],
            "data" => $content
        ));

        OW::getEventManager()->trigger($event);
        
        $data = $event->getData();

        if ( !empty($data) )
        {
            $item = empty($data["entityType"]) || empty($data["entityId"])
                    ? null
                    : array(
                        "entityType" => $data["entityType"],
                        "entityId" => $data["entityId"]
                    );

            $eventIisGroupsPlusManager = new OW_Event('iisgroupsplus.on.update.group.status', array('feedId' => $_POST['feedId'],
                'feedType' => $_POST['feedType'], 'status' => $_POST['status'], 'statusId'=>$item['entityId']));
            OW::getEventManager()->trigger($eventIisGroupsPlusManager);

            $this->echoOut($_POST['feedAutoId'], array(
                "item" => $item,
                "message" => empty($data["message"]) ? null : $data["message"],
                "error" => empty($data["error"]) ? null : $data["error"]
            ));
        }

        $attachmentUID=null;
        if(isset($attachment["uid"]))
        {
            $attachmentUID=$attachment["uid"];
        }
        $status = UTIL_HtmlTag::autoLink($status);
        $out = NEWSFEED_BOL_Service::getInstance()
                ->addStatus(OW::getUser()->getId(), $_POST['feedType'], $_POST['feedId'], $visibility, $status, array(
                    "content" => $content,
                    "attachmentId" => $attachmentUID
                ));
        $this->echoOut($_POST['feedAutoId'], array(
            "item" => $out
        ));
    }
}