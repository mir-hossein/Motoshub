<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 10:03 AM
 */
class IISQUESTIONS_BOL_QuestionDao extends OW_BaseDao
{
    const OWNER = 'owner';
    const PRIVACY = 'privacy';
    const ADD_OPTION = 'addOption';
    const CONTEXT = 'context';
    const CONTEXT_ID = 'contextId';
    const TIME_STAMP = 'timeStamp';
    const IS_MULTIPLE = 'isMultiple';
    const ENTITY_TYPE = 'entityType';
    const ENTITY_ID = 'entityId';

    private static $INSTANCE;

    public static function getInstance()
    {
        if (!isset(self::$INSTANCE))
            self::$INSTANCE = new self();
        return self::$INSTANCE;
    }

    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisquestions_question';
    }

    public function getDtoClassName()
    {
        return 'IISQUESTIONS_BOL_Question';
    }

    public function findAllByUserSortByTime($count = 5, $last = 0)
    {
        $friendsQuery = "";
        $groupsQuery = "";
        $friendsPrivacy = "OR `q`.`privacy` = :friends_only";
        $friendsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('friends');
        if(isset($friendsPlugin) && $friendsPlugin->isActive()) {
            $friendsQuery = " UNION
                
                SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                    LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f1` ON `f1`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = `q`.`" . self::OWNER . "`
                    LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f2` ON `f2`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = `q`.`" . self::OWNER . "`
                    WHERE `q`.`privacy` = :friends_only AND (`q`.`" . self::OWNER . "` = :user_id OR `f1`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = :user_id OR `f2`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = :user_id)  AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL           
                
                ";
            $friendsPrivacy = "";
        }
        $groupsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
        if(isset($groupsPlugin) && $groupsPlugin->isActive()) {
            $groupsQuery = "
                UNION
                
                SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                    LEFT JOIN `" . GROUPS_BOL_GroupUserDao::getInstance()->getTableName() . "` AS `gu` ON `gu`.`groupId` = `q`.`" . self::CONTEXT_ID . "`               
                    WHERE `q`.`context` = 'groups' AND `gu`.`userId` = :user_id  AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL             
                ";
        }

        $query = "
            SELECT * FROM (
                SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                    WHERE `q`.`privacy` = :every_body AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL
                    
                ".$friendsQuery."
                
              ".$groupsQuery."
                UNION
                
                SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                    WHERE `q`.`" . self::OWNER . "` = :user_id AND (`q`.`privacy` = :only_me ".$friendsPrivacy." ) AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL
            ) AS `questions`
            ORDER BY `questions`." . self::TIME_STAMP . " DESC
            LIMIT :row,:num
        ";
        $params = array(
            'user_id' => OW::getUser()->getId(),
            'every_body' => IISQUESTIONS_BOL_Service::PRIVACY_EVERYBODY,
            'friends_only' => IISQUESTIONS_BOL_Service::PRIVACY_FRIENDS_ONLY,
            'only_me' => IISQUESTIONS_BOL_Service::PRIVACY_ONLY_FOR_ME,
            'row' => $last,
            'num' => $count,
        );
        return $this->dbo->queryForObjectList($query, IISQUESTIONS_BOL_Question::class, $params);
    }

    public function findCountAllByUser()
    {
        $friendsQuery = "";
        $groupsQuery = "";
        $friendsPrivacy = "OR `q`.`privacy` = :friends_only";
        $friendsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('friends');
        if(isset($friendsPlugin) && $friendsPlugin->isActive()) {
            $friendsQuery = "
                UNION
                
                SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                    LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f1` ON `f1`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = `q`.`" . self::OWNER . "`
                    LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f2` ON `f2`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = `q`.`" . self::OWNER . "`
                    WHERE `q`.`privacy` = :friends_only AND (`q`.`" . self::OWNER . "` = :user_id OR `f1`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = :user_id OR `f2`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = :user_id)  AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL           
                ";
            $friendsPrivacy = "";
        }
        $groupsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
        if(isset($groupsPlugin) && $groupsPlugin->isActive()) {
            $groupsQuery = "
                
                UNION
                
                SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                    LEFT JOIN `" . GROUPS_BOL_GroupUserDao::getInstance()->getTableName() . "` AS `gu` ON `gu`.`groupId` = `q`.`" . self::CONTEXT_ID . "`               
                    WHERE `q`.`context` = 'groups' AND `gu`.`userId` = :user_id  AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL             
                ";
        }

        $query = "
            SELECT COUNT(*) as cnt FROM (
                SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                    WHERE `q`.`privacy` = :every_body  AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL
                    
               ".$friendsQuery."
                
              ". $groupsQuery."
                
                UNION
                
                SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                    WHERE `q`.`" . self::OWNER . "` = :user_id AND (`q`.`privacy` = :only_me ".$friendsPrivacy." ) AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL
            ) AS `questions`
        ";
        $params = array(
            'user_id' => OW::getUser()->getId(),
            'every_body' => IISQUESTIONS_BOL_Service::PRIVACY_EVERYBODY,
            'friends_only' => IISQUESTIONS_BOL_Service::PRIVACY_FRIENDS_ONLY,
            'only_me' => IISQUESTIONS_BOL_Service::PRIVACY_ONLY_FOR_ME,
        );
        return (int) $this->dbo->queryForColumn($query, $params);
    }

    public function findAllSortByTime($count = 5, $last = 0)
    {
        $query = "
            SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
            WHERE `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL
            ORDER BY `q`." . self::TIME_STAMP . " DESC
            LIMIT :row,:num
        ";
        $params = array(
            'row' => $last,
            'num' => $count
        );
        return $this->dbo->queryForObjectList($query, IISQUESTIONS_BOL_Question::class, $params);
    }

    public function findCountAll()
    {
        $query = "
             SELECT COUNT(*) FROM `" . $this->getTableName() . "` AS `q`
             WHERE `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL ";

        return (int) $this->dbo->queryForColumn($query);
    }

    public function findMyQuestionSortByTime($count = 5, $last = 0)
    {
        $query = "
            SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                WHERE `q`.`owner` = :user_id AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL
                ORDER BY `q`." . self::TIME_STAMP . " DESC
                LIMIT :row,:num
        ";
        $params = array(
            'user_id' => OW::getUser()->getId(),
            'row' => $last,
            'num' => $count,
        );
        return $this->dbo->queryForObjectList($query, IISQUESTIONS_BOL_Question::class, $params);
    }

    public function findMyQuestionCount()
    {
        $query = "
            SELECT COUNT(*) FROM `" . $this->getTableName() . "`
                WHERE `owner` = :user_id AND `entityType` IS NOT NULL AND `entityId` IS NOT NULL
        ";
        $params = array(
            'user_id' => OW::getUser()->getId(),
        );
        return (int) $this->dbo->queryForColumn($query, $params);
    }

    public function findFriendQuestionSortByTime($count = 5, $last = 0)
    {
        $groupsQuery = "";
        $groupsContext = "";

        $groupsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
        if(isset($groupsPlugin) && $groupsPlugin->isActive()) {
            $groupsQuery = "LEFT JOIN `" . GROUPS_BOL_GroupUserDao::getInstance()->getTableName() . "` AS `gu` ON `gu`.`groupId` = `q`.`" . self::CONTEXT_ID . "`";
            $groupsContext = "OR (`q`.`context` = 'groups' AND `gu`.`userId` = :user_id)";
        }

        $query = "
            SELECT * FROM (
                SELECT DISTINCT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                    LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f1` ON `f1`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = `q`.`" . self::OWNER . "`
                    LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f2` ON `f2`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = `q`.`" . self::OWNER . "`
                    ".$groupsQuery."
                    WHERE (`q`.`privacy` = :friends_only OR `q`.`privacy` = :every_body) AND (`f1`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = :user_id OR `f2`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = :user_id) AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL  
                    AND (`q`.`context` <> 'groups' ".$groupsContext." )         
            ) AS `questions`
            ORDER BY `questions`." . self::TIME_STAMP . " DESC
            LIMIT :row,:num
        ";
        $params = array(
            'user_id' => OW::getUser()->getId(),
            'every_body' => IISQUESTIONS_BOL_Service::PRIVACY_EVERYBODY,
            'friends_only' => IISQUESTIONS_BOL_Service::PRIVACY_FRIENDS_ONLY,
            'only_me' => IISQUESTIONS_BOL_Service::PRIVACY_ONLY_FOR_ME,
            'row' => $last,
            'num' => $count,
        );
        return $this->dbo->queryForObjectList($query, IISQUESTIONS_BOL_Question::class, $params);
    }

    public function findFriendQuestionCount()
    {
        $groupsQuery = "";
        $groupsContext = "";

        $groupsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
        if(isset($groupsPlugin) && $groupsPlugin->isActive()) {
            $groupsQuery = "LEFT JOIN `" . GROUPS_BOL_GroupUserDao::getInstance()->getTableName() . "` AS `gu` ON `gu`.`groupId` = `q`.`" . self::CONTEXT_ID . "`";
            $groupsContext = "OR (`q`.`context` = 'groups' AND `gu`.`userId` = :user_id)";
        }

        $query = "
           SELECT COUNT(*) FROM (
                SELECT DISTINCT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                    LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f1` ON `f1`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = `q`.`" . self::OWNER . "`
                    LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f2` ON `f2`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = `q`.`" . self::OWNER . "`
                    ".$groupsQuery."
                    WHERE (`q`.`privacy` = :friends_only OR `q`.`privacy` = :every_body) AND (`f1`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = :user_id OR `f2`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = :user_id) AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL  
                    AND (`q`.`context` <> 'groups' ".$groupsContext." )         
            ) AS `questions`
        ";
        $params = array(
            'user_id' => OW::getUser()->getId(),
            'every_body' => IISQUESTIONS_BOL_Service::PRIVACY_EVERYBODY,
            'friends_only' => IISQUESTIONS_BOL_Service::PRIVACY_FRIENDS_ONLY,
            'only_me' => IISQUESTIONS_BOL_Service::PRIVACY_ONLY_FOR_ME,
        );
        return (int) $this->dbo->queryForColumn($query, $params);
    }

    public function findAllHottestQuestion($count = 5, $last = 0)
    {
        $friendsQuery = "";
        $groupsQuery = "";
        $friendsPrivacy = "OR `q`.`privacy` = :friends_only";
        $friendsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('friends');
        if(isset($friendsPlugin) && $friendsPlugin->isActive()) {
            $friendsQuery = "
                        
                    UNION
                    
                    SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                        LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f1` ON `f1`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = `q`.`" . self::OWNER . "`
                        LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f2` ON `f2`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = `q`.`" . self::OWNER . "`
                        WHERE `q`.`privacy` = :friends_only AND (`q`.`" . self::OWNER . "` = :user_id OR `f1`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = :user_id OR `f2`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = :user_id)  AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL           
                     ";
            $friendsPrivacy = "";
        }
        $groupsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
        if(isset($groupsPlugin) && $groupsPlugin->isActive()) {
            $groupsQuery = "
                    
                     UNION
                
                    SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                        LEFT JOIN `" . GROUPS_BOL_GroupUserDao::getInstance()->getTableName() . "` AS `gu` ON `gu`.`groupId` = `q`.`" . self::CONTEXT_ID . "`               
                        WHERE `q`.`context` = 'groups' AND `gu`.`userId` = :user_id  AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL             
                
                ";
        }

        $query = "
            SELECT * FROM (
                SELECT `questions`.*,COUNT(`ans`.id) as cnt FROM (
                    SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                        WHERE `q`.`privacy` = :every_body AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL
                        
                   ".$friendsQuery."
                    ".$groupsQuery."
                    UNION
                    
                    SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                        WHERE `q`.`" . self::OWNER . "` = :user_id AND ( `q`.`privacy` = :only_me ".$friendsPrivacy." ) AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL
                ) AS `questions`
                LEFT JOIN `".IISQUESTIONS_BOL_AnswerDao::getInstance()->getTableName()."` AS `ans` ON `questions`.`id` = `ans`.`questionId`
                GROUP BY `questions`.`id`
                ORDER BY cnt DESC
            ) AS `q`
            LIMIT :row,:num
        ";
        $params = array(
            'user_id' => OW::getUser()->getId(),
            'every_body' => IISQUESTIONS_BOL_Service::PRIVACY_EVERYBODY,
            'friends_only' => IISQUESTIONS_BOL_Service::PRIVACY_FRIENDS_ONLY,
            'only_me' => IISQUESTIONS_BOL_Service::PRIVACY_ONLY_FOR_ME,
            'row' => $last,
            'num' => $count,
        );
        return $this->dbo->queryForObjectList($query, IISQUESTIONS_BOL_Question::class, $params);
    }

    public function findAllHottestQuestionCount()
    {
        $friendsQuery = "";
        $groupsQuery = "";
        $friendsPrivacy = "OR `q`.`privacy` = :friends_only";
        $friendsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('friends');
        if(isset($friendsPlugin) && $friendsPlugin->isActive()) {
            $friendsQuery = "
                    UNION
                    
                    SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                        LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f1` ON `f1`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = `q`.`" . self::OWNER . "`
                        LEFT JOIN `" . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . "` AS `f2` ON `f2`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = `q`.`" . self::OWNER . "`
                        WHERE `q`.`privacy` = :friends_only AND (`q`.`" . self::OWNER . "` = :user_id OR `f1`.`" . FRIENDS_BOL_FriendshipDao::FRIEND_ID . "` = :user_id OR `f2`.`" . FRIENDS_BOL_FriendshipDao::USER_ID . "` = :user_id)  AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL           
                ";
            $friendsPrivacy = "";
        }
        $groupsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
        if(isset($groupsPlugin) && $groupsPlugin->isActive()) {
            $groupsQuery = "
                    
                    UNION
                
                    SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                        LEFT JOIN `" . GROUPS_BOL_GroupUserDao::getInstance()->getTableName() . "` AS `gu` ON `gu`.`groupId` = `q`.`" . self::CONTEXT_ID . "`               
                        WHERE `q`.`context` = 'groups' AND `gu`.`userId` = :user_id  AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL             
                
                ";
        }

        $query = "
            SELECT COUNT(*) as cnt FROM (
                SELECT `questions`.*,COUNT(`ans`.id) as cnt FROM (
                    SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                        WHERE `q`.`privacy` = :every_body AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL
                   ".$friendsQuery."
                   ".$groupsQuery."
                    UNION
                    
                    SELECT `q`.* FROM `" . $this->getTableName() . "` AS `q`
                        WHERE `q`.`" . self::OWNER . "` = :user_id AND ( `q`.`privacy` = :only_me ".$friendsPrivacy." ) AND `q`.`context` <> 'groups' AND `q`.`entityType` IS NOT NULL AND `q`.`entityId` IS NOT NULL
                ) AS `questions`
                LEFT JOIN `".IISQUESTIONS_BOL_AnswerDao::getInstance()->getTableName()."` AS `ans` ON `questions`.`id` = `ans`.`questionId`
                GROUP BY `questions`.`id`
            ) AS `q`
        ";
        $params = array(
            'user_id' => OW::getUser()->getId(),
            'every_body' => IISQUESTIONS_BOL_Service::PRIVACY_EVERYBODY,
            'friends_only' => IISQUESTIONS_BOL_Service::PRIVACY_FRIENDS_ONLY,
            'only_me' => IISQUESTIONS_BOL_Service::PRIVACY_ONLY_FOR_ME,
        );
        return (int) $this->dbo->queryForColumn($query, $params);
    }

    public function findByEntity($entityId,$entityType){
        $example = new OW_Example();
        $example->andFieldEqual(self::ENTITY_TYPE,$entityType);
        $example->andFieldEqual(self::ENTITY_ID,$entityId);
        return $this->findObjectByExample($example);
    }
}