<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisnews.bol.dao
 * @since 1.0
 */
class EntryDao extends OW_BaseDao
{
    const CACHE_TAG_POST_COUNT = 'news.entry_count';
    const CACHE_LIFE_TIME = 86400; //24 hour

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
     * @var EntryDao
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return EntryDao
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
        return 'Entry';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisnews_entry';
    }

    public function getTagTableName()
    {
        return OW_DB_PREFIX . 'base_tag';
    }
    public function getEntityTagTableName()
    {
        return OW_DB_PREFIX . 'base_entity_tag';
    }

    public function findAdjacentUserEntry( $id, $entryId, $which )
    {
        $part = array();

        switch ( $which )
        {
            case 'next':
                $part['projection'] = 'MIN(`id`)';
                $part['inequality'] = '>';
                break;

            case 'prev':
                $part['projection'] = 'MAX(`id`)';
                $part['inequality'] = '<';
                break;
        }

        $query = "
			SELECT :projection
			FROM {$this->getTableName()}
			WHERE isDraft = 0 AND authorId =:id AND id {$part['inequality']} :entryId
		";

        $id = $this->dbo->queryForColumn($query, array('projection'=>$part['projection'], 'id'=>$id, 'entryId'=>$entryId));

        return (!empty($id)) ? $this->findById($id) : null;
    }

    public function deleteByAuthorId( $userId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId);

        $this->deleteByExample($ex);
    }

    public function findUserEntryList( $userId, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId)
            ->setOrder('`timestamp` DESC')
            ->andFieldEqual('isDraft', 0)
            ->setLimitClause($first, $count);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->findListByExample($ex, $cacheLifeTime, $tags);
    }

    public function findUserDraftList( $userId, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId)
            ->andFieldNotEqual('isDraft', 0)
            ->setOrder('`timestamp` DESC')
            ->setLimitClause($first, $count);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->findListByExample($ex, $cacheLifeTime, $tags);
    }

    public function countUserEntry( $userId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId);
        $ex->andFieldEqual('isDraft', 0);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->countByExample($ex,$cacheLifeTime, $tags);
    }


    public function findEntryList($first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $ex = new OW_Example();
        $ex->setOrder('`timestamp` DESC')
            ->andFieldEqual('isDraft', 0)
            ->setLimitClause($first, $count);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->findListByExample($ex, $cacheLifeTime, $tags);
    }

    public function countEntry()
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('isDraft', 0);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->countByExample($ex,$cacheLifeTime, $tags);
    }


    public function countUserDraft( $userId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId);
        $ex->andFieldNotEqual('isDraft', 0);
        $ex->andFieldNotEqual('isDraft', 3);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->countByExample($ex, $cacheLifeTime, $tags);
    }

    public function countEntrys()
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('isDraft', 0);
        $ex->andFieldEqual('privacy', 'everybody');

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->countByExample($ex, $cacheLifeTime, $tags);
    }

    public function countUserEntryComment( $userId )
    {
        $query = "
		SELECT COUNT(*)
		FROM `{$this->getTableName()}` as `p`
		INNER JOIN `" . BOL_CommentEntityDao::getInstance()->getTableName() . "` as `ce`
			ON( `p`.`id` = `ce`.`entityId` and `entityType` = 'news-entry' )
		INNER JOIN `" . BOL_CommentDao::getInstance()->getTableName() . "` as `c`
			ON( `ce`.`id` = `c`.`commentEntityId` )

		WHERE `p`.`authorId` = ? AND `p`.`isDraft` = 0
		";

        return $this->dbo->queryForColumn($query, array($userId));
    }

    public function countUserEntryNewComment( $userId )
    {
        $query = "
		SELECT COUNT(*)
		FROM `{$this->getTableName()}` as `p`
		INNER JOIN `" . BOL_CommentEntityDao::getInstance()->getTableName() . "` as `ce`
			ON( `p`.`id` = `ce`.`entityId` and `entityType` = 'news-entry' )
		INNER JOIN `" . BOL_CommentDao::getInstance()->getTableName() . "` as `c`
			ON( `ce`.`id` = `c`.`commentEntityId` )

		WHERE `p`.`authorId` = ? AND `p`.`isDraft` = 0 AND `c`.`createStamp` > ".(time()-86400*7)."
		";

        return $this->dbo->queryForColumn($query, array($userId));
    }

    public function findUserEntryCommentList( $userId, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $query = "
		SELECT `c`.*, `ce`.`entityId`
		FROM `{$this->getTableName()}` as `p`
		INNER JOIN `" . BOL_CommentEntityDao::getInstance()->getTableName() . "` as `ce`
			ON( `p`.`id` = `ce`.`entityId` and `entityType` = 'news-entry' )
		INNER JOIN `" . BOL_CommentDao::getInstance()->getTableName() . "` as `c`
			ON( `ce`.`id` = `c`.`commentEntityId` )

		WHERE `p`.`authorId` = ? AND `p`.`isDraft` = 0
		ORDER BY `c`.`createStamp` DESC
		LIMIT ?, ?
		";

        return $this->dbo->queryForList($query, array($userId, $first, $count));
    }

    public function findUserLastEntry( $userId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId)->andFieldEqual('isDraft', 0)->setOrder('timestamp DESC')->setLimitClause(0, 1);

        return $this->findObjectByExample($ex);
    }

    public function findUserArchiveData( $id )
    {
        $query = "
			SELECT YEAR( FROM_UNIXTIME(`timestamp`) ) as `y`, MONTH( FROM_UNIXTIME(`timestamp`) ) as `m`, DAY( FROM_UNIXTIME(`timestamp`) ) as `d`
			FROM `{$this->getTableName()}`
			WHERE isDraft = 0 AND `authorId` = ?
			GROUP BY `y` , `m` , `d`  
			ORDER BY `y` DESC, `m` DESC, `d` DESC
		";

        return $this->dbo->queryForList($query, array($id));
    }

    public function findArchiveData()
    {
        $query = "
			SELECT YEAR( FROM_UNIXTIME(`timestamp`) ) as `y`, MONTH( FROM_UNIXTIME(`timestamp`) ) as `m`, DAY( FROM_UNIXTIME(`timestamp`) ) as `d`
			FROM `{$this->getTableName()}`
			WHERE isDraft = 0 
			GROUP BY `y` , `m` , `d` 
			ORDER BY `y` DESC, `m` DESC, `d` DESC
		";

        return $this->dbo->queryForList($query);
    }

    public function findUserEntryListByPeriod( $id, $lb, $ub, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $id);

        $ex->andFieldBetween('timestamp', $lb, $ub);
        $ex->andFieldEqual('isDraft', 0);
        $ex->setOrder('`timestamp` DESC');
        $ex->setLimitClause($first, $count);

        return $this->findListByExample($ex);
    }

    public function countUserEntryByPeriod( $id, $lb, $ub )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $id);
        $ex->andFieldBetween('timestamp', $lb, $ub);
        $ex->andFieldEqual('isDraft', 0);
        $ex->setOrder('`timestamp` DESC');

        return $this->countByExample($ex);
    }

    public function findEntryListByPeriod($lb, $ub, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $ex = new OW_Example();

        $ex->andFieldBetween('timestamp', $lb, $ub);
        $ex->andFieldEqual('isDraft', 0);
        $ex->setOrder('`timestamp` DESC');
        $ex->setLimitClause($first, $count);

        return $this->findListByExample($ex);
    }

    public function countEntryByPeriod($lb, $ub )
    {
        $ex = new OW_Example();
        $ex->andFieldBetween('timestamp', $lb, $ub);
        $ex->andFieldEqual('isDraft', 0);
        $ex->setOrder('`timestamp` DESC');

        return $this->countByExample($ex);
    }



    public function findList( $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $ex = new OW_Example();
        $ex->andFieldEqual('isDraft', 0);
        $ex->andFieldEqual('privacy', 'everybody');
        $ex->setOrder('timestamp desc')->setLimitClause($first, $count);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->findListByExample($ex, $cacheLifeTime, $tags);
    }

    public function findTopRatedList( $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $query = "
			SELECT p.*, IF(SUM(r.score) IS NOT NULL, SUM(r.score), 0) as `t`
			FROM `{$this->getTableName()}` as p
			LEFT JOIN `ow_base_rate` as r /*todo: 8aa*/
			ON( r.`entityType` = 'news-entry' AND p.id = r.`entityId` )
			WHERE p.isDraft = 0
			GROUP BY p.`id`
			ORDER BY `t` DESC
			LIMIT ?, ?";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }


    public function findLatestPublicEntryIds( $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $query = "
			SELECT `p`.`id`
			FROM `{$this->getTableName()}` as `p`
			WHERE `p`.`isDraft` = 0 AND `p`.privacy ='everybody' 
			ORDER BY `p`.`timestamp` DESC
			LIMIT ?, ?";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function findLatestPublicEntries( $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $query = "
			SELECT `p`.*
			FROM `{$this->getTableName()}` as `p`
			WHERE `p`.`isDraft` = 0 AND `p`.privacy ='everybody' 
			ORDER BY `p`.`timestamp` DESC
			LIMIT ?, ?";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function findListByTag( $tag, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $query = "
			SELECT p.*
			FROM `{$this->getTagTableName()}` as t
			INNER JOIN {$this->getEntityTagTableName()} as `et`
				ON(`t`.`id` = `et`.`tagId` AND `et`.`entityType` = 'news-entry')
			INNER JOIN `{$this->getTableName()}` as p
				ON(`et`.`entityId` = `p`.`id`)
			WHERE p.isDraft = 0 AND `t`.`label`=:tag
			ORDER BY `p`.`timestamp` DESC
			LIMIT :first, :count";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array('first'=>$first, 'count' => $count, 'tag' => $tag));
        //return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function findListByTags( $tags, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }
        $query = "
			SELECT p.*
			FROM `{$this->getTagTableName()}` as t
			INNER JOIN {$this->getEntityTagTableName()} as `et`
				ON(`t`.`id` = `et`.`tagId` AND `et`.`entityType` = 'news-entry')
			INNER JOIN `{$this->getTableName()}` as p
				ON(`et`.`entityId` = `p`.`id`)
			WHERE p.isDraft = 0 AND `t`.`label` IN (".OW::getDbo()->mergeInClause($tags).")
			ORDER BY `p`.`timestamp` DESC
			LIMIT :first, :count";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array('first'=>$first, 'count' => $count));
        //return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function countByTag( $tag )
    {
        $query = "
			SELECT count( * )
			FROM `{$this->getTagTableName()}` as t
			INNER JOIN {$this->getEntityTagTableName()} as `et`
				ON(`t`.`id` = `et`.`tagId` AND `et`.`entityType` = 'news-entry')
			INNER JOIN `{$this->getTableName()}` as p
				ON(`et`.`entityId` = `p`.`id`)
			WHERE p.isDraft = 0 AND `t`.`label`=:tag";

        return $this->dbo->queryForColumn($query, array('tag' => $tag));
    }

    public function findListByIdList( $list )
    {
        if(empty($list)){
            return array();
        }
        $ex = new OW_Example();

        $ex->andFieldInArray('id', $list);
        $ex->andFieldEqual('privacy', 'everybody');

        $ex->setOrder('timestamp DESC');

        return $this->findListByExample($ex);
    }

    public function updateNewsPrivacy( $authorId, $privacy )
    {
        $this->clearCache();

        $sql = "UPDATE `" . $this->getTableName() . "` SET `privacy` = :privacy
            WHERE `authorId` = :authorId";

        $this->dbo->query($sql, array('privacy' => $privacy, 'authorId' => $authorId));
    }

    public function clearCache()
    {
        parent::clearCache();
        OW::getCacheManager()->clean( array( EntryDao::CACHE_TAG_POST_COUNT ));
    }

    public function findMostCommentedEntryList($first, $count )
    {
        $entityType = 'news-entry';
        $queryParts = BOL_ContentService::getInstance()->getQueryFilter(array(
            BASE_CLASS_QueryBuilderEvent::TABLE_USER => 'c',
            BASE_CLASS_QueryBuilderEvent::TABLE_CONTENT => 'c',
            'comment_entity' => 'ce'
        ), array(
            BASE_CLASS_QueryBuilderEvent::FIELD_USER_ID => 'userId',
            BASE_CLASS_QueryBuilderEvent::FIELD_CONTENT_ID => 'id'
        ), array(
            BASE_CLASS_QueryBuilderEvent::OPTION_METHOD => __METHOD__,
            BASE_CLASS_QueryBuilderEvent::OPTION_TYPE => $entityType
        ));
        $privacyConditionWhere = '';
        $privacyConditionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_CONTENT_LIST_QUERY_EXECUTE, array('entityType' => $entityType, 'commentEntityTableName' => '`ce`', 'listType' => 'commentDao')));
        if(isset($privacyConditionEvent->getData()['where'])){
            $privacyConditionWhere = $privacyConditionEvent->getData()['where'];
        }

        $query = 'SELECT `ce`.`entityId` AS `id`, COUNT(*) AS `commentCount`
            FROM `' . BOL_CommentDao::getInstance()->getTableName() . '` AS `c`
			    LEFT JOIN `' . BOL_CommentEntityDao::getInstance()->getTableName() . '` AS `ce` ON (`c`.`' . BOL_CommentDao::COMMENT_ENTITY_ID . '` = `ce`.`id`)
			    LEFT JOIN `'.$this->getTableName().'` AS `ne` ON `ne`.`id` = `ce`.`entityId`
			    ' . $queryParts['join'] . '
			WHERE `ce`.`' . BOL_CommentEntityDao::ENTITY_TYPE . '` = :entityType AND `ce`.`' . BOL_CommentEntityDao::ACTIVE . '` = 1 AND `ne`.`isDraft` = 0 AND ' . $queryParts['where'] . $privacyConditionWhere .  '
			GROUP BY `ce`.`' . BOL_CommentEntityDao::ENTITY_ID . '`
			ORDER BY `commentCount` DESC, `id` DESC
			LIMIT :first, :count';
        $boundParams = array_merge(array('entityType' => $entityType, 'first' => $first, 'count' => $count), $queryParts['params']);
        if(isset($privacyConditionEvent->getData()['params']) && is_array($privacyConditionEvent->getData()['params']) && sizeof($privacyConditionEvent->getData()['params'])>0){
            $boundParams = array_merge($boundParams, $privacyConditionEvent->getData()['params']);
        }
        return $this->dbo->queryForList($query, $boundParams);
    }

    public function findCommentedEntryCount()
    {
        $query = 'SELECT COUNT(*) AS `commentCount`
            FROM `'.BOL_CommentEntityDao::getInstance()->getTableName().'` AS `ce`
                LEFT JOIN `'.$this->getTableName().'` AS `ne` ON `ne`.`id` = `ce`.`entityId`
            WHERE `ce`.`' . BOL_CommentEntityDao::ENTITY_TYPE . '` = :entityType AND `ne`.`isDraft` = 0
        ';
        $boundParams = array('entityType'=>'news-entry');
        return (int) $this->dbo->queryForColumn($query,$boundParams);
    }

    public function findMostRatedEntryList($first, $count, $exclude )
    {
        $entityType = 'news-entry';
        $queryParts = BOL_ContentService::getInstance()->getQueryFilter(array(
            BASE_CLASS_QueryBuilderEvent::TABLE_USER => 'r',
            BASE_CLASS_QueryBuilderEvent::TABLE_CONTENT => 'r'
        ), array(
            BASE_CLASS_QueryBuilderEvent::FIELD_USER_ID => 'userId',
            BASE_CLASS_QueryBuilderEvent::FIELD_CONTENT_ID => 'id'
        ), array(
            BASE_CLASS_QueryBuilderEvent::OPTION_METHOD => __METHOD__,
            BASE_CLASS_QueryBuilderEvent::OPTION_TYPE => $entityType
        ));
        $privacyConditionWhere = '';
        $privacyConditionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_CONTENT_LIST_QUERY_EXECUTE, array('entityType' => $entityType, 'rateTableName' => '`r`', 'listType' => 'rateDao')));
        if(isset($privacyConditionEvent->getData()['where'])){
            $privacyConditionWhere = $privacyConditionEvent->getData()['where'];
        }
        $excludeCond = $exclude ? ' AND `' . BOL_RateDao::ENTITY_ID . '` NOT IN (' . BOL_RateDao::getInstance()->dbo->mergeInClause($exclude) . ')' : '';

        $query = 'SELECT `r`.`' . BOL_RateDao::ENTITY_ID . '` AS `id`, COUNT(*) as `ratesCount`, AVG(`r`.`score`) as `avgScore`
            FROM `' . BOL_RateDao::getInstance()->getTableName() . '` AS `r`
                LEFT JOIN `'.$this->getTableName().'` AS `ne` ON `ne`.`id` = `r`.`entityId`
                ' . $queryParts['join'] . '
            WHERE `r`.`' . BOL_RateDao::ENTITY_TYPE . '` = :entityType AND `ne`.`isDraft` = 0 AND `r`.`' . BOL_RateDao::ACTIVE . '` = 1 ' . $excludeCond . ' AND ' . $queryParts['where'] . $privacyConditionWhere . '
            GROUP BY `r`.`' . BOL_RateDao::ENTITY_ID . '`
            ORDER BY `avgScore` DESC, `ratesCount` DESC, MAX(`r`.`timeStamp`) DESC
            LIMIT :first, :count';
        $boundParams = array_merge(array('entityType' => $entityType, 'first' => (int) $first, 'count' => (int) $count), $queryParts['params']);
        if(isset($privacyConditionEvent->getData()['params']) && is_array($privacyConditionEvent->getData()['params']) && sizeof($privacyConditionEvent->getData()['params'])>0){
            $boundParams = array_merge($boundParams, $privacyConditionEvent->getData()['params']);
        }
        return $this->dbo->queryForList($query, $boundParams);
    }

    public function findMostRatedEntryCount($exclude = null )
    {
        $entityType = 'news-entry';
        $excludeCond = $exclude ? ' AND `r`.`' . BOL_RateDao::ENTITY_ID . '` NOT IN (' . BOL_RateDao::getInstance()->dbo->mergeInClause($exclude) . ')' : '';
        $privacyConditionWhere = '';
        $privacyConditionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_CONTENT_LIST_QUERY_EXECUTE, array('entityType' => $entityType, 'rateTableName' => '`r`', 'listType' => 'rateDao')));
        if(isset($privacyConditionEvent->getData()['where'])){
            $privacyConditionWhere = $privacyConditionEvent->getData()['where'];
        }
        $query = "SELECT COUNT(DISTINCT `r`.`" . BOL_RateDao::ENTITY_ID . "`) from `" . BOL_RateDao::getInstance()->getTableName() .
            "` AS `r`
                LEFT JOIN `".$this->getTableName()."` AS `ne` ON `ne`.`id` = `r`.`entityId`
            WHERE `r`.`" . BOL_RateDao::ENTITY_TYPE . "` = :entityType AND `ne`.`isDraft` = 0 AND `r`.`" . BOL_RateDao::ACTIVE . "` = 1" . $excludeCond . $privacyConditionWhere;
        $boundParams = array('entityType' => $entityType);
        if(isset($privacyConditionEvent->getData()['params']) && is_array($privacyConditionEvent->getData()['params']) && sizeof($privacyConditionEvent->getData()['params'])>0){
            $boundParams = array_merge($boundParams, $privacyConditionEvent->getData()['params']);
        }
        return (int) $this->dbo->queryForColumn($query, $boundParams);
    }
}
