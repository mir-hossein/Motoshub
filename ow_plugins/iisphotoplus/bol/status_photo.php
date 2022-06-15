<?php


class IISPHOTOPLUS_BOL_StatusPhoto extends OW_Entity
{

    public $photoId;
    public $userId;

    /**
     * @return mixed
     */
    public function getPhotoId()
    {
        return $this->photoId;
    }

    /**
     * @param mixed $photoId
     */
    public function setPhotoId($photoId)
    {
        $this->photoId = $photoId;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }


}
