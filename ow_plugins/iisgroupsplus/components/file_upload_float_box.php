<?php

class IISGROUPSPLUS_CMP_FileUploadFloatBox extends OW_Component
{
    public function __construct($iconClass, $groupId)
    {
        $isUserInGroup = GROUPS_BOL_Service::getInstance()->findUser($groupId, OW::getUser()->getId());
        if (!$isUserInGroup )
        {
            throw new Redirect404Exception();
        }
        $isChannel=false;
        if(OW::getUser()->isAuthenticated()){
            $isUserInGroup = GROUPS_BOL_Service::getInstance()->findUser($groupId, OW::getUser()->getId());
            $channelEvent = OW::getEventManager()->trigger(new OW_Event('iisgroupsplus.on.channel.add.widget',
                array('groupId'=>$groupId)));
            $isChannelParticipant = $channelEvent->getData()['channelParticipant'];
            if( $isUserInGroup && isset($isChannelParticipant) && $isChannelParticipant ){
                $isChannel=true;
            }
        }

        $isAuthorizedUpload=true;
        $groupSettingEvent = OW::getEventManager()->trigger(new OW_Event('can.upload.in.file.widget',
            array('groupId'=>$groupId)));
        if(isset($groupSettingEvent->getData()['accessUploadFile'])) {
            $isAuthorizedUpload = $groupSettingEvent->getData()['accessUploadFile'];
        }
        $groupDto= GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        $isModerator=GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($groupDto);
        if(!$isModerator) {
            if (!$isAuthorizedUpload) {
                throw new Redirect404Exception();
            }
            else if ($isAuthorizedUpload && $isChannel) {
                throw new Redirect404Exception();
            }
        }
        parent::__construct();
        $form = IISGROUPSPLUS_BOL_Service::getInstance()->getUploadFileForm($groupId);
        $this->assign('loaderIcon',$this->getIconUrl('LoaderIcon'));
        $this->addForm($form);
    }

    public function getIconUrl($name){
        return OW::getPluginManager()->getPlugin('iisgroupsplus')->getStaticUrl(). 'images/'.$name.'.gif';
    }
}


