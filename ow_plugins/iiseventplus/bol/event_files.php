<?php


/**
 * Data Transfer Object for `iiseventplus_event_files` table.
 *
 * @author Mohammad Agha Abbasloo
 * @package ow_plugins.iiseventplus.bol
 * @since 1.0
 */
class IISEVENTPLUS_BOL_EventFiles extends OW_Entity
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
    public $attachmentId;

    /**
     * @return int
     */
    public function getAttachmentId()
    {
        return $this->attachmentId;
    }

    /**
     * @param int $attachmentId
     */
    public function setAttachmentId($attachmentId)
    {
        $this->attachmentId = $attachmentId;
    }

}
