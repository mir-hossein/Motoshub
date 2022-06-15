<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iismobilesupport.bol
 * @since 1.0
 */
class IISMOBILESUPPORT_BOL_WebServiceMailbox
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

    private function __construct()
    {
    }

    public function getUnreadConversationsCount() {
        $unreadConversations = array();
        if(!OW::getUser()->isAuthenticated() || !IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('mailbox', true)){
            return $unreadConversations;
        }
        $markedUnreadConversationList = MAILBOX_BOL_ConversationService::getInstance()->getMarkedUnreadConversationList( OW::getUser()->getId() );
        return count($markedUnreadConversationList);
    }

    public function getMessages(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('mailbox', true)){
            return array();
        }

        if(!OW::getUser()->isAuthenticated()){
            return array();
        }

        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        $userId = OW::getUser()->getId();
        return $this->processGetMessages($userId, $count);
    }

    public function processGetMessages($userId, $count){
        $data = array();
        $first = 0;
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }
        $convList = MAILBOX_BOL_ConversationService::getInstance()->getConversationListByUserId($userId, $first, $count);
        foreach ($convList as $conv){
            $data[] = $this->preparedConversation($conv, $count, false);
        }

        return $data;
    }

    public function getUserMessage(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('mailbox', true)){
            return array();
        }

        if(!OW::getUser()->isAuthenticated()){
            return array();
        }

        $opponentId = null;
        if(isset($_GET['opponentId'])){
            $opponentId = $_GET['opponentId'];
        }

        if($opponentId == null){
            return array();
        }

        $userId = OW::getUser()->getId();
        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        return $this->processGetUserMessages($userId, $opponentId, $first, $count);
    }

    public function markUserMessage(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('mailbox', true)){
            return array('valid' => false);
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false);
        }

        $opponentId = null;
        if(isset($_GET['opponentId'])){
            $opponentId = $_GET['opponentId'];
        }

        if($opponentId == null){
            return array('valid' => false);
        }

        $userId = OW::getUser()->getId();

        $convId = MAILBOX_BOL_ConversationService::getInstance()->getChatConversationIdWithUserById($userId, $opponentId);
        $this->markMessages($convId, $userId);
        return array('valid' => true);
    }

    public function markMessages($convId, $userId) {
        if ($convId == null || $userId == null) {
            return;
        }
        $unreadMessages = MAILBOX_BOL_MessageDao::getInstance()->findUnreadMessagesForConversation($convId, $userId);
        $unreadMessagesId = array();
        foreach ($unreadMessages as $unreadMessage) {
            $unreadMessagesId[] = $unreadMessage->id;
        }
        if(!empty($unreadMessagesId)){
            MAILBOX_BOL_ConversationService::getInstance()->markMessageIdListRead($unreadMessagesId);
        }
    }

    public function processGetUserMessages($userId, $opponentId, $first, $count){
        $convId = MAILBOX_BOL_ConversationService::getInstance()->getChatConversationIdWithUserById($userId, $opponentId);
        $data = array();
        $this->markMessages($convId, $userId);

        if($convId != null) {
            $convList = MAILBOX_BOL_ConversationService::getInstance()->getConversationListByUserId($userId, $first, $count, $convId);
            if($convList != null){
                $data = $this->preparedConversation($convList[0], $count);
            }
        }

        return $data;
    }

    public function sendMessage(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('mailbox', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $opponentId = null;
        if(isset($_POST['opponentId'])){
            $opponentId = $_POST['opponentId'];
        }

        $text = null;
        if(isset($_POST['text'])){
            $text = $_POST['text'];
        }

        $userId = OW::getUser()->getId();
        if($userId == $opponentId || $text == null || $opponentId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!is_numeric($opponentId)){
            return array('valid' => false, 'message' => 'input_error');
        }

        $conversation = null;
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();
        $conversationId = MAILBOX_BOL_ConversationService::getInstance()->getChatConversationIdWithUserById($userId, $opponentId);
        if ($conversationId == null || empty($conversationId)){
            $conversation = $conversationService->createChatConversation($userId, $opponentId);
            $conversationId = $conversation->getId();
        }

        $firstMessage = $conversationService->getFirstMessage($conversationId);

        if (empty($firstMessage))
        {
            $actionName = 'send_chat_message';
        }
        else
        {
            $actionName = 'reply_to_chat_message';
        }

        $isAuthorized = OW::getUser()->isAuthorized('mailbox', $actionName);
        if ( !$isAuthorized )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);
            if ( $status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE )
            {
                return array('valid' => false, 'message' => 'authorization_error');
            }
        }

        $text = str_replace('↵',"\r\n", $text);
        $text = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($text, false);
        $event = new OW_Event('mailbox.before_send_message', array(
            'senderId' => $userId,
            'recipientId' => $opponentId,
            'conversationId' => $conversationId,
            'message' => $text
        ), array('result' => true, 'error' => '', 'message' => $text ));
        OW::getEventManager()->trigger($event);

        $data = $event->getData();

        if ( !$data['result'] )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $text = $data['message'];

        try
        {
            if($conversation == null && $conversationId != null){
                $conversation = MAILBOX_BOL_ConversationDao::getInstance()->findById($conversationId);
            }
            if($conversation == null){
                return array('valid' => false, 'message' => 'authorization_error');
            }
            $validFile = false;
            if(isset($_FILES) && isset($_FILES['file'])){
                $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
                if ($isFileClean) {
                    $validFile = true;
                    $bundle = IISSecurityProvider::generateUniqueId();
                    $maxUploadSize = OW::getConfig()->getValue('base', 'attch_file_max_size_mb');
                    $validFileExtensions = json_decode(OW::getConfig()->getValue('base', 'attch_ext_list'), true);
                    BOL_AttachmentService::getInstance()->processUploadedFile('mailbox', $_FILES['file'], $bundle, $validFileExtensions, $maxUploadSize);
                    $items = BOL_AttachmentService::getInstance()->getFilesByBundleName('mailbox', $bundle);
                } else {
                    return array('valid' => false, 'message' => 'virus_detected');
                }
            }
            $replyId = null;
            if (isset($_POST['replyId'])) {
                $replyId = $_POST['replyId'];
            }
            $message = $conversationService->createMessage($conversation, $userId, $text, $replyId);
            if($validFile){
                MAILBOX_BOL_ConversationService::getInstance()->addMessageAttachments($message->id, $items);
            }
        }
        catch(InvalidArgumentException $e)
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if (!empty($actionName))
        {
            BOL_AuthorizationService::getInstance()->trackAction('mailbox', $actionName);
        }

        $item = $this->getMessageInfo($message);
        if(isset($_POST['_id']) && !empty($_POST['_id']) && $_POST['_id'] != null && $_POST['_id'] != "null"){
            $item['_id'] = $_POST['_id'];
        }else{
            $item['_id'] = $message->id;
        }
        return array('valid' => true, 'message'=>$item);
    }

    public function getMessageInfo($message){
        $messageInfo = MAILBOX_BOL_ConversationService::getInstance()->getMessageDataForApi($message);
        $text = $messageInfo['text'];
        $text = MAILBOX_BOL_ConversationService::getInstance()->json_decode_text($text);
        $text = $this->removePrefetchFromText($text);
        $editable = false;
        if(OW::getUser()->isAuthenticated()) {
            $editable = $messageInfo['senderId'] == OW::getUser()->getId();
        }
        $messageInfo['editable'] = $editable;
        $messageInfo['removable'] = $editable;
        $messageInfo['text'] = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($text);
        $messageInfo['text'] = trim($messageInfo['text']);
        $messageInfo['text'] = trim($messageInfo['text'], '"');
        if ($messageInfo['received'] == 1) {
            $messageInfo['received'] = true;
        }else{
            $messageInfo['received'] = false;
        }
        $messageInfo['changed'] = (int) $messageInfo['changed'];
        $messageInfo['opponentId'] = (int) $messageInfo['opponentId'];
        $messageInfo['opponentId'] = (int) $messageInfo['opponentId'];
        return $messageInfo;
    }

    private function preparedConversation($conversationObj, $count, $returnMessages = true){
        $data = array();
        $data['conversation_info'] = array();

        $convInfo = array();
        if($conversationObj == null){
            return $data;
        }

        $convInfo['conversationId'] = (int) $conversationObj['conversationId'];
        $convInfo['user'] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($conversationObj['userId']);
        if (isset($convInfo['user'])) {
            $convInfo['user']['online'] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->isUserOnline($conversationObj['userId']);
        }
        $convInfo['preview_text'] = $conversationObj['previewText'];
        $convInfo['last_time'] = $conversationObj['lastMessageTimestamp'];
        $convInfo['new_count'] = $conversationObj['newMessageCount'];
        $convInfo['mode'] = $conversationObj['mode'];

        $data['conversation_info'] = $convInfo;
        if($returnMessages) {
            $data['messages'] = $this->getMessagesOfConversation($conversationObj['conversationId'], $count);
        }

        $editedMessages = array();
        $removedMessageIds = array();
        if(isset($_GET['existIds'])) {
            $existIds = $_GET['existIds'];
            $existIds = explode(',', $existIds);
            if (isset($existIds) && !empty($existIds) && sizeof($existIds) > 0) {
                foreach ($existIds as $existId) {
                    if (!empty($existId)) {
                        $message = MAILBOX_BOL_MessageDao::getInstance()->findById($existId);
                        if ($message && (int)$message->changed != 0) {
                            $editedMessages[] = $this->getMessageInfo($message);
                        } else if($message == null) {
                            $removedMessageIds[] = (int) $existId;
                        }
                    }
                }
                if (sizeof($editedMessages) > 0) {
                    $data['editedMessages'] = $editedMessages;
                }
                if (sizeof($removedMessageIds) > 0) {
                    $data['removedMessageIds'] = $removedMessageIds;
                }
            }
        }

        return $data;
    }

    private function getMessagesOfConversation($conversationId, $count){
        $deletedTimestamp = MAILBOX_BOL_ConversationService::getInstance()->getConversationDeletedTimestamp($conversationId);


        $dtoList = array();
        if(isset($_GET['last_id'])){
            $dtoList = MAILBOX_BOL_MessageDao::getInstance()->findHistory($conversationId, $_GET['last_id'], $count, $deletedTimestamp);
        }else{
            $dtoList = MAILBOX_BOL_MessageDao::getInstance()->findListByConversationId($conversationId, $count, $deletedTimestamp);
            $dtoList = array_reverse($dtoList);
        }

        foreach($dtoList as $message)
        {
            $messageInfo = $this->getMessageInfo($message);
            $list[] = $messageInfo;
        }

        return $list;
    }

    public function removePrefetchFromText($text){
        if(strpos($text, '<') !== false) {
            $text = '<div>'.$text.'</div>';
            $doc = new DOMDocument();
            @$doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
            //$domDoc1 = preg_replace('~<(?:!DOCTYPE|/?(?:html|head|body))[^>]*>\s*~i', '', $doc->saveHTML());

            # remove <!DOCTYPE
            $doc->removeChild($doc->doctype);
            # remove <html><body></body></html>
            $domDoc2 = "";
            $element = $doc->firstChild->firstChild->firstChild;
            $element = $this->removeAdditionalHtmlTag($element);
            $children  = $element->childNodes;
            foreach ($children as $child)
            {
                $domDoc2 .= $element->ownerDocument->saveHTML($child);
            }
            $text = $domDoc2;
        }

        return $text;
    }

    public function removeMessage() {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('mailbox', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $messageId = null;

        if(isset($_POST['id'])){
            $messageId = $_POST['id'];
        }

        if($messageId == null || !OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $done = MAILBOX_BOL_ConversationService::getInstance()->deleteMessage($messageId);
        if ($done){
            return array('valid' => true, 'id' => (int) $messageId);
        }

        return array('valid' => false, 'message' => 'authorization_error');
    }

    public function clearMessages() {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('mailbox', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $opponentId = null;

        if(isset($_POST['opponentId'])){
            $opponentId = $_POST['opponentId'];
        }

        if($opponentId == null || !OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userId = OW::getUser()->getId();

        $conversationId = MAILBOX_BOL_ConversationService::getInstance()->getChatConversationIdWithUserById($userId, $opponentId);

        if (!isset($conversationId)) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        MAILBOX_BOL_ConversationService::getInstance()->deleteConversation(array($conversationId), $userId);

        return array('valid' => true, 'opponentId' => (int) $opponentId, 'conversationId' => (int) $conversationId);
    }

    public function editMessage() {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('mailbox', true)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $messageId = null;
        $text = null;

        if(isset($_POST['id'])){
            $messageId = $_POST['id'];
        }

        if(isset($_POST['text'])){
            $text = $_POST['text'];
        }

        if($text == null || $messageId == null || !OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $text = str_replace('↵',"\r\n", $text);
        $message = MAILBOX_BOL_ConversationService::getInstance()->editMessage($messageId, $text);
        if ($message){
            if (isset($message->text)) {
                $message->text = MAILBOX_BOL_ConversationService::getInstance()->json_decode_text($message->text);
            }
            $info = $this->getMessageInfo($message);
            return array('valid' => true, 'message' => $info);
        }

        return array('valid' => false, 'message' => 'authorization_error');
    }

    /***
     * @param domElement $element
     * @return mixed
     */
    private function removeAdditionalHtmlTag($element){
        if($element->nodeType != XML_ELEMENT_NODE)
            return $element;

        if($element->hasAttribute('class')){
            $class = $element->getAttribute('class');
            $attrList = ['ow_oembed_attachment_preview'];

            foreach($attrList as $attr){
                if(strpos($class, $attr) !== false){
                    return false;
                }
            }
        }

        $children  = $element->childNodes;
        for ($i=$children->length-1;$i>=0;$i--)
        {
            $child = $children->item($i);
            $newChild = $this->removeAdditionalHtmlTag($child);
            if(isset($newChild) && $newChild!=false) {
                $element->replaceChild($newChild, $child);
            }
            else {
                $element->removeChild($child);
            }
        }

        return $element;
    }

}