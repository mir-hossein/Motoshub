<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * 
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iismobilesupport.bol
 * @since 1.0
 */
class IISMOBILESUPPORT_BOL_Service
{
    private static $classInstance;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private $deviceDao;
    private $appVersionDao;
    public $AndroidKey = 1;
    public $iOSKey = 2;
    public $nativeFcmKey = 3;
    public $webFcmKey = 4;
    public $COOKIE_SAVE_DAY = 365;
    CONST IISMOBILESUPPORT_CATCH_REQUEST='iismobilesupport.catch.request';

    const EVENT_SEND_NOTIFICATION_INCOMPLETE = 'iismobilesupport.send_notification_incomplete';
    const EVENT_AFTER_SAVE_NOTIFICATIONS = 'iismobilesupport.after_save_notifications';

    private function __construct()
    {
        $this->deviceDao = IISMOBILESUPPORT_BOL_DeviceDao::getInstance();
        $this->appVersionDao = IISMOBILESUPPORT_BOL_AppVersionDao::getInstance();
    }

    public function getAppInformation($type, $currentVersionCode){
        $information = array();
        $information['versionName'] = "";
        $information['versionCode'] = "";
        $information['lastVersionUrl'] = "";
        $information['isDeprecated'] = "false";
        $information['message'] = "";

        $lastVersion = $this->getLastVersions($type);
        if($lastVersion!=null){
            $information['versionName'] = $lastVersion->versionName;
            $information['versionCode'] = $lastVersion->versionCode;
            $information['lastVersionUrl'] = $lastVersion->url;
            $information['message'] = $lastVersion->message;
        }

        if($currentVersionCode!=null && $currentVersionCode != ""){
            $userCurrentVersion = $this->getVersionUsingCode($type, $currentVersionCode);
            if ($userCurrentVersion != null) {
                $information['isDeprecated'] = $userCurrentVersion->deprecated ? "true" : "false";
            }
        }

        return $information;
    }

    /***
     * @param $data
     */
    public function sendNotification($data){
        $lastViewedNotification = NOTIFICATIONS_BOL_NotificationDao::getInstance()->getLastViewedNotificationId($data['userId']);
        IISMOBILESUPPORT_BOL_Service::getInstance()->sendDataToDevice($data, $lastViewedNotification);
    }

    /***
     * @param $type
     * @return array
     */
    public function getAllVersions($type){
        return $this->appVersionDao->getAllVersions($type);
    }

    /***
     * @param $type
     * @return mixed
     */
    public function getLastVersions($type){
        return $this->appVersionDao->getLastVersions($type);
    }

    /***
     * @param $id
     */
    public function deleteVersion($id){
        $this->appVersionDao->deleteVersion($id);
    }

    /***
     * @param $id
     * @return mixed
     */
    public function deprecateVersion($id){
        return $this->appVersionDao->deprecateVersion($id);
    }

    /***
     * @param $id
     * @return mixed
     */
    public function approveVersion($id){
        return $this->appVersionDao->approveVersion($id);
    }

    /***
     * @param $type
     * @param $versionCode
     * @return mixed
     */
    public function getVersionUsingCode($type, $versionCode){
        return $this->appVersionDao->getVersionUsingCode($type, $versionCode);
    }

    /***
     * @param $type
     * @param $versionName
     * @param $versionCode
     * @return bool
     */
    public function hasVersion($type, $versionName, $versionCode){
        return $this->appVersionDao->hasVersion($type, $versionName, $versionCode);
    }

    /**
     * @param $type
     * @param $versionName
     * @param $versionCode
     * @param $url
     * @param $message
     * @return bool|IISMOBILESUPPORT_BOL_AppVersion
     */
    public function saveVersion($type, $versionName, $versionCode, $url, $message = ''){
        return $this->appVersionDao->saveVersion($type, $versionName, $versionCode, $url, $message);
    }

