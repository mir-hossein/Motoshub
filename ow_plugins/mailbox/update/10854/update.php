<?php

/*================================= staled conversation removal ============================================*/
/*remove sent conversations from deleted users*/
$userIdList=OW::getDbo()->queryForColumnList('SELECT DISTINCT `initiatorId` FROM `' . OW_DB_PREFIX . 'mailbox_conversation` ');
foreach($userIdList as $userID) {
    $userChecher=BOL_UserService::getInstance()->findUserByID($userID);
    if ( !isset($userChecher) ) {
        MAILBOX_BOL_ConversationDao::getInstance()->deleteSentConversationsByUserId($userID);
        MAILBOX_BOL_ConversationDao::getInstance()->deleteReceivedConversationsByUserId($userID);
    }
}
/*remove received conversations from deleted users*/
$userIdList=OW::getDbo()->queryForColumnList('SELECT DISTINCT `interlocutorId` FROM `' . OW_DB_PREFIX . 'mailbox_conversation` ');
foreach($userIdList as $userID) {
    $userChecher=BOL_UserService::getInstance()->findUserByID($userID);
    if ( !isset($userChecher) ) {
        MAILBOX_BOL_ConversationDao::getInstance()->deleteSentConversationsByUserId($userID);
        MAILBOX_BOL_ConversationDao::getInstance()->deleteReceivedConversationsByUserId($userID);
    }
}


/*================================= staled messages removal ============================================*/
/*remove sent messages from deleted users*/
$userIdList=OW::getDbo()->queryForColumnList('SELECT DISTINCT `senderId` FROM `' . OW_DB_PREFIX . 'mailbox_message` ');
foreach($userIdList as $userID) {
    $userChecher=BOL_UserService::getInstance()->findUserByID($userID);
    if ( !isset($userChecher) ) {
        MAILBOX_BOL_MessageDao::getInstance()->deleteSentMessagesByUserId($userID);
        MAILBOX_BOL_MessageDao::getInstance()->deleteReceivedMessagesByUserId($userID);
    }
}
/*remove received messages to deleted users*/
$userIdList=OW::getDbo()->queryForColumnList('SELECT DISTINCT `recipientId` FROM `' . OW_DB_PREFIX . 'mailbox_message` ');
foreach($userIdList as $userID) {
    $userChecher=BOL_UserService::getInstance()->findUserByID($userID);
    if ( !isset($userChecher) ) {
        MAILBOX_BOL_MessageDao::getInstance()->deleteSentMessagesByUserId($userID);
        MAILBOX_BOL_MessageDao::getInstance()->deleteReceivedMessagesByUserId($userID);
    }
}