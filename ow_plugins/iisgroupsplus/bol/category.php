<?php



/**
 * Data Transfer Object for `iisgroupsplus_category` table.
 *
 * @author Mohammad Agha Abbasloo
 * @package ow_plugins.iisgroupsplus.bol
 * @since 1.0
 */
class IISGROUPSPLUS_BOL_Category extends OW_Entity
{
    /**
     * @var string
     */
    public $label;

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

}
