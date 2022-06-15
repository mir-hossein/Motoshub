<?php


class IISGROUPSPLUS_CMP_FileListWidget extends BASE_CLASS_Widget
{

    /***
     * IISGROUPSPLUS_CMP_FileListWidget constructor.
     * @param BASE_CLASS_WidgetParameter $params
     */
    public function __construct( BASE_CLASS_WidgetParameter $params )
    {
        parent::__construct();

        $groupId = $params->additionalParamList['entityId'];
        $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        $canEdit=false;
        if ( GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($groupDto) )
        {
            $this->assign('canEdit',true);
            $canEdit=true;
        }
        $count = ( empty($params->customParamList['count']) ) ? 10 : (int) $params->customParamList['count'];
        $this->assignList($groupId, $count,$canEdit);
        $this->assign('view_all_files', OW::getRouter()->urlForRoute('iisgroupsplus.file-list', array('groupId' => $groupId)));

    }

    private function assignList( $groupId, $count,$canEdit )
    {
        $truncateLength = 24;
        $list = IISGROUPSPLUS_BOL_Service::getInstance()->findFileList($groupId, 0, $count);

        $filelist = array();
        $attachmentIds = array();
        $deleteUrls = array();
        foreach ( $list as $item )
        {
            $sentenceCorrected = false;
            if ( mb_strlen($item->getOrigFileName()) > 100 )
            {
                $sentence = $item->getOrigFileName();
                $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_HALF_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 100)));
                if(isset($event->getData()['correctedSentence'])){
                    $sentence = $event->getData()['correctedSentence'];
                    $sentenceCorrected=true;
                }
                $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 100)));
                if(isset($event->getData()['correctedSentence'])){
                    $sentence = $event->getData()['correctedSentence'];
                    $sentenceCorrected=true;
                }
            }
            if($sentenceCorrected){
                if(mb_strlen($sentence)>=$truncateLength-3){
                    $fileName = UTIL_String::truncate($item->getOrigFileName(), $truncateLength-3, '...');
                }else{
                    $fileName = $sentence.'...';
                }
            }
            else{
                $fileName = UTIL_String::truncate($item->getOrigFileName(), $truncateLength-3, '...');
            }

            $fileNameArr = explode('.',$item->fileName);
            $fileNameExt = end($fileNameArr);
            $filelist[$item->id]['fileUrl'] = $this->getAttachmentUrl($item->fileName);

            $filelist[$item->id]['iconUrl'] = IISGROUPSPLUS_BOL_Service::getInstance()->getProperIcon(strtolower($fileNameExt));
            $filelist[$item->id]['truncatedFileName'] = $fileName;
            $filelist[$item->id]['fileName'] = $item->getOrigFileName();
            $filelist[$item->id]['name'] =$item->id;
//            if($item->userId==OW::getUser()->getId() || $canEdit) {
//                $deleteUrls[$item->id] = OW::getRouter()->urlForRoute('iisgroupsplus.deleteFile', array('attachmentId' => $item->id, 'groupId' => $groupId));
//            }
        }

        $showAdd=true;
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
                $showAdd = false;
            }
            else if ($isAuthorizedUpload && $isChannel) {
                $showAdd = false;
            }
        }
        $this->assign("showAdd", $showAdd);

        $this->assign("fileList", $filelist);
        $this->assign("attachmentIds", $attachmentIds);
        $this->assign('deleteUrls', $deleteUrls);
        $plugin = OW::getPluginManager()->getPlugin('iisgroupsplus');
        OW::getDocument()->addScript($plugin->getStaticJsUrl() . 'iisgroupsplus.js');
        OW::getDocument()->addStyleSheet($plugin->getStaticCssUrl() . 'iisgroupsplus.css');
        $this->assign('deleteIconUrl', $plugin->getStaticUrl().'images/trash.svg');
        $this->assign("filesCount", IISGROUPSPLUS_BOL_Service::getInstance()->findFileListCount($groupId));
        $this->assign("groupId", $groupId);
        return !empty($filelist);
    }


    public function getAttachmentUrl($name)
    {
        return OW::getStorage()->getFileUrl($this->getAttachmentDir($name));
    }

    public function getAttachmentDir($name)
    {
        return OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments' . DS .$name ;
    }

    public static function getSettingList()
    {
        $settingList = array();
        $settingList['count'] = array(
            'presentation' => self::PRESENTATION_NUMBER,
            'label' => OW_Language::getInstance()->text('iisgroupsplus', 'widget_files_settings_count'),
            'value' => 10
        );

        return $settingList;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_WRAP_IN_BOX => true,
            self::SETTING_TITLE => OW_Language::getInstance()->text('iisgroupsplus', 'widget_files_title'),
            self::SETTING_ICON => self::ICON_FILE
        );
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }
}