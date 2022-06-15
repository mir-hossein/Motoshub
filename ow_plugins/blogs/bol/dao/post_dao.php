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
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow_plugins.blogs.bol.dao
 * @since 1.0
 */
class PostDao extends OW_BaseDao
{
    const CACHE_TAG_POST_COUNT = 'blogs.post_count';
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
     * @var PostDao
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return PostDao
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
        return 'Post';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'blogs_post';
    }

    public function getTagTableName()
    {
        return OW_DB_PREFIX . 'base_tag';
    }

    public function findAdjacentUserPost( $id, $postId, $which )
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
			WHERE isDraft = 0 AND authorId =:id AND id {$part['inequality']} :postId
		";

        $id = $this->dbo->queryForColumn($query, array('projection'=>$part['projection'], 'id'=>$id, 'postId'=>$postId));

        return (!empty($id)) ? $this->findById($id) : null;
    }

    public function deleteByAuthorId( $userId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId);

        $this->deleteByExample($ex);
    }

    public function findUserPostList( $userId, $first, $count )
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

    public function countUserPost( $userId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId);
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

    public function countPosts()
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('isDraft', 0);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->countByExample($ex, $cacheLifeTime, $tags);
    }

    public function countUserPostComment( $userId )
    {
        $query = "
		SELECT COUNT(*)
		FROM `{$this->getTableName()}` as `p`
		INNER JOIN `" . BOL_CommentEntityDao::getInstance()->getTableName() . "` as `ce`
			ON( `p`.`id` = `ce`.`entityId` and `entityType` = 'blog-post' )
		INNER JOIN `" . BOL_CommentDao::getInstance()->getTableName() . "` as `c`
			ON( `ce`.`id` = `c`.`commentEntityId` )

		WHERE `p`.`authorId` = ? AND `p`.`isDraft` = 0
		";

        return $this->dbo->queryForColumn($query, array($userId));
    }

    public function countUserPostNewComment( $userId )
    {
        $query = "
		SELECT COUNT(*)
		FROM `{$this->getTableName()}` as `p`
		INNER JOIN `" . BOL_CommentEntityDao::getInstance()->getTableName() . "` as `ce`
			ON( `p`.`id` = `ce`.`entityId` and `entityType` = 'blog-post' )
		INNER JOIN `" . BOL_CommentDao::getInstance()->getTableName() . "` as `c`
			ON( `ce`.`id` = `c`.`commentEntityId` )

		WHERE `p`.`authorId` = ? AND `p`.`isDraft` = 0 AND `c`.`createStamp` > ".(time()-86400*7)."
		";

        return $this->dbo->queryForColumn($query, array($userId));
    }

    public function findUserPostCommentList( $userId, $first, $count )
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
			ON( `p`.`id` = `ce`.`entityId` and `entityType` = 'blog-post' )
		INNER JOIN `" . BOL_CommentDao::getInstance()->getTableName() . "` as `c`
			ON( `ce`.`id` = `c`.`commentEntityId` )

		WHERE `p`.`authorId` = ? AND `p`.`isDraft` = 0
		ORDER BY `c`.`createStamp` DESC
		LIMIT ?, ?
		";

        return $this->dbo->queryForList($query, array($userId, $first, $count));
    }

    public function findUserLastPost( $userId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId)->andFieldEqual('isDraft', 0)->setOrder('timestamp DESC')->setLimitClause(0, 1);

        return $this->findObjectByExample($ex);
    }

    /**
     * Find latest posts authors ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findLatestPublicPostsAuthorsIds($first, $count)
    {
        $query = "SELECT
            `authorId`
        FROM
            `" . $this->getTableName() . "`
        WHERE
            `privacy` = :privacy
                AND
            `isDraft` = :draft
        GROUP BY
            `authorId`
        ORDER BY
            `timestamp` DESC
        LIMIT :f, :c";

        return $this->dbo->queryForColumnList($query, array(
            'privacy' => 'everybody',
            'draft' => 0,
            'f' => (int) $first,
            'c' => (int) $count,
        ));
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

    public function findUserPostListByPeriod( $id, $lb, $ub, $first, $count )
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

    public function countUserPostByPeriod( $id, $lb, $ub )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $id);
        $ex->andFieldBetween('timestamp', $lb, $ub);
        $ex->andFieldEqual('isDraft', 0);
        $ex->setOrder('`timestamp` DESC');

        return $this->countByExample($ex);
    }

    /**
     * Find latest public list ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findLatestPublicListIds( $first, $count )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('isDraft', 0);
        $ex->andFieldEqual('privacy', 'everybody');
        $ex->setOrder('timestamp desc')->setLimitClause((int) $first, (int) $count);

        return $this->findIdListByExample($ex);
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
        $ex->setOrder('timestamp desc')->setLimitClause((int) $first, (int) $count);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->findListByExample($ex, $cacheLifeTime, $tags);
    }

    public function findListByUser( $first, $count, $userId, $showAll = false  )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $extra = array();
        $extra['select'] = array();
        $extra['select'][] = "*";
        $extra['select'][] = '%subSelect.*';
        $extra['select'][] = '%subSelect.*';
        $extra['select'][] = '%subSelect.*';
        $extra['join'] = "";
        $extra['where'] = "%subSelect.`isDraft` = 0";
        $extra['aggregate'] = "ORDER BY %wholeSelect.`timestamp` DESC";
        $extra['params'] = array();

        $result = $this->findListAllPrivacy($userId,$extra,$showAll,$first,$count);
        return $result;
    }

    public function countPostsByUser($userId, $showAll = false )
    {
        $extra = array();
        $extra['select'] = array();
        $extra['select'][] = "COUNT(*) AS `counts`";
        $extra['select'][] = '%subSelect.*';
        $extra['select'][] = '%subSelect.*';
        $extra['select'][] = '%subSelect.*';
        $extra['join'] = "";
        $extra['where'] = "%subSelect.`isDraft` = 0";
        $extra['aggregate'] = "";
        $extra['params'] = array();

        $result = $this->findListAllPrivacy($userId,$extra,$showAll);
        return $result[0]['counts'];
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
			ON( r.`entityType` = 'blog-post' AND p.id = r.`entityId` )
			WHERE p.isDraft = 0
			GROUP BY p.`id`
			ORDER BY `t` DESC
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
			INNER JOIN ".OW_DB_PREFIX.base_entity_tag." as `et`
				ON(`t`.`id` = `et`.`tagId` AND `et`.`entityType` = 'blog-post')
			INNER JOIN `{$this->getTableName()}` as p
				ON(`et`.`entityId` = `p`.`id`)
			WHERE p.isDraft = 0 AND `t`.`label`=:tag
			ORDER BY
			LIMIT :first, :count";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array('first'=>$first, 'count' => $count, 'tag' => $tag));
//        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function countByTag( $tag )
    {
        $query = "
			SELECT count( * )
			FROM `{$this->getTagTableName()}` as t
			INNER JOIN ".OW_DB_PREFIX.base_entity_tag." as `et`
				ON(`t`.`id` = `et`.`tagId` AND `et`.`entityType` = 'blog-post')
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

    public function findListByIdListAndUser( $list,$userId, $showAll = false  ){
        if(empty($list)){
            return array();
        }
        $listString = $this->dbo->mergeInClause($list);

        $extra = array();
        $extra['select'] = array();
        $extra['select'][] = "*";
        $extra['select'][] = '%subSelect.*';
        $extra['select'][] = '%subSelect.*';
        $extra['select'][] = '%subSelect.*';
        $extra['join'] = "";
        $extra['where'] = "%subSelect.`id` IN (" . $listString . ") ";
        $extra['aggregate'] = "";
        $extra['params'] = array();

        $result = $this->findListAllPrivacy($userId,$extra,$showAll);
        return $result;
    }

    public function updateBlogsPrivacy( $authorId, $privacy )
    {
        $this->clearCache();

        $sql = "UPDATE `" . $this->getTableName() . "` SET `privacy` = :privacy
            WHERE `authorId` = :authorId";

        $this->dbo->query($sql, array('privacy' => $privacy, 'authorId' => $authorId));
    }

    public function clearCache()
    {
        OW::getCacheManager()->clean( array( PostDao::CACHE_TAG_POST_COUNT ));
    }

    public function findMostCommentedBlogList($first, $count, $userId, $showAll = false)
    {
        $entityType = 'blog-post';
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

        $extra = array();
        $extra['select'] = array();
        $extra['select'][] = "%wholeSelect.`entityId` AS `id`, COUNT(*) AS `commentCount`";
        $extra['select'][] = '`ce`.`entityId`';
        $extra['select'][] = '`ce`.`entityId`';
        $extra['select'][] = '`ce`.`entityId`';
        $extra['join'] = "
            LEFT JOIN `".BOL_CommentEntityDao::getInstance()->getTableName()."` AS `ce` ON %subSelect.`id` = `ce`.`entityId`
            LEFT JOIN `" . BOL_CommentDao::getInstance()->getTableName() . "` AS `c` ON (`c`.`" . BOL_CommentDao::COMMENT_ENTITY_ID . "` = `ce`.`id`)
        ";
        $extra['where'] = "`ce`.`" . BOL_CommentEntityDao::ENTITY_TYPE . "` = :entityType AND `ce`.`" . BOL_CommentEntityDao::ACTIVE . "` = 1 AND %subSelect.`isDraft` = 0 AND ". $queryParts['where'] . $privacyConditionWhere;
        $extra['aggregate'] = "
            GROUP BY %wholeSelect.`" . BOL_CommentEntityDao::ENTITY_ID . "`
			ORDER BY `commentCount` DESC, `id` DESC
        ";
        $extra['params'] = array('entityType' => $entityType);
        if(isset($privacyConditionEvent->getData()['params']) && is_array($privacyConditionEvent->getData()['params']) && sizeof($privacyConditionEvent->getData()['params'])>0){
            $extra['params'] = array_merge($extra['params'], $privacyConditionEvent->getData()['params']);
        }
        $result = $this->findListAllPrivacy($userId,$extra,$showAll,$first,$count);
        return $result;
    }

    public function findCommentedBlogCount($userId, $showAll = false )
    {
        $extra = array();
        $extra['select'] = array();
        $extra['select'][] = "COUNT(*) AS `counts`";
        $extra['select'][] = '%subSelect.*';
        $extra['select'][] = '%subSelect.*';
        $extra['select'][] = '%subSelect.*';
        $extra['join'] = "LEFT JOIN ".BOL_CommentEntityDao::getInstance()->getTableName()." AS `ce` ON %subSelect.`id` = `ce`.`entityId`";
        $extra['where'] = "`ce`.`" . BOL_CommentEntityDao::ENTITY_TYPE . "` = :entityType AND `ce`.`" . BOL_CommentEntityDao::ACTIVE . "` = 1 AND %subSelect.`isDraft` = 0";
        $extra['aggregate'] = "";
        $extra['params'] = array('entityType' => 'blog-post');
        $result = $this->findListAllPrivacy($userId,$extra,$showAll);
        return (int) $result[0]['counts'];
    }

    public function findMostRatedBlogList($first, $count,$userId, $showAll = false , $exclude )
    {
        $entityType = 'blog-post';
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


        $extra = array();
        $extra['select'] = array();
        $extra['select'][] = "%wholeSelect.`" . BOL_RateDao::ENTITY_ID . "` AS `id`, COUNT(*) as `ratesCount`, AVG(%wholeSelect.`score`) as `avgScore`";
        $extra['select'][] = '`r`.*';
        $extra['select'][] = '`r`.*';
        $extra['select'][] = '`r`.*';
        $extra['join'] = "LEFT JOIN `".BOL_RateDao::getInstance()->getTableName() . "` AS `r` ON %subSelect.`id` = `r`.`entityId`";
        $extra['where'] = "`r`.`" . BOL_RateDao::ENTITY_TYPE . "` = :entityType AND %subSelect.`isDraft` = 0 AND `r`.`" . BOL_RateDao::ACTIVE . "` = 1 ".$excludeCond . " AND " . $queryParts['where'] . $privacyConditionWhere;
        $extra['aggregate'] = "
            GROUP BY %wholeSelect.`" . BOL_RateDao::ENTITY_ID . "`
            ORDER BY `avgScore` DESC, `ratesCount` DESC, MAX(%wholeSelect.`timeStamp`) DESC
        ";
        $extra['params'] = array('entityType' => $entityType);
        if (isset($queryParts['params']))
            $extra['params'] = array_merge($extra['params'], $queryParts['params']);
        if(isset($privacyConditionEvent->getData()['params']) && is_array($privacyConditionEvent->getData()['params']) && sizeof($privacyConditionEvent->getData()['params'])>0){
            $extra['params'] = array_merge($extra['params'], $privacyConditionEvent->getData()['params']);
        }
        $result = $this->findListAllPrivacy($userId,$extra,$showAll,$first,$count);
        return $result;
    }

    public function findMostRatedBlogCount($userId, $showAll = false ,$exclude = null )
    {
        $entityType = 'blog-post';
        $excludeCond = $exclude ? ' AND `r`.`' . BOL_RateDao::ENTITY_ID . '` NOT IN (' . BOL_RateDao::getInstance()->dbo->mergeInClause($exclude) . ')' : '';
        $privacyConditionWhere = '';
        $privacyConditionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_CONTENT_LIST_QUERY_EXECUTE, array('entityType' => $entityType, 'rateTableName' => '`r`', 'listType' => 'rateDao')));
        if(isset($privacyConditionEvent->getData()['where'])){
            $privacyConditionWhere = $privacyConditionEvent->getData()['where'];
        }

        $extra = array();
        $extra['select'] = array();
        $extra['select'][] = 'COUNT(DISTINCT %wholeSelect.`' . BOL_RateDao::ENTITY_ID . '`) AS cnt';
        $extra['select'][] = '`r`.*';
        $extra['select'][] = '`r`.*';
        $extra['select'][] = '`r`.*';
        $extra['join'] = "LEFT JOIN `".BOL_RateDao::getInstance()->getTableName() . "` AS `r` ON %subSelect.`id` = `r`.`entityId`";
        $extra['where'] = "`r`.`" . BOL_RateDao::ENTITY_TYPE . "` = :entityType AND %subSelect.`isDraft` = 0 AND `r`.`" . BOL_RateDao::ACTIVE . "` = 1 ".$excludeCond . $privacyConditionWhere;
        $extra['aggregate'] = "";
        $extra['params'] = array('entityType' => $entityType);
        if (isset($privacyConditionEvent->getData()['params']) && is_array($privacyConditionEvent->getData()['params']) && sizeof($privacyConditionEvent->getData()['params'])>0)
            $extra['params'] = array_merge($extra['params'], $privacyConditionEvent->getData()['params']);

        $result = $this->findListAllPrivacy($userId,$extra,$showAll);
        return (int) $result[0]['cnt'];
    }

    public function findBlogListByTag( $tag, $first, $count )
    {
        $entityType = 'blog-post';
        $privacyConditionWhere = '';
        $privacyConditionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_CONTENT_LIST_QUERY_EXECUTE, array('tagEntityTableName' => '`et`', 'entityType' => $entityType, 'listType' => 'tag')));
        if(isset($privacyConditionEvent->getData()['where'])){
            $privacyConditionWhere = $privacyConditionEvent->getData()['where'];
        }
        $query = "SELECT `et`.`" . BOL_EntityTagDao::ENTITY_ID . "` AS `id` from `" . BOL_TagDao::getInstance()->getTableName() . "` AS `t` 
                    INNER JOIN `" . BOL_EntityTagDao::getInstance()->getTableName() . "` AS `et` ON(`et`.`" . BOL_EntityTagDao::TAG_ID . "`=`t`.`id`)
                    LEFT JOIN `".$this->getTableName()."` AS `bp` ON `bp`.`id` = `et`.`entityId`
                WHERE `t`.`" . BOL_TagDao::LABEL . "` LIKE :tag AND `bp`.`isDraft` = 0 AND `et`.`" . BOL_EntityTagDao::ENTITY_TYPE . "` = :entityType AND `et`.`" . BOL_EntityTagDao::ACTIVE . "` = 1" . $privacyConditionWhere . "
                ORDER BY `et`.`entityId` DESC
                LIMIT :first, :count";
        $params = array('tag' => '%'.$tag.'%', 'entityType' => $entityType, 'first' => (int) $first, 'count' => (int) $count);
        if(isset($privacyConditionEvent->getData()['params']) && is_array($privacyConditionEvent->getData()['params']) && sizeof($privacyConditionEvent->getData()['params'])>0){
            $params = array_merge($params, $privacyConditionEvent->getData()['params']);
        }
        return $this->dbo->queryForColumnList($query, $params);
    }

    public function findBlogCountByTag( $tag )
    {
        $entityType = 'blog-post';
        $query = "SELECT COUNT(*) from `" . BOL_TagDao::getInstance()->getTableName() . "` AS `t` 
                    INNER JOIN `" . BOL_EntityTagDao::getInstance()->getTableName() . "` AS `et` ON(`et`.`" . BOL_EntityTagDao::TAG_ID . "`=`t`.`id`)
                    LEFT JOIN `".$this->getTableName()."` AS `bp` ON `bp`.`id` = `et`.`entityId`
                where `t`.`" . BOL_TagDao::LABEL . "` = :tag AND `bp`.`isDraft` = 0 AND `et`.`" . BOL_EntityTagDao::ENTITY_TYPE . "` = :entityType AND `et`.`" . BOL_EntityTagDao::ACTIVE . "` = 1";

        return (int) $this->dbo->queryForColumn($query, array('tag' => $tag, 'entityType' => $entityType));
    }

    public function findListAllPrivacy( $userId, $extra = null, $showAll = false ,$first = -1, $count = -1)
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = PHP_INT_MAX;
        }

        if (!isset($extra))
            $extra = array('join' => '', 'where' => '', 'select' => array('','','',''), 'aggregate' => '', 'params' => array());

        $data = array(
            '%wholeSelect' => '`posts`',
            '%subSelect' => '`p`',
        );

        $friendClause="";
        $friendsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('friends');
        if(isset($friendsPlugin) && $friendsPlugin->isActive()) {
            $friendClause = "UNION   
              SELECT " . str_replace(array_keys($data), array_values($data), $extra['select'][3]) . "
                FROM `" . $this->getTableName() . "` AS `p`
                    " . str_replace(array_keys($data), array_values($data), $extra['join']) . "
                    LEFT JOIN " . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . " AS `f1` ON `f1`.`friendId` = `p`.`authorId`
                    LEFT JOIN " . FRIENDS_BOL_FriendshipDao::getInstance()->getTableName() . " AS `f2` ON `f2`.`userId` = `p`.`authorId`
                WHERE `p`.`privacy` = 'friends_only' AND (`f1`.`userId` = :userId OR `f2`.`friendId` = :userId OR `p`.`authorId` = :userId OR :showAll) AND (" . str_replace(array_keys($data), array_values($data), $extra['where']) . ")";
        }
        $query = "SELECT ".str_replace(array_keys($data), array_values($data),$extra['select'][0])."
            FROM( 
              SELECT ".str_replace(array_keys($data), array_values($data),$extra['select'][1])."
                FROM `" . $this->getTableName() . "` AS `p`
                    ".str_replace(array_keys($data), array_values($data),$extra['join'])."
                WHERE `p`.`privacy` = 'everybody' AND (".str_replace(array_keys($data), array_values($data),$extra['where']).")
                
              UNION
                
              SELECT ".str_replace(array_keys($data), array_values($data),$extra['select'][2])."
                FROM `" . $this->getTableName() . "` AS `p`
                    ".str_replace(array_keys($data), array_values($data),$extra['join'])."
                WHERE `p`.`privacy` = 'only_for_me' AND (`p`.`authorId` = :userId OR :showAll) AND (".str_replace(array_keys($data), array_values($data),$extra['where']).")
               
            ".$friendClause."
            ) AS `posts`
              ".str_replace(array_keys($data), array_values($data),$extra['aggregate'])."
              LIMIT :firstRow, :countRow
            ";

        $count = (int) $count;
        $params = array(
            'userId'=>$userId,
            'firstRow'=>$first,
            'countRow'=> $count,
            'showAll'=>$showAll
        );

        $params = array_merge($params, $extra['params']);

        return $this->dbo->queryForList($query, $params);
    }
}
