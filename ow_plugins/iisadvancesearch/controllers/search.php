<?php

class IISADVANCESEARCH_CTRL_Search extends OW_ActionController
{
    /**
     * @var IISADVANCESEARCH_CTRL_Search
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return IISADVANCESEARCH_CTRL_Search
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
            self::$classInstance = new self();

        return self::$classInstance;
    }

    /***
     * @param $params
     * @throws Redirect404Exception
     */
    public function searchAll($params)
    {

//        if(!OW::getUser()->isAuthenticated()){
//            return;
//        }

        if(!OW::getRequest()->isAjax() || !isset($_POST['searchValue'])){
            throw new Redirect404Exception();
        }

        $searchValue = $_POST['searchValue'];
        $searchValue = trim($searchValue);

        $resultData = array();
        $event = OW::getEventManager()->trigger(new OW_Event('iisadvancesearch.on_collect_search_items',
            array('q' => $searchValue, 'maxCount' => 10), $resultData));
        $resultData = $event->getData();

        foreach($resultData as $key => $value){
            $resultData[$key] = $value['data'];
            if(OW::getConfig()->configExists('iisadvancesearch','search_allowed_'.$key)){
                $isAllowed = OW::getConfig()->getValue('iisadvancesearch','search_allowed_'.$key);
                if(!$isAllowed){
                    unset($resultData[$key]);
                }
            }
        }

        exit(json_encode(array('searchedValue' => $searchValue, 'data'=>$resultData)));
    }

    public function searchUsers ($params){
        $key = isset($params['key'])?urldecode($params['key']):'';
        $type = $params['type'];
        $searchFriends = (in_array($type, array('all', 'friends')));
        $searchNonFriends = (in_array($type, array('all', 'new')));

        $start = isset($_GET['start'])?$_GET['start']:0;
        $count = isset($_GET['count'])?$_GET['count']:12;
        $users = $this->getUsersBySearchValue($key, $searchFriends, $searchNonFriends , $start, $count);

        $more_available = (count($users)>=$count);
        $result = array('q'=>$key, 'more_available'=>$more_available, 'is_appending'=>($start>0),
            'next_start'=>$start+$count, 'items'=>$users);
        exit(json_encode($result));
    }

    public function searchFriends ($params){
        $key = urldecode($params['key']);
        $users = $this->getUsersBySearchValue($key, true, false );
        $result = array('q'=>$key, 'items'=>$users);
        exit(json_encode($result));
    }

    public function getUsersBySearchValue($searchValue, $search_friends = true, $search_non_friends = true, $_start=0, $_count=12){
        $users = array();
        $userIdList = array();

        if(!$search_friends || !$search_non_friends) {
            $_count = 1000;
        }

        $userIdListByUsername = $this->getUsersByQuestionAndValue('username', $searchValue, 0, $_count*10);
        $userIdListByRealName = $this->getUsersByQuestionAndValue('realname', $searchValue, 0, $_count*10);

        foreach($userIdListByUsername as $userId){
            if(!in_array($userId, $userIdList)){
                $userIdList[] = $userId;
            }
        }

        foreach($userIdListByRealName as $userId){
            if(!in_array($userId, $userIdList)){
                $userIdList[] = $userId;
            }
        }

        $userIdList = array_slice($userIdList, $_start, $_count);

        if ( count($userIdList) > 0 ){
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($userIdList);
            foreach($avatars as $avatar){
                $userId = $avatar['userId'];
                if(!$search_non_friends && $userId == OW::getUser()->getId()){
                    continue;
                }
                if(!$search_friends || !$search_non_friends){
                    $friendshipItem = FRIENDS_BOL_FriendshipDao::getInstance()->findFriendship(OW::getUser()->getId(),$userId);
                    $areFriends = (isset($friendshipItem) && $friendshipItem->status == 'active');
                    if($areFriends == $search_non_friends)
                        continue;
                }
                $user = array();
                $user['url'] = $avatar['url'];
                $user['src'] = $avatar['src'];
                $user['title'] = $avatar['title'];
                $user['id'] = $avatar['userId'];
                $users[] = $user;
            }
        }

        return $users;
    }


    public function getUsersByQuestionAndValue($questionName, $searchValue, $first = 0, $_count = 12){
        $questionData = array($questionName => $searchValue);
        $first = (int) $first;
        $count = (int) $_count;
        $data = array(
            'data' => $questionData,
            'first' => $first,
            'count' => $count,
            'isAdmin' => OW::getUser()->isAdmin(),
            'aditionalParams' => array()
        );

        $event = new OW_Event("base.question.before_user_search", $data, $data);
        OW_EventManager::getInstance()->trigger($event);
        $data = $event->getData();

        $userIdList = BOL_UserService::getInstance()->findUserIdListByQuestionValues($data['data'], $data['first'], $data['count'], $data['isAdmin'], $data['aditionalParams']);
        return $userIdList;
    }

    public function getForumPosts($searchValue){
        $result = array();
        $topics = FORUM_BOL_ForumService::getInstance()->advancedFindEntities($searchValue, '1', null, array(""), null, 'date', 'decrease', true);
        $topicsUsingTitle = FORUM_BOL_ForumService::getInstance()->advancedFindEntities($searchValue, '1', null, array(""), null, 'date', 'decrease', false);
        foreach($topicsUsingTitle as $key => $topic){
            if(!isset($topics[$key])){
                $topics[] = $topic;
            }
        }

        $count = 0;
        $numberOfCount = 12;

        foreach($topics as $topic){
            $topicInformation = array();
            $topicInformation['title'] = $topic['title'];
            $topicInformation['groupName'] = $topic['groupName'];
            $topicInformation['sectionName'] = $topic['sectionName'];
            $topicInformation['topicUrl'] = $topic['topicUrl'];
            $result[] = $topicInformation;
            $count++;
            if($count == $numberOfCount){
                return $result;
            }
        }

        return $result;

    }
}