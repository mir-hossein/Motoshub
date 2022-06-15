<?php
/**
 * Created by PhpStorm.
 * User: Milad Heshmati
 * Date: 5/22/2019
 * Time: 12:45 PM
 */

class IISNEWSFEEDPLUS_BOL_ThumbnailDao extends OW_BaseDao
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
     * @var IISNEWSFEEDPLUS_BOL_ThumbnailDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return IISNEWSFEEDPLUS_BOL_ThumbnailDao
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
        return 'IISNEWSFEEDPLUS_BOL_Thumbnail';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisnewsfeedplus_thumbnail';
    }


    public function getThumbnailsByAttachmentIds($attachmentIds)
    {
        $ex = new OW_Example();
        $ex->andFieldInArray('attachmentId', $attachmentIds);
        return $this->findListByExample($ex);
    }

    public function getThumbnailById( $attachmentId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('attachmentId', $attachmentId);
        return $this->findObjectByExample($ex);
    }

    /***
     * @param $attachmentId
     * @param $userId
     * @return IISNEWSFEEDPLUS_BOL_Thumbnail|null
     */
    public function addThumbnail($attachmentId, $userId){

        if($userId == null || $attachmentId == null ){
            return null;
        }

        $thumbnailInfo = new IISNEWSFEEDPLUS_BOL_Thumbnail();
        $thumbnail = $this->getThumbnailById($attachmentId);

        if( $thumbnail == null ) {
            $thumbnailInfo->setAttachmentId($attachmentId);
            $thumbnailInfo->setUserId($userId);
            $thumbnailInfo->setName($attachmentId. '.png');
            $thumbnailInfo->setCreationTime(time());
            $this->save($thumbnailInfo);
        }

        return $thumbnailInfo;
    }


}