    /***
     * @param $type
     * @return array
     */
    public function getArraysOfVersions($type){
        $versions = $this->getAllVersions($type);
        $lastVersion = $this->getLastVersions($type);
        $versionsArray = array();
        foreach ($versions as $value) {
            $versionInformation = array(
                'versionName' => $value->versionName,
                'versionCode' => $value->versionCode,
                'message' => $value->message,
                'isDeprecated' => $value->deprecated == true ? "0" : "1",
                'time' => UTIL_DateTime::formatSimpleDate($value->timestamp),
                'deleteUrl' => "if(confirm('".OW::getLanguage()->text('iismobilesupport','delete_item_warning')."')){location.href='" . OW::getRouter()->urlForRoute('iismobilesupport-admin-delete-value', array('id' => $value->id)) . "';}",
                'downloadFile'=> $value->url
            );
            $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$value->id,'isPermanent'=>true,'activityType'=>'delete_mobileVersion')));
            if(isset($iisSecuritymanagerEvent->getData()['code'])){
                $code = $iisSecuritymanagerEvent->getData()['code'];
                $versionInformation['deleteUrl'] = "if(confirm('".OW::getLanguage()->text('iismobilesupport','delete_item_warning')."')){location.href='" .
                    OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('iismobilesupport-admin-delete-value',
                        array('id' => $value->id)),array('code'=>$code)) . "';}";
            }
            if($value->deprecated){
                $versionInformation['deprecateLabel'] = OW::getLanguage()->text('iismobilesupport', 'approve');
                $versionInformation['deprecateUrl'] = "location.href='" . OW::getRouter()->urlForRoute('iismobilesupport-admin-approve-value', array('id' => $value->id)) . "';";
                $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                    array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$value->id,'isPermanent'=>true,'activityType'=>'approve_mobileVersion')));
                if(isset($iisSecuritymanagerEvent->getData()['code'])) {
                    $code = $iisSecuritymanagerEvent->getData()['code'];
                    $versionInformation['deprecateUrl'] = "location.href='" .
                        OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('iismobilesupport-admin-approve-value',
                            array('id' => $value->id)), array('code' => $code)) . "';";
                }
            }else{
                $versionInformation['deprecateLabel'] = OW::getLanguage()->text('iismobilesupport', 'deprecate');
                $versionInformation['deprecateUrl'] = "location.href='" . OW::getRouter()->urlForRoute('iismobilesupport-admin-deprecate-value', array('id' => $value->id)) . "';";
                $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                    array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$value->id,'isPermanent'=>true,'activityType'=>'deprecate_mobileVersion')));
                if(isset($iisSecuritymanagerEvent->getData()['code'])) {
                    $code = $iisSecuritymanagerEvent->getData()['code'];
                    $versionInformation['deprecateUrl'] = "location.href='" . OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('iismobilesupport-admin-deprecate-value', array('id' => $value->id)),array('code'=>$code)) . "';";
                }
            }

            $versionInformation['isLastVersion'] = false;
            if($lastVersion!=null && $lastVersion->versionCode == $value->versionCode){
                $versionInformation['isLastVersion'] = true;
            }

            $versionsArray[] = $versionInformation;
        }

        return $versionsArray;
    }

    /***
     * @param $userId
     * @return array
     */
    public function getUsersDevices($userId){
        return $this->deviceDao->getUsersDevices($userId);
    }

    public function deleteInActiveDevicesOfUser($userId){
        $devices = IISMOBILESUPPORT_BOL_Service::getInstance()->getUsersDevices($userId);
        $data = array(
            "userId" => $userId,
            "title" => 'check-user-activity',
            "description" => 'check-user-activity',
            "avatarUrl" => "",
            "notificationId" => "0",
        );

        $this->sendDataToDevice($data,0, $devices, [], true);
    }

    /***
     * @param $inputData
     * @param $lastViewedNotification
     * @param null $devices
     * @param $blackList
     * @param $waitForResponse
     */
    public function sendDataToDevice($inputData, $lastViewedNotification, $devices = null, $blackList = array(), $waitForResponse=false){
        if(is_array($inputData)){
            $inputData = (object)$inputData;
        }
        if($devices == null) {
            $devices = IISMOBILESUPPORT_BOL_Service::getInstance()->getUsersDevices($inputData->userId);
        }

        $androidDeviceTokens = array();
        $iosDeviceTokens = array();
        $nativeSignalTokens = array();
        $webTokens = array();
        $checkDescription = trim($inputData->description);
        foreach ($devices as $device) {
            if($device->type == $this->iOSKey && !in_array($this->iOSKey, $blackList)){
                if (!empty($checkDescription)) {
                    $iosDeviceTokens[] = $device->token;
                }
            }else if($device->type == $this->AndroidKey && !in_array($this->AndroidKey, $blackList)){
                $androidDeviceTokens[] = $device->token;
            }else if($device->type == $this->nativeFcmKey && !in_array($this->nativeFcmKey, $blackList)){
                if (!empty($checkDescription)) {
                    $nativeSignalTokens[] = $device->token;
                }
            }else if($device->type == $this->webFcmKey && !in_array($this->webFcmKey, $blackList)){
                if (!empty($checkDescription)) {
                    $webTokens[] = $device->token;
                }
            }
        }

        //go to notifications page
        $url = OW::getRouter()->urlForRoute('iismobilesupport-notifications');

        if(OW::getConfig()->getValue('iismobilesupport', 'disable_notification_content')){
            $inputData->description = OW::getLanguage()->text('iismobilesupport', 'new_notification_label');
        }else{
            if(isset($inputData->type) && $inputData->type == 'chat_direct_notification'){
            }else if(isset($inputData->description) && !empty($inputData->description)){
                $inputData->description = OW::getLanguage()->text('iismobilesupport', 'new_notification_label') . ': '. $inputData->description;
            }
        }

        $sendData = array();

        if(!empty($androidDeviceTokens) || !empty($webTokens)){
            $sendData[] = $this->getJsonDataForSendingToAndroidDevices($inputData, $url, array_merge($androidDeviceTokens, $webTokens) , $lastViewedNotification);
        }

        if(!empty($iosDeviceTokens) || !empty($nativeSignalTokens)){
            $sendData[] = $this->getDefaultJsonDataForSendingToDevices($inputData, $url, array_merge($iosDeviceTokens, $nativeSignalTokens));
        }

        if (!empty($sendData)) {
            foreach ($sendData as $datum) {
                $this->postDataToMobile($datum, $waitForResponse);
            }
        }
    }

    public function onMailboxSendMessage(OW_Event $event){
        $params = $event->getParams();
        if(isset($params['senderId']) && isset($params['recipientId'])) {
            $senderId = $params['senderId'];
            $recipientId = $params['recipientId'];
            $message = $event->getData();
            OW::getEventManager()->trigger(new OW_Event('iismobilesupport.send_message', array('message' => $message, 'userId' => $senderId, 'opponentId' => $recipientId)));
        }
    }

    public function onMarkConversation(OW_Event $event){
        $params = $event->getParams();
        if(isset($params['conversationIdList']) && isset($params['markType'])){
            $conversationIdList = $params['conversationIdList'];
            $markType = $params['markType'];

            if(!is_array($conversationIdList)){
                return;
            }

            if($markType != 'read'){
                return;
            }

            $data = array();
            foreach ($conversationIdList as $cid){
                $conversation = MAILBOX_BOL_ConversationService::getInstance()->getConversation($cid);
                if($conversation != null){
                    $singleData = array();
                    $singleData['userId1'] = $conversation->initiatorId;
                    $singleData['userId2'] = $conversation->interlocutorId;
                    $singleData['conversationId'] = $cid;
                    $data[] = $singleData;
                }
            }

            foreach ($data as $singleData){
                $item['type'] = 'mark';
                $item['conversationId'] = $singleData['conversationId'];
                $additionalData = array('message' => $item);
                if (OW::getUser()->getId() != $singleData['userId1']) {
                    $this->sendDataUsingFirebaseForUserId($data, $additionalData, $singleData['userId1']);
                }
                if (OW::getUser()->getId() != $singleData['userId2']) {
                    $this->sendDataUsingFirebaseForUserId($data, $additionalData, $singleData['userId2']);
                }
            }
        }
    }

    public function onSendMessageAttachment(OW_Event $event){
        $params = $event->getParams();
        if(isset($params['messageId'])){
            $messageId = $params['messageId'];
            $message = MAILBOX_BOL_MessageDao::getInstance()->findById($messageId);
            if($message == null) {
                return;
            }
            $item = MAILBOX_BOL_ConversationService::getInstance()->getMessageDataForApi($message);
            if(isset($_POST['_id'])){
                $item['_id'] = $_POST['_id'];
            }
            if (isset($item['text'])) {
                $item['text'] = MAILBOX_BOL_ConversationService::getInstance()->json_decode_text($item['text']);
            }
//            $this->sendDataToFirebase(array('message' => $item), $opponentId, $userId);
            $data = array();
            $additionalData = array('message' => $item);
            $this->sendDataUsingFirebaseForUserId($data, $additionalData, $message->senderId);
            $this->sendDataUsingFirebaseForUserId($data, $additionalData, $message->recipientId);
        }
    }

    public function onSendMessage(OW_Event $event){
        if (isset($_FILES) && sizeof($_FILES) > 0) {
            return;
        }
        $params = $event->getParams();
        if(isset($params['message']) && isset($params['userId']) && $params['opponentId']){
            $message = $params['message'];
            $userId = $params['userId'];
            $opponentId = $params['opponentId'];
            $item = MAILBOX_BOL_ConversationService::getInstance()->getMessageDataForApi($message);
            if(isset($_POST['_id'])){
                $item['_id'] = $_POST['_id'];
            }

            $data = array();
            if (isset($item['text'])) {
                $item['text'] = MAILBOX_BOL_ConversationService::getInstance()->json_decode_text($item['text']);
            }
            $additionalData = array('message' => $item);
            $this->sendDataUsingFirebaseForUserId($data, $additionalData, $userId);
            $this->sendDataUsingFirebaseForUserId($data, $additionalData, $opponentId);

            //FCM notification
            $lastViewedNotification = NOTIFICATIONS_BOL_NotificationDao::getInstance()->getLastViewedNotificationId($opponentId);
            $userDisplayName = BOL_UserService::getInstance()->getDisplayName($userId);
            $userAvatar = BOL_AvatarService::getInstance()->getAvatarUrl($userId);
            if ($userAvatar == null) {
                $userAvatar = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
            }
            $title = OW::getConfig()->getValue('base', 'site_name');
            $data = (object)[
                'type'=>'chat_direct_notification',
                'description' => $userDisplayName.": ".$message->text,
                'title' => $title,
                'notification_id' => $lastViewedNotification + 1,
                'userId' => $opponentId,
                'avatarUrl' => $userAvatar,
                'type_concat' => $userId,
                'senderRealname' => $userDisplayName,
                'senderUserId' => $userId,
            ];
            $this->sendDataToDevice($data, $lastViewedNotification);
        }
    }

    public function sendDataUsingFirebaseForUserId($data, $additionalData = array(), $userId = null){
        if( $userId == null ){
            return null;
        }

        $devices = IISMOBILESUPPORT_BOL_Service::getInstance()->getUsersDevices($userId);
        $firebaseTokens = array();
        foreach ($devices as $device) {
            if($device->type == $this->nativeFcmKey){
                $firebaseTokens[] = $device->token;
            }
        }

        if(!empty($firebaseTokens)){
            $url = OW::getRouter()->urlForRoute('iismobilesupport-notifications');
            $sendData = $this->getDefaultJsonDataForSendingToDevices($data, $url, $firebaseTokens, true, $additionalData);
            $this->postDataToMobile($sendData);
        }

        return null;
    }

    public function sendDataToFirebase($data = array(), $opponentId, $userId){
        if( $opponentId == null || !isset($data['message'])){
            return null;
        }
        $message = $data['message'];
        $data = array();
        $data['type'] = 'chat_direct_notification';
        $data['notification_id'] = -1;
        $data['userId'] = $opponentId;
        $data['description'] = BOL_UserService::getInstance()->getDisplayName($userId) . ': ' . $message['text'];
        $data['title']  = OW::getLanguage()->text('mailbox', 'label_btn_new_message');
        $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($userId);
        $profileAvatarUrl = empty($avatarUrl) ? BOL_AvatarService::getInstance()->getDefaultAvatarUrl(2) : $avatarUrl;
        $data['avatarUrl']  = $profileAvatarUrl;
        $data['url']  = BOL_UserService::getInstance()->getUserUrl($userId);
        IISMOBILESUPPORT_BOL_Service::getInstance()->sendNotification($data);
    }

    /***
     * @param $sendData
     * @param bool $waitForResponse
     * @return array
     */
    public function postDataToMobile($sendData, $waitForResponse = false){
        if (defined('RABBIT_HOST') && defined('RABBIT_PORT') && defined('RABBIT_USER') && defined('RABBIT_PASSWORD') ) {
            $sendData['itemType'] = 'notification';

            $rabbitConnection = new AMQPStreamConnection(RABBIT_HOST, RABBIT_PORT, RABBIT_USER, RABBIT_PASSWORD);
            $channel = $rabbitConnection->channel();

            $queueName = 'queue';
            if (defined('RABBIT_QUEUE_NAME')) {
                $queueName = RABBIT_QUEUE_NAME;
            }

            $channel->queue_declare($queueName, false, false, false, false);

            $msg = new AMQPMessage(json_encode($sendData));
            $channel->basic_publish($msg, '', $queueName);

            $channel->close();
            $rabbitConnection->close();
        } else {
            $this->sendDataToFCM($sendData, $waitForResponse);
        }
    }

    public function sendDataToFCM($sendData, $waitForResponse = false) {
        $fcmUrl = OW::getConfig()->getValue('iismobilesupport','fcm_api_url');
        $fcmKey = OW::getConfig()->getValue('iismobilesupport','fcm_api_key');

        if ($fcmUrl == null || $fcmUrl == '' || $fcmKey == null || $fcmKey == ''){
            return;
        }

        $params = new UTIL_HttpClientParams();
        $params->setHeader('Content-Type' ,'application/json');
        $params->setHeader('Authorization' ,'key=' . $fcmKey);
        $params->setJson($sendData);
        if(!$waitForResponse){
            $params->setTimeout(0.5);
        }
        try {
            $response = UTIL_HttpClient::post($fcmUrl, $params);
            if ($response != null) {
                $userId = null;
                if ( is_array($sendData) && isset($sendData['notification']['userId'])){
                    $userId = $sendData['notification']['userId'];
                } else if (isset($sendData->notification->userId)) {
                    $userId = $sendData->notification->userId;
                }
                if ($userId == null) {
                    return;
                }
                $devices = IISMOBILESUPPORT_BOL_Service::getInstance()->getUsersDevices($userId);
                $results = json_decode($response->getBody())->results;

                $orderOfTokensMustBeDeleted = array();
                $count = 0;
                foreach($results as $result){
                    if(isset($result->error) && ($result->error=='InvalidRegistration' || $result->error=='NotRegistered')){
                        $orderOfTokensMustBeDeleted[] = $count;
                    }
                    $count++;
                }

                $count = 0;
                foreach($devices as $device){
                    if(in_array($count, $orderOfTokensMustBeDeleted)){
                        $this->deleteUserDevice($userId, $device->token);
                        OW::getLogger()->writeLog(OW_Log::INFO, 'fcm_delete_device', [ 'result'=>'ok', 'userId' => $userId, 'token' => $device->token]);
                    }
                    $count++;
                }
            }else{
                $results = ['connect_timeout' => true];
            }
            OW::getLogger()->writeLog(OW_Log::INFO, 'fcm_post_to_mobile', [ 'result'=>'ok', 'response' => $results]);
        } catch (Exception $e) {
            OW::getLogger()->writeLog(OW_Log::INFO, 'fcm_post_to_mobile', [ 'result'=>'http_error', 'message' => $e->getMessage()]);
        }
    }

    /***
     * @param $inputData
     * @param $url
     * @param $deviceTokens
     * @param $lastViewedNotification
     * @return mixed
     */
    public function getJsonDataForSendingToAndroidDevices($inputData, $url, $deviceTokens, $lastViewedNotification){
        $data = array();
        $title = null;
        $userId = null;
        if(isset($inputData->title) && $inputData->title != null && $inputData->title != ''){
            $title = $inputData->title;
        }
        if(isset($inputData->userId) && $inputData->userId != null && $inputData->userId != ''){
            $userId = $inputData->userId;
        }
        $description = '';
        if(isset($inputData->description) && $inputData->description != null && $inputData->description != '') {
            $description = strip_tags($inputData->description);
        }
        $avatarUrl = null;
        if(isset($inputData->avatarUrl) && $inputData->avatarUrl != null && $inputData->avatarUrl != '') {
            $avatarUrl = $inputData->avatarUrl;
        }
        if(isset($inputData->type)){
            $data['type'] = $inputData->type;
        }
        $data['lastViewedNotification'] = $lastViewedNotification;
        $data['notificationId'] = isset($inputData->notification_id)?$inputData->notification_id:0;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['avatarUrl'] = $avatarUrl;
        $data['userId'] = $userId;
        $data['url'] = $url;
        $sendData['data'] = $data;
        $sendData["registration_ids"] = $deviceTokens;
        return $sendData;
    }

    /***
     * @param $inputData
     * @param $url
     * @param $deviceTokens
     * @param $sendDataOnly
     * @param $additionalData
     * @return mixed
     */
    public function getDefaultJsonDataForSendingToDevices($inputData, $url, $deviceTokens, $sendDataOnly = false, $additionalData = array()){
        $title = null;
        if(isset($inputData->title) && $inputData->title != null && $inputData->title != ''){
            $title = $inputData->title;
        }
        $userId = null;
        if(isset($inputData->userId) && $inputData->userId != null && $inputData->userId != ''){
            $userId = $inputData->userId;
        }
        $senderUserId = null;
        if(isset($inputData->senderUserId) && $inputData->senderUserId != null && $inputData->senderUserId != ''){
            $senderUserId = $inputData->senderUserId;
        }
        $senderRealname = null;
        if(isset($inputData->senderRealname) && $inputData->senderRealname != null && $inputData->senderRealname != ''){
            $senderRealname = $inputData->senderRealname;
        }
        $description = '';
        if(isset($inputData->description) && $inputData->description != null && $inputData->description != '') {
            $description = $inputData->description;
        }
        $avatarUrl = null;
        if(isset($inputData->avatarUrl) && $inputData->avatarUrl != null && $inputData->avatarUrl != '') {
            $avatarUrl = $inputData->avatarUrl;
        }

        $data = array();
        $data['title'] = $title;
        $data['body'] = strip_tags($description);
        $data['sound'] = "default";
        $data['avatarUrl'] = $avatarUrl;
        $data['url'] = $url;
        $data['userId'] = $userId;
        $data['senderUserId'] = $senderUserId;
        $data['senderRealname'] = $senderRealname;
        if (isset($inputData->type) && $inputData->type == 'chat_direct_notification') {
            $tag = $inputData->type;
            if (isset($inputData->type_concat)) {
                $tag .= $inputData->type_concat;
            }
            $data["tag"] = $tag;
        }
        if(!$sendDataOnly){
            $sendData['notification'] = $data;
            $sendData['data'] = $data;
        }else{
            $data['data'] = $additionalData;
            $sendData['data'] = $data;
        }
        $sendData["registration_ids"] = $deviceTokens;
        $sendData["priority"] = "high";

        return $sendData;
    }

    /***
     * @param $userId
     */
    public function deleteAllDevicesOfUser($userId){
        $this->deviceDao->deleteAllDevicesOfUser($userId);
    }


    /***
     * @param $userId
     * @param $token
     * @param $type
     * @param $cookie
     */
    public function saveDevice($userId, $token, $type, $cookie){
        if(!$this->hasUserDevice($userId, $token)) {
            $allUserDevices = $this->getUsersDevices($userId);
            $canAddDevice = sizeof($allUserDevices) < OW::getConfig()->getValue('iismobilesupport', 'constraint_user_device');
            if(!$canAddDevice){
                $this->deleteInActiveDevicesOfUser($userId);
                $allUserDevices = $this->getUsersDevices($userId);
                $canAddDevice = sizeof($allUserDevices) < OW::getConfig()->getValue('iismobilesupport', 'constraint_user_device');
            }
        }else{
            $canAddDevice = true;
        }

        if($canAddDevice) {
            /***
             * handles token register in cases of improper logout
             * @author Issa Annamoradnejad
             */
            $ex = new OW_Example();
            $ex->andFieldEqual('token',$token);
            $this->deviceDao->deleteByExample($ex);

            $this->deviceDao->saveDevice($userId, $token, $type, $cookie);
        }
    }

    /***
     * @param $userId
     * @param $token
     * @return array|bool
     */
    public function hasUserDevice($userId, $token){
        return $this->deviceDao->hasUserDevice($userId, $token);
    }

    /***
     * @param $token
     * @return IISMOBILESUPPORT_BOL_Device
     */
    public function findDevice($token){
        return $this->deviceDao->findDevice($token);
    }

    /***
     * @param $userId
     * @param $token
     * @param $cookie
     * @return IISMOBILESUPPORT_BOL_Device
     */
    public function findDeviceTokenRow($userId, $token, $cookie){
        return $this->deviceDao->findDeviceTokenRow($userId, $token, $cookie);
    }

    /***
     * @param $userId
     * @param $token
     */
    public function deleteUserDevice($userId, $token){
        $this->deviceDao->deleteUserDevice($userId, $token);
    }

    /***
     * @param $userId
     * @param $cookie
     */
    public function deleteUserDeviceByCookie($userId, $cookie){
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        $example->andFieldEqual('cookie', $cookie);
        $this->deviceDao->deleteByExample($example);
    }

    /***
     * @param $token
     */
    public function deleteDevice($token){
        $this->deviceDao->deleteDevice($token);
    }

    public function checkForUsingOnlyMobile(OW_Event $event){
        $checkUriEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::BEFORE_CHECK_URI_REQUEST));
        if(isset($checkUriEvent->getData()['ignore']) && $checkUriEvent->getData()['ignore']){
            return;
        }
        if(!$this->isUrlInWhitelist() && $this->isUserShoouldUseOnlyMobile()){
            if (OW::getRequest()->isAjax()) {
                exit();
            } else {
                OW::getApplication()->redirect(OW::getRouter()->urlForRoute('iismobilesupport-use-mobile'));
            }
        }
    }

    /**
     * @return bool
     */
    public function isUrlInWhitelist()
    {
        if (OW::getRequest()->getRequestUri() == 'sign-out' ||
            strpos($_SERVER['REQUEST_URI'], '/mobile/use_mobile_only')!==false ||
            strpos($_SERVER['REQUEST_URI'], 'mobile-version')!==false ||
            strpos($_SERVER['REQUEST_URI'], 'desktop-version')!==false) {
            return true;
        }

        $pluginPasswordChangeInterval = BOL_PluginService::getInstance()->findPluginByKey('iispasswordchangeinterval');
        if($pluginPasswordChangeInterval!=null){
            if( $pluginPasswordChangeInterval->isActive()) {
                $serviceOfPluginPasswordChangeInterval = IISPASSWORDCHANGEINTERVAL_BOL_Service::getInstance();
                if ($serviceOfPluginPasswordChangeInterval->isUrlInWhitelist()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isUserShoouldUseOnlyMobile(){
        if(!$this->useMobile() && OW::getUser()->isAuthenticated() && !OW::getUser()->isAdmin()){
            if(!OW::getUser()->isAuthorized('iismobilesupport', 'show-desktop-version')) {
                return true;
            }
        }

        return false;
    }

    public function onCollectAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'iismobilesupport' => array(
                    'label' => $language->text('iismobilesupport', 'admin_settings_title'),
                    'actions' => array(
                        'show-desktop-version' => $language->text('iismobilesupport', 'auth_action_label_show_desktop_version')
                    )
                )
            )
        );
    }

    public function useMobile(){
        return isset($_COOKIE['UsingMobileApp']);
    }

    public function useAndroidMobile(){
        return $_COOKIE['UsingMobileApp']=='android';
    }

    public function useIOSMobile(){
        return $_COOKIE['UsingMobileApp']=='ios';
    }

    public function saveDeviceToken(OW_Event $event)
    {
        $params = $event->getParams();
        if ($this->useMobile() && isset($_COOKIE['MobileTokenNotification']) && OW::getUser()->isAuthenticated()){
            $cookie = null;
            if (isset($params['cookie'])) {
                $cookie = $params['cookie'];
            }else if (isset($_COOKIE['ow_login'])){
                $cookie = $_COOKIE['ow_login'];
            }else{
                return;
            }
            $type = 1;
            if ($this->useAndroidMobile()) {
                $type = $this->AndroidKey;
            } else if ($this->useIOSMobile()) {
                $type = $this->iOSKey;
            }
            $this->saveDevice(OW::getUser()->getId(), $_COOKIE['MobileTokenNotification'], $type, $cookie);
        }
    }

    public function deleteDeviceToken(OW_Event $event){
        $params = $event->getParams();
        if(isset($params['cookies']) && sizeof($params['cookies'])>0){
            $example = new OW_Example();
            $example->andFieldInArray('cookie', $params['cookies']);
            $this->deviceDao->deleteByExample($example);
        }
    }

    public function addMobileCss(OW_Event $event){
        if($this->useMobile()) {
            $cssUrl = OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticCssUrl() . "mobile.css";
            OW::getDocument()->addStyleSheet($cssUrl);

            $jsUrl = OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticJsUrl() . "mobile.js";
            OW::getDocument()->addScript($jsUrl);
        }
        if($this->useMobile() && $this->useAndroidMobile() && isset($_COOKIE['version_code'])) {
            $versionCode = (int) $_COOKIE['version_code'];
            if($versionCode > 33){
                OW::getDocument()->addStyleDeclaration('header#header {display: none;}');
            }
        }
    }

    public function getBrowserInformation(OW_Event $event){
        if($this->useMobile()) {
            if($this->useAndroidMobile()){
                $event->setData(array('browser_name' => OW::getLanguage()->text('iismobilesupport','android_app_label')));
            }else if($this->useIOSMobile()){
                $event->setData(array('browser_name' => OW::getLanguage()->text('iismobilesupport','ios_app_label')));
            }
        }
        if(isset($_SERVER['HTTP_USER_AGENT']) && strtolower($_SERVER['HTTP_USER_AGENT'])=='android native app'){
            $event->setData(array('browser_name' => 'Android native app'));
        }else if(isset($_SERVER['HTTP_USER_AGENT']) && strtolower($_SERVER['HTTP_USER_AGENT'])=='ios native app'){
            $event->setData(array('browser_name' => 'iOS native app'));
        }
    }

    public function userLogout(OW_Event $event){
        if($this->useMobile()) {
            $params = $event->getParams();
            if (isset($params['userId'])) {
                $deleteAllDevices = false;
                if (isset($_COOKIE['MobileTokenNotification'])) {
                    $service = IISMOBILESUPPORT_BOL_Service::getInstance();
                    $existUserDevice = $service->hasUserDevice($params['userId'], $_COOKIE['MobileTokenNotification']);
                    if ($existUserDevice) {
                        $service->deleteUserDevice($params['userId'], $_COOKIE['MobileTokenNotification']);
                    } else {
                        $deleteAllDevices = false;
                    }
                } else {
                    $deleteAllDevices = true;
                }

                if ($deleteAllDevices) {
                    //IISMOBILESUPPORT_BOL_Service::getInstance()->deleteAllDevicesOfUser($params['userId']);
                }
            }
        }
    }

    /***
     * @param OW_Event $event
     * @return array|void
     */
    public function onNotificationAdd(OW_Event $event){
        $params = $event->getParams();
        $data = $event->getData();

        if (isset($params['mobile_notification']) && $params['mobile_notification'] == false) {
            return;
        }

        $fcmUrl = OW::getConfig()->getValue('iismobilesupport','fcm_api_url');
        $fcmKey = OW::getConfig()->getValue('iismobilesupport','fcm_api_key');

        if (is_string($data) || $fcmUrl == null || $fcmUrl == '' || $fcmKey == null || $fcmKey == '' || empty($data['avatar'])){
            return;
        }

        foreach ( array('string', 'conten') as $langProperty )
        {
            if ( !empty($data[$langProperty]) && is_array($data[$langProperty]) )
            {
                $key = explode('+', $data[$langProperty]['key']);
                $vars = empty($data[$langProperty]['vars']) ? array() : $data[$langProperty]['vars'];
                $data[$langProperty] = BOL_LanguageService::getInstance()->getText(BOL_LanguageService::getInstance()->getCurrent()->getId(), $key[0], $key[1], $vars);
            }
        }

        if ( empty($data['string']) )
        {
            return array();
        }

        $notification_id = isset($data['notification_id'])?$data['notification_id']:0;
        $title = OW::getConfig()->getValue('base', 'site_name');
        $description = $data['string'];
        $url = isset($data['url'])?$data['url']:null;
        $avatarUrl = null;
        $user = null;
        if(isset($params['userId'])) {
            $user = BOL_UserService::getInstance()->findUserById($params['userId']);
        }
        if(isset($data['avatar']['src'])){
            $avatarUrl = $data['avatar']['src'];
        }
        if ($avatarUrl == null) {
            $avatarUrl = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
        }

        if($user != null) {
            $data = array();
            $data['notification_id'] = $notification_id;
            $data['userId'] = $user->getId();
            $data['title'] = $title;
            $data['description']  = $description;
            $data['avatarUrl']  = $avatarUrl;
            $data['url']  = $url;
            IISMOBILESUPPORT_BOL_Service::getInstance()->sendNotification($data);
        }
    }

    public function getAllSections($sectionKey){
        $sections = array();

        $sections[] = array(
            'sectionId' => "settings",
            'active' => $sectionKey == "settings" ? true : false,
            'url' => OW::getRouter()->urlForRoute('iismobilesupport-admin'),
            'label' => OW::getLanguage()->text('iismobilesupport', 'settings')
        );

        $sections[] = array(
            'sectionId' => "versions",
            'active' => $sectionKey == "versions" ? true : false,
            'url' => OW::getRouter()->urlForRoute('iismobilesupport-admin-versions'),
            'label' => OW::getLanguage()->text('iismobilesupport', 'versions')
        );

        $sections[] = array(
            'sectionId' => "android-versions",
            'active' => $sectionKey == "android-versions" ? true : false,
            'url' => OW::getRouter()->urlForRoute('iismobilesupport-admin-android-versions'),
            'label' => OW::getLanguage()->text('iismobilesupport', 'android_versions')
        );

        $sections[] = array(
            'sectionId' => "ios-versions",
            'active' => $sectionKey == "ios-versions" ? true : false,
            'url' => OW::getRouter()->urlForRoute('iismobilesupport-admin-ios-versions'),
            'label' => OW::getLanguage()->text('iismobilesupport', 'ios_versions')
        );

        $sections[] = array(
            'sectionId' => "android-native-versions",
            'active' => $sectionKey == "android-native-versions" ? true : false,
            'url' => OW::getRouter()->urlForRoute('iismobilesupport-admin-android-native-versions'),
            'label' => OW::getLanguage()->text('iismobilesupport', 'native_versions')
        );

        $sections[] = array(
            'sectionId' => "web-settings",
            'active' => $sectionKey == "web-settings" ? true : false,
            'url' => OW::getRouter()->urlForRoute('iismobilesupport-admin-web-settings'),
            'label' => OW::getLanguage()->text('iismobilesupport', 'web_settings')
        );

        $sections[] = array(
            'sectionId' => "download-show",
            'active' => $sectionKey == "download-show" ? true : false,
            'url' => OW::getRouter()->urlForRoute('iismobilesupport-admin-download-show'),
            'label' => OW::getLanguage()->text('iismobilesupport', 'download_show')
        );

        return $sections;
    }

    public function onAddMembersOnlyException( BASE_CLASS_EventCollector $event )
    {
        $event->add(array('controller' => 'IISMOBILESUPPORT_MCTRL_Service', 'action' => 'index'));
        $event->add(array('controller' => 'IISMOBILESUPPORT_MCTRL_Service', 'action' => 'getInformation'));
        $event->add(array('controller' => 'IISMOBILESUPPORT_MCTRL_Service', 'action' => 'action'));
    }

    public function canUseWebNotifications(){
        if(strpos(OW_URL_HOME, 'http://localhost/')===false && strpos(OW_URL_HOME, 'https://')===false){
            return false;
        }
        if(!OW::getUser()->isAuthenticated()){
            return false;
        }

        $web_config = OW::getConfig()->getValue('iismobilesupport', 'web_config');
        $web_key = OW::getConfig()->getValue('iismobilesupport', 'web_key');
        return (!empty($web_config) && !empty($web_key));
    }

    public function showDownloadLinks()
    {
        if (OW::getConfig()->getValue('iismobilesupport', 'custom_download_link_activation')) {

            $cssUrl = OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticCssUrl() . "iismobilesupport.css";
            OW::getDocument()->addStyleSheet($cssUrl);
            $appendToClassName = 'ow_mobile_app_download_links';
            $this->addDownloadIconToPlace('.ow_footer_menu', $appendToClassName);
            $this->addDownloadIconToPlace('.ow_sign_in', $appendToClassName);
        }

        if($this->canUseWebNotifications()){
            $address = OW_URL_HOME . 'manifest.json';
            $head_html = '<link rel="manifest" href="'.$address.'">';
            OW::getDocument()->addCustomHeadInfo($head_html);
        }
    }

    public function addDownloadIconToPlace($placeName, $appendToClassName){
        $DownloadLinkJS = "var iDiv = document.createElement('div');
                               iDiv.className = '".$appendToClassName."';
                               if ($('".$placeName."').size()>0) {
                               $('".$placeName."').append(iDiv);
                               }";

        OW::getDocument()->addOnloadScript($DownloadLinkJS, 1000);

        $service = IISMOBILESUPPORT_BOL_Service::getInstance();
        $androidLastVersion= $service->getLastVersions($service->AndroidKey);
        $isNewTheme = IISSecurityProvider::themeCoreDetector() ? true : false;
        if($isNewTheme){
            $service->downloadLinkJS($androidLastVersion, 'app_download_link', 'android', 'androidNew.png', $appendToClassName);
        }else{
            $service->downloadLinkJS($androidLastVersion, 'app_download_link', 'android', 'android.png', $appendToClassName);
        }

        $iosLastVersion = $service->getLastVersions($service->iOSKey);
        if($isNewTheme){
        $service->downloadLinkJS($iosLastVersion, 'app_download_link', 'ios', 'iosNew.png', $appendToClassName);
        }else {
            $service->downloadLinkJS($iosLastVersion, 'app_download_link', 'ios', 'ios.png', $appendToClassName);
        }

        $customLimksHtml=OW::getConfig()->getValue('iismobilesupport', 'custom_download_link_code');
        if(isset($customLimksHtml)){
            OW::getDocument()->addOnloadScript('if ($(".'.$appendToClassName.'").size()>0) {$(".'.$appendToClassName.'").append("' .UTIL_HtmlTag::escapeJs($customLimksHtml). '")}', 1001);
        }
    }

    public function downloadLinkJS($LastVersion, $className, $childClassname, $imageName, $appendToClassName){
        if(isset($LastVersion)){
            $LastVersionUrl=$LastVersion->url;
            $downloadImgCss = 'a.'.$className.'.'.$childClassname.'{
                                background-image: url("' . OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl(). 'img/' . $imageName . '");}';
            $DownloadLinkJs='if ($(\'.'.$appendToClassName.'\').size()>0) {var ia = document.createElement(\'a\');
                               ia.className = "' .$className. ' ' . $childClassname .  '";
                               ia.href="' .$LastVersionUrl. '";
                               ia.target="_blank";
                               $(\'.'.$appendToClassName.'\').append(ia);}';

            OW::getDocument()->addOnloadScript($DownloadLinkJs, 1001);
            OW::getDocument()->addStyleDeclaration($downloadImgCss);
        }
    }

    /***
     * @author Issa Annamoradnejad
     * @param OW_Event $event
     */
    public function onNotificationViewed(OW_Event $event){
        $userId = $event->getParams()['userId'];

        $data = array();
        $data['notification_id'] = 0;
        $data['userId'] = $userId;
        $data['title'] = OW::getConfig()->getValue('base', 'site_name');
        $data['description']  = ' ';
        $data['avatarUrl']  = ' ';
        $data['url']  = OW_URL_HOME;
        IISMOBILESUPPORT_BOL_Service::getInstance()->sendNotification($data);
    }

    public function checkUrlIsWebService(){
        if(!defined("ACCESS_WEB_SERVICE") || ACCESS_WEB_SERVICE == false){
            return false;
        }

        if(!isset($_SERVER['REQUEST_URI'])){
            return false;
        }

        if (strpos($_SERVER['REQUEST_URI'], '/mobile/services/action') !== false ||
            strpos($_SERVER['REQUEST_URI'], '/mobile/services/information') !== false) {
            return true;
        }
        return false;
    }

    public function checkUrlIsWebServiceEvent(OW_Event $event){
        $result= $this->checkUrlIsWebService();
        $event->setData(array('isWebService'=>$result));
    }

    public function onBeforeCSRFCheck(OW_Event $event){
        if ($this->checkUrlIsWebService()) {
            $event->setData(array('not_check' => true));
        }
    }

    public function onAfterMessageRemoved(OW_Event $event){
        if (!OW::getUser()->isAuthenticated()){
            return;
        }
        $params = $event->getParams();
        if (!isset($params['senderId']) || !isset($params['recipientId']) || !isset($params['id'])) {
            return;
        }
        $senderId = $params['senderId'];
        $recipientId = $params['recipientId'];
        $messageId = $params['id'];
        $data = array();
        $additionalData = array('removedMessageId' => (int) $messageId);
        $this->sendDataUsingFirebaseForUserId($data, $additionalData, $senderId);
        $this->sendDataUsingFirebaseForUserId($data, $additionalData, $recipientId);
    }

    public function onBeforeSessionDelete(OW_Event $event){
        if ($this->checkUrlIsWebService()) {
            $event->setData(array('ignore' => true));
        }
    }

    public function currentUserApproved()
    {
        if (OW::getUser()->isAuthenticated() &&
            OW::getConfig()->getValue('base', 'mandatory_user_approve') &&
            !OW::getUser()->isAdmin() &&
            !BOL_UserService::getInstance()->isApproved()){
            return false;
        }
        return true;
    }

    public function onPluginsInit(){
        if(!$this->checkUrlIsWebService()){
            return;
        }
        
        if (!$this->currentUserApproved())
        {
            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.wait_for_approval', array(
                OW_RequestHandler::ATTRS_KEY_CTRL => 'IISMOBILESUPPORT_MCTRL_Service',
                OW_RequestHandler::ATTRS_KEY_ACTION => 'action'
            ));
            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.wait_for_approval', array(
                OW_RequestHandler::ATTRS_KEY_CTRL => 'IISMOBILESUPPORT_MCTRL_Service',
                OW_RequestHandler::ATTRS_KEY_ACTION => 'getInformation'
            ));
        }

        if(OW::getUser()->isAuthenticated()) {
            OW::getRequestHandler()->setCatchAllRequestsAttributes('iispasswordchangeinterval.catch', array(
                OW_RequestHandler::ATTRS_KEY_CTRL => 'IISMOBILESUPPORT_MCTRL_Service',
                OW_RequestHandler::ATTRS_KEY_ACTION => 'action'
            ));
            OW::getRequestHandler()->setCatchAllRequestsAttributes('iispasswordchangeinterval.catch', array(
                OW_RequestHandler::ATTRS_KEY_CTRL => 'IISMOBILESUPPORT_MCTRL_Service',
                OW_RequestHandler::ATTRS_KEY_ACTION => 'getInformation'
            ));

            $questions = BOL_QuestionService::getInstance()->getEmptyRequiredQuestionsList(OW::getUser()->getId());

            if (!empty($questions)) {
                OW::getRequestHandler()->setCatchAllRequestsAttributes('base.complete_required_questions', array(
                    OW_RequestHandler::ATTRS_KEY_CTRL => 'IISMOBILESUPPORT_MCTRL_Service',
                    OW_RequestHandler::ATTRS_KEY_ACTION => 'action'
                ));
                OW::getRequestHandler()->setCatchAllRequestsAttributes('base.complete_required_questions', array(
                    OW_RequestHandler::ATTRS_KEY_CTRL => 'IISMOBILESUPPORT_MCTRL_Service',
                    OW_RequestHandler::ATTRS_KEY_ACTION => 'getInformation'
                ));
                OW::getRequestHandler()->setCatchAllRequestsAttributes('base.complete_profile', array(
                    OW_RequestHandler::ATTRS_KEY_CTRL => 'IISMOBILESUPPORT_MCTRL_Service',
                    OW_RequestHandler::ATTRS_KEY_ACTION => 'action'
                ));
                OW::getRequestHandler()->setCatchAllRequestsAttributes('base.complete_profile', array(
                    OW_RequestHandler::ATTRS_KEY_CTRL => 'IISMOBILESUPPORT_MCTRL_Service',
                    OW_RequestHandler::ATTRS_KEY_ACTION => 'getInformation'
                ));
            }
        }
    }

    public function onBeforeMobileValidationRedirect(OW_Event $event)
    {
        if($this->checkUrlIsWebService()){
            $event->setData(array('not_redirect' => true));
        }
    }

    public function onBeforePostRequestFailForCSRF(OW_Event $event){
        $url = $_SERVER['REQUEST_SCHEME'] . '://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $passPaths = array();
        $passPaths[] = OW::getRouter()->urlForRoute('iismobilesupport-web-service-get-information',array('type'=>''));
        $passPaths[] = OW::getRouter()->urlForRoute('iismobilesupport-web-service-get-information-without-type');
        $passPaths[] = OW::getRouter()->urlForRoute('iismobilesupport-web-service-action',array('type'=>''));
        $passPaths[] = OW::getRouter()->urlForRoute('iismobilesupport-web-service-action-without-type'); ;

        foreach ($passPaths as $passPath){
            if(strpos($url, $passPath)==0){
                $event->setData(array('pass' => true));
                return;
            }
        }
    }

    public function onMobileNotificationDataReceived(OW_Event $event){
        $params = $event->getParams();
        $pluginKey = $params['pluginKey'];
        $entityType = $params['entityType'];
        $data = $params['data'];
        switch ($pluginKey) {
            case 'groups':
                switch ($entityType){
                    case 'groups-add-file':
                        $event->setData(array('url' => $data['string']['vars']['groupUrl']));
                        break;
                    case 'groups-update-status':
                        //No change is needed
                        break;
                }
                break;
            case 'iismention':
                //No change is needed
                break;
            case 'forum':
                switch ($entityType){
                    case 'forum_topic_reply':
                        //No change is needed
                        break;
                }
                break;
            case 'iissecurityessentials':
                switch ($entityType){
                    case 'security-privacy_alert':
                        //No change is needed
                        break;
                }
                break;
            case 'newsfeed':
                switch ($entityType){
                    case 'user_status':
                        //No change is needed
                        break;
                    case 'status_comment':
                        //No change is needed
                        break;
                    case 'status_like':
                        //No change is needed
                        break;
                    case 'groups-status':
                        //No change is needed
                        break;
                }
                break;
            case 'event':
                switch ($entityType){
                    case 'event':
                        $event->setData(array('url' => $data['string']['vars']['url']));
                        break;
                    case 'event-add-file':
                        $event->setData(array('url' => $data['string']['vars']['eventUrl']));
                        break;
                }
                break;
            case 'photo':
                switch ($entityType){
                    case 'photo-add_comment':
                        //No change is needed
                        break;
                }
                break;
            case 'video':
                switch ($entityType){
                    case 'video-add_comment':
                        //No change is needed
                        break;
                }
                break;
            case 'iisnews':
                switch ($entityType) {
                    case 'news-add_comment':
                        //No change is needed
                        break;
                    case 'news-add_news':
                        //No change is needed
                        break;
                }
                break;
            case 'iiscompetition':
                switch ($entityType) {
                    case 'competition-add_competition':
                        //No change is needed
                        break;
                    case 'competition-add_user_point':
                        //No change is needed
                        break;
                    case 'competition-add_group_point':
                        //No change is needed
                        break;
                }
                break;
            case 'iisterms':
                switch ($entityType) {
                    case 'iisterms-terms':
                        $event->setData(array('url' => $data['string']['vars']['url']));
                        break;
                }
                break;
            case 'base':
                switch ($entityType) {
                    case 'base_profile_wall':
                        //No change is needed
                        break;
                }
                break;
            case 'questions':
                switch ($entityType) {
                    case 'questions-post':
                        //No change is needed
                        break;
                    case 'questions-answer':
                        //No change is needed
                        break;
                }
                break;
            case 'iispasswordchangeinterval':
                switch ($entityType) {
                    case 'iispasswordchangeinterval':
                        $event->setData(array('url' => OW::getRouter()->urlForRoute('iisprofilemanagement.edit')));
                }
                break;
        }
    }

    public function excludeCatchGetInformationRequest(OW_Event $event)
    {
        if(OW::getUser()->isAuthenticated()) {
            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.complete_profile', array(
                OW_RequestHandler::ATTRS_KEY_CTRL => 'IISMOBILESUPPORT_MCTRL_Service',
                OW_RequestHandler::ATTRS_KEY_ACTION => 'index'
            ));
        }
    }



    /*              Extracted from iismobilesupport               */

    public function generalBeforeViewRender(OW_EVENT $event){
        $params = $event->getParams();
        if(!isset($params['targetPage'])) {
            return;
        }

        switch ($params['targetPage']){
            case 'userProfile':
                $username = $params['username'];
                $user = BOL_UserService::getInstance()->findByUsername($username);
                $user_avatar =  BOL_AvatarService::getInstance()->getAvatarUrl($user->getId(), 2);
                if ($user_avatar == null)
                    $user_avatar = OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/profile_default.svg';
                $js = $this->createBackMenu(BOL_UserService::getInstance()->getUserUrl($user->getId()),
                    BOL_UserService::getInstance()->getDisplayName($user->getId()),
                    $user_avatar
                   );
                OW::getDocument()->addScriptDeclaration($js);
                break;
            case 'forum':
                $js = $this->createBackMenu(OW::getRouter()->urlForRoute('forum-default'),
                    OW::getLanguage()->text('forum','forum'),
                    OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/topics.svg');
                OW::getDocument()->addScriptDeclaration($js);
                break;
            case 'forumGroup':
                $groupId = $params['groupId'];
                $js = $this->createBackMenu(OW::getRouter()->urlForRoute('group-default', array('groupId'=>$groupId)),
                    FORUM_BOL_ForumService::getInstance()->getGroupInfo($groupId)->name,
                    OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/topics.svg');
                OW::getDocument()->addScriptDeclaration($js);
                break;
            case 'blogs':
                $js = $this->createBackMenu(OW::getRouter()->urlForRoute('blogs'),
                    OW::getLanguage()->text('blogs','list_page_heading'),
                    OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/news.svg');
                OW::getDocument()->addScriptDeclaration($js);
                break;
        }
    }

    public function beforeGroupViewRender(OW_EVENT $event){
        $param = $event->getParams();
        if(isset($param['pageType'])){
            if($param['pageType'] == "userList" || $param['pageType'] == "edit" || $param['pageType'] == "fileList"){
                $js = $this->createBackMenu(OW::getRouter()->urlForRoute('groups-view' , array('groupId'=>$param['groupId'])),
                    GROUPS_BOL_Service::getInstance()->findGroupById($param['groupId'])->title,
                    OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/groups.svg');
                OW::getDocument()->addScriptDeclaration($js);
            }
        }
        else{
            $url = OW::getRouter()->urlForRoute("groups-index");
            $plugin = BOL_PluginDao::getInstance()->findPluginByKey('iismainpage');
            if(isset($plugin) && $plugin->isActive() && !IISMAINPAGE_BOL_Service::getInstance()->isDisabled('user-groups')) {
                $url = OW::getRouter()->urlForRoute('iismainpage.user.groups');
            }
            $js = $this->createBackMenu($url,
                OW::getLanguage()->text('groups', 'group_list_heading'),
                OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/groups.svg');
            OW::getDocument()->addScriptDeclaration($js);
        }
    }

    public function beforeNewsViewRender(OW_EVENT $event){
        $param = $event->getParams();
        if(isset($param['pageType']) && $param['newsId'] != 0){
            if($param['pageType'] == "edit") {
                $js = $this->createBackMenu(OW::getRouter()->urlForRoute('user-entry', array('id' => $param['newsId'])),
                    EntryService::getInstance()->findById($param['newsId'])->title,
                    OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/news.svg');
                OW::getDocument()->addScriptDeclaration($js);
            }
        }
        else{
            $js = $this->createBackMenu(OW::getRouter()->urlForRoute("iisnews"),
                OW::getLanguage()->text('iisnews', 'list_page_heading'),
                OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/news.svg');
            OW::getDocument()->addScriptDeclaration($js);
        }
    }

    public function beforeVideoViewRender(OW_EVENT $event){
        $param = $event->getParams();
        if(isset($param['pageType'])){
            if($param['pageType'] == "edit") {
                $js = $this->createBackMenu(OW::getRouter()->urlForRoute('view_clip', array('id' => $param['videoId'])),
                    VIDEO_BOL_ClipService::getInstance()->findClipById($param['videoId'])->title,
                    OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/videos.svg');
                OW::getDocument()->addScriptDeclaration($js);
            }
        }
        else{
            $url = OW::getRouter()->urlForRoute("video_list_index");
            $plugin = BOL_PluginDao::getInstance()->findPluginByKey('iismainpage');
            if(isset($plugin) && $plugin->isActive() && !IISMAINPAGE_BOL_Service::getInstance()->isDisabled('videos')) {
                $url = OW::getRouter()->urlForRoute('iismainpage.videos');
            }
            $js = $this->createBackMenu($url,
                OW::getLanguage()->text('video', 'page_title_browse_video'),
                OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/videos.svg');
            OW::getDocument()->addScriptDeclaration($js);
        }
    }

    public function beforePhotoViewRender(OW_EVENT $event){
        $url = OW::getRouter()->urlForRoute("photo_list_index");
        $plugin = BOL_PluginDao::getInstance()->findPluginByKey('iismainpage');
        if(isset($plugin) && $plugin->isActive() && !IISMAINPAGE_BOL_Service::getInstance()->isDisabled('photos')) {
            $url = OW::getRouter()->urlForRoute('iismainpage.photos');
        }
        $js = $this->createBackMenu($url,
            OW::getLanguage()->text('photo', 'page_title_browse_photos'),
            OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/photos.svg');
        OW::getDocument()->addScriptDeclaration($js);
    }

    public function beforeCompetitionViewRender(OW_EVENT $event){
        $js = $this->createBackMenu(OW::getRouter()->urlForRoute("iiscompetition.index"),
            OW::getLanguage()->text('iiscompetition', 'competitions'),
            OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/competition.svg');
        OW::getDocument()->addScriptDeclaration($js);
    }

    public function beforeEventViewRender(OW_EVENT $event){
        $param = $event->getParams();
        if(isset($param['pageType'])){
            if($param['pageType'] == "edit" || $param['pageType'] == "fileList"){
                $js = $this->createBackMenu(OW::getRouter()->urlForRoute('event.view' , array('eventId'=>$param['eventId'])),
                    EVENT_BOL_EventService::getInstance()->findEvent($param['eventId'])->title,
                    OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/event.svg');
                OW::getDocument()->addScriptDeclaration($js);
            }
        }
        else{
            $js = $this->createBackMenu(OW::getRouter()->urlForRoute("event.main_menu_route"),
                OW::getLanguage()->text('event', 'main_menu_item'),
                OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/event.svg');
            OW::getDocument()->addScriptDeclaration($js);
        }
    }

    /***
     * @param BASE_CLASS_EventCollector $event
     */
    public function addJSForWebNotifications($event){
        if(!$this->canUseWebNotifications()){
            return;
        }

        $web_key = OW::getConfig()->getValue('iismobilesupport', 'web_key');
        $url = OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticJsUrl() . 'web/firebase_loader.js';

        // js before the end of Body
        $js = '
            <script src="'.OW_URL_HOME.'__/firebase/6.2.0/firebase-app.js"></script>
            <script src="'.OW_URL_HOME.'__/firebase/6.2.0/firebase-auth.js"></script>
            <script src="'.OW_URL_HOME.'__/firebase/6.2.0/firebase-messaging.js"></script>
            <script src="'.OW_URL_HOME.'__/firebase/init.js"></script>
            <script src="'.$url.'"></script>
            <script> loadWebFCM("' . $web_key . '"); </script>';
        $event->add($js);
    }

    public function beforeProfilePagesViewRender(OW_EVENT $event){
        $param = $event->getParams();
        if(isset($param['pageType'])){
            switch ($param['pageType']) {
                case "editProfile":
                    break;
                case "preferences":
                    $menuReplaceJs = '$("section#content").prepend($("div.owm_nav_cap"))';
                    OW::getDocument()->addScriptDeclaration($menuReplaceJs);
                    break;
            }
        }

        $plugin = BOL_PluginDao::getInstance()->findPluginByKey('iismainpage');
        if($plugin !== null && $plugin->isActive() && !IISMAINPAGE_BOL_Service::getInstance()->isDisabled('settings')){
            $js = $this->createBackMenu(OW::getRouter()->urlForRoute('iismainpage.settings'),
                OW::getLanguage()->text('base', 'mobile_admin_settings'),
                OW::getPluginManager()->getPlugin('iismainpage')->getStaticUrl() . 'img/'."Settings.svg");
            OW::getDocument()->addScriptDeclaration($js);
        }
        else{
            $js = $this->createBackMenu(BOL_UserService::getInstance()->getUserUrl(OW::getUser()->getId()),
                OW::getLanguage()->text('base', 'my_profile_heading'),
                BOL_AvatarService::getInstance()->getAvatarUrl(OW::getUser()->getId(), 2));
            OW::getDocument()->addScriptDeclaration($js);
        }
    }

    public function beforeGroupForumViewRender(OW_EVENT $event){
        $param = $event->getParams();
        if(isset($param['groupId'])) {
            $js = $this->createBackMenu(GROUPS_BOL_Service::getInstance()->
            getGroupUrl(GROUPS_BOL_Service::getInstance()->findGroupById($param['groupId'])),
                GROUPS_BOL_Service::getInstance()->findGroupById($param['groupId'])->title,
                OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/group.svg');
            OW::getDocument()->addScriptDeclaration($js);
        }
    }

    public function beforeGroupForumTopicViewRender(OW_EVENT $event){
        $param = $event->getParams();
        if(isset($param['groupId'])) {
            $js = $this->createBackMenu(OW::getRouter()->urlFor('FORUM_MCTRL_Group', 'index', array('groupId' => FORUM_BOL_ForumService::getInstance()->findGroupByEntityId('groups', $param['groupId'])->id)),
                OW::getLanguage()->text('forum','forum_subjects_list'),
                OW::getPluginManager()->getPlugin('iismobilesupport')->getStaticUrl() . 'img/topics.svg');
            OW::getDocument()->addScriptDeclaration($js);
        }
    }

    public function onRabbitMQNotificationRelease(OW_EVENT $event) {
        $data = $event->getData();
        if (!isset($data) || !isset($data->body)) {
            return;
        }

        $data = $data->body;
        $data = (object) json_decode($data);

        if (!isset($data->itemType) || $data->itemType != 'notification') {
            return;
        }

        $this->sendDataToFCM($data, true);
    }

    public function createBackMenu($backAddress, $backTitle, $icon){
        $backSrc = OW::getThemeManager()->getCurrentTheme()->getStaticUrl() . 'mobile/images/arr_nav_next.svg';
//        $languageTag = BOL_LanguageService::getInstance()->getCurrent()->getTag();
//        if($languageTag == "fa-IR"){
        $js = '
            $("div.owm_nav_cap").append(\'<a href="'. $backAddress . '" class="mobile_back_button_title">' . $backTitle . '</a>\');
            container = document.createElement("a");
            container.classList.add("mobile_back_container");
            container.setAttribute("href", "'. $backAddress . '");
            $("div.owm_nav_cap").append(container);
            $("a.owm_nav_cap_left").remove();
            back = document.createElement("div");
            back.classList.add("mobile_back_menu_back");
            back.style.backgroundImage= "url('.$backSrc.')";
            $("a.mobile_back_container").append(back);
            icon = document.createElement("div");
            icon.classList.add("mobile_back_menu_icon");
            icon.style.backgroundImage= "url('.$icon.')";
            $("a.mobile_back_container").append(icon);
        ';
//        }
//        else if($languageTag == "en"){
//            $js = '';
//        }
        return $js;
    }

    /****************************************************/


}