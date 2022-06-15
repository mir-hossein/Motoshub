<?php

class NEWSFEED_FORMAT_Content extends NEWSFEED_CLASS_Format
{
    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $defaults = array(
            "iconClass" => null,
            "title" => '',
            "description" => '',
            "status" => null,
            "url" => null,
            'activity' => null
        );

        $tplVars = array_merge($defaults, $this->vars);

        if ( !empty($tplVars['activity']['title']) )
        {
            $tplVars['activity']["title"] = $this->getLocalizedText($tplVars['activity']["title"]);
        }

        $tplVars["url"] = $this->getUrl($tplVars["url"]);
        $tplVars["title"] = $this->getLocalizedText($tplVars["title"]);
        $stringDecode = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('toDecode' => $tplVars['title'])));
        if(isset($stringDecode->getData()['decodedString'])){
            $tplVars['title'] = $stringDecode->getData()['decodedString'];
        }
        $this->assign('vars', $tplVars);
    }
}
