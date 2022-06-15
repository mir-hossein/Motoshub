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
 * Data Access Object for `mailbox_message` table.
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.bol
 * @since 1.0
 */
class MAILBOX_BOL_MessageDao extends OW_BaseDao
{

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }
    /**
     * Class instance
     *
     * @var MAILBOX_BOL_MessageDao
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return MAILBOX_BOL_MessageDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see MAILBOX_BOL_MessageDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'MAILBOX_BOL_Message';
    }

    /**
     * @see MAILBOX_BOL_MessageDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'mailbox_message';
    }

    /**
     * Deletes conversation's messages
     *
     * @param int $conversationId
     * @return int
     */
    public function deleteByConversationId( $conversationId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('conversationId', (int) $conversationId);
        return $this->deleteByExample($example);
    }

    /**
     * Deletes conversation's messages
     *
     * @param int $replyId
     */
    public function clearReplies( $replyId )
    {
        $sql = "UPDATE ".$this->getTableName()." SET replyId = null WHERE replyId = :replyId;";
        $this->dbo->query($sql,array('replyId'=>$replyId));
    }

    /**
     * Returns conversation's message list
     *
     * @param int $conversationId
     * @return array
     */
    public function findListByConversationId( $conversationId, $count, $deletedTimestamp = 0 )
    {
        $sql = "SELECT `tmp`.* FROM (SELECT * FROM `{$this->getTableName()}` WHERE `conversationId` = :conversationId AND `timeStamp` > :deletedTimestamp ORDER BY `timeStamp` DESC LIMIT :count) as `tmp` ORDER BY `tmp`.`timeStamp` ASC, `tmp`.`id`";

        $result = $this->dbo->queryForObjectList($sql, $this->getDtoClassName(), array('conversationId' => $conversationId, 'count' => $count, 'deletedTimestamp'=>$deletedTimestamp));
        foreach($result as $message)
        {
            $message->text = MAILBOX_BOL_ConversationService::getInstance()->json_decode_text($message->text);
        }
        return $result;
    }

    public function getConversationLength( $conversationId, $deletedTimestamp = 0 )
    {
        $sql = "SELECT COUNT(*) FROM `{$this->getTableName()}` WHERE `conversationId` = :conversationId AND `timeStamp` > :deletedTimestamp";

        return $this->dbo->queryForColumn($sql, array('conversationId' => $conversationId, 'deletedTimestamp'=>$deletedTimestamp));
    }

    public function findHistory( $conversationId, $beforeMessageId, $count, $deletedTimestamp = 0 )
    {
        $sql = "SELECT * FROM `{$this->getTableName()}` WHERE `conversationId` = :conversationId AND `id` < :beforeMessageId AND `timeStamp` > :deletedTimestamp ORDER BY `id` DESC LIMIT :count";

        $result = $this->dbo->queryForObjectList($sql, $this->getDtoClassName(), array('conversationId' => $conversationId, 'beforeMessageId'=>$beforeMessageId, 'count' => $count, 'deletedTimestamp'=>$deletedTimestamp));
        foreach($result as $message)
        {
            $message->text = MAILBOX_BOL_ConversationService::getInstance()->json_decode_text($message->text);
        }

        return $result;
    }

    /**
     * Returns conversation's messages count
     *
     * @param int $conversationId
     * @return int
     */
    public function findCountByConversationId( $conversationId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('conversationId', (int) $conversationId);
        return $this->countByExample($example);
    }

    /**
     * don't call this function
     * This is a temporary method used for mailbox plugin update.
     *
     * @param int $messageId
     * @param int $limit
     * @return MAILBOX_BOL_Message
     */
    public function findNotUpdatedMessages( $messageId, $limit = 100 )
    {
        $example = new OW_Example();
        $example->andFieldGreaterThan('id', (int) $messageId);
        $example->setOrder(" id ");
        $example->setLimitClause(0, $limit);

        return $this->findListByExample($example);
    }

    public function findLastMessage( $conversationId )
    {
        $sql = "SELECT * FROM `{$this->getTableName()}` WHERE `conversationId`=:conversationId ORDER BY `timestamp` DESC LIMIT 0,1 ";

        return $this->dbo->queryForObject($sql, $this->getDtoClassName(), array('conversationId'=>$conversationId));
    }

    public function findLastMessageByConversationIdListAndUserIdList( $userId, $userIdList )
    {
        $sql = "SELECT max(`timeStamp`) as `timeStamp`, `recipientId`, `senderId` FROM `{$this->getTableName()}`
        WHERE `senderId` IN ( ".$this->dbo->mergeInClause($userIdList)." ) AND `recipientId` = :userId
        GROUP BY `senderId`, `recipientId`

        UNION

        SELECT max(`timeStamp`) as `timeStamp`, `recipientId`, `senderId`  FROM `{$this->getTableName()}`
        WHERE `senderId` = :userId AND `recipientId` IN ( ".$this->dbo->mergeInClause($userIdList)." )
        GROUP BY `senderId`, `recipientId`
        ";


        return $this->dbo->queryForList($sql, array('userId'=>$userId));
    }

    /***
     * @param $userId
     * @param $userIdList
     * @return array
     */
    public function findLastSenderMessageByConversationIdListAndUserIdList( $userId, $userIdList )
    {
        $sql = "SELECT max(`id`) as `id`,`conversationId`, max(`timeStamp`), `recipientId`, `senderId`  FROM ". $this->getTableName()."  
          WHERE `recipientId` IN ( ".$this->dbo->mergeInClause($userIdList)." ) AND `senderId` = :userId GROUP BY `conversationId`, `recipientId`, `senderId`";
        
        return $this->dbo->queryForList($sql, array('userId'=>$userId));
    }

    public function findFirstMessage( $conversationId )
    {
        $sql = "SELECT * FROM `{$this->getTableName()}` WHERE `conversationId`=:conversationId AND `isSystem`=0 ORDER BY `timestamp` ASC LIMIT 0,1 ";

        return $this->dbo->queryForObject($sql, $this->getDtoClassName(), array('conversationId'=>$conversationId));
    }

    public function findLastSentMessage( $userId )
    {
        $sql = "SELECT `m`.* FROM `{$this->getTableName()}` as `m`
        WHERE `m`.`senderId` = :userId
        ORDER BY `m`.`timeStamp` DESC
        LIMIT 1";

        $result = $this->dbo->queryForObject($sql, $this->getDtoClassName(), array('userId' => $userId));
        if(isset($result)){
            $result->text = MAILBOX_BOL_ConversationService::getInstance()->json_decode_text($result->text);
        }
        return $result;
    }

    public function findUnreadMessages( $userId, $ignoreList, $timeStamp = null, $activeModeList = array())
    {
        $mailModeEnabled = (in_array('mail', $activeModeList)) ? true : false;
        $chatModeEnabled = (in_array('chat', $activeModeList)) ? true : false;

        if ($timeStamp === null)
        {
            $timeStamp = 0;
        }

        $ignore = "";

        if ( !empty( $ignoreList ) )
        {
            $ignore = " AND `m`.id NOT IN (". $this->dbo->mergeInClause($ignoreList) .") ";
        }

        $mailModeIgnore = "";
        if ( !$mailModeEnabled || !$chatModeEnabled )
        {
            if ( !$chatModeEnabled )
            {
                $mailModeIgnore = " AND `conv`.subject <> '" . MAILBOX_BOL_ConversationService::CHAT_CONVERSATION_SUBJECT . "' ";
            }
            else
            {
                $mailModeIgnore = " AND `conv`.subject = '" . MAILBOX_BOL_ConversationService::CHAT_CONVERSATION_SUBJECT . "' ";
            }
        }

        $sql = "SELECT `m`.* FROM `{$this->getTableName()}` as `m`
        LEFT JOIN `".MAILBOX_BOL_ConversationDao::getInstance()->getTableName()."` as `conv` ON (`conv`.`id` = `m`.`conversationId`)
        WHERE ( ( ( ( `conv`.`initiatorId` = :userId AND (`conv`.`deleted` != " . MAILBOX_BOL_ConversationDao::DELETED_INITIATOR . " OR `m`.`timeStamp`>`conv`.`initiatorDeletedTimestamp` ) )
        OR ( `conv`.`interlocutorId` = :userId AND (`conv`.`deleted` != " . MAILBOX_BOL_ConversationDao::DELETED_INTERLOCUTOR . " OR `m`.`timeStamp`>`conv`.`interlocutorDeletedTimestamp`) ) )
        AND `m`.`recipientId` = :userId AND `m`.`recipientRead` = 0 {$ignore} ) OR ( `m`.`senderId` = :userId AND `m`.`timeStamp` > :timeStamp )) {$mailModeIgnore}
        ORDER BY `m`.`id`, `m`.`timeStamp` DESC";

        $result = $this->dbo->queryForObjectList($sql, $this->getDtoClassName(), array('userId' => $userId, 'timeStamp'=>$timeStamp));
        foreach($result as $message)
        {
            $message->text = MAILBOX_BOL_ConversationService::getInstance()->json_decode_text($message->text);
        }
        return $result;
    }

    public function findUnreadMessagesForConversation( $convId, $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('conversationId', $convId);
        $example->andFieldEqual('recipientId', $userId);
        $example->andFieldEqual('recipientRead', 0);

        $result = $this->findListByExample($example);
        foreach($result as $message)
        {
            $message->text = MAILBOX_BOL_ConversationService::getInstance()->json_decode_text($message->text);
        }
        return $result;
    }

    public function findConversationMessagesByLastMessageTimestamp( $convId, $lastMessageTimestamp )
    {
        $sql = "SELECT `m`.* FROM `{$this->getTableName()}` as `m`
        WHERE `m`.`conversationId` = :convId AND `m`.`timeStamp` > :lastMessageTimestamp";

        return $this->dbo->queryForObjectList($sql, $this->getDtoClassName(), array('convId' => $convId, 'lastMessageTimestamp' => $lastMessageTimestamp));
    }

    public function countUnreadMessagesForConversation( $convId, $userId )
    {
        $sql = "SELECT COUNT(*) FROM `{$this->getTableName()}` as `m`
        WHERE `m`.`conversationId` = :convId AND `m`.`recipientId` = :userId AND `m`.`recipientRead` = 0";

        return $this->dbo->queryForColumn($sql, array('convId' => $convId, 'userId' => $userId));
    }

    public function countUnreadMessagesForConversationList( $conversationIdList, $userId )
    {
        $convIdListString = $this->dbo->mergeInClause($conversationIdList);

        $sql = "SELECT `m`.`conversationId`, COUNT(*) as count FROM `{$this->getTableName()}` as `m`
        WHERE `m`.`conversationId` IN ( {$convIdListString} ) AND `m`.`recipientId` = :userId AND `m`.`recipientRead` = 0 GROUP BY `m`.`conversationId`";

        return $this->dbo->queryForList($sql, array('userId' => $userId));
    }

    public function findUnreadConversations( $userId, $messageIgnoreList, $timeStamp = null)
    {
        if (empty($timeStamp))
        {
            $timeStamp = time();
        }

        $ignore = "";

        if ( !empty( $messageIgnoreList ) )
        {
            $ignore = " AND `m`.id NOT IN (". $this->dbo->mergeInClause($messageIgnoreList) .") ";
        }

        $sql = "SELECT `conv`.id FROM `{$this->getTableName()}` as `m`
        INNER JOIN `".MAILBOX_BOL_ConversationDao::getInstance()->getTableName()."` as `conv` ON (`conv`.`id` = `m`.`conversationId`)
        WHERE ( ( ( `conv`.`initiatorId` = :userId AND `conv`.`deleted` != " . MAILBOX_BOL_ConversationDao::DELETED_INITIATOR . " )
        OR ( `conv`.`interlocutorId` = :userId AND `conv`.`deleted` != " . MAILBOX_BOL_ConversationDao::DELETED_INTERLOCUTOR . " ) )
        AND `m`.`recipientId` = :userId AND `m`.`recipientRead` = 0 {$ignore} ) OR ( `m`.`senderId` = :userId AND `m`.`timeStamp` > :timeStamp )
        GROUP BY `conv`.`id`";

        return $this->dbo->queryForColumnList($sql, array('userId' => $userId, 'timeStamp'=>$timeStamp));
    }

    public function findUserListWithCorrespondence($userId, $from = 0, $count = 50)
    {
        $sql = "SELECT `t`.`userId` FROM ( SELECT IF (`m`.`senderId` = :userId, `m`.`recipientId`, `m`.`senderId`) AS `userId`, max(`m`.`timeStamp`) as `maxTimeStamp` FROM `".$this->getTableName()."` as `m`
        WHERE (`m`.`senderId`=:userId OR `m`.`recipientId`=:userId)
        GROUP BY `userId`) AS `t`
        INNER JOIN `".BOL_UserDao::getInstance()->getTableName()."` AS `u` ON ( `u`.`id` = `t`.`userId` )
        ORDER BY `t`.`maxTimeStamp` DESC
        LIMIT :from, :count";

        return $this->dbo->queryForColumnList($sql, array('userId'=>$userId, 'from'=>$from, 'count'=>$count));
    }

    public function findUserListWithCorrespondenceAlt($userId, $friendIdList)
    {
        $sql = "SELECT `t`.`userId` FROM ( SELECT IF (`m`.`senderId` = :userId, `m`.`recipientId`, `m`.`senderId`) AS `userId`, max(`m`.`timeStamp`) as `maxTimeStamp` FROM `".$this->getTableName()."` as `m`
        WHERE (`m`.`senderId`=:userId OR `m`.`recipientId`=:userId)
        GROUP BY `userId`) AS `t`
        INNER JOIN `".BOL_UserDao::getInstance()->getTableName()."` AS `u` ON ( `u`.`id` = `t`.`userId` )
        ORDER BY `t`.`maxTimeStamp` DESC";

        return $this->dbo->queryForColumnList($sql, array('userId'=>$userId));
    }

    /**
     * @param $userId
     * @return MAILBOX_BOL_Message
     */
    public function findUserLastMessage($userId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('senderId', $userId);
        $example->setOrder('timeStamp DESC');
        $example->setLimitClause(0,1);

        return $this->findObjectByExample($example);
    }

    /**
     * @param $userId
     * @param $opponentsId
     * @return array
     */
    public function findUserOpponentsUnreadMessages($userId, $opponentsId)
    {
        if(sizeof($opponentsId) == 0){
            return null;
        }
        $example = new OW_Example();
        $example->andFieldEqual('senderId', $userId);
        $example->andFieldEqual('recipientRead', 0);
        $example->andFieldInArray('recipientId', $opponentsId);

        return $this->findListByExample($example);
    }

    public function findUserSentUnreadMessages($userId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('senderId', $userId);
        $example->andFieldEqual('recipientRead', 0);

        return $this->findListByExample($example);
    }

    public function getChangedData($convId = null){
        $example = new OW_Example();
        if(isset($convId))
            $example->andFieldEqual('conversationId', $convId);
        $example->andFieldEqual('changed', 1);

        return $this->findListByExample($example);
    }

    /**
     * @param $conversationId
     * @param $id
     * @param $senderId
     * @return MAILBOX_BOL_Message
     */
    public function findConversationLastMessage($conversationId,$id,$senderId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('conversationId', $conversationId);
        $example->andFieldEqual('senderId', $senderId);
        $example->andFieldNotEqual('id', $id);
        $example->setOrder('timeStamp DESC');
        $example->setLimitClause(0,1);
        return $this->findObjectByExample($example);
    }

    public function deleteSentMessagesByUserId( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('senderId', (int) $userId);
        return $this->deleteByExample($example);
    }

    public function deleteReceivedMessagesByUserId( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('recipientId', (int) $userId);
        return $this->deleteByExample($example);
    }
}