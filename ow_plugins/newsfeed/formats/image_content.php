<?php

class NEWSFEED_FORMAT_ImageContent extends NEWSFEED_CLASS_Format
{
    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $defaults = array(
            "image" => null,
            "thumbnail" => null,
            "iconClass" => null,
            "title" => '',
            "description" => '',
            "status" => null,
            "url" => null,
            "userList" => null
        );

        $tplVars = array_merge($defaults, $this->vars);
        if (strlen($tplVars['description']) > 3) {
            if (substr($tplVars['description'], -3) === '...') {
                $tplVars['loadMore'] = true;
            }
        }

        $tplVars["url"] = $this->getUrl($tplVars["url"]);
        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('string' => $tplVars['image'])));
        if(isset($stringRenderer->getData()['string'])){
            $tplVars['image'] = $stringRenderer->getData()['string'];
        }
        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('string' => $tplVars['thumbnail'])));
        if(isset($stringRenderer->getData()['string'])){
            $tplVars['thumbnail'] = $stringRenderer->getData()['string'];
        }
        $stringDecode = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('toDecode' => $tplVars['title'])));
        if(isset($stringDecode->getData()['decodedString'])){
            $tplVars['title'] = $stringDecode->getData()['decodedString'];
        }
        if ( !empty($tplVars["userList"]) )
        {
            $tplVars["userList"] = $this->getUserList($tplVars["userList"]);
        }
        
        $this->assign('vars', $tplVars);
    }
    
    protected function getUserList( $data )
    {
        $userList = OW::getClassInstance("BASE_CMP_MiniAvatarUserList", $data["ids"]);
        $userList->setEmptyListNoRender(true);
        
        if ( !empty($data["viewAllUrl"]) )
        {
            $userList->setViewMoreUrl($this->getUrl($data["viewAllUrl"]));
        }
        
        return array(
            "label" => $this->getLocalizedText($data['label']),
            "list" => $userList->render()
        );
    }
}
