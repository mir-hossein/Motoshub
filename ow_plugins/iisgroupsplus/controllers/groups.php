<?php



/**
 * iisgroupsplus
 *
 * @author Mohammad Agha Abbasloo
 * @package ow_plugins.iisgroupsplus.controllers
 * @since 1.0
 */
class IISGROUPSPLUS_CTRL_Groups extends OW_ActionController
{
    /**
     *
     * @var IISGROUPSPLUS_BOL_Service
     */
    private $service;

    public function __construct()
    {
        $this->service = IISGROUPSPLUS_BOL_Service::getInstance();

    }

    public function deleteUserAsManager( $params )
    {
        if ( empty($params['groupId']) || empty($params['userId']) )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($params['groupId']);

        if ( $groupDto === null )
        {
            throw new Redirect404Exception();
        }

        if ( !GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($groupDto))
        {
            throw new Redirect403Exception();
        }
        $groupId = (int) $groupDto->id;
        $userId = $params['userId'];

        $this->service->deleteUserManager($groupId, $userId);

        OW::getFeedback()->info(OW::getLanguage()->text('iisgroupsplus', 'delete_user_as_manager_success_message'));

        $redirectUri = urldecode($_GET['redirectUri']);
        $this->redirect(OW_URL_HOME . $redirectUri);
    }

