<?php

/**
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iismobilesupport.classes
 * @since 1.0
 */
class IISMOBILESUPPORT_CLASS_NewsfeedDriver extends NEWSFEED_CLASS_Driver
{
    protected function findActionList( $params )
    {
        if(!isset($params['includeActionIdList']) || !is_array($params['includeActionIdList']) || sizeof($params['includeActionIdList']) == 0){
            return array();
        }

        $params['length'] = $params['displayCount'];
        $params['formats'] = null;
        $params['feedType'] = 'my';
        if(OW::getUser()->isAuthenticated()){
            $params['feedId'] = OW::getUser()->getId();
        }else{
            $params['feedId'] = null;
        }
        $params['startTime'] = time();
        $params['displayType'] = 'action';
        $params['viewMore'] = 1;
        $params['checkMore'] = true;
        $this->params = $params;
        $hashtag = 'ha';
        if($params['feedId']!=null){
            $event = new OW_Event('newsfeed.find_action_list_by_user_hashtag',array('params'=>$params,'driver'=>$this,'hashtag'=>$hashtag));
            OW::getEventManager()->trigger($event);
            $actionList = $event->getData();
            if(!isset($actionList)) {
                return $this->findByUser($params['feedId'], $params['includeActionIdList'], $hashtag, array($params['offset'], $params['displayCount'], $params['checkMore']), $params['startTime'], $params['formats'], $this);
            }
            return $actionList;
        }else{
            $event = new OW_Event('newsfeed.find_action_list_by_public_hashtag',array('params'=>$params,'driver'=>$this,'hashtag'=>$hashtag));
            OW::getEventManager()->trigger($event);
            $actionList = $event->getData();
            if(!isset($actionList)) {
                return $this->findPublic($params['includeActionIdList'], $hashtag, array($params['offset'], $params['displayCount'], $params['checkMore']), $params['startTime'], $params['formats'], $this);
            }
            return $actionList;
        }
    }

    protected function findActionCount( $params )
    {
        if(!isset($params['includeActionIdList']) || !is_array($params['includeActionIdList'])){
            return 0;
        }
        return sizeof($params['includeActionIdList']);
    }

    protected function findActivityList( $params, $actionIds )
    {
        return NEWSFEED_BOL_ActivityDao::getInstance()->findByActionIds($actionIds);
    }

    private function findOrderedListByIdList( $idList )
    {
        if ( empty($idList) )
        {
            return array();
        }

        $unsortedDtoList = NEWSFEED_BOL_ActionDao::getInstance()->findByIdList($idList);
        $unsortedList = array();
        foreach ( $unsortedDtoList as $dto )
        {
            $unsortedList[$dto->id] = $dto;
        }

        $sortedList = array();
        foreach ( $idList as $id )
        {
            if ( !empty($unsortedList[$id]) )
            {
                $sortedList[] = $unsortedList[$id];
            }
        }

        return $sortedList;
    }


