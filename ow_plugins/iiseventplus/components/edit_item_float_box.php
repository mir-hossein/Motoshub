<?php

class IISEVENTPLUS_CMP_EditItemFloatBox extends OW_Component
{
    public function __construct($id)
    {
        parent::__construct();
        $form = IISEVENTPLUS_BOL_Service::getInstance()->getItemForm($id);
        $this->addForm($form);
    }
}
