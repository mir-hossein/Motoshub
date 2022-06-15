<?php


/**
 * @author Mohammad Agha Abbasloo
 * @package ow_plugins.iiseventplus.controllers
 * @since 1.0
 */
class IISEVENTPLUS_MCTRL_Base extends OW_MobileActionController
{
    /**
     * @var EVENT_BOL_EventService
     */
    private $eventPlusService;

    private $eventService;

    public function __construct()
    {
        parent::__construct();
        $this->eventPlusService = IISEVENTPLUS_BOL_Service::getInstance();
        $this->eventService = EVENT_BOL_EventService::getInstance();
    }

    /***
     * leave event controller
     * @param $params
     * @throws Redirect403Exception
     * @throws Redirect404Exception
     */
    public function leave( $params )
    {
        $event = $this->getEventForParams($params);

        if ( !OW::getUser()->isAuthenticated() || ( OW::getUser()->getId() == $event->getUserId() && !OW::getUser()->isAuthorized('event') ) )
        {
            throw new Redirect403Exception();
        }

        $eventService = EVENT_BOL_EventService::getInstance();
        $eventUser = $eventService->findEventUser($event->getId(),OW::getUser()->getId());
        $this->eventPlusService->leaveEvent($event->getId(),OW::getUser()->getId());

        OW::getEventManager()->call("feed.delete_activity", array(
            'activityType' => 'event-join',
            'activityId' => $eventUser->getId(),
            'entityId' => $event->getId(),
            'userId' => OW::getUser()->getId(),
            'entityType' => 'event'
        ));

        OW::getEventManager()->call("feed.delete_activity", array(
            'activityType' => 'subscribe',
            'activityId' => $eventUser->getId(),
            'entityId' => $event->getId(),
            'userId' => OW::getUser()->getId(),
            'entityType' => 'event'
        ));

        OW::getFeedback()->info(OW::getLanguage()->text('iiseventplus', 'leave_success_message'));
        $this->redirect(OW::getRouter()->urlForRoute('event.main_menu_route'));
    }

    /***
     * Get event by params(eventId)
     * @param $params
     * @return EVENT_BOL_Event
     * @throws Redirect404Exception
     */
    private function getEventForParams( $params )
    {
        if ( empty($params['eventId']) )
        {
            throw new Redirect404Exception();
        }

        $event = EVENT_BOL_EventService::getInstance()->findEvent($params['eventId']);

        if ( $event === null )
        {
            throw new Redirect404Exception();
        }

        return $event;
    }


