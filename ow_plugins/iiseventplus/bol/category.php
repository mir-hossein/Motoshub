<?php



/**
 * Data Transfer Object for `iiseventplus_category` table.
 *
 * @author Mohammad Agha Abbasloo
 * @package ow_plugins.iiseventplus.bol
 * @since 1.0
 */
class IISEVENTPLUS_BOL_Category extends OW_Entity
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
