<?php

/**
 * @author Hamid Haghshenas <hamid.haghshenas@gmail.com>
 * @package ow_plugins.forum.bol
 * @since 10845
 */
class FORUM_BOL_UpdateSearchIndexService
{
    use OW_Singleton;

    /**
     * Delete group
     */
    const DELETE_GROUP = 'delete_group';
    /**
     * Normal priority
     */
    const NORMAL_PRIORITY = 0;
    /**
     * Update topic posts
     */
    const UPDATE_TOPIC_POSTS = 'update_topic_posts';
    /**
     * High priority
     */
    const HIGH_PRIORITY = 1;
    /**
     * Update group
     */
    const UPDATE_GROUP = 'update_group';
    /**
     * Delete topic
     */
    const DELETE_TOPIC = 'delete_topic';
    /**
     * Update topic
     */
    const UPDATE_TOPIC = 'update_topic';

    private $updateSearchIndexDao;

    private function __construct()
    {
        $this->updateSearchIndexDao = FORUM_BOL_UpdateSearchIndexDao::getInstance();
    }

    /**
     * Find first queue
     *
     * @return FORUM_BOL_UpdateSearchIndex
     */
    public function findFirstQueue()
    {
        return $this->updateSearchIndexDao->findFirstQueue();
    }

    /**
     * @param $queue FORUM_BOL_UpdateSearchIndex
     */
    public function saveQueue($queue)
    {
        $this->updateSearchIndexDao->save($queue);
        OW::getEventManager()->trigger(new OW_Event(FORUM_BOL_ForumService::EVENT_UPDATE_SEARCH_INDEX_QUEUED));
    }

    /**
     * @param $queue FORUM_BOL_UpdateSearchIndex
     */
    public function deleteQueue($queue)
    {
        $this->updateSearchIndexDao->delete($queue);
    }

    /**
     * Add a queue
     *
     * @param integer $entityId
     * @param string $type
     * @param integer $priority
     * @return void
     */
    public function addQueue($entityId, $type, $priority = FORUM_BOL_UpdateSearchIndexService::NORMAL_PRIORITY)
    {
        $this->updateSearchIndexDao->addQueue($entityId, $type, $priority);
        OW::getEventManager()->trigger(new OW_Event(FORUM_BOL_ForumService::EVENT_UPDATE_SEARCH_INDEX_QUEUED));
    }
}