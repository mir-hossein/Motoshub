<?php
/**
 * Created by PhpStorm.
 * User: Milad Heshmati
 * Date: 5/22/2019
 * Time: 12:43 PM
 */

class IISNEWSFEEDPLUS_BOL_Thumbnail extends OW_Entity
{

    /**
     * @var int
     */
    public $attachmentId;


    /**
     * @var string
     */
    public $name;


    /**
     * @var int
     */
    public $userId;

    /**
     * @var int
     */
    public $creationTime;


    /**
     * @return int
     */

    public function getAttachmentId()
    {
        return $this->attachmentId;
    }

    /**
     * @param string $name
     */
    public function setAttachmentId($attachmentId)
    {
        $this->attachmentId = $attachmentId;
    }

    /**
     * @return string
     */

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }


    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->name;
    }

    /**
     * @param string $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }


    /**
     * @return int
     */
    public function getCreationTime()
    {
        return $this->name;
    }

    /**
     * @param string $creationTime
     */
    public function setCreationTime($creationTime)
    {
        $this->creationTime = $creationTime;
    }



}