    public function findByUser( $userId, $includeIdList = array(), $hashtag, $limit = null, $startTime = null, $formats = null, $driver = null )
    {
        $cacheKey = md5('hashtag_feed' . '_' . $hashtag . '_' .  $userId . ( empty($limit) ? '' : implode('', $limit) ) );

        $cachedIdList = OW::getCacheManager()->load($cacheKey);

        if ( $cachedIdList !== null )
        {
            $idList = json_decode($cachedIdList, true);

            return $this->findOrderedListByIdList($idList);
        }

        $followDao = NEWSFEED_BOL_FollowDao::getInstance();
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();
        $actionSetDao = NEWSFEED_BOL_ActionSetDao::getInstance();

        $limitStr = '';
        if ( !empty($limit) )
        {
            $limitStr = "LIMIT " . intval($limit[0]) . ", " . intval($limit[1]);
        }

        $supportWithClause = false;
        if(defined('SUPPORT_WITH_CLAUSE_IN_MYSQL_VERSION') && SUPPORT_WITH_CLAUSE_IN_MYSQL_VERSION){
            $supportWithClause = true;
        }
        $actionIdListQueryAndParam = array('query' => '', 'params' => array(), 'tableName' => $actionSetDao->getTableName());
        if($supportWithClause){
            $actionIdListQueryAndParam = $actionSetDao->getActionUserActionIdList($userId, $startTime);
        }else{
            $actionSetDao->deleteActionSetUserId($userId);
            $actionSetDao->generateActionSet($userId, $startTime);
        }

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("cactivity", "userId", array(
            "method" => "NEWSFEED_BOL_ActionDao::findByUser"
        ));

        if ( $formats !== null )
        {
            $queryParts["where"] .= " AND action.format IN ( '" . implode("','", $formats) . "' )";
        }
        $followerPrivacyWhereCondition = '';
        $viewerActivityPrivacyWhereCondition = '';
        $privacyConditionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_USER_FEED_LIST_QUERY_EXECUTE));
        if (isset($privacyConditionEvent->getData()['whereConditionPrivacy'])) {
            $followerPrivacyWhereCondition = $privacyConditionEvent->getData()['whereConditionPrivacy']['followerPrivacyWhereCondition'];
            $viewerActivityPrivacyWhereCondition = $privacyConditionEvent->getData()['whereConditionPrivacy']['viewerActivityPrivacyWhereCondition'];
        }

        $privacyWhereCondition = ' AND cactivity.privacy=:peb ';
        if(OW::getUser()->isAuthenticated() && OW::getUser()->isAdmin()){
            $privacyWhereCondition = '';
        }


        $siteFeedQuery = 'SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN ' . $activityDao->getTableName() . ' cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            WHERE ' . $queryParts["where"] . ' AND action.`id` in (' . OW::getDbo()->mergeInClause($includeIdList) . ') AND 
                (cactivity.status=:s AND cactivity.activityType=:ac '.$privacyWhereCondition.' AND cactivity.visibility & :v)
                AND
                (activity.status=:s '.$privacyWhereCondition.' AND activity.visibility & :v AND activity.timeStamp < :st)';

        $dashboardQuery = $actionIdListQueryAndParam['query'] . ' SELECT  b.`id` FROM
            ( 
            '.$siteFeedQuery.' 
            
            UNION
            
            SELECT  action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
            LEFT JOIN ' . $followDao->getTableName() . ' follow ON action_feed.feedId = follow.feedId AND action_feed.feedType = follow.feedType
            WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND action.`id` in (' . OW::getDbo()->mergeInClause($includeIdList) . ') AND (
                    ( follow.userId=:u AND activity.visibility & :vf ) )'.$followerPrivacyWhereCondition.'

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                ' . $queryParts["join"] . '
                WHERE ' . $queryParts["where"] . ' AND action.`id` in (' . OW::getDbo()->mergeInClause($includeIdList) . ') AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND (
                        ( activity.userId=:u AND activity.visibility & :va ) ) '.$viewerActivityPrivacyWhereCondition.'

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                ' . $queryParts["join"] . '
                INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
                WHERE ' . $queryParts["where"] . ' AND action.`id` in (' . OW::getDbo()->mergeInClause($includeIdList) . ') AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st
                AND ( ( action_feed.feedId=:u AND action_feed.feedType="user" AND activity.visibility & :vfeed ) )

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                ' . $queryParts["join"] . '
                INNER JOIN ' . $activityDao->getTableName() . ' subscribe ON activity.actionId=subscribe.actionId and subscribe.activityType=:as AND subscribe.userId=:u
                WHERE ' . $queryParts["where"] . ' AND action.`id` in (' . OW::getDbo()->mergeInClause($includeIdList) . ') AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st

                ) b

            GROUP BY b.`id` ORDER BY MAX(b.timeStamp) DESC ' . $limitStr;

        $additionalParams = array();
        if(isset($actionIdListQueryAndParam['params'])){
            $additionalParams = $actionIdListQueryAndParam['params'];
        }
        $params = array_merge($additionalParams, array(
            'v' => NEWSFEED_BOL_Service::VISIBILITY_SITE,
            'u' => $userId,
            'va' => NEWSFEED_BOL_Service::VISIBILITY_AUTHOR,
            'vf' => NEWSFEED_BOL_Service::VISIBILITY_FOLLOW,
            'vfeed' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'st' => empty($startTime) ? time() : $startTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
            'as' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_SUBSCRIBE
        ));
        $idList = array_unique(OW::getDbo()->queryForColumnList($dashboardQuery, $params));

        if ( $limit[0] == 0 )
        {
            $cacheLifeTime = NEWSFEED_BOL_ActionDao::CACHE_LIFETIME/240;
            $cacheTags = array(
                NEWSFEED_BOL_ActionDao::CACHE_TAG_ALL . '_hashtag'. '_' . $hashtag,
                NEWSFEED_BOL_ActionDao::CACHE_TAG_USER . '_hashtag'. '_' . $hashtag,
                NEWSFEED_BOL_ActionDao::CACHE_TAG_USER_PREFIX . $userId . '_hashtag'. '_' . $hashtag
            );

            OW::getCacheManager()->save(json_encode($idList), $cacheKey, $cacheTags, $cacheLifeTime);
        }
        $eventActionList = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_ACTIONS_LIST_RETURN, array('limit' => $limit, 'driver'=> $driver, 'idList' => $idList)));
        if(isset($eventActionList->getData()['idList']) && isset($eventActionList->getData()['count'])){
            $idList = $eventActionList->getData()['idList'];
            $driver->setCount($eventActionList->getData()['count']);
        }
        return $this->findOrderedListByIdList($idList);
    }

    public function findPublic( $includeIdList = array(), $hashtag, $limit = null, $startTime = null, $formats = null, $driver = null )
    {
        $limitStr = '';
        if ( !empty($limit) )
        {
            $limitStr = "LIMIT " . intval($limit[0]) . ", " . intval($limit[1]);
        }

        $cacheStartTime = OW::getCacheManager()->load('newsfeed.site_cache_time' . '_' . $hashtag);
        if ( $cacheStartTime === null )
        {
            OW::getCacheManager()->save($startTime, 'newsfeed.site_cache_time' . '_' . $hashtag, array(
                NEWSFEED_BOL_ActionDao::CACHE_TAG_ALL . '_' . $hashtag,
                NEWSFEED_BOL_ActionDao::CACHE_TAG_INDEX . '_' . $hashtag,
            ), NEWSFEED_BOL_ActionDao::CACHE_LIFETIME/240);
        }
        else
        {
            $startTime = $cacheStartTime;
        }

        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("cactivity", "userId", array(
            "method" => "NEWSFEED_BOL_ActionDao::findSiteFeedCount"
        ));

        if ( $formats !== null )
        {
            $queryParts["where"] .= " AND action.format IN ( '" . implode("','", $formats) . "' )";
        }

        $query = 'SELECT action.id FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN ' . $activityDao->getTableName() . ' cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            WHERE ' . $queryParts["where"] . ' AND
                (cactivity.status=:s AND cactivity.activityType=:ac AND cactivity.privacy=:peb AND cactivity.visibility & :v)
                AND
                (activity.status=:s AND action.`id` in (' . OW::getDbo()->mergeInClause($includeIdList) . ') AND activity.privacy=:peb AND activity.visibility & :v AND activity.timeStamp < :st)
              GROUP BY action.id
              ORDER BY MAX(activity.timeStamp) DESC ' . $limitStr;

        $idList = OW::getDbo()->queryForColumnList($query, array(
            'v' => NEWSFEED_BOL_Service::VISIBILITY_SITE,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'st' => empty($startTime) ? time() : $startTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
        ), NEWSFEED_BOL_ActionDao::CACHE_LIFETIME/240, array(
            NEWSFEED_BOL_ActionDao::CACHE_TAG_ALL . '_' . $hashtag,
            NEWSFEED_BOL_ActionDao::CACHE_TAG_INDEX . '_' . $hashtag
        ));
        $eventActionList = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_ACTIONS_LIST_RETURN, array('limit' => $limit, 'driver'=> $driver, 'idList' => $idList)));
        if(isset($eventActionList->getData()['idList']) && isset($eventActionList->getData()['count'])){
            $idList = $eventActionList->getData()['idList'];
            $driver->setCount($eventActionList->getData()['count']);
        }
        return $this->findOrderedListByIdList($idList);
    }

}