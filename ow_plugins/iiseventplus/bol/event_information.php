<?php


/**
 * Data Transfer Object for `iiseventplus_EventInformation` table.
 *
 * @author Mohammad Agha Abbasloo
 * @package ow_plugins.iiseventplus.bol
 * @since 1.0
 */
class IISEVENTPLUS_BOL_EventInformation extends OW_Entity
{
    /**
     * @var integer
     */
    public $eventId;

    /**
     * @return integer
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @param string integer
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    }

   /**
     * @var integer
    */
    public $categoryId;

    /**
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param integer $categoryId
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }

}
