<?php

class IISNEWSFEEDPLUS_CMP_EditPostFloatBox extends OW_Component
{
    public function __construct($iconClass, $eid, $etype)
    {
        $service = IISNEWSFEEDPLUS_BOL_Service::getInstance();
        if(!$service->canEditPost($eid, $etype)){
            throw new Redirect404Exception();
        }

        parent::__construct();

        $text = $service->getText($eid, $etype, true);
        $form = $service->getEditPostForm($text, $eid, $etype);
        $this->addForm($form);
    }
}


