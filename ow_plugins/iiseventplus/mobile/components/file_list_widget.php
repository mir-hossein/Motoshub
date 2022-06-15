<?php


class IISEVENTPLUS_MCMP_FileListWidget extends BASE_CLASS_Widget
{

    /**
     * IISEVENTPLUS_MCMP_FileListWidget constructor.
     * @param BASE_CLASS_WidgetParameter $params
     */
    public function __construct( BASE_CLASS_WidgetParameter $params )
    {
        parent::__construct();

        $eventId = $params->additionalParamList['entityId'];
        $eventDto = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
        $canEdit=false;
        if ( $eventDto->userId==OW::getUser()->getId())
        {
            $this->assign('canEdit',true);
            $canEdit=true;
        }
        $count = ( empty($params->customParamList['count']) ) ? 10 : (int) $params->customParamList['count'];
        $this->assignList($eventId, $count,$canEdit);
        $this->assign('view_all_files', OW::getRouter()->urlForRoute('iiseventplus.file-list', array('eventId' => $eventId)));
        $this->setTemplate(OW::getPluginManager()->getPlugin('iiseventplus')->getMobileCmpViewDir() . 'file_list_widget.html');
    }

    private function assignList( $eventId, $count,$canEdit )
    {
        $list = IISEVENTPLUS_BOL_Service::getInstance()->findFileList($eventId, 0, $count);

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
                $fileName = $sentence.'...';
            }
            else{
                $fileName = UTIL_String::truncate($item->getOrigFileName(), 100, '...');
            }

            $fileNameArr = explode('.',$item->fileName);
            $fileNameExt = end($fileNameArr);
            $filelist[$item->id]['fileUrl'] = $this->getAttachmentUrl($item->fileName);

            $filelist[$item->id]['iconUrl'] = IISEVENTPLUS_BOL_Service::getInstance()->getProperIcon(strtolower($fileNameExt));
            $filelist[$item->id]['truncatedFileName'] = $fileName;
            $filelist[$item->id]['fileName'] = $item->getOrigFileName();
            $filelist[$item->id]['name'] =$item->id;
        }

        $this->assign("showAdd", $canEdit);
        $this->assign("fileList", $filelist);
        $this->assign("attachmentIds", $attachmentIds);
        $this->assign('deleteUrls', $deleteUrls);
        $plugin = OW::getPluginManager()->getPlugin('iiseventplus');
        OW::getDocument()->addStyleSheet($plugin->getStaticCssUrl() . 'iiseventplus.css');
        OW::getDocument()->addScript($plugin->getStaticJsUrl() . 'iiseventplus.js');

        $this->assign("eventId", $eventId);
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
            'label' => OW_Language::getInstance()->text('iiseventplus', 'widget_files_settings_count'),
            'value' => 10
        );

        return $settingList;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_WRAP_IN_BOX => true,
            self::SETTING_TITLE => OW_Language::getInstance()->text('iiseventplus', 'widget_files_title'),
            self::SETTING_ICON => self::ICON_FILE
        );
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }
}