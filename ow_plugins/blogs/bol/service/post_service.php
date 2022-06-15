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
 * @package ow_plugins.blogs.bol.service
 * @since 1.0
 */
class PostService
{
    const FEED_ENTITY_TYPE = 'blog-post';
    const PRIVACY_ACTION_VIEW_BLOG_POSTS = 'blogs_view_blog_posts';
    const PRIVACY_ACTION_COMMENT_BLOG_POSTS = 'blogs_comment_blog_posts';

    const POST_STATUS_PUBLISHED = 0;
    const POST_STATUS_DRAFT = 1;
    const POST_STATUS_DRAFT_WAS_NOT_PUBLISHED = 2;
    const POST_STATUS_APPROVAL = 3;

    const EVENT_AFTER_DELETE = 'blogs.after_delete';
    const EVENT_BEFORE_DELETE = 'blogs.before_delete';
    const EVENT_AFTER_EDIT = 'blogs.after_edit';
    const EVENT_AFTER_ADD = 'blogs.after_add';

    const EVENT_UNINSTALL_IN_PROGRESS = 'blogs.uninstall_in_progress';

    /*
     * @var BLOG_BOL_BlogService
     */
    private static $classInstance;

    /**
     * @var array
     */
    private $config = array();

    /*
      @var PostDao
     */
    private $dao;

    private function __construct()
    {
        $this->dao = PostDao::getInstance();

        $this->config['allowedMPElements'] = array();
    }

    public function getConfig()
    {
        return $this->config;
    }

        /**
     * Returns class instance
     *
     * @return PostService
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
            self::$classInstance = new self();

        return self::$classInstance;
    }

    public function save( $dto )
    {
        $dao = $this->dao;

        return $dao->save($dto);
    }

    /**
     * @return Post
     */
    public function findById( $id )
    {
        $dao = $this->dao;

        return $dao->findById($id);
    }

    //<USER-BLOG>

    private function deleteByAuthorId( $userId ) // do not use it!!
    {
        //$this->dao->deleteByAuthorId($userId);
    }
    /*
     * $which can take on of two following 'next', 'prev' values
     */

    public function findAdjacentUserPost( $id, $postId, $which )
    {
        return $this->dao->findAdjacentUserPost($id, $postId, $which);
    }

    public function findUserPostList( $userId, $first, $count )
    {
        return $this->dao->findUserPostList($userId, $first, $count);
    }

    public function findUserDraftList( $userId, $first, $count )
    {
        return $this->dao->findUserDraftList($userId, $first, $count);
    }

    public function countUserPost( $userId )
    {
        return $this->dao->countUserPost($userId);
    }

    public function countUserPostComment( $userId )
    {
        return $this->dao->countUserPostComment($userId);
    }

    public function countUserDraft( $userId )
    {
        return $this->dao->countUserDraft($userId);
    }

    public function findUserPostCommentList( $userId, $first, $count )
    {
        return $this->dao->findUserPostCommentList($userId, $first, $count);
    }

    public function findUserLastPost( $userId )
    {
        return $this->dao->findUserLastPost($userId);
    }

    public function findUserArchiveData( $id )
    {
        return $this->dao->findUserArchiveData($id);
    }

    public function findUserPostListByPeriod( $id, $lb, $ub, $first, $count )
    {
        return $this->dao->findUserPostListByPeriod($id, $lb, $ub, $first, $count);
    }

    public function countUserPostByPeriod( $id, $lb, $ub )
    {
        return $this->dao->countUserPostByPeriod($id, $lb, $ub);
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
        return $this->dao->findLatestPublicListIds($first, $count);
    }

    //</USER-BLOG>
    //<SITE-BLOG>
    public function findList( $first, $count )
    {
        return $this->dao->findList($first, $count);
    }

