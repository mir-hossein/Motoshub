<?php

class IISNEWSFEEDPIN_BOL_Service
{
    private static $classInstance;
    private static $ID_PREFIX = 'newsfeed_pin_';

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }


    private function __construct()
    {
    }

    public function canEditPin($feedId,$feedType){
        if ($feedType == "groups") {
            $group = $this->findGroupById($feedId);
            return $this->canEditGroup($group);
        }
        return true;
    }

    public function addPinButtonToNewsfeed(OW_Event $event)
    {
        $form = $event->getParams()['form'];
        $feedId = $form->getElement('feedId')->getValue();
        $feedType = $form->getElement('feedType')->getValue();
        if ($this->canEditPin($feedId,$feedType)) {
            OW::getDocument()->addOnloadScript('$(\'.ow_status_update_btn_block .ow_attachment_icons\').append(\'<span class="ow_cursor_pointer"><span id="IISNEWSFEEDPIN_Pin" class="iisnewsfeedpin_pin" href="javascript://"></span></span>\');');
        }

        OW::getDocument()->addOnloadScript('new IISNEWSFEEDPIN_PinButton();');
    }

    public function addPinButtonToNewsfeedMobile(OW_Event $event)
    {
        $form = $event->getParams()['form'];
        $feedId = $form->getElement('feedId')->getValue();
        $feedType = $form->getElement('feedType')->getValue();
        if ($this->canEditPin($feedId,$feedType)) {
            OW::getDocument()->addOnloadScript('$(\'.owm_newsfeed_status_update_btns \').append(\'<span class="ow_cursor_pointer owm_float_right"><span id="IISNEWSFEEDPIN_Pin" class="iisnewsfeedpin_pin" href="javascript://"></span></span>\');');
        }

        OW::getDocument()->addOnloadScript('new IISNEWSFEEDPIN_PinButton();');
    }

    public function addPinInputFieldsToNewsfeed(OW_Event $event)
    {
        $params = $event->getParams();
        $form = $event->getParams()['form'];
        $groupId = $form->getElement('feedId')->getValue();
        $group = $this->findGroupById($groupId);
        $canEditPin = $this->canEditGroup($group);
        if (isset($params['form']) && $canEditPin) {
            $pinInput = new HiddenField('pin');
            $pinInput->setValue(false);
            $pinInput->addAttribute("id", "pin");
            $params['form']->addElement($pinInput);
        }
    }

    public function loadNewItem(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['pin'])) {
            $pinned = (($params['pin'] === 'true' || $params['pin'] === '1') || $params['pin'] === true) ? true : false;
            if ($pinned) {
                $pin = new IISNEWSFEEDPIN_BOL_Pin();
                $pin->setCreateDate(time());
                $pin->setEntityId($params['entityId']);
                $pin->setEntityType($params['entityType']);
                IISNEWSFEEDPIN_BOL_PinDao::getInstance()->save($pin);
            }
        }
    }

    public function afterStatusComponentAddition(OW_Event $event)
    {
        $params = $event->getParams();
        $eventData= $event->getData();
        $jsDir = OW::getPluginManager()->getPlugin("iisnewsfeedpin")->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir . 'newsfeed_pin.js');
        $cssDir = OW::getPluginManager()->getPlugin("iisnewsfeedpin")->getStaticCssUrl();
        OW::getDocument()->addStyleSheet($cssDir . 'newsfeed_pin.css');
        $uri = OW::getRequest()->getRequestUri();
        if (preg_match('/newsfeed\/\d*\??.*$/', $uri) == 0)
            if(isset($params['feedType']) && isset($params['feedId'])) {
                $eventData['extra_component']=$this->getPinMenu();
                $event->setData($eventData);
            }
    }

    public function afterStatusComponentAdditionMobile(OW_Event $event)
    {
        $params = $event->getParams();
        $eventData= $event->getData();
        $jsDir = OW::getPluginManager()->getPlugin("iisnewsfeedpin")->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir . 'newsfeed_pin.js');
        $cssDir = OW::getPluginManager()->getPlugin("iisnewsfeedpin")->getStaticCssUrl();
        OW::getDocument()->addStyleSheet($cssDir . 'newsfeed_pin_mobile.css');
        $uri = OW::getRequest()->getRequestUri();
        if (preg_match('/newsfeed\/\d*\??.*$/', $uri) == 0)
            if(isset($params['feedType']) && isset($params['feedId'])) {
                $eventData['extra_component']=$this->getPinMenuMobile();
                $event->setData($eventData);
            }
    }

    public function feedRemove(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['action_id'])) {
            $action = NEWSFEED_BOL_Service::getInstance()->findActionById($params['action_id']);
            if (isset($action)) {
                $entityId = $action->entityId;
                $entityType = $action->entityType;
                IISNEWSFEEDPIN_BOL_PinDao::getInstance()->deleteByEntityIdAndEntityType($entityId, $entityType);
            }
        }
    }

    public function canEditGroup($group)
    {
        if(!OW::getPluginManager()->isPluginActive('groups') || !isset($group))
            return false;
        $isAuthenticated = OW::getUser()->isAuthenticated();
        $canEditGroup = GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($group);
        $isModerator = OW::getUser()->isAuthorized('groups');

        return $isAuthenticated && ($canEditGroup || $isModerator);
    }

    private function findGroupById($groupId){
        if(!OW::getPluginManager()->isPluginActive('groups'))
            return null;
        return GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
    }

    public function genericItemRender(OW_Event $event)
    {
        $params = $event->getParams();
//        if (!isset($params['feedType']) || $params['feedType'] != 'groups')
//            return;
        $data = $event->getData();

        if($params['feedType'] == 'groups') {
            $groupId = $params['feedId'];
            $group = $this->findGroupById($groupId);
            $canEditPin = $this->canEditGroup($group);
        }else{
            $isFeedOwner = $params['feedType'] == "user" && $params["feedId"] == OW::getUser()->getId();
            $isStatus = in_array($params['action']['entityType'], array('user-comment', 'user-status'));

            $canRemove = OW::getUser()->isAuthenticated()
                && (
                    $params['action']['userId'] == OW::getUser()->getId()
                    || OW::getUser()->isAuthorized('newsfeed')
                    || ( $isFeedOwner && $isStatus && $params['action']['onOriginalFeed'] )
                );

            $canEditPin = $canRemove && in_array($params['feedType'], array('site', 'my', 'user'));
        }

        if (isset($canEditPin)) {
            $entityId = $params['action']['entityId'];
            $entityType = $params['action']['entityType'];

            $pin = IISNEWSFEEDPIN_BOL_PinDao::getInstance()->findByEntityIdAndEntityType($entityId, $entityType);
            if (isset($pin)) {
                $data['view']['class'] = 'iisnewsfeedpin_pined_class';
            }
            if ($canEditPin) {
                if (!isset($pin)) {
                    array_unshift($data['contextMenu'], array(
                        'label' => OW::getLanguage()->text('iisnewsfeedpin', 'pin_button_label'),
                        "class" => "iisnewsfeedpin_add_pin iisnewsfeedpin_pin_id_[" . $entityType . ']_[' . $entityId . ']'
                    ));
                } else {
                    array_unshift($data['contextMenu'], array(
                        'label' => OW::getLanguage()->text('iisnewsfeedpin', 'un_pin_button_label'),
                        'attributes' => array(
                            'data-confirm-msg' => OW::getLanguage()->text('base', 'are_you_sure')
                        ),
                        "class" => "iisnewsfeedpin_remove_pin iisnewsfeedpin_pin_id_[" . $entityType . ']_[' . $entityId . ']'
                    ));
                }
            }
            $isPinnedPage = false;
            if (isset($_GET['filter']) && $_GET['filter'] == 'pins') {
                $isPinnedPage = true;
            }
            if (isset($_GET['p'])) {
                $json = json_decode($_GET['p'], true);
                parse_str(parse_url($json['url'], PHP_URL_QUERY), $output);
                if (isset($output['filter']) && $output['filter'] == 'pins') {
                    $isPinnedPage = true;
                }
            }
            $dataParams = array(
                'removeURL' => OW::getRouter()->urlForRoute('iisnewsfeedpin.pin_delete'),
                'addURL' => OW::getRouter()->urlForRoute('iisnewsfeedpin.add_pin_by_entity'),
                'entityId' => $entityId,
                'entityType' => $entityType,
                'isPinnedPage' => $isPinnedPage
            );
            $js = UTIL_JsGenerator::composeJsString('
                    window.ow_iisnewsfeedpin_feed_list[{$feedAutoId}] = new IISNEWSFEEDPIN_PinItem({$isPinned},{$feedAutoId});
                    window.ow_iisnewsfeedpin_feed_list[{$feedAutoId}].construct({$data});
                ', array(
                    'feedAutoId' => $event->getParams()['autoId'],
                    'isPinned' => isset($pin),
                    'data' => $dataParams
                )
            );

            OW::getDocument()->addOnloadScript($js, 50);
        }
        $event->setData($data);
    }

    public function findPinedFeedByFeed($feedType, $feedId, $limit = null, $startTime = null, $formats = null, $driver = null, $endTime = null)
    {
        $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
        $activityDao = NEWSFEED_BOL_ActivityDao::getInstance();

        $limitStr = '';
        if (!empty($limit)) {
            $limitStr = "LIMIT " . intval($limit[0]) . ", " . intval($limit[1]);
        }

        $cacheStartTime = OW::getCacheManager()->load('newsfeed.feed_cache_time_' . $feedType . $feedId);
        if ($cacheStartTime === null) {
            OW::getCacheManager()->save($startTime, 'newsfeed.feed_cache_time_' . $feedType . $feedId, array(
                NEWSFEED_BOL_ActionDao::CACHE_TAG_ALL,
                NEWSFEED_BOL_ActionDao::CACHE_TAG_FEED,
                NEWSFEED_BOL_ActionDao::CACHE_TAG_FEED_PREFIX . $feedType . $feedId
            ), NEWSFEED_BOL_ActionDao::CACHE_LIFETIME);
        } else {
            $startTime = $cacheStartTime;
        }

        $queryParts = BOL_UserDao::getInstance()->getUserQueryFilter("cactivity", "userId", array(
            "method" => "NEWSFEED_BOL_ActionDao::findByFeed"
        ));

        if ($formats !== null) {
            $queryParts["where"] .= " AND action.format IN ( '" . implode("','", $formats) . "' )";
        }


        $privacyCondition = '\'' . NEWSFEED_BOL_Service::PRIVACY_EVERYBODY . '\'';
        if (OW::getUser()->isAuthenticated() && OW::getUser()->isAdmin()) {
            $privacyCondition = '\'' . NEWSFEED_BOL_Service::PRIVACY_EVERYBODY . '\', ' .
                '\'' . NEWSFEED_BOL_Service::PRIVACY_FRIENDS . '\', ' .
                '\'' . NEWSFEED_BOL_Service::PRIVACY_ONLY_ME . '\'';
        } else {
            $eventPrivacyCondition = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_QUERY_FEED_CREATE, array('feedId' => $feedId)));
            if (isset($eventPrivacyCondition->getData()['privacy'])) {
                $privacyCondition = $eventPrivacyCondition->getData()['privacy'];
            }
        }

        $query = 'SELECT action.id FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN ' . $activityDao->getTableName() . ' cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
            INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType

            WHERE ' . $queryParts["where"] . '
                AND activity.status=:s
                AND activity.timeStamp<:st
                AND activity.timeStamp>:st2
                AND (activity.privacy in (' . $privacyCondition . ') or activity.userId =:vi )
                AND action_feed.feedType=:ft
                AND action_feed.feedId=:fi
                AND activity.visibility & :v

                AND cactivity.status=:s
                AND cactivity.activityType=:ac
                AND (cactivity.privacy in (' . $privacyCondition . ') or cactivity.userId =:vi )
                AND cactivity.visibility & :v

            GROUP BY action.id ORDER BY MAX(activity.timeStamp) DESC ' . $limitStr;


        $idList = OW::getDbo()->queryForColumnList($query, array(
            'ft' => $feedType,
            'fi' => $feedId,
            'vi' => OW::getUser()->getId(),
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'v' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            'st' => empty($startTime) ? time() : $startTime,
            'st2' => empty($endTime) ? 0 : $endTime,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE
        ), NEWSFEED_BOL_ActionDao::CACHE_LIFETIME, array(
            NEWSFEED_BOL_ActionDao::CACHE_TAG_ALL,
            NEWSFEED_BOL_ActionDao::CACHE_TAG_FEED,
            NEWSFEED_BOL_ActionDao::CACHE_TAG_FEED_PREFIX . $feedType . $feedId
        ));
        $eventActionList = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_ACTIONS_LIST_RETURN, array('limit' => $limit, 'driver' => $driver, 'idList' => $idList)));
        if (isset($eventActionList->getData()['idList']) && isset($eventActionList->getData()['count'])) {
            $idList = $eventActionList->getData()['idList'];
            $driver->setCount($eventActionList->getData()['count']);
        }
        return $this->findOrderedListByIdList($idList);
    }

    public function findPinedSiteFeed( $limit = null, $startTime = null, $formats = null, $driver = null, $endTime = null)
    {
        $limitStr = '';
        if ( !empty($limit) )
        {
            $limitStr = "LIMIT " . intval($limit[0]) . ", " . intval($limit[1]);
        }

        $cacheStartTime = OW::getCacheManager()->load('newsfeed.site_cache_time');
        if ( $cacheStartTime === null )
        {
            OW::getCacheManager()->save($startTime, 'newsfeed.site_cache_time', array(
                NEWSFEED_BOL_ActionDao::CACHE_TAG_ALL,
                NEWSFEED_BOL_ActionDao::CACHE_TAG_INDEX,
            ), NEWSFEED_BOL_ActionDao::CACHE_LIFETIME);
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
            INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType
            ' . $queryParts["join"] . '
            WHERE ' . $queryParts["where"] . ' AND
                (cactivity.status=:s AND cactivity.activityType=:ac AND cactivity.privacy=:peb AND cactivity.visibility & :v)
                AND
                (activity.status=:s AND activity.privacy=:peb AND activity.visibility & :v AND activity.timeStamp<:st AND activity.timeStamp>:st2 AND activity.activityType NOT LIKE :as)
              GROUP BY action.id
              ORDER BY MAX(activity.timeStamp) DESC ' . $limitStr;

        $idList = OW::getDbo()->queryForColumnList($query, array(
            'v' => NEWSFEED_BOL_Service::VISIBILITY_SITE,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'st' => empty($startTime) ? time() : $startTime,
            'st2' => empty($endTime) ? 0 : $endTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
            'as' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_SUBSCRIBE
        ), NEWSFEED_BOL_ActionDao::CACHE_LIFETIME, array(
            NEWSFEED_BOL_ActionDao::CACHE_TAG_ALL,
            NEWSFEED_BOL_ActionDao::CACHE_TAG_INDEX
        ));
        $eventActionList = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_ACTIONS_LIST_RETURN, array('limit' => $limit, 'driver'=> $driver, 'idList' => $idList)));
        if(isset($eventActionList->getData()['idList']) && isset($eventActionList->getData()['count'])){
            $idList = $eventActionList->getData()['idList'];
            $driver->setCount($eventActionList->getData()['count']);
        }
        return $this->findOrderedListByIdList($idList);
    }

    public function findPinedFeedByUser( $userId, $limit = null, $startTime = null, $formats = null, $driver = null, $endTime = null)
    {
        $cacheKey = md5('user_feed' . $userId . ( empty($limit) ? '' : implode('', $limit) ) );

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
        if(isset($privacyConditionEvent->getData()['whereConditionPrivacy'])){
            $followerPrivacyWhereCondition = $privacyConditionEvent->getData()['whereConditionPrivacy']['followerPrivacyWhereCondition'];
            $viewerActivityPrivacyWhereCondition = $privacyConditionEvent->getData()['whereConditionPrivacy']['viewerActivityPrivacyWhereCondition'];
        }

        $query = $actionIdListQueryAndParam['query'] . ' SELECT  b.`id` FROM
            ( SELECT  action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
            INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
            INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
            ' . $queryParts["join"] . '
            INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
            LEFT JOIN ' . $followDao->getTableName() . ' follow ON action_feed.feedId = follow.feedId AND action_feed.feedType = follow.feedType
            INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType
            
            WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND activity.timeStamp>:st2 AND activity.activityType NOT LIKE :as 
                AND ( ( follow.userId=:u AND activity.visibility & :vf ) )'.$followerPrivacyWhereCondition.'

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType
                ' . $queryParts["join"] . '
                WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND activity.timeStamp>:st2 AND activity.activityType NOT LIKE :as 
                    AND ( ( activity.userId=:u AND activity.visibility & :va ) ) '.$viewerActivityPrivacyWhereCondition.'

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                ' . $queryParts["join"] . '
                INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
                INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType
                WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND activity.timeStamp>:st2 AND activity.activityType NOT LIKE :as
                    AND ( ( action_feed.feedId=:u AND action_feed.feedType="user" AND activity.visibility & :vfeed ) )

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                ' . $queryParts["join"] . '
                INNER JOIN ' . $activityDao->getTableName() . ' subscribe ON activity.actionId=subscribe.actionId and subscribe.activityType=:as AND subscribe.userId=:u
                INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType
                WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND activity.timeStamp>:st2 AND activity.activityType NOT LIKE :as

                ) b

            GROUP BY b.`id` ORDER BY MAX(b.timeStamp) DESC ' . $limitStr;

        $additionalParams = array();
        if(isset($actionIdListQueryAndParam['params'])){
            $additionalParams = $actionIdListQueryAndParam['params'];
        }
        $params = array_merge($additionalParams, array(
            'u' => $userId,
            'va' => NEWSFEED_BOL_Service::VISIBILITY_AUTHOR,
            'vf' => NEWSFEED_BOL_Service::VISIBILITY_FOLLOW,
            'vfeed' => NEWSFEED_BOL_Service::VISIBILITY_FEED,
            's' => NEWSFEED_BOL_Service::ACTION_STATUS_ACTIVE,
            'st' => empty($startTime) ? time() : $startTime,
            'st2' => empty($endTime) ? 0 : $endTime,
            'peb' => NEWSFEED_BOL_Service::PRIVACY_EVERYBODY,
            'ac' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE,
            'as' => NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_SUBSCRIBE
        ));
        $idList = array_unique(OW::getDbo()->queryForColumnList($query, $params));

        if ( $limit[0] == 0 )
        {
            $cacheLifeTime = NEWSFEED_BOL_ActionDao::CACHE_LIFETIME;
            $cacheTags = array(
                NEWSFEED_BOL_ActionDao::CACHE_TAG_ALL,
                NEWSFEED_BOL_ActionDao::CACHE_TAG_USER,
                NEWSFEED_BOL_ActionDao::CACHE_TAG_USER_PREFIX . $userId
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

    public function findPinnedByUserHashtag( $userId, $includeIdList = array(), $hashtag, $limit = null, $startTime = null, $formats = null, $driver = null )
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
            INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType
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
            INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType
            WHERE ' . $queryParts["where"] . ' AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND action.`id` in (' . OW::getDbo()->mergeInClause($includeIdList) . ') AND (
                    ( follow.userId=:u AND activity.visibility & :vf ) )'.$followerPrivacyWhereCondition.'

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType
                ' . $queryParts["join"] . '
                WHERE ' . $queryParts["where"] . ' AND action.`id` in (' . OW::getDbo()->mergeInClause($includeIdList) . ') AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st AND (
                        ( activity.userId=:u AND activity.visibility & :va ) ) '.$viewerActivityPrivacyWhereCondition.'

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                ' . $queryParts["join"] . '
                INNER JOIN ' . $actionFeedDao->getTableName() . ' action_feed ON activity.id=action_feed.activityId
                INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType
                WHERE ' . $queryParts["where"] . ' AND action.`id` in (' . OW::getDbo()->mergeInClause($includeIdList) . ') AND cactivity.userId = :u AND activity.status=:s AND activity.timeStamp<:st
                AND ( ( action_feed.feedId=:u AND action_feed.feedType="user" AND activity.visibility & :vfeed ) )

            UNION

            SELECT action.`id`, action.`entityId`, action.`entityType`, action.`pluginKey`, action.`data`, activity.timeStamp FROM ' . NEWSFEED_BOL_ActionDao::getInstance()->getTableName() . ' action
                INNER JOIN ' . $activityDao->getTableName() . ' activity ON action.id = activity.actionId
                INNER JOIN `' . $actionSetDao->getTableName() . '` cactivity ON action.id = cactivity.actionId
                ' . $queryParts["join"] . '
                INNER JOIN ' . $activityDao->getTableName() . ' subscribe ON activity.actionId=subscribe.actionId and subscribe.activityType=:as AND subscribe.userId=:u
                INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType
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

    public function findPinnedPublicHashtag( $includeIdList = array(), $hashtag, $limit = null, $startTime = null, $formats = null, $driver = null )
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
            INNER JOIN ' . IISNEWSFEEDPIN_BOL_PinDao::getInstance()->getTableName() . ' pin ON pin.entityId=action.entityId AND pin.entityType=action.entityType
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

    private function findOrderedListByIdList($idList)
    {
        if (empty($idList)) {
            return array();
        }

        $unsortedDtoList = NEWSFEED_BOL_ActionDao::getInstance()->findByIdList($idList);
        $unsortedList = array();
        foreach ($unsortedDtoList as $dto) {
            $unsortedList[$dto->id] = $dto;
        }

        $sortedList = array();
        foreach ($idList as $id) {
            if (!empty($unsortedList[$id])) {
                $sortedList[] = $unsortedList[$id];
            }
        }

        return $sortedList;
    }

    public function findActionListByFeed(OW_Event $event)
    {
        $isPinnedPage = false;
        if (isset($_GET['p'])) {
            $json = json_decode($_GET['p'],true);
            parse_str(parse_url($json['url'], PHP_URL_QUERY), $output);
            if (isset($output['filter']) && $output['filter'] == 'pins') {
                $isPinnedPage = true;
            }
        }
        if (isset($_GET['filter']) && $_GET['filter'] == 'pins') {
            $isPinnedPage = true;
        }
        if(!$isPinnedPage)
            return;
        $params = $event->getParams()['params'];
        $endTime = $event->getParams()['end_time'];
        $driver = $event->getParams()['driver'];
        $actionList = $this->findPinedFeedByFeed($params['feedType'], $params['feedId'], array($params['offset'], $params['displayCount'], $params['checkMore']), $params['startTime'], $params['formats'], $driver, $endTime);
        $event->setData($actionList);
    }

    public function findActionListByUser(OW_Event $event)
    {
        $isPinnedPage = false;
        if (isset($_GET['p'])) {
            $json = json_decode($_GET['p'],true);
            parse_str(parse_url($json['url'], PHP_URL_QUERY), $output);
            if (isset($output['filter']) && $output['filter'] == 'pins') {
                $isPinnedPage = true;
            }
        }
        if (isset($_GET['filter']) && $_GET['filter'] == 'pins') {
            $isPinnedPage = true;
        }
        if(!$isPinnedPage)
            return;
        $params = $event->getParams()['params'];
        $endTime = $event->getParams()['end_time'];
        $driver = $event->getParams()['driver'];
        $actionList = $this->findPinedFeedByUser($params['feedId'], array($params['offset'], $params['displayCount'], $params['checkMore']), $params['startTime'], $params['formats'], $driver, $endTime);
        $event->setData($actionList);
    }

    public function findActionListBySite(OW_Event $event)
    {
        $isPinnedPage = false;
        if (isset($_GET['p'])) {
            $json = json_decode($_GET['p'],true);
            parse_str(parse_url($json['url'], PHP_URL_QUERY), $output);
            if (isset($output['filter']) && $output['filter'] == 'pins') {
                $isPinnedPage = true;
            }
        }
        if (isset($_GET['filter']) && $_GET['filter'] == 'pins') {
            $isPinnedPage = true;
        }
        if(!$isPinnedPage)
            return;
        $params = $event->getParams()['params'];
        $endTime = $event->getParams()['end_time'];
        $driver = $event->getParams()['driver'];
        $actionList = $this->findPinedSiteFeed(array($params['offset'], $params['displayCount'], $params['checkMore']), $params['startTime'], $params['formats'], $driver, $endTime);
        $event->setData($actionList);
    }

    public function findActionListByHashtagUser(OW_Event $event)
    {
        $isPinnedPage = false;
        if (isset($_GET['p'])) {
            $json = json_decode($_GET['p'],true);
            parse_str(parse_url($json['url'], PHP_URL_QUERY), $output);
            if (isset($output['filter']) && $output['filter'] == 'pins') {
                $isPinnedPage = true;
            }
        }
        if (isset($_GET['filter']) && $_GET['filter'] == 'pins') {
            $isPinnedPage = true;
        }
        if(!$isPinnedPage)
            return;
        $params = $event->getParams()['params'];
        $driver = $event->getParams()['driver'];
        $hashtag = $event->getParams()['hashtag'];
        $actionList = $this->findPinnedByUserHashtag($params['feedId'], $params['includeActionIdList'], $hashtag, array($params['offset'], $params['displayCount'], $params['checkMore']), $params['startTime'], $params['formats'], $driver);
        $event->setData($actionList);
    }

    public function findActionListByHashtagPublic(OW_Event $event)
    {
        $isPinnedPage = false;
        if (isset($_GET['p'])) {
            $json = json_decode($_GET['p'],true);
            parse_str(parse_url($json['url'], PHP_URL_QUERY), $output);
            if (isset($output['filter']) && $output['filter'] == 'pins') {
                $isPinnedPage = true;
            }
        }
        if (isset($_GET['filter']) && $_GET['filter'] == 'pins') {
            $isPinnedPage = true;
        }
        if(!$isPinnedPage)
            return;
        $params = $event->getParams()['params'];
        $driver = $event->getParams()['driver'];
        $hashtag = $event->getParams()['hashtag'];
        $actionList = $this->findPinnedPublicHashtag($params['includeActionIdList'], $hashtag, array($params['offset'], $params['displayCount'], $params['checkMore']), $params['startTime'], $params['formats'], $driver);
        $event->setData($actionList);
    }

    private function getPinMenu()
    {
        $isFiltered = isset($_GET['filter']) && $_GET['filter'] == 'pins';
        $language = OW::getLanguage();
        $uri = OW::getRequest()->getRequestUri();
        $uri_parts = explode('?', $uri, 2);
        $items = array();

        $items[0] = new BASE_MenuItem();
        $items[0]->setLabel($language->text('iisnewsfeedpin', 'list_menu_item_all'))
            ->setKey('all')
//            ->setUrl(OW::getRouter()->urlForRoute('groups-view', array('groupId' => $groupId)))
            ->setUrl(OW_URL_HOME . $uri_parts[0])
            ->setOrder(1)
            ->setIconClass('ow_ic_all_posts')
            ->setActive(!$isFiltered);

        $items[1] = new BASE_MenuItem();
        $items[1]->setLabel($language->text('iisnewsfeedpin', 'list_menu_item_pins'))
            ->setKey('pined')
//            ->setUrl(OW::getRouter()->urlForRoute('groups-view', array('groupId' => $groupId)) . '?filter=pins')
            ->setUrl(OW_URL_HOME . $uri_parts[0] . '?filter=pins')
            ->setOrder(2)
            ->setIconClass('ow_ic_pinned_posts')
            ->setActive($isFiltered);

        return new BASE_CMP_ContentMenu($items);
    }

    private function getPinMenuMobile()
    {
        $isFiltered = isset($_GET['filter']) && $_GET['filter'] == 'pins';
        $language = OW::getLanguage();
        $uri = OW::getRequest()->getRequestUri();
        $uri_parts = explode('?', $uri, 2);
        $items = array();

        $items[0] = new BASE_MenuItem();
        $items[0]->setLabel($language->text('iisnewsfeedpin', 'list_menu_item_all'))
            ->setKey('all')
//            ->setUrl(OW::getRouter()->urlForRoute('groups-view', array('groupId' => $groupId)))
            ->setUrl(OW_URL_HOME . $uri_parts[0])
            ->setOrder(1)
            ->setIconClass('ow_ic_files')
            ->setActive(!$isFiltered);

        $items[1] = new BASE_MenuItem();
        $items[1]->setLabel($language->text('iisnewsfeedpin', 'list_menu_item_pins'))
            ->setKey('pined')
//            ->setUrl(OW::getRouter()->urlForRoute('groups-view', array('groupId' => $groupId)) . '?filter=pins')
            ->setUrl(OW_URL_HOME . $uri_parts[0] . '?filter=pins')
            ->setOrder(2)
            ->setIconClass('ow_ic_star')
            ->setActive($isFiltered);

        return new BASE_MCMP_ContentMenu($items);
    }

    public function loadNewFeedItemHTML(OW_Event $event){
        $params = $event->getParams();
        $entityId = $params['entityId'];
        $entityType = $params['entityType'];

        $parts = parse_url($params['url']);
        $query = array();
        if(isset($parts['query']))
            parse_str($parts['query'], $query);

        $isFiltered = false;
        foreach($query as $key=>$value){
            if($key == 'filter' && $value == 'pins'){
                $isFiltered = true;
            }
        }

        $pin = IISNEWSFEEDPIN_BOL_PinDao::getInstance()->findByEntityIdAndEntityType($entityId, $entityType);

        if($isFiltered && !isset($pin)){
            $event->setData(array('html'=>''));
        }
    }
}
