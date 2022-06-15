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
 * Data Access Object for `groups_group` table.
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.groups.bol
 * @since 1.0
 */
class GROUPS_BOL_GroupDao extends OW_BaseDao
{
    const LIST_CACHE_LIFETIME = 86400;
    const LIST_CACHE_TAG = 'groups.list';
    const LIST_CACHE_TAG_LATEST = 'groups.list.latest';
    const LIST_CACHE_TAG_POPULAR = 'groups.list.popular';

    /**
     * Singleton instance.
     *
     * @var GROUPS_BOL_GroupDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return GROUPS_BOL_GroupDao
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
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'GROUPS_BOL_Group';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'groups_group';
    }

    /**
     * Find latest group authors ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findLatestGroupAuthorsIds($first, $count)
    {
        $where = 'WHERE';

        if ( !OW::getUser()->isAuthorized('groups') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $where .= ' `g`.`whoCanView`="' . GROUPS_BOL_Service::WCV_ANYONE . '" AND';
        }

        $query = "SELECT `g`.`userId` FROM `" . $this->getTableName() . "` AS `g`
            $where `g`.`status`=:s
            GROUP BY `g`.`userId` ORDER BY MAX(`g`.`timeStamp`) DESC LIMIT :f, :c";

        return $this->dbo->queryForColumnList($query, array(
            'f' => $first,
            'c' => $count,
            's' => GROUPS_BOL_Group::STATUS_ACTIVE
        ));
    }

    /**
     * Find latest public group list ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findLatestPublicGroupListIds( $first, $count )
    {
        $example = new OW_Example();

        $example->setOrder('`timeStamp` DESC');
        $example->setLimitClause($first, $count);

        if ( !OW::getUser()->isAuthorized('groups') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $example->andFieldEqual('whoCanView', GROUPS_BOL_Service::WCV_ANYONE);
        }

        $example->andFieldEqual("status", GROUPS_BOL_Group::STATUS_ACTIVE);

        return $this->findIdListByExample($example);
    }

    public function findOrderedList( $first, $count )
    {
        $first = (int) $first;
        $count = (int) $count;
        if(OW::getUser()->isAuthenticated()){
            if(OW::getUser()->isAdmin() || OW::getUser()->isAuthorized('groups')){
                $example = new OW_Example();
                $example->setOrder('`timeStamp` DESC');
                $example->setLimitClause($first, $count);
                $example->andFieldEqual("status", GROUPS_BOL_Group::STATUS_ACTIVE);
                return $this->findListByExample($example, self::LIST_CACHE_LIFETIME, array( self::LIST_CACHE_TAG, self::LIST_CACHE_TAG_LATEST ));
            }else{
                $query = "select distinct g.* from ".OW_DB_PREFIX."groups_group g, ".OW_DB_PREFIX."groups_group_user guser where guser.groupId = g.id and g.`status` = :status and (g.whoCanView = :whoCanView or guser.userId = :userId) order by timeStamp desc limit :f,:c";
                return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
                    'f' => $first,
                    'c' => $count,
                    'userId' => OW::getUser()->getId(),
                    'whoCanView' => GROUPS_BOL_Service::WCV_ANYONE,
                    'status' => GROUPS_BOL_Group::STATUS_ACTIVE));
            }
        }else{
            $example = new OW_Example();
            $example->setOrder('`timeStamp` DESC');
            $example->setLimitClause($first, $count);
            $example->andFieldEqual('whoCanView', GROUPS_BOL_Service::WCV_ANYONE);
            $example->andFieldEqual("status", GROUPS_BOL_Group::STATUS_ACTIVE);
            return $this->findListByExample($example, self::LIST_CACHE_LIFETIME, array( self::LIST_CACHE_TAG, self::LIST_CACHE_TAG_LATEST ));
        }
    }

    public function findLimitedList( $count )
    {
        $example = new OW_Example();
        $example->setLimitClause(0, $count);

        return $this->findListByExample($example);
    }

    public function findMostPupularList( $first, $count )
    {
        $groupUserTable = GROUPS_BOL_GroupUserDao::getInstance()->getTableName();

        $where = 'WHERE';
        if(OW::getUser()->isAuthenticated()){
            $userId = OW::getUser()->getId();
            if(!OW::getUser()->isAuthorized('groups')){
                $where .= ' (g.whoCanView="' . GROUPS_BOL_Service::WCV_ANYONE . '" or gu.userId = '.$userId.') AND';
            }
        }else{
            $where .= ' g.whoCanView="' . GROUPS_BOL_Service::WCV_ANYONE . '" AND';
        }

        $query="SELECT `g`.`id`, `g`.`title`, `g`.`description`, `g`.`imageHash`, `g`.`timeStamp`, `g`.`userId`,`g`.`privacy`, `g`.`whoCanView`, `g`.`whoCanInvite`, `g`.`status`,count(gu.userId) as `groupMembers` from `".$this->getTableName(). "` as `g` inner join `".OW_DB_PREFIX."groups_group_user` as `gu` on `g`.`id` = `gu`.`groupId` $where `g`.status=:s  group by `g`.`id` order by count(`gu`.`userId`) desc LIMIT :f, :c";

       /* $query = "SELECT `g`.* FROM `" . $this->getTableName() . "` AS `g`
            LEFT JOIN `" . $groupUserTable . "` AS `gu` ON `g`.`id` = `gu`.`groupId`
            $where g.status=:s
            GROUP BY `g`.`id` ORDER BY COUNT(`gu`.`id`) DESC LIMIT :f, :c";*/

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'f' => $first,
            'c' => $count,
            's' => GROUPS_BOL_Group::STATUS_ACTIVE
        ), self::LIST_CACHE_LIFETIME, array( self::LIST_CACHE_TAG, self::LIST_CACHE_TAG_POPULAR ));
    }

    public function findAllCount()
    {
        $example = new OW_Example();

        if ( !OW::getUser()->isAuthorized('groups') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $example->andFieldEqual('whoCanView', GROUPS_BOL_Service::WCV_ANYONE);
        }
        
        $example->andFieldEqual("status", GROUPS_BOL_Group::STATUS_ACTIVE);

        return $this->countByExample($example);
    }

    public function findByTitle( $title )
    {
        $example = new OW_Example();
        $example->andFieldEqual('title', $title);

        return $this->findObjectByExample($example);
    }

    public function findAllUserGroups( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);

        return $this->findListByExample($example);
    }

    public function findByUserId( $userId, $first = null, $count = null, $inGroupIdList = null, $searchTitle = null, $orderWithLastActivity = true )
    {
        $groupUserDao = GROUPS_BOL_GroupUserDao::getInstance();
        $owner = false;
        $viewerGroupQuery["before"] = "";
        $viewerGroupQuery["query"] = "";
        $viewerGroupQuery["after"] = "";
        $viewerId = null;
        $limit = '';
        if ( $first !== null && $count !== null )
        {
            $limit = " LIMIT $first, $count";
        }

        $params = array(
            'u' => $userId,
            's' => GROUPS_BOL_Group::STATUS_ACTIVE
        );

        $searchQuery = "";
        if($inGroupIdList!=null && sizeof($inGroupIdList)>0){
            $searchQuery.=  " AND `g`.`id` in (". OW::getDbo()->mergeInClause($inGroupIdList) .")";
        }
        if($searchTitle!=null){
            $searchQuery.=' AND UPPER(`g`.`title`) like UPPER (:searchTitle)';
            $params['searchTitle']= '%'. $searchTitle . '%';
        }

        if(OW::getUser()->isAuthenticated()){
            if($userId == OW::getUser()->getId()){
                $owner = true;
            }else if ( !OW::getUser()->isAuthorized('groups')){
                $viewerId = OW::getUser()->getId();
                $viewerGroupQuery["before"] = "select * from ( ";
                $viewerGroupQuery["query"] = " union (select g.* from ".$groupUserDao->getTableName()." gu, " . $this->getTableName() . " g where g.id = gu.groupId and gu.userId = :u and g.whoCanView = :invite and g.id in (select gu2.groupId from ".$groupUserDao->getTableName()." gu2 where gu2.userId = :vid) ".$searchQuery." ) ";
                $viewerGroupQuery["query"] = $viewerGroupQuery["query"] . " ) as g ";
            }
        }

        if($viewerId != null){
            $params['vid'] = OW::getUser()->getId();
            $params['invite'] = GROUPS_BOL_Service::WCV_INVITE;
        }

        $wcvWhere = ' 1 ';

        if ( !OW::getUser()->isAuthorized('groups') && !$owner ) //TODO TEMP Hack - checking if current user is moderator
        {
            $wcvWhere = 'g.whoCanView="' . GROUPS_BOL_Service::WCV_ANYONE . '"';
        }

        $columnOrder = 'timeStamp';
        if($orderWithLastActivity){
            $columnOrder = 'lastActivityTimeStamp';
        }

        $query = $viewerGroupQuery["before"] . "SELECT g.* FROM " . $this->getTableName() . " g
            INNER JOIN " . $groupUserDao->getTableName() . " u ON g.id = u.groupId
            WHERE u.userId=:u AND g.status=:s AND " . $wcvWhere . $searchQuery  . $viewerGroupQuery["query"] . " order by g.".$columnOrder." DESC " . $limit;

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), $params);
    }

    public function findCountByUserId( $userId, $inGroupIdList = null, $searchTitle = null )
    {

        $groupUserDao = GROUPS_BOL_GroupUserDao::getInstance();
        $owner = false;
        $viewerGroupQuery["before"] = "SELECT count(g.id) ";
        $viewerGroupQuery["query"] = "";
        $viewerGroupQuery["after"] = "";
        $viewerId = null;
        $searchQuery = "";

        $params = array(
            'u' => $userId,
            's' => GROUPS_BOL_Group::STATUS_ACTIVE
        );

        if($inGroupIdList!=null && sizeof($inGroupIdList)>0){
            $searchQuery.=  " AND `g`.`id` in (". OW::getDbo()->mergeInClause($inGroupIdList) .")";
        }
        if($searchTitle!=null){
            $searchQuery.=' AND UPPER(`g`.`title`) like UPPER (:searchTitle)';
            $params['searchTitle']= '%'. $searchTitle . '%';
        }

        if(OW::getUser()->isAuthenticated()){
            if($userId == OW::getUser()->getId()){
                $owner = true;
            }else if ( !OW::getUser()->isAuthorized('groups')){
                $viewerId = OW::getUser()->getId();
                $viewerGroupQuery["before"] = "select count(*) from ( SELECT g.* ";
                $viewerGroupQuery["query"] = " union (select g.* from ".$groupUserDao->getTableName()." gu, " . $this->getTableName() . " g where g.id = gu.groupId and gu.userId = :u and g.whoCanView = :invite and g.id in (select gu2.groupId from ".$groupUserDao->getTableName()." gu2 where gu2.userId = :vid) ".$searchQuery.") ";
                $viewerGroupQuery["query"] = $viewerGroupQuery["query"] . " ) as g ";
            }
        }

        if($viewerId != null){
            $params['vid'] = OW::getUser()->getId();
            $params['invite'] = GROUPS_BOL_Service::WCV_INVITE;
        }

        $wcvWhere = ' 1 ';

        if ( !OW::getUser()->isAuthorized('groups') && !$owner ) //TODO TEMP Hack - checking if current user is moderator
        {
            $wcvWhere = 'g.whoCanView="' . GROUPS_BOL_Service::WCV_ANYONE . '"';
        }

        $query = $viewerGroupQuery["before"] . " FROM " . $this->getTableName() . " g
            INNER JOIN " . $groupUserDao->getTableName() . " u ON g.id = u.groupId
            WHERE u.userId=:u AND g.status=:s AND " . $wcvWhere . $searchQuery . $viewerGroupQuery["query"];

        return (int) $this->dbo->queryForColumn($query, $params);
    }

    public function findMyGroups( $userId, $first = null, $count = null )
    {
        return $this->findByUserId($userId, $first, $count);
    }

    /***
     * @param $ids
     * @param null $first
     * @param null $count
     * @return array
     */
    public function findGroupsWithIds( $ids, $first = null, $count = null )
    {
        if($ids == null || sizeof($ids) == 0){
            return array();
        }
        $groupUserDao = GROUPS_BOL_GroupUserDao::getInstance();

        $limit = '';
        if ( $first !== null && $count !== null )
        {
            $limit = "LIMIT $first, $count";
        }

        $query = "SELECT `g2`.* FROM " . $this->getTableName() . " AS `g2` where `g2`.whoCanView = '".GROUPS_BOL_Service::WCV_ANYONE."' AND `g2`.id in (".OW::getDbo()->mergeInClause($ids).")";
        $params = array();
        if(OW::getUser()->isAdmin() ||  OW::getUser()->isAuthorized('groups')){
            $query = "SELECT `g2`.* FROM " . $this->getTableName() . " AS `g2` where `g2`.id in (".OW::getDbo()->mergeInClause($ids).")";
        }
        else if(OW::getUser()->isAuthenticated()){
            $userCondition = " AND `g`.whoCanView = '".GROUPS_BOL_Service::WCV_INVITE . "'  AND `u`.`userId` = :userId ";
            $query = $query ." union SELECT g.* FROM " . $this->getTableName() . " g INNER JOIN " . $groupUserDao->getTableName() . " u ON g.id = u.groupId WHERE g.status=:s AND `g`.id in (".OW::getDbo()->mergeInClause($ids).") ".$userCondition;

            $params = array(
                's' => GROUPS_BOL_Group::STATUS_ACTIVE,
                'userId' => OW::getUser()->getId()
            );
        }
        $query = $query ." ORDER BY `timeStamp` DESC " . $limit;

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), $params);
    }

    public function findMyGroupsCount( $userId )
    {
        return $this->findCountByUserId($userId);
    }

    /*
     * @author Mohammad Agha Abbasloo
     *
     */
    public function findGroupsByFiltering($popular=false,$onlyActive=true,$latest=null,$first=null, $count=null,$userId, $groupIds=array(),$searchTitle)
    {

        if($userId!=null)
        {
            return $this->findByUserId($userId, $first, $count, $groupIds, $searchTitle);
        }

        $groupUserDao = GROUPS_BOL_GroupUserDao::getInstance();

        $whereClause = ' WHERE 1=1 ';
        $OrderClause="";
        $limit="";
        $params = array();
        if ( !OW::getUser()->isAuthorized('groups') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $whereClause .= ' AND (`g`.`whoCanView`="' . GROUPS_BOL_Service::WCV_ANYONE.'"';
            if(OW::getUser()->isAuthenticated()){
                $whereClause .=" OR `gu`.`userId`=:userId ) ";
                $params['userId']=OW::getUser()->getId();
            }else{
                $whereClause .=" ) ";
            }
        }
        if ( $first !== null && $count !== null )
        {
            $limit = " LIMIT $first, $count";
        }
        if($userId!=null)
        {
            $whereClause.=" AND `gu`.`userId`=:u";
            $params['u']=$userId;
        }
        if($groupIds!=null && sizeof($groupIds)>0){
            $whereClause.=  " AND `g`.`id` in (". OW::getDbo()->mergeInClause($groupIds) .")";
        }
        if($searchTitle!=null){
            $whereClause.=' AND UPPER(`g`.`title`) like UPPER (:searchTitle)';
            $params['searchTitle']= '%'. $searchTitle . '%';
        }
        if($latest || $userId){
            $OrderClause=" ORDER BY `g`.`timeStamp` DESC";
        }
        if($onlyActive){
            $whereClause.=" AND `g`.`status`=:s ";
            $params['s']=GROUPS_BOL_Group::STATUS_ACTIVE;
        }

        if($popular) {
            $whereClause=str_replace("`g`.","`g1`.",$whereClause);
            $query="
                SELECT count(`gu`.`userId`),  `g`.`id`, `g`.`title`, `g`.`description`, `g`.`imageHash`, `g`.`timeStamp`, `g`.`userId`,`g`.`privacy`,
                 `g`.`whoCanView`, `g`.`whoCanInvite`, `g`.`status`  
    from (select `g1`.`id`, `g1`.`title`, `g1`.`description`, `g1`.`imageHash`, `g1`.`timeStamp`, `g1`.`userId`,`g1`.`privacy`, `g1`.`whoCanView`, `g1`.`whoCanInvite`, `g1`.`status`  
    from `".$this->getTableName(). "` as g1 inner join `".OW_DB_PREFIX."groups_group_user` as `gu` on `g1`.`id` = `gu`.`groupId`  ". $whereClause." 
    group by `g1`.`id` ) as `g` 
    inner join `".OW_DB_PREFIX."groups_group_user` as `gu` on `g`.`id` = `gu`.`groupId`  
    group by `gu`.`groupId` order by count(`gu`.`userId`) desc" .$limit;

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), $params,
            self::LIST_CACHE_LIFETIME, array( self::LIST_CACHE_TAG, self::LIST_CACHE_TAG_POPULAR));
        }else{
            $query = "SELECT DISTINCT g.* FROM " . $this->getTableName() . " g
            INNER JOIN " . $groupUserDao->getTableName() . " gu ON g.id = gu.groupId"
                .$whereClause. $OrderClause. $limit;
            return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), $params);
        }
    }
    /*
     * @author Mohammad Agha Abbasloo
     *
     */
    public function findGroupsByFilteringCount($popular=false,$onlyActive=true,$latest=null,$userId, $groupIds=array(),$searchTitle)
    {
        if($userId!=null)
        {
            return $this->findCountByUserId($userId, $groupIds, $searchTitle);
        }
        $groupUserDao = GROUPS_BOL_GroupUserDao::getInstance();

        $whereClause = " WHERE 1=1 ";
        $OrderClause="";
        $params = array();
        if ( !OW::getUser()->isAuthorized('groups') ) //TODO TEMP Hack - checking if current user is moderator
        {
            $whereClause .= ' AND (`g`.`whoCanView`="' . GROUPS_BOL_Service::WCV_ANYONE.'"';
            if(OW::getUser()->isAuthenticated()){
                $whereClause .=" OR `u`.`userId`=:userId ) ";
                $params['userId']=OW::getUser()->getId();
            }else{
                $whereClause .=" ) ";
            }
        }
        if($userId!=null)
        {
            $whereClause.=" AND `u`.`userId`=:u";
            $params['u']=$userId;
        }
        if($groupIds!=null && sizeof($groupIds)>0){
            $whereClause.=  " AND `g`.`id` in (". OW::getDbo()->mergeInClause($groupIds) .")";
        }
        if($searchTitle!=null){
            $whereClause.=' AND UPPER(`g`.`title`) like UPPER (:searchTitle)';
            $params['searchTitle']= '%'. $searchTitle . '%';
        }
        if($latest || $userId){
            $OrderClause=" ORDER BY `g`.`timeStamp` DESC";
        }
        if($onlyActive){
            $whereClause.=" AND `g`.`status`=:s ";
            $params['s']=GROUPS_BOL_Group::STATUS_ACTIVE;
        }
        if($popular){
            $whereClause.=" ORDER BY COUNT(`u`.`id`) DESC ";
        }
        $query = "SELECT COUNT(DISTINCT `g`.`id`) FROM " . $this->getTableName() . " g
            INNER JOIN " . $groupUserDao->getTableName() . " u ON g.id = u.groupId"
            . $whereClause .$OrderClause ;
        if($popular) {
            return (int)$this->dbo->queryForColumn($query, $params,
                self::LIST_CACHE_LIFETIME, array( self::LIST_CACHE_TAG, self::LIST_CACHE_TAG_POPULAR));
        }else{
            return (int)$this->dbo->queryForColumn($query, $params);
        }
    }

    public function setPrivacy( $userId, $privacy )
    {
        $query = 'UPDATE ' . $this->getTableName() . ' SET privacy=:p WHERE userId=:u';

        $this->dbo->query($query, array(
            'p' => $privacy,
            'u' => $userId
        ));
    }


    /**
     * @param integer $userId
     * @return array<GROUPS_BOL_Invite>
     */
    public function findUserInvitedGroups( $userId, $first, $count )
    {
        $query = "SELECT `g`.*, max(`i`.`timestamp`) AS inviteTimeStamp  FROM `" . $this->getTableName() . "` AS `g`
            INNER JOIN `" . GROUPS_BOL_InviteDao::getInstance()->getTableName() . "` AS `i` ON ( `g`.`id` = `i`.`groupId` )
            WHERE `i`.`userId` = :u AND g.`status`=:status GROUP BY `g`.`id`
            ORDER BY inviteTimeStamp DESC LIMIT :f, :c";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            'u' => (int) $userId,
            'f' => (int) $first,
            'c' => (int) $count,
            "status" => GROUPS_BOL_Group::STATUS_ACTIVE
        ));
    }

    /**
     * @param integer $userId
     * @return integer
     */
    public function findUserInvitedGroupsCount( $userId, $newOnly = false )
    {
        $addWhere = $newOnly ? 'i.viewed=0' : '1';

        $query = "SELECT COUNT(DISTINCT g.id) AS `count` FROM `" . $this->getTableName() . "` AS `g`
            INNER JOIN `" . GROUPS_BOL_InviteDao::getInstance()->getTableName() . "` AS `i` ON ( `g`.`id` = `i`.`groupId` )
            WHERE `i`.`userId` = :u AND g.status=:status AND " . $addWhere;

        return $this->dbo->queryForColumn($query, array(
            'u' => (int) $userId,
            'status' => GROUPS_BOL_Group::STATUS_ACTIVE
        ));
    }

    public function findAllLimited( $first = null, $count = null )
    {
        $example = new OW_Example();

        $example->setOrder(" id DESC ");

        if ( $first != null && $count !=null )
        {
            $example->setLimitClause($first, $count);
        }

        return $this->findListByExample($example);
    }
}