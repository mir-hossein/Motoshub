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
 * Data Access Object for `event_item` table.
 *
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_plugins.event.bol
 * @since 1.0
 */
class EVENT_BOL_EventDao extends OW_BaseDao
{
    const TITLE = 'title';
    const LOCATION = 'location';
    const CREATE_TIME_STAMP = 'createTimeStamp';
    const START_TIME_STAMP = 'startTimeStamp';
    const END_TIME_STAMP = 'endTimeStamp';
    const USER_ID = 'userId';
    const WHO_CAN_VIEW = 'whoCanView';
    const WHO_CAN_INVITE = 'whoCanInvite';
    const STATUS = 'status';

    const VALUE_WHO_CAN_INVITE_CREATOR = 1;
    const VALUE_WHO_CAN_INVITE_PARTICIPANT = 2;
    const VALUE_WHO_CAN_VIEW_ANYBODY = 1;
    const VALUE_WHO_CAN_VIEW_INVITATION_ONLY = 2;

    const CACHE_LIFE_TIME = 86400;

    const CACHE_TAG_PUBLIC_EVENT_LIST = 'event_public_event_list';
    const CACHE_TAG_EVENT_LIST = 'event_event_list';

    /**
     * Singleton instance.
     *
     * @var EVENT_BOL_EventDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return EVENT_BOL_EventDao
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
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'EVENT_BOL_Event';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'event_item';
    }

    /**
     * Returns latest public events ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findAllLatestPublicEventsIds( $first, $count )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::WHO_CAN_VIEW, self::VALUE_WHO_CAN_VIEW_ANYBODY);
        $example->andFieldEqual(self::STATUS, 1);
        $example->setOrder(self::CREATE_TIME_STAMP . ' DESC');
        $example->setLimitClause($first, $count);

        return $this->findIdListByExample($example);
    }

    /**
     * Returns latest public events.
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findPublicEvents( $first, $count, $past = false )
    {
        $where = " `" . self::WHO_CAN_VIEW . "` = :wcv ";
        $params = array('wcv' => self::VALUE_WHO_CAN_VIEW_ANYBODY, 'startTime' => time(), 'endTime' => time(), 'first' => (int) $first, 'count' => (int) $count);

        if ( OW::getUser()->isAuthorized('event') )
        {
            $params = array('startTime' => time(), 'endTime' => time(), 'first' => (int) $first, 'count' => (int) $count);
            $where = " 1 ";
        }

        $where .= " AND `".self::STATUS."` = 1 ";
        
        if ( $past )
        {
            $query = "SELECT * FROM `" . $this->getTableName() . "` WHERE " . $where . "
                AND " . $this->getTimeClause(true) . " ORDER BY `startTimeStamp` DESC LIMIT :first, :count";
        }
        else
        {
            $query = "SELECT * FROM `" . $this->getTableName() . "` WHERE " . $where . "
                AND " . $this->getTimeClause() . " ORDER BY `startTimeStamp` LIMIT :first, :count";
        }

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), $params);
    }

        /**
     * Returns latest public events.
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findExpiredEventsForCronJobs( $first, $count )
    {        
        $params = array('first' => (int) $first, 'count' => (int) $count, 'time' => time());
        
        $query = " SELECT DISTINCT `e`.* FROM `" . $this->getTableName() . "` as `e` "
               . " INNER JOIN `" . EVENT_BOL_EventInviteDao::getInstance()->getTableName() . "` as `ei` ON ( `ei`.eventId = e.id ) "
               . " WHERE `e`.`endTimeStamp` < :time LIMIT :first, :count";
        
        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), $params);       
        
    }
    
    /**
     * @return integer
     */
    public function findPublicEventsCount( $past = false )
    {
        $where = " AND `".self::STATUS."` = 1 ";
        if ( $past )
        {
            $query = "SELECT COUNT(*) FROM `" . $this->getTableName() . "` WHERE `" . self::WHO_CAN_VIEW . "` = :wcv AND " . $this->getTimeClause(true) . $where;
        }
        else
        {
            $query = "SELECT COUNT(*) FROM `" . $this->getTableName() . "` WHERE `" . self::WHO_CAN_VIEW . "` = :wcv AND " . $this->getTimeClause() . $where;
        }

        return $this->dbo->queryForColumn($query, array('wcv' => self::VALUE_WHO_CAN_VIEW_ANYBODY, 'startTime' => time(), 'endTime' => time()));
    }

