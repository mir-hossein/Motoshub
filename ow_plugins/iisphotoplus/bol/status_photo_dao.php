<?php


class IISPHOTOPLUS_BOL_StatusPhotoDao extends OW_BaseDao
{

    private static $classInstance;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function getDtoClassName()
    {
        return 'IISPHOTOPLUS_BOL_StatusPhoto';
    }

    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisphotoplus_status_photo';
    }



    public function addStatusPhoto($photoId, $userId)
    {
        if (!isset($photoId) || !isset($userId)) {
            return;
        }
        $statusPhoto = new IISPHOTOPLUS_BOL_StatusPhoto();
        $statusPhoto->setPhotoId($photoId);
        $statusPhoto->setUserId($userId);
        $this->save($statusPhoto);
    }

    public function getStatusPhotosByUserId($userId)
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('userId', $userId);
        return $this->findListByExample($ex);
    }

    public function deleteStatusPhotosByUserId($userId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        $this->deleteByExample($example);
    }
}
