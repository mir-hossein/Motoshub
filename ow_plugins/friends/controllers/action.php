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
 *  derived from this software without specific  prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow_plugins.friends.controllers
 * @since 1.0
 */
class FRIENDS_CTRL_Action extends OW_ActionController
{
    /**
     * Request new friendship controller
     *
     * @param array $params
     * @throws Redirect404Exception
     * @throws AuthenticateException
     */
    public function request( $params )
    {
         if ( !OW::getUser()->isAuthenticated() )
        {
           throw new AuthenticateException();
        }

        $isAutorise=true;
        $requesterId = OW::getUser()->getId();

        $userId = (int) $params['id'];

        if ( BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $userId) )
        {
            throw new Redirect404Exception();
        }

        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            if(!isset($params['code'])){
                throw new Redirect404Exception();
            }
            $code = $params['code'];
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'request_friends')));
        }
        if (!OW::getUser()->isAuthorized('friends', 'add_friend'))
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('friends', 'add_friend');
            OW::getFeedback()->error($status['msg']);
            $isAutorise = false;
        }

        $service = FRIENDS_BOL_Service::getInstance();

        if ( $service->findFriendship($requesterId, $userId) === null  )
        {
            if ($isAutorise !== false)
            {
                $service->request($requesterId, $userId);
                $service->onRequest($requesterId, $userId);
                $service->onFriendshipRequestNotification($requesterId,$userId);
                OW::getFeedback()->info(OW::getLanguage()->text('friends', 'feedback_request_was_sent'));
            }        
        }
        else
        {
            OW::getFeedback()->error(OW::getLanguage()->text('friends', 'feedback_request_already_sent_error_message'));
        }

        if ( isset( $params['backUri'] ) )
        {
            if(strpos( $params['backUri'], ":") === false ) {
                $this->redirect($params['backUri']);
            }
        }else {
            $user = BOL_UserService::getInstance()->findUserById($userId);
            if ($user != null) {
                $this->redirect(OW::getRouter()->urlForRoute('base_user_profile', array('username' => $user->username)));
            }
            if ( !empty($_SERVER['HTTP_REFERER']) )
            {
                if(strpos( $_SERVER['HTTP_REFERER'], ":") === false ) {
                    $this->redirect($_SERVER['HTTP_REFERER']);
                }
            }
            $this->redirect(OW::getRouter()->urlForRoute('friends_list'));
        }
    }



    /**
     * Accept new friendship request
     *
     * @param array $params
     * @throws AuthenticateException
     */
    public function accept( $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $requesterId = (int) $params['id'];
        $userId = OW::getUser()->getId();
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            if(!isset($params['code'])){
                throw new Redirect404Exception();
            }
            $code = $params['code'];
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => $userId, 'code'=>$code,'activityType'=>'accept_friends')));
        }
        $service = FRIENDS_BOL_Service::getInstance();

        $frendshipDto = $service->accept($userId, $requesterId);

        if ( !empty($frendshipDto) )
        {
            $service->onAccept($userId, $requesterId, $frendshipDto);

            OW::getFeedback()->info(OW::getLanguage()->text('friends', 'feedback_request_accepted'));
        }

        if ( !empty($params['backUrl']) )
        {
            if(strpos( $params['backUrl'], ":") === false ) {
                $this->redirect($params['backUrl']);
            }
        }

        if ( $service->count(null, $userId, FRIENDS_BOL_Service::STATUS_PENDING) > 0 )
        {
            $backUrl = OW::getRouter()->urlForRoute('friends_lists', array('list'=>'got-requests'));
        }
        else
        {
            $backUrl = OW::getRouter()->urlForRoute('friends_list');
        }

        $this->redirect($backUrl);
    }

    /**
     * Ignore new friendship request
     *
     * @param array $params
     * @throws AuthenticateException
     */
    public function ignore( $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $requesterId = (int) OW::getUser()->getId();
        $userId = (int) $params['id'];

        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            $code =$params['code'];
            if(!isset($code)){
                throw new Redirect404Exception();
            }
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => $requesterId, 'code'=>$code,'activityType'=>'ignore_friends')));
        }
        $service = FRIENDS_BOL_Service::getInstance();

        $service->ignore($userId, $requesterId);

        OW::getFeedback()->info(OW::getLanguage()->text('friends', 'feedback_request_ignored'));

        $this->redirect( OW::getRouter()->urlForRoute('friends_lists', array('list'=>'got-requests')) );
    }

    /**
     * Cancel friendship
     *
     * @param array $params
     * @throws AuthenticateException
     */
    public function cancel( $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $requesterId = (int) $params['id'];
        $userId = (int) OW::getUser()->getId();
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            if(!isset($params['code'])){
                throw new Redirect404Exception();
            }
            $code = $params['code'];
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => $userId, 'code'=>$code,'activityType'=>'cancel_friends')));
        }

        //Remove notification that is produced due to friendship request
        $service = FRIENDS_BOL_Service::getInstance();
        $service->onCancelFriendshipRequest($requesterId,$userId);

        $event = new OW_Event('friends.cancelled', array(
            'senderId' => $requesterId,
            'recipientId' => $userId
        ));

        OW::getEventManager()->trigger($event);

        OW::getFeedback()->info(OW::getLanguage()->text('friends', 'feedback_cancelled'));

        if ( isset($params['redirect']) )
        {
            $username = BOL_UserService::getInstance()->getUserName($requesterId);
            $backUrl = OW::getRouter()->urlForRoute('base_user_profile', array('username'=>$username));
            $this->redirect($backUrl);
        }

        $this->redirect( OW::getRouter()->urlForRoute('friends_lists', array('list'=>'sent-requests')) );
    }

    public function activate( $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $requesterId = (int) $params['id'];
        $userId = (int) OW::getUser()->getId();

        FRIENDS_BOL_Service::getInstance()->activate($userId, $requesterId);

        OW::getFeedback()->info(OW::getLanguage()->text('friends', 'new_friend_added'));
        if ( !empty($_SERVER['HTTP_REFERER']) )
        {
            if(strpos( $_SERVER['HTTP_REFERER'], ":") === false ) {
                $this->redirect($_SERVER['HTTP_REFERER']);
            }
        }
        $this->redirect(OW::getRouter()->urlForRoute('friends_list'));
    }

    public function ajax()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect403Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $command = $_POST['command'];
        $data = json_decode($_POST['data'], true);

        $result = '';

        switch($command)
        {
            case 'friends-accept':
                $userId = (int) OW::getUser()->getId();
                $requesterId = (int) $data['id'];
                $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
                if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
                    $code =$data['code'];
                    if(!isset($code)){
                        throw new Redirect404Exception();
                    }
                    OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                        array('senderId' => $userId, 'code'=>$code,'activityType'=>'accept_friends')));
                }
                $service = FRIENDS_BOL_Service::getInstance();

                $frendshipDto = $service->accept($userId, $requesterId);

                if ( !empty($frendshipDto) )
                {
                    $service->onAccept($userId, $requesterId, $frendshipDto);
                }

                $feedback = OW::getLanguage()->text('friends', 'feedback_request_accepted');
                $result = "OW.info('{$feedback}');";
                break;
            
            case 'friends-ignore':
                $userId = (int) OW::getUser()->getId();
                $requesterId = (int) $data['id'];
                $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
                if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
                    $code =$data['code'];
                    if(!isset($code)){
                        throw new Redirect404Exception();
                    }
                    OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                        array('senderId' => $userId, 'code'=>$code,'activityType'=>'ignore_friends')));
                }
                $service = FRIENDS_BOL_Service::getInstance();

                $service->ignore($requesterId, $userId);

                $feedback = OW::getLanguage()->text('friends', 'feedback_request_ignored');
                $result = "OW.info('{$feedback}');";
                break;
        }

        echo json_encode(array(
            'script' => $result
        ));

        exit;
    }
}