    public function findListByUser( $first, $count)
    {
        list($userId,$showAll) = $this->userIsAuthorized();
        $result = $this->dao->findListByUser($first, $count, $userId,$showAll);
        $output = array();
        foreach ($result as $postArray) {
            $post = new Post();
            $post->id = $postArray['id'];
            $post->post = $postArray['post'];
            $post->authorId = $postArray['authorId'];
            $post->isDraft = $postArray['isDraft'];
            $post->privacy = $postArray['privacy'];
            $post->timestamp = $postArray['timestamp'];
            $post->title = $postArray['title'];
            $output[] = $post;
        }
        return $output;
    }

    public function countAll()
    {
        return $this->dao->countAll();
    }

    public function countPosts()
    {
        return $this->dao->countPosts();
    }

    public function countPostsByUser()
    {
        list($userId,$showAll) = $this->userIsAuthorized();
        return $this->dao->countPostsByUser($userId,$showAll);
    }

    public function findTopRatedList( $first, $count )
    {
        return $this->dao->findTopRatedList($first, $count);
    }

    public function findListByTag( $tag, $first, $count )
    {
        return $this->dao->findListByTag($tag, $first, $count);
    }

    public function countByTag( $tag )
    {
        return $this->dao->countByTag($tag);
    }

    public function delete( Post $dto )
    {
        $this->deletePost($dto->getId());
    }

    //</SITE-BLOG>

    public function findListByIdList( $list )
    {
        return $this->dao->findListByIdList($list);
    }

    public function findListByIdListAndUser( $list )
    {
        list($userId,$showAll) = $this->userIsAuthorized();
        $result = $this->dao->findListByIdListAndUser($list,$userId,$showAll);
        $output = array();
        foreach ($list as $id){
            foreach ($result as $postArray){
                if($postArray['id'] == $id) {
                    $post = new Post();
                    $post->id = $postArray['id'];
                    $post->post = $postArray['post'];
                    $post->authorId = $postArray['authorId'];
                    $post->isDraft = $postArray['isDraft'];
                    $post->privacy = $postArray['privacy'];
                    $post->timestamp = $postArray['timestamp'];
                    $post->title = $postArray['title'];
                    $output[] = $post;
                    break;
                }
            }
        }
        return $output;
    }

    public function onAuthorSuspend( OW_Event $event )
    {
        $params = $event->getParams();
    }

    /**
     * Get set of allowed tags for blogs
     *
     * @return array
     */
    public function getAllowedHtmlTags()
    {
        return array("object", "embed", "param", "strong", "i", "u", "a", "!--more--", "img", "blockquote", "span", "pre", "iframe");
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
        return $this->dao->findLatestPublicPostsAuthorsIds($first, $count);
    }

    public function updateBlogsPrivacy( $userId, $privacy )
    {
        $count = $this->countUserPost($userId);
        $entities = PostService::getInstance()->findUserPostList($userId, 0, $count);
        $entityIds = array();

        foreach ($entities as $post)
        {
            $entityIds[] = $post->getId();
        }

        $status = ( $privacy == 'everybody' ) ? true : false;

        $event = new OW_Event('base.update_entity_items_status', array(
            'entityType' => 'blog-post',
            'entityIds' => $entityIds,
            'status' => $status,
        ));
        OW::getEventManager()->trigger($event);

        $this->dao->updateBlogsPrivacy( $userId, $privacy );
        OW::getCacheManager()->clean( array( PostDao::CACHE_TAG_POST_COUNT ));
    }

    public function processPostText($text)
    {
        $text = str_replace('&nbsp;', ' ', $text);
        $text = strip_tags($text);
        return $text;
    }

    public function findUserNewCommentCount($userId)
    {
        return $this->dao->countUserPostNewComment($userId);
    }

