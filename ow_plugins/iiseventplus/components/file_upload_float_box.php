<?php

class IISEVENTPLUS_CMP_FileUploadFloatBox extends OW_Component
{
    public function __construct($iconClass, $eventId)
    {
        $eventDto = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
        if (!isset($eventDto) || $eventDto->userId!=OW::getUser()->getId())
        {
            throw new Redirect404Exception();
        }
        parent::__construct();
        $form = IISEVENTPLUS_BOL_Service::getInstance()->getUploadFileForm($eventId);
        OW::getDocument()->setTitle(OW::getLanguage()->text('iiseventplus','add_file'));
        OW::getDocument()->setHeading(OW::getLanguage()->text('iiseventplus','add_file'));
        $this->assign('loaderIcon',$this->getIconUrl('LoaderIcon'));
        $this->addForm($form);
    }

    public function getIconUrl($name){
        return OW::getPluginManager()->getPlugin('iiseventplus')->getStaticUrl(). 'images/'.$name.'.svg';
    }
}


