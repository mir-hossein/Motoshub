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
class NEWSFEED_CTRL_Ajax extends OW_ActionController
{
    /**
     *
     * @var NEWSFEED_BOL_Service
     */
    protected $service;

    public function __construct()
    {
        $this->service = NEWSFEED_BOL_Service::getInstance();
    }

    public function init()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }
    }

    public function like()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            $code =$_GET['code'];
            if(!isset($code)){
                throw new Redirect404Exception();
            }
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'like_feed')));
        }

        $entityType = !empty($_POST['entityType']) ?  $_POST['entityType'] : null;
        $entityId = !empty($_POST['entityId']) ? $_POST['entityId'] : null;

        $this->service->addLike(OW::getUser()->getId(), $entityType, $entityId);
        $this->afterLike($entityType, $entityId);
    }
    
    protected function afterLike( $entityType, $entityId )
    {
        $cmp = new NEWSFEED_CMP_Likes($entityType, $entityId);

        echo json_encode(array(
            'count' => $cmp->getCount(),
            'markup' => $cmp->render()
        ));

        exit;
    }

    public function users(){
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            $code =$_GET['code'];
            if(!isset($code)){
                throw new Redirect404Exception();
            }
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'users_feed')));
        }
        $entityType = !empty($_POST['entityType']) ?  $_POST['entityType'] : null;
        $entityId = !empty($_POST['entityId']) ? (int) $_POST['entityId'] : null;
        $likes = NEWSFEED_BOL_Service::getInstance()->findEntityLikes($entityType, $entityId);
        $userIds = array();
        foreach ( $likes as $like )
        {
            $userIds[] = (int) $like->userId;
        }
        echo json_encode($userIds);
        exit;
    }

    public function unlike()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            $code =$_GET['code'];
            if(!isset($code)){
                throw new Redirect404Exception();
            }
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'unlike_feed')));
        }
        $entityType = !empty($_POST['entityType']) ?  $_POST['entityType'] : null;
        $entityId = !empty($_POST['entityId']) ? (int) $_POST['entityId'] : null;
        $like = $this->service->findLike(OW::getUser()->getId(), $entityType, $entityId);

        $this->service->removeLike(OW::getUser()->getId(), $entityType, $entityId);

        $event = new OW_Event('feed.after_like_removed', array(
            'entityType' => $entityType,
            'entityId' => $entityId,
            'userId' => OW::getUser()->getId()
        ));

        OW::getEventManager()->trigger($event);
        if ($entityType == 'user-status'){
            OW::getEventManager()->call('notifications.remove', array(
                'entityType' => 'status_like',
                'entityId' => $like->getId()
            ));
        }

        if ($entityType == 'birthday'){
            OW::getEventManager()->call('notifications.change.birthday.like', array(
                'entityType' => 'birthday',
                'entityId' => $like->entityId
            ));
        }
        
        $this->afterUnlike($entityType, $entityId);
    }
    
    protected function afterUnlike( $entityType, $entityId )
    {
        $this->afterLike($entityType, $entityId);
    }

    public function statusUpdate()
    {
        if ( empty($_POST['status']) && empty($_POST['attachment']) )
        {
            echo json_encode(array(
                "error" => OW::getLanguage()->text('base', 'form_validate_common_error_message')
            ));
            exit;
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            echo json_encode(array(
                "error" => OW::getLanguage()->text('base', 'form_validate_common_error_message')
            ));
            exit;
        }

        //checking csrf hash
        $params = $_POST;
        $params['actionUrl'] = OW_Router::getInstance()->urlFor('NEWSFEED_CTRL_Ajax', 'statusUpdate');
        $event = new OW_Event('iissecurityessentials.after.form.submission', $params);
        OW::getEventManager()->trigger($event);
        if(isset($event->getData()['not_allowed'])){
            echo json_encode(array(
                "error" => OW::getLanguage()->text('base', 'form_validate_common_error_message')
            ));
            exit;
        }

        $oembed = null;
        $attachId = null;
        $status = empty($_POST['status']) ? '' : strip_tags($_POST['status']);

        /**
         * replace unicode emoji characters
         */
        $replaceUnicodeEmoji= new OW_Event('iis.replace.unicode.emoji', array('text' => $status));
        OW::getEventManager()->trigger($replaceUnicodeEmoji);
        if(isset($replaceUnicodeEmoji->getData()['correctedText'])) {
            $status = $replaceUnicodeEmoji->getData()['correctedText'];
        }
        /**
         * remove remaining utf8 unicode emoji characters
         */
        $removeUnicodeEmoji= new OW_Event('iis.remove.unicode.emoji', array('text' => $status));
        OW::getEventManager()->trigger($removeUnicodeEmoji);
        if(isset($removeUnicodeEmoji->getData()['correctedText'])) {
            $status = $removeUnicodeEmoji->getData()['correctedText'];
        }
        $content = array();

        if (!empty($_POST['attachment'])) {
            $content = json_decode($_POST['attachment'], true);

            if (!empty($content)) {
                if ($content['type'] == 'photo' && !empty($content['uid'])) {
                    $attachmentData = OW::getEventManager()->call('base.attachment_save_image', array(
                        "pluginKey" => "newsfeed",
                        'uid' => $content['uid']
                    ));

                    $content['url'] = $content['href'] = $attachmentData["url"];
                    $attachId = $content['uid'];
                }

                if ($content['type'] == 'video') {
                    $content['html'] = BOL_TextFormatService::getInstance()->validateVideoCode($content['html']);
                }
            }
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

        if (!empty($data)) {
            if (!empty($attachId)) {
                BOL_AttachmentService::getInstance()->deleteAttachmentByBundle("newsfeed", $attachId);
            }

            $item = empty($data["entityType"]) || empty($data["entityId"])
                ? null
                : array(
                    "entityType" => $data["entityType"],
                    "entityId" => $data["entityId"]
                );

            $eventIisGroupsPlusManager = new OW_Event('iisgroupsplus.on.update.group.status', array('feedId' => $_POST['feedId'],
                'feedType' => $_POST['feedType'], 'status' => $_POST['status'], 'statusId'=>$item['entityId']));
            OW::getEventManager()->trigger($eventIisGroupsPlusManager);

            echo json_encode(array(
                "item" => $item,
                "message" => empty($data["message"]) ? null : $data["message"],
                "error" => empty($data["error"]) ? null : $data["error"]
            ));
            exit;
        }

        $status = UTIL_HtmlTag::autoLink($status);
        $out = NEWSFEED_BOL_Service::getInstance()
            ->addStatus(OW::getUser()->getId(), $_POST['feedType'], $_POST['feedId'], $visibility, $status, array(
                "content" => $content,
                "attachmentId" => $attachId
            ));
        echo json_encode(array(
            "item" => $out
        ));
        exit;
    }

    public function remove()
    {
        $id = !empty($_POST['actionId']) ? (int) $_POST['actionId'] : null;

        if ( !$id )
        {
            throw new Redirect404Exception();
        }

        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            $code =$_GET['code'];
            if(!isset($code)){
                throw new Redirect404Exception();
            }
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'remove_feed')));
        }

        $dto = $this->service->findActionById($id);

        if ( empty($dto) )
        {
            exit;
        }

        // check permissions
        $removeAllowed = OW::getUser()->isAuthorized("newsfeed");

        if ( !$removeAllowed )
        {
            $activities = $this->service->
                    findActivity(NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE . ':' . $dto->id);

            // check for the ownership
            foreach ($activities as $activity) {
                $feedObject = NEWSFEED_BOL_Service::getInstance()->findFeedListByActivityids(array($activity->id));
                $feedId = $feedObject[$activity->id][0]->feedId;
                if ( OW::getUser()->getId() == $feedId ) {
                    $removeAllowed = true;
                    break;
                }

                if ( OW::getUser()->getId() == $activity->userId ) {
                    $removeAllowed = true;
                    break;
                }
            }
        }

        if ( $removeAllowed )
        {
            if ($dto->entityType == 'groups-status' || $dto->entityType == 'user-status'){
                $this->service->removeLikeNotifications($dto->entityType, $dto->entityId);
                OW::getEventManager()->trigger(new OW_Event(BOL_ContentService::EVENT_BEFORE_DELETE, array(
                    "entityType" => $dto->entityType,
                    "entityId" => $dto->entityId
                )));

            }
            $this->service->removeActionById($id);
            echo json_encode(array(
                'msg' => OW::getLanguage()->text('newsfeed', 'item_deleted_feedback'),
                'url' => OW::getRouter()->urlForRoute('base_member_dashboard')
            ));
            OW_EventManager::getInstance()->trigger(new OW_Event('hashtag.on_entity_change', array('entityType' => $dto->entityType, 'entityId'=>$dto->entityId, 'actionId' => $dto->id)));
        }

        $event = new OW_Event('newsfeed_after_feed_remove', array('action_id' => $id));
        OW_EventManager::getInstance()->trigger($event);

        exit;
    }

    public function removeAttachment()
    {
        $id = !empty($_POST['actionId']) ? (int) $_POST['actionId'] : null;

        if ( !$id )
        {
            throw new Redirect404Exception();
        }

        $dto = $this->service->findActionById($id);
        $data = json_decode($dto->data, true);

        if( !empty($data['attachmentId']) )
        {
            BOL_AttachmentService::getInstance()->deleteAttachmentByBundle("newsfeed", $data['attachmentId']);
        }

        unset($data['attachment']);
        $dto->data = json_encode($data);

        $this->service->saveAction($dto);

        exit;
    }

    public function loadItem()
    {
        $params = json_decode($_GET['p'], true);

        $feedData = $params['feedData'];

        $driverClass = $feedData['driver']['class'];
        /* @var $driver NEWSFEED_CLASS_Driver */
        $driver = OW::getClassInstance($driverClass);
        $driver->setup($feedData['driver']['params']);

        if ( isset($params['actionId']) )
        {
            $action = $driver->getActionById($params['actionId']);
        }
        else if ( isset($params['entityType']) && isset($params['entityId']) )
        {
            $action = $driver->getAction($params['entityType'], $params['entityId']);
        }
        else
        {
            throw new InvalidArgumentException('Invalid paraeters: `entityType` and `entityId` or `actionId`');
        }

        if ( $action === null )
        {
            $this->echoError('Action not found');
        }

        $data = $feedData['data'];

        $sharedData['feedAutoId'] = $data['feedAutoId'];
        $sharedData['feedType'] = $data['feedType'];
        $sharedData['feedId'] = $data['feedId'];

        $sharedData['configs'] = OW::getConfig()->getValues('newsfeed');

        $userIdList = array($action->getUserId());
        $sharedData['usersIdList'] = $userIdList;

        $usersInfo = BOL_AvatarService::getInstance()->getDataForUserAvatars($userIdList);

        $sharedData['usersInfo']['avatars'][$action->getUserId()] = $usersInfo[$action->getUserId()]['src'];
        $sharedData['usersInfo']['urls'][$action->getUserId()] = $usersInfo[$action->getUserId()]['url'];
        $sharedData['usersInfo']['names'][$action->getUserId()] = $usersInfo[$action->getUserId()]['title'];
        $sharedData['usersInfo']['roleLabels'][$action->getUserId()] = array(
            'label' => $usersInfo[$action->getUserId()]['label'],
            'labelColor' => $usersInfo[$action->getUserId()]['labelColor']
        );

        $entityList = array();
        $entityList[] = array(
            'entityType' => $action->getEntity()->type,
            'entityId' => $action->getEntity()->id,
            'pluginKey' => $action->getPluginKey(),
            'userId' => $action->getUserId(),
            'countOnPage' => $sharedData['configs']['comments_count']
        );

        $sharedData['commentsData'] = BOL_CommentService::getInstance()->findBatchCommentsData($entityList);
        $sharedData['likesData'] = NEWSFEED_BOL_Service::getInstance()->findLikesByEntityList($entityList);

        $cmp = $this->createFeedItem($action, $sharedData);
        $cmp->setDisplayType($data['displayType']);
        $html = $cmp->renderMarkup(empty($params['cycle']) ? null : $params['cycle']);

        $eventTest = new OW_Event('newsfeed.load_new_feed_item_html',$params);
        OW_EventManager::getInstance()->trigger($eventTest);
        if(isset($eventTest->getData()['html'])) {
            $html = $eventTest->getData()['html'];
        }
        $this->synchronizeData($data['feedAutoId'], array(
            'data' => $data,
            'driver' => $driver->getState()
        ));

        $this->echoMarkup($html);
    }
    
    /**
     * 
     * @param NEWSFEED_CLASS_Action $action
     * @param array $sharedData
     * @return NEWSFEED_CMP_FeedItem
     */
    protected function createFeedItem( $action, $sharedData )
    {
        return OW::getClassInstance("NEWSFEED_CMP_FeedItem", $action, $sharedData);
    }

    public function loadItemList()
    {
        $params = json_decode($_GET['p'], true);

        //to prevent unauthorized access
        if($params['driver']['params']['feedType'] == 'my'){
            if(!OW::getUser()->isAuthenticated()){
                $params['data']['feedType'] = $params['driver']['params']['feedType'] = 'site';
                $params['data']['feedId'] = $params['driver']['params']['feedId'] = '';
            }else if( !OW::getUser()->isAdmin() && !OW::getUser()->getId()==$params['driver']['params']['feedId']){
                $params['data']['feedType'] = $params['driver']['params']['feedType'] = 'site';
                $params['data']['feedId'] = $params['driver']['params']['feedId'] = '';
            }
        }

        $event = new OW_Event('feed.on_ajax_load_list', $params);
        OW::getEventManager()->trigger($event);

        $driverClass = $params['driver']['class'];

        /*@var $cmp NEWSFEED_CLASS_Driver */
        $driver = OW::getClassInstance($driverClass);

        $driverParams = $params['driver']['params'];
        $driverParams['displayCount'] = $driverParams['displayCount'] > 20 ? 20 : $driverParams['displayCount'];

        $driver->setup($driverParams);

        $driver->moveCursor();
        $actionList = $driver->getActionList();

        $list = $this->createFeedList($actionList, $params['data']);
        $list->setDisplayType($params['data']['displayType']);
        $html = $list->render();

        $this->synchronizeData($params['data']['feedAutoId'], array(
            'data' => $params['data'],
            'driver' => $driver->getState()
        ));

        $this->echoMarkup($html);
    }

    /**
     * 
     * @param array $actionList
     * @param array $data
     * @return NEWSFEED_CMP_FeedList
     */
    protected function createFeedList( $actionList, $data )
    {
        return OW::getClassInstance("NEWSFEED_CMP_FeedList", $actionList, $data);
    }
    
    private function synchronizeData( $autoId, $data )
    {
        $script = UTIL_JsGenerator::newInstance()
                ->callFunction(array('window', 'ow_newsfeed_feed_list', $autoId, 'setData'), array($data));
        OW::getDocument()->addOnloadScript($script);
    }

    private function echoError( $msg, $code = null )
    {
        echo json_encode(array(
            'result' => 'error',
            'code' => $code,
            'msg' => $msg
        ));

        exit;
    }

    private function echoMarkup( $html )
    {
        /* @var $document OW_AjaxDocument */
        $document = OW::getDocument();

        $markup = array();

        $markup['result'] = 'success';
        $markup['html'] = trim($html);

        $beforeIncludes = $document->getScriptBeforeIncludes();
        if ( !empty($beforeIncludes) )
        {
            $markup['beforeIncludes'] = $beforeIncludes;
        }

        $scripts = $document->getScripts();
        if ( !empty($scripts) )
        {
            $markup['scriptFiles'] = $scripts;
        }

        $styleSheets = $document->getStyleSheets();
        if ( !empty($styleSheets) )
        {
            $markup['styleSheets'] = $styleSheets;
        }

        $onloadScript = $document->getOnloadScript();
        if ( !empty($onloadScript) )
        {
            $markup['onloadScript'] = $onloadScript;
        }

        $styleDeclarations = $document->getStyleDeclarations();
        if ( !empty($styleDeclarations) )
        {
            $markup['styleDeclarations'] = $styleDeclarations;
        }

        $encodedMarkup=json_encode($markup);
        if(!$encodedMarkup)
        {
            $encodedMarkup=json_encode($this->utf8ize($markup));
        }
        echo $encodedMarkup;

        exit;
    }

    function utf8ize($mixed)
    {
        if(is_array($mixed))
        {
            foreach ($mixed as $key=>$value) {
                $mixed[$key]=$this->utf8ize($value);
            }
        }
        else if(is_string($mixed)){
            return mb_convert_encoding($mixed,"UTF-8","UTF-8");
        }
        return $mixed;
    }
}