<?php



/**
 * Data Access Object for `iisgroupsplus_group_managers` table.
 *
 * @author Mohammad
 * @package ow_plugins.iisgroupsplus.bol
 * @since 1.0
 */
class IISGROUPSPLUS_BOL_GroupFilesDao extends OW_BaseDao
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
     * @var IISGROUPSPLUS_BOL_GroupFilesDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return IISGROUPSPLUS_BOL_GroupFilesDao
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
        return 'IISGROUPSPLUS_BOL_GroupFiles';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisgroupsplus_group_files';
    }

    public function getGroupFilesByGroupId($groupId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('groupId', $groupId);
        return $this->findListByExample($example);
    }

    public function deleteGroupFilesByAidAndGid($groupId, $attachmentId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('attachmentId', $attachmentId);
        $example->andFieldEqual('groupId', $groupId);
        return $this->deleteByExample($example);
    }
    public function findFileIdByAidAndGid($groupId, $attachmentId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('attachmentId', $attachmentId);
        $example->andFieldEqual('groupId', $groupId);
        return $this->findIdByExample($example);
    }
    public function deleteGroupFilesByGroupId($groupId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('groupId', $groupId);
        return $this->deleteByExample($example);
    }
    public function addFileForGroup($groupId, $attachmentId)
    {
        $groupFiles = new IISGROUPSPLUS_BOL_GroupFiles();
        $groupFiles->setGroupId($groupId);
        $groupFiles->setAttachmentId($attachmentId);
        $this->save($groupFiles);

        OW::getEventManager()->trigger(new OW_Event('groups.group.content.update', array('action' => 'add_file', 'groupId' => $groupId, 'attachmentId' => $attachmentId)));

        return $groupFiles->getId();
    }

    public function findFileListByGroupId( $groupId, $first, $count )
    {
        $first = (int) $first;
        $count = (int) $count;
        $query = "SELECT u.* FROM " . $this->getTableName() . " u WHERE u.groupId=:g ORDER BY u.id DESC LIMIT :lf, :lc";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array(
            "g" => $groupId,
            "lf" => $first,
            "lc" => $count
        ));
    }

    public function findAttachmentIdListByGroupId( $groupId, $first, $count )
    {
        $first = (int) $first;
        $count = (int) $count;
        $query = "SELECT u.attachmentId FROM " . $this->getTableName() . " u WHERE u.groupId=:g ORDER BY u.id DESC LIMIT :lf, :lc";

        return $this->dbo->queryForColumnList($query, array(
            "g" => $groupId,
            "lf" => $first,
            "lc" => $count
        ));
    }

    public function findCountByGroupId( $groupId )
    {

        $query = "SELECT COUNT(*) FROM " . $this->getTableName() . " u WHERE u.groupId=:g";

        return $this->dbo->queryForColumn($query, array(
            "g" => $groupId
        ));
    }

    public function findAllFiles()
    {
        $query = "SELECT * FROM " . $this->getTableName();

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName());
    }
}