    public function addUserAsManager( $params )
    {
        if ( empty($params['groupId']) || empty($params['userId']) )
        {
            throw new Redirect404Exception();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($params['groupId']);

        if ( $groupDto === null )
        {
            throw new Redirect404Exception();
        }
        if (!GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($groupDto))
        {
            throw new Redirect403Exception();
        }

        $groupId = (int) $groupDto->id;
        $userId = $params['userId'];

        $this->service->addUserAsManager($groupId, $userId);

        OW::getFeedback()->info(OW::getLanguage()->text('iisgroupsplus', 'add_user_as_manager_success_message'));

        $redirectUri = urldecode($_GET['redirectUri']);
        $this->redirect(OW_URL_HOME . $redirectUri);
    }

    public function fileList( $params )
    {

        $searchTitle='';

        if (OW::getRequest()->isPost()) {
            $searchTitle = $_POST['searchTitle'];
        }

        if(isset($_GET['searchTitle'])){
            $searchTitle = $_GET['searchTitle'];
        }

        $groupId = (int) $params['groupId'];
        $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);

        if ( $groupDto === null )
        {
            throw new Redirect404Exception();
        }

        $language = OW::getLanguage();

        if ( !GROUPS_BOL_Service::getInstance()->isCurrentUserCanView($groupDto) )
        {
            throw new Redirect404Exception();
        }
        if ( $groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE && !OW::getUser()->isAuthorized('groups') )
        {
            if ( !OW::getUser()->isAuthenticated() )
            {
                throw new Redirect404Exception();
            }

            $invite = GROUPS_BOL_Service::getInstance()->findInvite($groupDto->id, OW::getUser()->getId());
            $user = GROUPS_BOL_Service::getInstance()->findUser($groupDto->id, OW::getUser()->getId());

            if ( $groupDto->whoCanView == GROUPS_BOL_Service::WCV_INVITE && $user === null )
            {
                if( $invite === null ) {
                    throw new Redirect404Exception();
                }else{
                    $this->redirect(OW::getRouter()->urlForRoute('event.view_event_list', array('list' => 'invited')));
                }
            }
        }

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $perPage = 20;
        $first = ($page - 1) * $perPage;
        $count = $perPage;

        $dtoList = $this->service->findFileList($groupId, $first, $count, $searchTitle);
        $listCount = $this->service->findFileListCount($groupId);
        $paging = new BASE_CMP_Paging($page, ceil($listCount / $perPage), 2);
        $this->addComponent('paging',$paging);
        $filelist = array();
        $attachmentIds = array();
        $deleteUrls = array();
        foreach ( $dtoList as $item )
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

            $canEdit=false;
            if ( GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($groupDto) )
            {
                $canEdit = true;
            }

            if($item->userId==OW::getUser()->getId()){
                $canEdit=true;
            }
            $code = '';
            $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$item->id,'isPermanent'=>true,'activityType'=>'groups_deleteFile')));
            if(isset($iisSecuritymanagerEvent->getData()['code'])){
                $code = $iisSecuritymanagerEvent->getData()['code'];
            }
            $fileNameArr = explode('.',$item->fileName);
            $fileNameExt = end($fileNameArr);
            $filelist[$item->id]['fileUrl'] = $this->getAttachmentUrl($item->fileName);
            $filelist[$item->id]['iconUrl'] = IISGROUPSPLUS_BOL_Service::getInstance()->getProperIcon(strtolower($fileNameExt));
            $filelist[$item->id]['truncatedFileName'] = $fileName;
            $filelist[$item->id]['fileName'] = $item->getOrigFileName();
            $filelist[$item->id]['createdDate'] =$item->addStamp;
            $filelist[$item->id]['userName'] =BOL_UserService::getInstance()->getDisplayName($item->getUserId());
            $filelist[$item->id]['userUrl'] = OW::getRouter()->urlForRoute('base_user_profile', array('username' => BOL_UserService::getInstance()->getUserName($item->getUserId())));
            $filelist[$item->id]['name'] =$item->id;
            if ($item->userId == OW::getUser()->getId() || $canEdit) {
                $deleteUrls[$item->id] = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('iisgroupsplus.deleteFile',
                    array('attachmentId' => $item->id, 'groupId' => $groupId)),array('code' =>$code));
            }
        }
        $showAdd=false;
        if(OW::getUser()->isAuthenticated()){
            $isUserInGroup = GROUPS_BOL_Service::getInstance()->findUser($groupId, OW::getUser()->getId());
            $channelEvent = OW::getEventManager()->trigger(new OW_Event('iisgroupsplus.on.channel.add.widget',
                array('groupId'=>$groupId)));
            $isChannelParticipant = $channelEvent->getData()['channelParticipant'];
            if( $isUserInGroup && !(isset($isChannelParticipant) && $isChannelParticipant) ){
                $showAdd=true;
            }
        }


        $groupSettingEvent = OW::getEventManager()->trigger(new OW_Event('can.upload.in.file.widget',
            array('groupId'=>$groupId)));
        if(isset($groupSettingEvent->getData()['accessUploadFile'])) {
            $showAdd = $groupSettingEvent->getData()['accessUploadFile'] || $showAdd;
        }

        $this->assign("showAdd", $showAdd);
        $this->assign("fileList", $filelist);
        $this->assign("attachmentIds", $attachmentIds);
        $this->assign('deleteUrls', $deleteUrls);
        $plugin = OW::getPluginManager()->getPlugin('iisgroupsplus');
        OW::getDocument()->addScript($plugin->getStaticJsUrl() . 'iisgroupsplus.js');

        $this->assign("groupId", $groupId);
        $this->assign('backUrl',OW::getRouter()->urlForRoute('groups-view' , array('groupId'=>$groupId)));
        OW::getDocument()->addStyleSheet($plugin->getStaticCssUrl() . 'iisgroupsplus.css');
        $this->assign('deleteIconUrl', $plugin->getStaticUrl().'images/trash.svg');
        $params = array(
            "sectionKey" => "iisgroupsplus",
            "entityKey" => "groupFiles",
            "title" => "iisgroupsplus+meta_title_group_files",
            "description" => "iisgroupsplus+meta_desc_group_files",
            "keywords" => "iisgroupsplus+meta_keywords_group_files",
            "vars" => array( "group_title" => $groupDto->title )
        );
        $this->assign('search_url',OW::getRouter()->urlForRoute('iisgroupsplus.file-list',array('groupId'=>$groupId)));
        $this->assign('searchTitle',$searchTitle);
        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));
    }

    public function getAttachmentUrl($name)
    {
        return OW::getStorage()->getFileUrl($this->getAttachmentDir($name));
    }

    public function getAttachmentDir($name)
    {
        return OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments' . DS .$name ;
    }

    public function addFile($params)
    {
        if (!OW::getUser()->isAuthenticated()) {
            throw new AuthenticateException();
        }
        $groupId = (int) $params['groupId'];
        if(!isset($groupId)){
            throw new Redirect404Exception();
        }
        if(isset($_POST['id'])) {
            $groupIdPosted = $_POST['id'];
        }
        if ( $groupId<=0 || $groupIdPosted != $groupId)
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
        $form = $this->service->getUploadFileForm($groupId);
        if (OW::getRequest()->isPost() && $form->isValid($_POST)) {
            if (!empty($_FILES)) {
               IISGROUPSPLUS_BOL_Service::getInstance()->manageAddFile($groupId, $_FILES['fileUpload']);
            }

            exit();
        }
    }

    public function deleteFile($params){
        if (!OW::getUser()->isAuthenticated()) {
            throw new AuthenticateException();
        }
        $groupId = $params['groupId'];
        $attachmentId = $params['attachmentId'];
        if ( !isset($groupId)  || !isset($attachmentId))
        {
            throw new Redirect404Exception();
        }
        $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
        if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
            $code =$_GET['code'];
            if(!isset($code)){
                throw new Redirect404Exception();
            }
            OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.check.request.manager',
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'groups_deleteFile')));
        }
        $groupDto = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
        if(!$groupDto) {
            throw new Redirect404Exception();
        }
        $canEdit=false;
        if (GROUPS_BOL_Service::getInstance()->isCurrentUserCanEdit($groupDto) )
        {
            $canEdit = true;
        }

        $attachment = BOL_AttachmentDao::getInstance()->findById($attachmentId);
        if ($attachment->userId != OW::getUser()->getId() && !$canEdit) {
            throw new Redirect404Exception();
        }
        $isUserInGroup = GROUPS_BOL_Service::getInstance()->findUser($groupId, OW::getUser()->getId());
        if(!$isUserInGroup){
            throw new Redirect404Exception();
        }
        try {
            $this->service->deleteFileForGroup($groupId, $attachmentId);
        }
        catch (Exception $e){

        }
        $this->redirect(OW::getRouter()->urlForRoute('iisgroupsplus.file-list' , array('groupId'=>$groupId)));
    }
}
