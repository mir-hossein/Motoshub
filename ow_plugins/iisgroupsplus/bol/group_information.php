<?php


/**
 * Data Transfer Object for `iisgroupsplus_GroupInformation` table.
 *
 * @author Mohammad Agha Abbasloo
 * @package ow_plugins.iisgroupsplus.bol
 * @since 1.0
 */
class IISGROUPSPLUS_BOL_GroupInformation extends OW_Entity
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
