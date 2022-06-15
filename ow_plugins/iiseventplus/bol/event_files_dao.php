<?php



/**
 * Data Access Object for `iiseventplus_event_files` table.
 *
 * @author Mohammad
 * @package ow_plugins.iiseventplus.bol
 * @since 1.0
 */
class IISEVENTPLUS_BOL_EventFilesDao extends OW_BaseDao
{

    /**
     * Constructor.
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }
    /**
     * Singleton instance.
     *
     * @var IISEVENTPLUS_BOL_EventFilesDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return IISEVENTPLUS_BOL_EventFilesDao
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
        return 'IISEVENTPLUS_BOL_EventFiles';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'iiseventplus_event_files';
    }

    public function getEventFilesByEventId($eventId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('eventId', $eventId);
        return $this->findListByExample($example);
    }

    public function deleteEventFilesByAidAndEid($eventId, $attachmentId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('attachmentId', $attachmentId);
        $example->andFieldEqual('eventId', $eventId);
        return $this->deleteByExample($example);
    }
    public function findFileIdByAidAndEid($eventId, $attachmentId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('attachmentId', $attachmentId);
        $example->andFieldEqual('eventId', $eventId);
        return $this->findIdByExample($example);
    }
    public function deleteEventFilesByEventId($eventId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('eventId', $eventId);
        return $this->deleteByExample($example);
    }
    public function addFileForEvent($eventId, $attachmentId)
    {
        $eventFiles = new IISEVENTPLUS_BOL_EventFiles();
        $eventFiles->setEventId($eventId);
        $eventFiles->setAttachmentId($attachmentId);
        $this->save($eventFiles);
        return $eventFiles->getId();
    }

    public function findFileListByEventId( $eventId, $first, $count )
    {

        $query = "SELECT u.* FROM " . $this->getTableName() . " u WHERE u.eventId=:g ORDER BY u.id DESC LIMIT :lf, :lc";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            "g" => $eventId,
            "lf" => $first,
            "lc" => $count
        ));
    }

    public function findCountByEventId( $eventId )
    {

        $query = "SELECT COUNT(*) FROM " . $this->getTableName() . " u WHERE u.eventId=:g";

        return $this->dbo->queryForColumn($query, array(
            "g" => $eventId
        ));
    }

    public function findAllFiles()
    {
        $query = "SELECT * FROM " . $this->getTableName();

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName());
    }
}