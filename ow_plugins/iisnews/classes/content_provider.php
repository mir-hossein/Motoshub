<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow.ow_plugins.iisnews
 * @since 1.7.2
 */
class IISNEWS_CLASS_ContentProvider
{
    const ENTITY_TYPE = EntryService::FEED_ENTITY_TYPE;

    /**
     * Singleton instance.
     *
     * @var IISNEWS_CLASS_ContentProvider
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return IISNEWS_CLASS_ContentProvider
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
     *
     * @var EntryService
     */
    private $service;

    private function __construct()
    {
        $this->service = EntryService::getInstance();
    }

    public function onCollectTypes( BASE_CLASS_EventCollector $event )
    {
        $event->add(array(
            "pluginKey" => "iisnews",
            "group" => "iisnews",
            "groupLabel" => OW::getLanguage()->text("iisnews", "content_news_label"),
            "entityType" => self::ENTITY_TYPE,
            "entityLabel" => OW::getLanguage()->text("iisnews", "content_news_label"),
            "displayFormat" => "content"
        ));
    }

    public function onGetInfo( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params["entityType"] != self::ENTITY_TYPE )
        {
            return;
        }

        $entrys = $this->service->findEntryListByIds($params["entityIds"]);
        $out = array();
        /**
         * @var Entry $entry
         */
        foreach ( $entrys as $entry )
        {
            $info = array();

            $info["id"] = $entry->id;
            $info["userId"] = $entry->authorId;
            $info["title"] = $entry->title;
            $info["description"] = $entry->entry;
            $info["url"] = $this->service->getEntryUrl($entry);
            $info["timeStamp"] = $entry->timestamp;

            $out[$entry->id] = $info;
        }

        $event->setData($out);

        return $out;
    }

    public function onUpdateInfo( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        if ( $params["entityType"] != self::ENTITY_TYPE )
        {
            return;
        }

        foreach ( $data as $entryId => $info )
        {
            $status = $info["status"] == BOL_ContentService::STATUS_APPROVAL ? EntryService::POST_STATUS_APPROVAL : EntryService::POST_STATUS_PUBLISHED;

            $entityDto = $this->service->findById($entryId);
            $entityDto->isDraft = $status;

            $this->service->save($entityDto);

            // Set tags status
            $tagActive = ($info["status"] == BOL_ContentService::STATUS_APPROVAL) ? false : true;
            BOL_TagService::getInstance()->setEntityStatus(self::ENTITY_TYPE, $entryId, $tagActive);
        }
    }

    public function onDelete( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params["entityType"] != self::ENTITY_TYPE )
        {
            return;
        }

        foreach ( $params["entityIds"] as $entryId )
        {
            $this->service->deleteEntry($entryId);
        }
    }

    public function onBeforeEntryDelete( OW_Event $event )
    {
        $params = $event->getParams();

        OW::getEventManager()->trigger(new OW_Event(BOL_ContentService::EVENT_BEFORE_DELETE, array(
            "entityType" => self::ENTITY_TYPE,
            "entityId" => $params["entryId"]
        )));
    }

    public function onAfterEntryAdd( OW_Event $event )
    {
        $params = $event->getParams();

        OW::getEventManager()->trigger(new OW_Event(BOL_ContentService::EVENT_AFTER_ADD, array(
            "entityType" => self::ENTITY_TYPE,
            "entityId" => $params["entryId"]
        ), array(
            "string" => array("key" => "iisnews+feed_add_item_label")
        )));
    }

    public function onAfterEntryEdit( OW_Event $event )
    {
        $params = $event->getParams();

        OW::getEventManager()->trigger(new OW_Event(BOL_ContentService::EVENT_AFTER_CHANGE, array(
            "entityType" => self::ENTITY_TYPE,
            "entityId" => $params["entryId"]
        ), array(
            "string" => array("key" => "iisnews+feed_edit_item_label")
        )));
    }

    public function init()
    {
        OW::getEventManager()->bind(EntryService::EVENT_BEFORE_DELETE, array($this, "onBeforeEntryDelete"));
        OW::getEventManager()->bind(EntryService::EVENT_AFTER_ADD, array($this, "onAfterEntryAdd"));
        OW::getEventManager()->bind(EntryService::EVENT_AFTER_EDIT, array($this, "onAfterEntryEdit"));

        OW::getEventManager()->bind(BOL_ContentService::EVENT_COLLECT_TYPES, array($this, "onCollectTypes"));
        OW::getEventManager()->bind(BOL_ContentService::EVENT_GET_INFO, array($this, "onGetInfo"));
        OW::getEventManager()->bind(BOL_ContentService::EVENT_UPDATE_INFO, array($this, "onUpdateInfo"));
        OW::getEventManager()->bind(BOL_ContentService::EVENT_DELETE, array($this, "onDelete"));
    }
}