    public function deletePost($postId)
    {
        BOL_CommentService::getInstance()->deleteEntityComments('blog-post', $postId);
        BOL_RateService::getInstance()->deleteEntityRates($postId, 'blog-post');
        BOL_TagService::getInstance()->deleteEntityTags($postId, 'blog-post');
        BOL_FlagService::getInstance()->deleteByTypeAndEntityId(BLOGS_CLASS_ContentProvider::ENTITY_TYPE, $postId);

        OW::getCacheManager()->clean( array( PostDao::CACHE_TAG_POST_COUNT ));

        OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array('entityType' => 'blog-post', 'entityId' => $postId)));

        $this->dao->deleteById($postId);
        OW::getLogger()->writeLog(OW_Log::INFO, 'delete_blog', ['actionType'=>OW_Log::DELETE, 'enType'=>'blog', 'enId'=>$postId]);
    }

    public function findPostListByIds($postIds)
    {
        return $this->dao->findByIdList($postIds);
    }

    public function getPostUrl($post)
    {
        return OW::getRouter()->urlForRoute('post', array('id'=>$post->getId()));
    }

    public function findIdListBySearch( $q, $first, $count )
    {
        $ex = new OW_Example();
        $ex->andFieldLike('title', '%'.$q.'%');
        $ex->setOrder('timestamp desc')->setLimitClause(0, $first + $count);
        $list1 = $this->dao->findIdListByExample($ex);

        $ex = new OW_Example();
        $ex->andFieldLike('post', '%'.$q.'%');
        $ex->setOrder('timestamp desc')->setLimitClause(0, $first + $count);
        $list2 = $this->dao->findIdListByExample($ex);

        $list = array_unique(array_merge($list1, $list2));
        return array_splice($list, $first, $count );
    }

    public function onCollectSearchItems(OW_Event $event){
        if (!OW::getUser()->isAdmin() && !OW::getUser()->isAuthorized('blogs', 'view'))
        {
            return;
        }
        $searchValue = '';
        $params = $event->getParams();
        if ( !empty($params['q']) )
        {
            $searchValue = $params['q'];
        }
        $maxCount = empty($params['maxCount'])?10:$params['maxCount'];
        $first= empty($params['first'])?0:$params['first'];
        $first=(int)$first;
        $count=empty($params['count'])?$first+$maxCount:$params['count'];
        $count=(int)$count;

        $idList = $this->findIdListBySearch(strip_tags(UTIL_HtmlTag::stripTags($searchValue)), $first, $count);

        $resultData = $this->findListByIdListAndUser($idList);

        $result = array();
        $count = 0;
        foreach($resultData as $item){
            /* @var $item ‌Post */
            if($item->isDraft)
                continue;
            $itemInformation = array();
            $itemInformation['title'] = $item->getTitle();
            $itemInformation['id'] = $item->id;
            $itemInformation['userId'] = $item->getAuthorId();
            $itemInformation['createdDate'] = $item->getTimestamp();
            $itemInformation['link'] = $this->getPostUrl($item);
            $itemInformation['label'] = OW::getLanguage()->text('iisadvancesearch', 'blogs_label');
            $result[] = $itemInformation;
            $count++;
            if($count == $maxCount){
                break;
            }
        }

        $data = $event->getData();
        $data['blogs'] = array('label' => OW::getLanguage()->text('iisadvancesearch', 'blogs_label'), 'data' => $result);
        $event->setData($data);
    }

    public function getBlogList($case, $first, $count )
    {

        $list = array();
        $itemsCount = 0;
        switch ( $case )
        {
            case 'most-discussed':
                $info = $this->findMostCommentedBlogList($first, $count);
                $idList = array();
                foreach ( $info as $item )
                {
                    $idList[] = $item['id'];
                }

                if ( empty($idList) )
                {
                    break;
                }

                $dtoList = $this->findListByIdListAndUser($idList);

                foreach ( $dtoList as $dto )
                {
                    $info[$dto->id]['dto'] = $dto;

                    $list[] = array(
                        'dto' => $dto,
                        'commentCount' => $info[$dto->id] ['commentCount'],
                    );
                }

                function sortMostCommented( $e, $e2 )
                {
                    return $e['commentCount'] < $e2['commentCount'];
                }
                usort($list, 'sortMostCommented');

                $itemsCount = $this->findCommentedBlogCount();

                break;

            case 'top-rated':
                $info = $this->findMostRatedBlogList($first, $count);

                $idList = array();

                foreach ( $info as $item )
                {
                    $idList[] = $item['id'];
                }

                if ( empty($idList) )
                {
                    break;
                }

                $dtoList = $this->findListByIdListAndUser($idList);

                foreach ( $dtoList as $dto )
                {
                    $list[] = array(
                        'dto' => $dto,
                        'avgScore' => $info[$dto->id] ['avgScore'],
                        'ratesCount' => $info[$dto->id] ['ratesCount']
                    );
                }

                function sortTopRated( $e, $e2 )
                {
                    if ($e['avgScore'] == $e2['avgScore'])
                    {
                        if ($e['ratesCount'] == $e2['ratesCount'])
                        {
                            return 0;
                        }

                        return $e['ratesCount'] < $e2['ratesCount'];
                    }
                    return $e['avgScore'] < $e2['avgScore'];
                }
                usort($list, 'sortTopRated');

                $itemsCount = $this->findMostRatedBlogCount();

                break;

            case 'browse-by-tag':
                if ( empty($_GET['tag']) )
                {
                    $mostPopularTagsArray = BOL_TagService::getInstance()->findMostPopularTags('blog-post', 20);
                    $mostPopularTags = "";

                    foreach ( $mostPopularTagsArray as $tag )
                    {
                        $mostPopularTags .= $tag['label'] . ", ";
                    }
                    break;
                }

                $info = PostDao::getInstance()->findBlogListByTag(strip_tags(UTIL_HtmlTag::stripTags($_GET['tag'])), $first, $count);

                $itemsCount = PostDao::getInstance()->findBlogCountByTag(strip_tags(UTIL_HtmlTag::stripTags($_GET['tag'])));

                foreach ( $info as $item )
                {
                    $idList[] = $item;
                }

                if ( empty($idList) )
                {
                    break;
                }

                $dtoList = $this->findListByIdListAndUser($idList);

                function sortByTimestamp( $post1, $post2 )
                {
                    return $post1->timestamp < $post2->timestamp;
                }
                usort($dtoList, 'sortByTimestamp');


                foreach ( $dtoList as $dto )
                {
                    $list[] = array('dto' => $dto);
                }
                break;

            case 'latest':
                $arr = $this->findListByUser($first, $count);

                foreach ( $arr as $item )
                {
                    $list[] = array('dto' => $item);
                }

                $itemsCount = $this->countPostsByUser();

                break;
        }

        return array($list, $itemsCount);
    }

    public function findMostCommentedBlogList($first, $count)
    {
        list($userId,$showAll) = $this->userIsAuthorized();
        $resultArray = PostDao::getInstance()->findMostCommentedBlogList($first, $count, $userId,$showAll);

        $resultList = array();

        foreach ( $resultArray as $item )
        {
            $resultList[$item['id']] = $item;
        }

        return $resultList;
    }

    public function findMostRatedBlogList($first, $count, $exclude = null)
    {
        list($userId,$showAll) = $this->userIsAuthorized();
        $arr = PostDao::getInstance()->findMostRatedBlogList($first, $count, $userId,$showAll, $exclude);

        $resultArray = array();

        foreach ( $arr as $value )
        {
            $resultArray[$value['id']] = $value;
        }

        return $resultArray;
    }

    public function userIsAuthorized(){
        $userId = null;
        $showAll= false;
        $user = OW::getUser();
        if(isset($user)) {
            $userId = $user->getId();
            if($user->isAdmin() || $user->isAuthorized('blogs'))
                $showAll = true;
        }
        return array($userId,$showAll);
    }

    public function findCommentedBlogCount(){
        list($userId,$showAll) = $this->userIsAuthorized();
        return PostDao::getInstance()->findCommentedBlogCount($userId,$showAll);
    }

    public function findMostRatedBlogCount(){
        list($userId,$showAll) = $this->userIsAuthorized();
        return PostDao::getInstance()->findMostRatedBlogCount($userId,$showAll);
    }
}