    /**
     * Returns events with user status.
     *
     * @param integer $userId
     * @param integer $userStatus
     * @param integer $first
     * @param inetger $count
     * @return array
     */
    public function findUserEventsWithStatus( $userId, $userStatus, $first, $count, $addUnapproved = false )
    {
        $where = ' 1 ';
        
        if ( $addUnapproved )
        {
             $where = ' `e`.status = 1 ';
        }
        
        $query = "SELECT `e`.* FROM `" . $this->getTableName() . "` AS `e`
            LEFT JOIN `" . EVENT_BOL_EventUserDao::getInstance()->getTableName() . "` AS `eu` ON (`e`.`id` = `eu`.`eventId`)
            WHERE $where AND `eu`.`userId` = :userId AND `eu`.`" . EVENT_BOL_EventUserDao::STATUS . "` = :status AND " . $this->getTimeClause(false, 'e') . "
            ORDER BY `" . self::START_TIME_STAMP . "` LIMIT :first, :count";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array('userId' => $userId, 'status' => $userStatus, 'first' => $first, 'count' => $count, 'startTime' => time(), 'endTime' => time()));
    }


    /***
     * @param $ids
     * @param $first
     * @param $count
     * @param bool $addUnapproved
     * @return array
     */
    public function findEventsWithIds( $ids, $first, $count, $addUnapproved = false )
    {
        if($ids == null || sizeof($ids) == 0){
            return array();
        }
        $where = ' 1 ';

        if ( $addUnapproved )
        {
            $where = ' `e`.status = 1 ';
        }

        $query = "SELECT `e2`.* FROM `" . $this->getTableName() . "` AS `e2` where `e2`.whoCanView = ".self::VALUE_WHO_CAN_VIEW_ANYBODY." AND `e2`.id in (".OW::getDbo()->mergeInClause($ids).")";
        $params = array('first' => $first, 'count' => $count);
        if(OW::getUser()->isAdmin() || OW::getUser()->isAuthorized('event')){
            $query = "SELECT `e2`.* FROM `" . $this->getTableName() . "` AS `e2` where `e2`.id in (".OW::getDbo()->mergeInClause($ids).")";
        }
        else if(OW::getUser()->isAuthenticated()){
            $userCondition = " AND `e`.whoCanView = ".self::VALUE_WHO_CAN_VIEW_INVITATION_ONLY . " AND `eu`.`userId` = :userId ";
            $query .= " union SELECT `e`.* FROM `" . $this->getTableName() . "` AS `e`
            LEFT JOIN `" . EVENT_BOL_EventUserDao::getInstance()->getTableName() . "` AS `eu` ON (`e`.`id` = `eu`.`eventId`)
            WHERE $where AND `eu`.`" . EVENT_BOL_EventUserDao::STATUS . "` = 1 AND `e`.id in (".OW::getDbo()->mergeInClause($ids).") ". $userCondition;

            $params['userId'] = OW::getUser()->getId();
        }
        $query .= " ORDER BY `" . self::START_TIME_STAMP . "` DESC LIMIT :first, :count";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), $params);
    }

    /**
     * @param integer $userId
     * @param integer $status
     * @return integer
     */
    public function findUserEventsCountWithStatus( $userId, $status, $addUnapproved = false )
    {
        $where = ' 1 ';
        
        if ( $addUnapproved )
        {
             $where = ' `e`.status = 1 ';
        }
        
        $query = "SELECT COUNT(*) AS `count` FROM `" . $this->getTableName() . "` AS `e`
            LEFT JOIN `" . EVENT_BOL_EventUserDao::getInstance()->getTableName() . "` AS `eu` ON (`e`.`id` = `eu`.`eventId`)
            WHERE $where AND `eu`.`userId` = :userId AND `eu`.`" . EVENT_BOL_EventUserDao::STATUS . "` = :status AND " . $this->getTimeClause(false, 'e');
        
        return (int) $this->dbo->queryForColumn($query, array('userId' => $userId, 'status' => $status, 'startTime' => time(), 'endTime' => time()));
    }

    /**
     * Returns events with user status.
     *
     * @param integer $userId
     * @param integer $userStatus
     * @param integer $first
     * @param inetger $count
     * @return array
     */
    public function findPublicUserEventsWithStatus( $userId, $userStatus, $first, $count )
    {
        $query = "SELECT `e`.* FROM `" . $this->getTableName() . "` AS `e`
            LEFT JOIN `" . EVENT_BOL_EventUserDao::getInstance()->getTableName() . "` AS `eu` ON (`e`.`id` = `eu`.`eventId`)
            WHERE `e`.status = 1 AND `eu`.`userId` = :userId AND `eu`.`" . EVENT_BOL_EventUserDao::STATUS . "` = :status AND " . $this->getTimeClause(false, 'e') . " AND `e`.`" . self::WHO_CAN_VIEW . "` = " . self::VALUE_WHO_CAN_VIEW_ANYBODY . "
            ORDER BY `" . self::START_TIME_STAMP . "` LIMIT :first, :count";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array('userId' => $userId, 'status' => $userStatus, 'first' => $first, 'count' => $count, 'startTime' => time(), 'endTime' => time()));
    }

    /**
     * @param integer $userId
     * @param integer $status
     * @return integer
     */
    public function findPublicUserEventsCountWithStatus( $userId, $status )
    {
        $query = "SELECT COUNT(*) AS `count` FROM `" . $this->getTableName() . "` AS `e`
            LEFT JOIN `" . EVENT_BOL_EventUserDao::getInstance()->getTableName() . "` AS `eu` ON (`e`.`id` = `eu`.`eventId`)
            WHERE `e`.status = 1 AND `eu`.`userId` = :userId AND `eu`.`" . EVENT_BOL_EventUserDao::STATUS . "` = :status AND " . $this->getTimeClause(false, 'e') . " AND `e`.`" . self::WHO_CAN_VIEW . "` = " . self::VALUE_WHO_CAN_VIEW_ANYBODY . "";

        return (int) $this->dbo->queryForColumn($query, array('userId' => $userId, 'status' => $status, 'startTime' => time(), 'endTime' => time()));
    }

    /**
     * Returns user created events.
     *
     * @param integer $userId
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findUserCreatedEvents( $userId, $first, $count )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::USER_ID, $userId);
        $example->andFieldEqual(self::STATUS, 1);
        $example->setOrder(self::START_TIME_STAMP );
        $example->andFieldGreaterThan(self::START_TIME_STAMP, time());
        $example->setLimitClause($first, $count);

        return $this->findListByExample($example);
    }

    /**
     * @param integer $userId
     * @return integer
     */
    public function findUserCretedEventsCount( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::USER_ID, $userId);
        $example->andFieldEqual(self::STATUS, 1);
        $example->andFieldGreaterThan(self::START_TIME_STAMP, time());

        return $this->countByExample($example);
    }

    /**
     * @param integer $userId
     * @return array<EVENT_BOL_Event>
     */
    public function findUserInvitedEvents( $userId, $first, $count )
    {
        $query = "SELECT `e`.* FROM `" . $this->getTableName() . "` AS `e`
            INNER JOIN `" . EVENT_BOL_EventInviteDao::getInstance()->getTableName() . "` AS `ei` ON ( `e`.`id` = `ei`.`" . EVENT_BOL_EventInviteDao::EVENT_ID . "` )
            WHERE `e`.status = 1 AND `ei`.`" . EVENT_BOL_EventInviteDao::USER_ID . "` = :userId 
            GROUP BY `e`.`id` LIMIT :first, :count";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array('userId' => (int) $userId, 'first' => (int) $first, 'count' => (int) $count));
    }

    /**
     * @param integer $userId
     * @return integer
     */
    public function findUserInvitedEventsCount( $userId )
    {
        $query = "SELECT COUNT(*) AS `count` FROM `" . $this->getTableName() . "` AS `e`
            INNER JOIN `" . EVENT_BOL_EventInviteDao::getInstance()->getTableName() . "` AS `ei` ON ( `e`.`id` = `ei`.`" . EVENT_BOL_EventInviteDao::EVENT_ID . "` )
            WHERE `e`.status = 1 AND `ei`.`" . EVENT_BOL_EventInviteDao::USER_ID . "` = :userId";

        return $this->dbo->queryForColumn($query, array('userId' => (int) $userId));
    }

    /**
     * @param integer $userId
     * @return integer
     */
    public function findDisplayedUserInvitationCount( $userId )
    {
        $query = "SELECT COUNT(*) AS `count` FROM `" . $this->getTableName() . "` AS `e`
            INNER JOIN `" . EVENT_BOL_EventInviteDao::getInstance()->getTableName() . "` AS `ei` ON ( `e`.`id` = `ei`.`" . EVENT_BOL_EventInviteDao::EVENT_ID . "` )
            WHERE `e`.status = 1 AND `ei`.`" . EVENT_BOL_EventInviteDao::USER_ID . "` = :userId AND `ei`.`displayInvitation` = true AND " . $this->getTimeClause(false, 'e') . " GROUP BY `e`.`id`";

        return $this->dbo->queryForColumn($query, array('userId' => (int) $userId, 'startTime' => time(), 'endTime' => time()));
    }


    /**
     * @param integer $userId
     * @return array<EVENT_BOL_Event>
     */
    public function findAllUserEvents( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::USER_ID, (int) $userId);
        
        return $this->findListByExample($example);
    }

    private function getTimeClause( $past = false, $alias = null )
    {
        if ( $past )
        {
            return "( " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::START_TIME_STAMP . "` <= :startTime AND ( " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::END_TIME_STAMP . "` IS NULL OR " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::END_TIME_STAMP . "` <= :endTime ) )";
        }

        return "( " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::START_TIME_STAMP . "` > :startTime OR ( " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::END_TIME_STAMP . "` IS NOT NULL AND " . (!empty($alias) ? "`{$alias}`." : "" ) . "`" . self::END_TIME_STAMP . "` > :endTime ) )";
    }


    /**
     * @author Mohammad Agha Abbasloo
     * @param $userId
     * @param $userStatus
     * @param $first
     * @param count
     * @param $past
     * @param $eventIds
     * @param $addUnapproved
     * @param $isPublic
     * @param $searchTitle
     * @return array<EVENT_BOL_Event>
     */
    public function findPublicEventsByFiltering($userId,$userStatus,$first, $count, $past ,$eventIds = array(), $addUnapproved = false,$isPublic=true, $searchTitle)
    {
        if($userId!=null)
        {
            return $this->findUserEvents($userId,$userStatus,$first, $count, $past ,$eventIds, $addUnapproved,$isPublic, $searchTitle);
        }
        $whereClause = ' WHERE 1=1 ';
        $params = array();
        if ( !OW::getUser()->isAuthorized('event') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $whereClause .= ' AND (`e`.`whoCanView`="' . self::VALUE_WHO_CAN_VIEW_ANYBODY.'"';
            if(OW::getUser()->isAuthenticated()){
                $whereClause .=" OR `eu`.`userId`=:userId ) ";
                $params['userId']=OW::getUser()->getId();
            }else{
                $whereClause .=" ) ";
            }
        }
        if ($userStatus != null) {
            $whereClause .= ' AND `eu`.`' . EVENT_BOL_EventUserDao::STATUS . '` =:userStatus';
            $params['userStatus']=$userStatus;
        }
//        if ($isPublic) {
//            $whereClause .= ' AND `e`.`' . self::WHO_CAN_VIEW . '` =:wcv';
//            $params['wcv']=self::VALUE_WHO_CAN_VIEW_ANYBODY;
//        }
        $order =  'DESC';
        if ($past !== null)
        {
            $params['startTime']= time();
            $params['endTime']= time();
            if ($past)
            {
                $whereClause .= ' AND ' . $this->getTimeClause(true, 'e');
            } else
            {
                $order =  'ASC';
                $whereClause .= ' AND ' . $this->getTimeClause(false, 'e');
            }
        }
        if ( $addUnapproved )
        {
            $whereClause .= ' AND `e`.`status` = 1 ';
        }
        if($eventIds!=null && sizeof($eventIds)>0){
            $whereClause.=  ' AND `e`.`id` in ('. OW::getDbo()->mergeInClause($eventIds) .')';
        }
        if($searchTitle!=null){
            $whereClause.=' AND UPPER(`e`.`title`) like UPPER (:searchTitle)';
            $params['searchTitle']= '%'. $searchTitle . '%';
        }
        $params['first'] = (int) $first;
        $params['count'] = (int) $count;

        $query = 'SELECT DISTINCT `e`.* FROM `' . $this->getTableName() . '` AS `e`
            LEFT JOIN `' . EVENT_BOL_EventUserDao::getInstance()->getTableName() . '` AS `eu` ON (`e`.`id` = `eu`.`eventId`)'
            . $whereClause . '
            ORDER BY `' . self::START_TIME_STAMP . '` '.$order.' LIMIT :first, :count';
        return $this->dbo->queryForObjectList($query,$this->getDtoClassName(), $params);
    }

    /**
     * @author Mohammad Agha Abbasloo
     * @param $userId
     * @param $userStatus
     * @param $past
     * @param array <eventIds>
     * @param $addUnapproved
     * @param $isPublic
     * @return count of events number
     */
    public function findPublicEventsByFilteringCount($userId,$userStatus, $past , $eventIds = array(), $addUnapproved = false,$isPublic=true,$searchTitle)
    {
       if($userId!=null)
        {
            return $this->findUserEventsCount($userId,$userStatus, $past ,$eventIds, $addUnapproved,$isPublic, $searchTitle);
        }
        $whereClause = "WHERE 1=1 ";
        $params = array();
        if ( !OW::getUser()->isAuthorized('event') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $whereClause .= ' AND (`e`.`whoCanView`="' . self::VALUE_WHO_CAN_VIEW_ANYBODY.'"';
            if(OW::getUser()->isAuthenticated()){
                $whereClause .=" OR `eu`.`userId`=:userId ) ";
                $params['userId']=OW::getUser()->getId();
            }else{
                $whereClause .=" ) ";
            }
        }
        if ($userStatus != null) {
            $whereClause .= " AND `eu`.`" . EVENT_BOL_EventUserDao::STATUS . "` =:userStatus";
            $params['userStatus']=$userStatus;
        }
//        if ($isPublic) {
//
//            $whereClause .= " AND `e`.`" . self::WHO_CAN_VIEW . "` =:wcv";
//            $params['wcv']=self::VALUE_WHO_CAN_VIEW_ANYBODY;
//        }
        if ($past !== null)
        {
            $params['startTime']= time();
            $params['endTime']= time();
            if ($past)
            {
                $whereClause .= " AND " . $this->getTimeClause(true, 'e');
            } else
            {
                $whereClause .= " AND " . $this->getTimeClause(false, 'e');
            }
        }
        if ( $addUnapproved )
        {
            $whereClause .= " AND `e`.`status` = 1 ";
        }
        if($eventIds!=null && sizeof($eventIds)>0){
            $whereClause.=  " AND `e`.`id` in (". OW::getDbo()->mergeInClause($eventIds) .")";
        }

        if($searchTitle!=null){
            $whereClause.=' AND UPPER(`e`.`title`) like UPPER (:searchTitle)';
            $params['searchTitle']= '%'. $searchTitle . '%';
        }

        $query = "SELECT COUNT(DISTINCT `e`.`id`) AS `count` FROM `" . $this->getTableName() . "` AS `e`
            LEFT JOIN `" . EVENT_BOL_EventUserDao::getInstance()->getTableName() . "` AS `eu` ON (`e`.`id` = `eu`.`eventId`)"
            . $whereClause . "
            ORDER BY `" . self::START_TIME_STAMP."`";
        return $this->dbo->queryForColumn($query, $params);

    }

    /**
     * Returns user created events.
     *
     * @param integer $userId
     * @param string $userStatus
     * @param integer $first
     * @param integer $count
     * @param boolean $past
     * @param array $eventIds
     * @param boolean $addUnapproved
     * @param boolean $isPublic
     * @param string $searchTitle
     * @return array
     */
    public function findUserEvents($userId = null,$userStatus,$first, $count, $past ,$eventIds = array(), $addUnapproved = false,$isPublic=true, $searchTitle)
    {
        $owner = false;
        $viewerEventQuery["before"] = "";
        $viewerEventQuery["query"] = "";
        $viewerEventQuery["after"] = "";
        $viewerId = null;
        $limit = '';
        if ( $first !== null && $count !== null )
        {
            $limit = " LIMIT $first, $count";
        }
        $searchQuery = "";
        $params = array('s' => 1);
        if(isset($userId)){
            $params['u'] =  $userId;
            $searchQuery.= ' AND eu.userId=:u ';
        }

        if($eventIds!=null && sizeof($eventIds)>0){
            $searchQuery.=  " AND `e`.`id` in (". OW::getDbo()->mergeInClause($eventIds) .")";
        }
        if($searchTitle!=null){
            $searchQuery.=' AND UPPER(`e`.`title`) like UPPER (:searchTitle)';
            $params['searchTitle']= '%'. $searchTitle . '%';
        }
        if ($userStatus != null) {
            $searchQuery .= ' AND `eu`.`' . EVENT_BOL_EventUserDao::STATUS . '` =:userStatus';
            $params['userStatus']=$userStatus;
        }
/*        if ($isPublic) {
            $searchQuery .= ' AND `e`.`' . self::WHO_CAN_VIEW . '` =:wcv';
            $params['wcv']=self::VALUE_WHO_CAN_VIEW_ANYBODY;
        }*/
        $order =  'DESC';
        if ($past !== null)
        {
            $params['startTime']= time();
            $params['endTime']= time();
            if ($past)
            {
                $searchQuery .= ' AND ' . $this->getTimeClause(true, 'e');
            } else
            {
                $order =  'ASC';
                $searchQuery .= ' AND ' . $this->getTimeClause(false, 'e');
            }
        }
        if ( $addUnapproved )
        {
            $searchQuery .= ' AND `e`.`status` = 1 ';
        }
        if(OW::getUser()->isAuthenticated()){
            if($userId == OW::getUser()->getId()){
                $owner = true;
            }else if ( !OW::getUser()->isAuthorized('event')){
                $viewerId = OW::getUser()->getId();
                $viewerEventQuery["before"] = "select * from ( ";
                $viewerEventQuery["query"] = " union (select e.* from ".EVENT_BOL_EventUserDao::getInstance()->getTableName()." eu, " . $this->getTableName() . " e where e.id = eu.eventId and eu.userId = :u and e.whoCanView = :invite and e.id in (select eu2.eventId from ".EVENT_BOL_EventUserDao::getInstance()->getTableName()." eu2 where eu2.userId = :vid)".$searchQuery." ) ";
                $viewerEventQuery["query"] = $viewerEventQuery["query"] . " ) as e ";
            }
        }

        if($viewerId != null){
            $params['vid'] = OW::getUser()->getId();
            $params['invite'] = self::VALUE_WHO_CAN_VIEW_INVITATION_ONLY;
        }
        $wcvWhere = ' 1 ';

        if ( !OW::getUser()->isAuthorized('event') && !$owner ) //TODO TEMP Hack - checking if current user is moderator
        {
            $wcvWhere = 'e.whoCanView="' . self::VALUE_WHO_CAN_VIEW_ANYBODY . '"';
        }

        $query = $viewerEventQuery["before"] . "SELECT e.* FROM " . $this->getTableName() . " e
            INNER JOIN " . EVENT_BOL_EventUserDao::getInstance()->getTableName() . " eu ON e.id = eu.eventId
            WHERE e.status=:s AND " . $wcvWhere . $searchQuery  . $viewerEventQuery["query"] . " order by e.startTimeStamp ".$order . $limit;

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), $params);
    }

    /**
     * @param integer $userId
     * @param string $userStatus
     * @param boolean $past
     * @param array $eventIds
     * @param boolean $addUnapproved
     * @param boolean $isPublic
     * @param string $searchTitle
     * @return integer
     */
    public function findUserEventsCount($userId=null,$userStatus, $past ,$eventIds = array(), $addUnapproved = false,$isPublic=true, $searchTitle)
    {
        $owner = false;
        $viewerEventQuery["before"] = "SELECT count(e.id) ";
        $viewerEventQuery["query"] = "";
        $viewerEventQuery["after"] = "";
        $viewerId = null;
        $params = array('s' => 1);
        $searchQuery = "";
        if(isset($userId)){
            $params['u'] =  $userId;
            $searchQuery.= ' AND eu.userId=:u ';
        }

        if($eventIds!=null && sizeof($eventIds)>0){
            $searchQuery.=  " AND `e`.`id` in (". OW::getDbo()->mergeInClause($eventIds) .")";
        }
        if($searchTitle!=null){
            $searchQuery.=' AND UPPER(`e`.`title`) like UPPER (:searchTitle)';
            $params['searchTitle']= '%'. $searchTitle . '%';
        }
        if ($userStatus != null) {
            $searchQuery .= ' AND `eu`.`' . EVENT_BOL_EventUserDao::STATUS . '` =:userStatus';
            $params['userStatus']=$userStatus;
        }
/*        if ($isPublic) {
            $searchQuery .= ' AND `e`.`' . self::WHO_CAN_VIEW . '` =:wcv';
            $params['wcv']=self::VALUE_WHO_CAN_VIEW_ANYBODY;
        }*/
        if ($past !== null)
        {
            $params['startTime']= time();
            $params['endTime']= time();
            if ($past)
            {
                $searchQuery .= ' AND ' . $this->getTimeClause(true, 'e');
            } else
            {
                $searchQuery .= ' AND ' . $this->getTimeClause(false, 'e');
            }
        }
        if ( $addUnapproved )
        {
            $searchQuery .= ' AND `e`.`status` = 1 ';
        }
        if(OW::getUser()->isAuthenticated()){
            if($userId == OW::getUser()->getId()){
                $owner = true;
            }else if ( !OW::getUser()->isAuthorized('event')){
                $viewerId = OW::getUser()->getId();
                $viewerEventQuery["before"] = "select count(*) from ( SELECT e.* ";
                $viewerEventQuery["query"] = " union (select e.* from ".EVENT_BOL_EventUserDao::getInstance()->getTableName()." eu, " . $this->getTableName() . " e where e.id = eu.eventId and eu.userId = :u and e.whoCanView = :invite and e.id in (select eu2.eventId from ".EVENT_BOL_EventUserDao::getInstance()->getTableName()." eu2 where eu2.userId = :vid)".$searchQuery.") ";
                $viewerEventQuery["query"] = $viewerEventQuery["query"] . " ) as e ";
            }
        }

        if($viewerId != null){
            $params['vid'] = OW::getUser()->getId();
            $params['invite'] = self::VALUE_WHO_CAN_VIEW_INVITATION_ONLY;
        }

        $wcvWhere = ' 1 ';

        if ( !OW::getUser()->isAuthorized('event') && !$owner ) //TODO TEMP Hack - checking if current user is moderator
        {
            $wcvWhere = 'e.whoCanView="' . self::VALUE_WHO_CAN_VIEW_ANYBODY . '"';
        }

        $query = $viewerEventQuery["before"] . " FROM " . $this->getTableName() . " e
            INNER JOIN " . EVENT_BOL_EventUserDao::getInstance()->getTableName() . " eu ON e.id = eu.eventId
            WHERE e.status=:s AND " . $wcvWhere . $searchQuery . $viewerEventQuery["query"];

        return (int) $this->dbo->queryForColumn($query, $params);

    }

    /**
     * Returns user created events.
     *
     * @param integer $userId
     * @param integer $count
     * @return array
     */
    public function findUpComingEventsForUser($count,$userId = null)
    {
        $params = array('s' => 1);
        $params['startTime']= time();
        $params['endTime']= time();
        if(isset($userId))
        {
            $whereClause=' AND (eu.userId=:u OR `e`.whoCanView="'.self::VALUE_WHO_CAN_VIEW_ANYBODY.'") AND ' . $this->getTimeClause(false, 'e');
            $params['u']=$userId;
        }else{
            $whereClause=' AND `e`.whoCanView="'.self::VALUE_WHO_CAN_VIEW_ANYBODY.'" AND ' . $this->getTimeClause(false, 'e');
        }
        $query = "SELECT e.* FROM " . $this->getTableName() . " e
            INNER JOIN " . EVENT_BOL_EventUserDao::getInstance()->getTableName() . " eu ON e.id = eu.eventId
            WHERE e.status=:s ".$whereClause." GROUP BY e.id ORDER BY e.startTimeStamp ASC LIMIT 0,".$count;
        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), $params);
    }

    /**
     * @author Mohammad Agha Abbasloo
     * @param $userId
     * @return integer
     */
    public function findUpComingEventsForUserCount($userId=null)
    {
        $params = array('s' => 1);
        $params['startTime']= time();
        $params['endTime']= time();
        if(isset($userId))
        {
            $whereClause=' AND (eu.userId=:u OR `e`.whoCanView="'.self::VALUE_WHO_CAN_VIEW_ANYBODY.'") AND ' . $this->getTimeClause(false, 'e');
            $params['u']=$userId;
        }else{
            $whereClause=' AND `e`.whoCanView="'.self::VALUE_WHO_CAN_VIEW_ANYBODY.'" AND ' . $this->getTimeClause(false, 'e');
        }
        $query = "SELECT count(e.id)  FROM " . $this->getTableName() . " e
            INNER JOIN " . EVENT_BOL_EventUserDao::getInstance()->getTableName() . " eu ON e.id = eu.eventId
            WHERE e.status=:s ".$whereClause." GROUP BY e.id";
        return (int)$this->dbo->queryForColumn($query, $params);
    }

}
