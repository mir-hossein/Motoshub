<?php


/**
 * Data Transfer Object for `iisgroupsplus_group_managers` table.
 *
 * @author Mohammad Agha Abbasloo
 * @package ow_plugins.iisgroupsplus.bol
 * @since 1.0
 */
class IISGROUPSPLUS_BOL_GroupFiles extends OW_Entity
{
    /**
     * @var integer
     */
    public $groupId;

    /**
     * @return integer
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param string integer
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
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