    public function fileList( $params )
    {

        $eventId = (int) $params['eventId'];
        $eventDto = EVENT_BOL_EventService::getInstance()->findEvent($eventId);

        if ( $eventDto === null )
        {
            throw new Redirect404Exception();
        }
        $language = OW::getLanguage();

        if ( !EVENT_BOL_EventService::getInstance()->canUserView($eventId,OW::getUser()->getId())) {

            throw new Redirect403Exception();
        }
        if ( $eventDto->whoCanView == EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY && !OW::getUser()->isAuthorized('event') )
        {
            if ( !OW::getUser()->isAuthenticated() )
            {
                throw new Redirect404Exception();
            }

            $eventInvite = $this->eventService->findEventInvite($eventDto->getId(), OW::getUser()->getId());
            $eventUser = $this->eventService->findEventUser($eventDto->getId(), OW::getUser()->getId());

            // check if user can view event
            if ( (int) $eventDto->getWhoCanView() === EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY && $eventUser === null && !OW::getUser()->isAuthorized('event') )
            {
                if( $eventInvite === null ) {
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

        $dtoList = $this->eventPlusService->findFileList($eventId, $first, $count);
        $listCount = $this->eventPlusService->findFileListCount($eventId);
        $paging = new BASE_CMP_PagingMobile($page, ceil($listCount / $perPage), 2);
        $this->addComponent('paging',$paging);
        $fileList = array();
        $attachmentIds = array();
        $deleteUrls = array();
        $showAdd=false;
        $canEdit=false;
        if (Ow::getUser()->isAuthenticated() &&  $eventDto->userId==OW::getUser()->getId())
        {
            $this->assign("canEdit", true);
            $canEdit = true;
            $showAdd = true;
        }
        foreach ( $dtoList as $item ) {
            $sentenceCorrected = false;
            if (mb_strlen($item->getOrigFileName()) > 100) {
                $sentence = $item->getOrigFileName();
                $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_HALF_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 100)));
                if (isset($event->getData()['correctedSentence'])) {
                    $sentence = $event->getData()['correctedSentence'];
                    $sentenceCorrected = true;
                }
                $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 100)));
                if (isset($event->getData()['correctedSentence'])) {
                    $sentence = $event->getData()['correctedSentence'];
                    $sentenceCorrected = true;
                }
            }
            if ($sentenceCorrected) {
                $fileName = $sentence . '...';
            } else {
                $fileName = UTIL_String::truncate($item->getOrigFileName(), 100, '...');
            }

            $code = '';
            $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$item->id,'isPermanent'=>true,'activityType'=>'event_deleteFile')));
            if(isset($iisSecuritymanagerEvent->getData()['code'])){
                $code = $iisSecuritymanagerEvent->getData()['code'];
            }
            $toolbar = array(
                array(
                    'label' => UTIL_DateTime::formatSimpleDate($item->addStamp)
                )
            );

            $fileNameArr = explode('.', $item->fileName);
            $fileNameExt = end($fileNameArr);
            $itemInformation = array();
            $itemInformation['url'] = $this->getAttachmentUrl($item->fileName);
            $itemInformation['imageSrc'] = IISEVENTPLUS_BOL_Service::getInstance()->getProperIcon(strtolower($fileNameExt));
            $itemInformation['title'] = $fileName;
            $itemInformation['imageTitle'] = $fileName;
            $itemInformation['fileName'] = $item->getOrigFileName();
            $itemInformation['time'] = $item->addStamp;
            $itemInformation['content'] = $language->text('iiseventplus', 'by') . ' :' . '<a href="'.OW::getRouter()->urlForRoute('base_user_profile', array('username' => BOL_UserService::getInstance()->getUserName($item->getUserId()))).'">' .BOL_UserService::getInstance()->getDisplayName($item->getUserId()) . '</a>';
            $itemInformation['id'] = $item->id;

            if ($item->userId == OW::getUser()->getId() || $canEdit) {
                $deleteUrl =  OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('iiseventplus.deleteFile',
                    array('attachmentId' => $item->id, 'eventId' => $eventId)),array('code' =>$code));
                $toolbar[] = array(
                    'label' => '<span>'.$language->text('iiseventplus', 'delete_item').'</span>',
                    'href' => $deleteUrl,
                    'class' => 'delete_file'
                );
            }
            $itemInformation['toolbar'] = $toolbar;
            $fileList[] = $itemInformation;
        }

        $this->assign("showAdd", $showAdd);
        $this->assign("fileList", $fileList);
        $this->assign("attachmentIds", $attachmentIds);
        $this->assign('deleteUrls', $deleteUrls);
        $plugin = OW::getPluginManager()->getPlugin('iiseventplus');
        OW::getDocument()->addScript($plugin->getStaticJsUrl() . 'iiseventplus.js');
        OW::getDocument()->addStyleSheet($plugin->getStaticCssUrl() . 'iiseventplus.css');
        $this->assign('deleteIconUrl', $plugin->getStaticUrl().'images/trash.svg');
        $this->assign('addIconUrl', $plugin->getStaticUrl().'images/add.svg');
        $this->assign("eventId", $eventId);
        $this->assign('backUrl',OW::getRouter()->urlForRoute('event.view' , array('eventId'=>$eventId)));
        OW::getDocument()->addStyleDeclaration('.owm_event_list.files_list span.ow_nowrap.delete_file a {background-image: url('.$plugin->getStaticUrl().'images/trash.svg'.');}');
        $params = array(
            "sectionKey" => "iiseventplus",
            "entityKey" => "eventFiles",
            "title" => "iiseventplus+meta_title_event_files",
            "description" => "iiseventplus+meta_desc_event_files",
            "keywords" => "iiseventplus+meta_keywords_event_files",
            "vars" => array( "event_title" => $eventDto->title)
        );

        OW::getEventManager()->trigger(new OW_Event("base.provide_page_meta_info", $params));
        OW::getEventManager()->trigger(new OW_Event('iis.on.before.event.view.render', array('eventId' => $eventId,
            'pageType' => "fileList")));
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
        $eventId = (int) $params['eventId'];

        if (!isset($eventId) || $eventId<=0 )
        {
            throw new Redirect404Exception();
        }

        $form = $this->eventPlusService->getUploadFileForm($eventId);
        if (OW::getRequest()->isPost() && $form->isValid($_POST)) {
            if (!empty($_FILES)) {
                $resultArr = array('result' => false, 'message' => 'General error');
                $bundle = IISSecurityProvider::generateUniqueId();

                $pluginKey = 'iiseventplus';
                $item = $_FILES['fileUpload'];
                if(isset($_POST['name']) && $_POST['name']!=""){
                    $item['name'] = $_POST['name'].'.'.end(explode('.',$item['name'] ));
                }
                try {
                    $dtoArr = BOL_AttachmentService::getInstance()->processUploadedFile($pluginKey, $item, $bundle);
                    OW::getEventManager()->call('base.attachment_save_image', array('uid' => $bundle, 'pluginKey' => $pluginKey));
                    $resultArr['result'] = true;
                    $resultArr['url'] = $dtoArr['url'];
                    $attachmentId = $dtoArr['dto']->id;
                    $fileId = $this->eventPlusService->addFileForEvent($eventId,$attachmentId);
                    $eventDto = $this->eventService->findEvent($eventId);
                    if(!isset($eventDto)){
                        return;
                    }
                    $url = OW::getRouter()->urlForRoute('event.view' , array('eventId'=>$eventId));
                    $data = array(
                        'time' => time(),
                        'string' => array(
                            "key" => 'iiseventplus+feed_add_file_string',
                            "vars" => array(
                                'eventTitle' => $eventDto->title,
                                'eventUrl' => $url,
                                'fileUrl' => $this->getAttachmentUrl($dtoArr['dto']->fileName),
                                'fileName' => $dtoArr['dto']->origFileName
                            )
                        ),
                        'view' => array(
                            'iconClass' => 'ow_ic_add'
                        ),
                        'data' => array(
                            'fileAddId' => $fileId
                        )
                    );

                    $event = new OW_Event('feed.action', array(
                        'feedType' => 'event',
                        'feedId' => $eventDto->id,
                        'entityType' => 'event-add-file',
                        'entityId' => $fileId,
                        'pluginKey' => 'event',
                        'userId' => OW::getUser()->getId(),
                    ), $data);

                    OW::getEventManager()->trigger($event);

                    /*
                     * send notification to event members
                    */
                    $userId = OW::getUser()->getId();
                    $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));
                    $avatar = $avatars[$userId];
                    $userUrl = BOL_UserService::getInstance()->getUserUrl($userId);
                    $notificationParams = array(
                        'pluginKey' => 'event',
                        'action' => 'event-add-file',
                        'entityType' => 'event-add-file',
                        'entityId' => $fileId,
                        'userId' => null,
                        'time' => time()
                    );

                    $notificationData = array(
                        'string' => array(
                            "key" => 'iiseventplus+notif_add_file_string',
                            "vars" => array(
                                'eventTitle' => $eventDto->title,
                                'eventUrl' => $url,
                                'userName' => BOL_UserService::getInstance()->getDisplayName($userId),
                                'fileName' => $dtoArr['dto']->origFileName,
                                'userUrl' => $userUrl
                            )
                        ),
                        'avatar' => $avatar,
                        'content' => '',
                        'url' => $this->getAttachmentUrl($dtoArr['dto']->fileName)
                    );

                    $users = $this->eventService->findAllUsersForEvent($eventId);

                    foreach ( $users as $user )
                    {
                        if ( $user->userId == OW::getUser()->getId() )
                        {
                            continue;
                        }

                        $notificationParams['userId'] = $user->userId;

                        $event = new OW_Event('notifications.add', $notificationParams, $notificationData);
                        OW::getEventManager()->trigger($event);
                    }
                } catch (Exception $e) {
                    $resultArr['message'] = $e->getMessage();
                    OW::getFeedback()->error($resultArr['message']);
                }
            }

            exit();
        }
    }

    public function deleteFile($params){
        if (!OW::getUser()->isAuthenticated()) {
            throw new AuthenticateException();
        }
        $eventId = $params['eventId'];
        $attachmentId = $params['attachmentId'];
        if ( !isset($eventId)  || !isset($attachmentId))
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
                array('senderId' => OW::getUser()->getId(), 'code'=>$code,'activityType'=>'event_deleteFile')));
        }
        $eventDto = $this->eventService->findEvent($eventId);
        if(!$eventDto) {
            throw new Redirect404Exception();
        }
        $canEdit=false;
        if ($eventDto->userId==OW::getUser()->getId())
        {
            $canEdit = true;
        }

        $attachment = BOL_AttachmentDao::getInstance()->findById($attachmentId);
        if ($attachment->userId != OW::getUser()->getId() && !$canEdit) {
            throw new Redirect404Exception();
        }

        try {
            $fileId = $this->eventPlusService->findFileIdByAidAndGid($eventDto->getId(), $attachmentId);
            $this->eventPlusService->deleteFileForEvent($eventDto->getId(), $attachmentId);
            BOL_AttachmentService::getInstance()->deleteAttachmentById($attachmentId);
            OW::getEventManager()->trigger(new OW_Event("feed.delete_item", array(
                'entityType' => 'event-add-file',
                'entityId' => $fileId
            )));
            OW::getEventManager()->call('notifications.remove', array(
                'entityType' => 'event-add-file',
                'entityId' => $fileId
            ));
        }
        catch (Exception $e){

        }

        $this->redirect(OW::getRouter()->urlForRoute('iiseventplus.file-list' , array('eventId'=>$eventId)));
    }
}
