<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 * 
 *
 * @author Mohammad Aghaabbasloo
 * @package ow_plugins.iiseventplus
 * @since 1.0
 */
class IISEVENTPLUS_BOL_Service
{
    private static $EVENT_GENERAL = 'event_general';
    private static $EVENT_MY = 'event_my';
    private static $classInstance;
    public static $PARTICIPATE_ALL = 1;
    public static $PARTICIPATE_SURE = 2;
    public static $PARTICIPATE_MAYBE = 3;
    public static $PARTICIPATE_NO = 4;
    const ADD_FILTER_PARAMETERS_TO_PAGING = 'eventplus.add.filter.parameters.to.paging';
    const CHECK_IF_EVENTPLUS_IS_ACTIVE = 'eventplus.check.if.active';
    public static $DATE_ALL = 1;
    public static $DATE_LATEST = 2;
    public static $DATE_PAST = 3;


    private  $eventInformationDao;
    private $categoryDao;
    private $past=null;
    private $categoryStatus = '';
    private $searchTitle = '';
    private $dateStatus = '';
    private $participateStatus = '';
    private $participationStatus = '';
    private $page;


    const DELETE_FILES = 'iiseventplus.delete.files';
    const ADD_FILE_WIDGET = 'iiseventplus.add.file.widget';
    private  $eventFileDao;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }


    private function __construct()
    {
        $this->eventInformationDao = IISEVENTPLUS_BOL_EventInformationDao::getInstance();
        $this->categoryDao = IISEVENTPLUS_BOL_CategoryDao::getInstance();
        $this->eventFileDao = IISEVENTPLUS_BOL_EventFilesDao::getInstance();
    }

    public function setTitleHeaderListItemEvent(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['listType']) && $params['listType'] == IISEVENTPLUS_BOL_Service::$EVENT_GENERAL) {
            OW::getDocument()->setTitle(OW::getLanguage()->text('iiseventplus', 'meta_title_event_add_general'));
            OW::getDocument()->setDescription(OW::getLanguage()->text('iiseventplus', 'meta_description_event_general'));
        } else if (isset($params['listType']) && $params['listType'] == IISEVENTPLUS_BOL_Service::$EVENT_MY) {
            OW::getDocument()->setTitle(OW::getLanguage()->text('iiseventplus', 'meta_title_event_add_my'));
            OW::getDocument()->setDescription(OW::getLanguage()->text('iiseventplus', 'meta_description_event_my'));
        }
    }

    public function addListTypeToEvent(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['list'])){
            $list = $params['list'];
            $keys = array(IISEVENTPLUS_BOL_Service::$EVENT_GENERAL
            ,IISEVENTPLUS_BOL_Service::$EVENT_MY, 'invited');
            if(!in_array($list, $keys))
            {
                $list=self::$EVENT_GENERAL;
            }
            $event->setData(array('list' => $list));
        }
        else {
            if (isset($params['menuItems'])) {
                $menuItems = $params['menuItems'];
                foreach($menuItems as $item){
                    $item->setOrder($item->getOrder()+2);
                }
                if (OW::getUser()->isAuthenticated()) {
                    $item = new BASE_MenuItem();
                    $item->setLabel(OW::getLanguage()->text('iiseventplus', IISEVENTPLUS_BOL_Service::$EVENT_MY));
                    $item->setUrl(OW::getRouter()->urlForRoute('event.view_event_list', array('list' => IISEVENTPLUS_BOL_Service::$EVENT_MY)));
                    $item->setKey(IISEVENTPLUS_BOL_Service::$EVENT_MY);
                    $item->setOrder(1);
                    $item->setIconClass('ow_ic_my_events');
                    array_push($menuItems, $item);
                }
                $item = new BASE_MenuItem();
                $item->setLabel(OW::getLanguage()->text('iiseventplus', IISEVENTPLUS_BOL_Service::$EVENT_GENERAL));
                $item->setUrl(OW::getRouter()->urlForRoute('event.view_event_list', array('list' => IISEVENTPLUS_BOL_Service::$EVENT_GENERAL)));
                $item->setKey(IISEVENTPLUS_BOL_Service::$EVENT_GENERAL);
                $item->setOrder(2);
                $item->setIconClass('ow_ic_latest');
                array_push($menuItems, $item);

                $toRemoveKeys = array('joined', 'past', 'latest');
                foreach ($menuItems as $key=>$item) {
                    if (in_array((string)$item->getKey(), $toRemoveKeys)) {
                        unset($menuItems[$key]);
                    }
                }
                $event->setData(array('menuItems' => $menuItems));
            }
        }
    }

    public function addEventFilterForm(OW_Event $event)
    {
        $params = $event->getParams();
        $tab = self::$EVENT_GENERAL;
        $participationStatus = '';
        $dateStatus = '';
        $searchTitle = '';
        if (isset($params['tab'])) {
            $tab = $params['tab'];
        }
        if (isset($params['participationStatus'])) {
            $participationStatus = $params['participationStatus'];
        }
        if (isset($params['dateStatus'])) {
            $dateStatus = $params['dateStatus'];
        }
        if (isset($params['categoryStatus'])) {
            $categoryStatus = $params['categoryStatus'];
        }
        if (isset($params['searchTitle'])) {
            $searchTitle = $params['searchTitle'];
        }
        $plugin = OW::getPluginManager()->getPlugin('iiseventplus');
        OW::getDocument()->addScript($plugin->getStaticJsUrl() . 'iiseventplus.js');
        $event->setData(array('eventFilterForm' => $this->getEventFilterForm('EventFilterForm', $tab, $participationStatus, $dateStatus,$categoryStatus,$searchTitle)));
    }

    public function setFilterParameters(){
        if (OW::getRequest()->isPost()) {
            $this->page=1;
            $this->getFilterParameters($_POST);
        }else{
            $this->getFilterParameters( $_GET);
        }
    }

    public function getFilterParameters( $data){
        $eventService = EVENT_BOL_EventService::getInstance();
        if(isset($data['dateStatus'])) {
            $this->dateStatus = $data['dateStatus'];
        }
        switch($this->dateStatus){
            case self::$DATE_LATEST:
                $this->past=false;
                break;
            case self::$DATE_PAST:
                $this->past=true;
                break;
            default:
                $this->past = null;
        }

        if(isset($data['participationStatus'])) {
            $this->participationStatus = $data['participationStatus'];
        }
        switch($this->participationStatus){
            case self::$PARTICIPATE_SURE:
                $this->participateStatus= $eventService::USER_STATUS_YES ;
                break;
            case self::$PARTICIPATE_MAYBE:
                $this->participateStatus= $eventService::USER_STATUS_MAYBE ;
                break;
            case self::$PARTICIPATE_NO:
                $this->participateStatus= $eventService::USER_STATUS_NO ;
                break;
            default:
                $this->participateStatus = null;
        }
        if(isset($data['categoryStatus'])) {
            $this->categoryStatus = $data['categoryStatus'];
        }
        if(isset($data['searchTitle'])) {
            $this->searchTitle = $data['searchTitle'];
        }
    }

    public function getResultForListItemEvent(OW_Event $event)
    {
        if(class_exists("EVENT_BOL_EventService")) {
            $params = $event->getParams();
            $eventService = EVENT_BOL_EventService::getInstance();
            $eventController = $params['eventController'];
            $isPublic = true;
            $addUnapproved = false;
            $userId = '';
            $eventIds = array();
            $activeTab = IISEVENTPLUS_BOL_Service::$EVENT_GENERAL;
            $this->page = $params['page'];
            $this->setFilterParameters();
            if ($params['list'] != IISEVENTPLUS_BOL_Service::$EVENT_MY) {
                $eventAddFilterFormParams = array('tab' => self::$EVENT_GENERAL, 'dateStatus' => $this->dateStatus, 'categoryStatus' => $this->categoryStatus, 'searchTitle' => $this->searchTitle);
                if(isset($_GET['userId'])){
                    $userId = $_GET['userId'];
                }
                $resultsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ADD_EVENT_FILTER_FORM, $eventAddFilterFormParams));
                if (isset($resultsEvent->getData()['eventFilterForm'])) {
                    $eventFilterForm = $resultsEvent->getData()['eventFilterForm'];
                }

            } else {
                $resultsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ADD_EVENT_FILTER_FORM, array('tab' => self::$EVENT_MY, 'participationStatus' => $this->participationStatus, 'dateStatus' => $this->dateStatus, 'categoryStatus' => $this->categoryStatus, 'searchTitle' => $this->searchTitle)));
                if (isset($resultsEvent->getData()['eventFilterForm'])) {
                    $eventFilterForm = $resultsEvent->getData()['eventFilterForm'];
                }
                $isPublic = false;
                $addUnapproved = true;
                $userId = OW::getUser()->getId();
                $activeTab = self::$EVENT_MY;
            }
            if ($this->categoryStatus != null) {
                $eventIds = $this->getEventIdListByCategoryID($this->categoryStatus);
                if ($eventIds == null) {
                    $eventIds[] = -1;
                }
            }
            $events = $eventService->findPublicEventsByFiltering($this->page, null, $userId, $this->participateStatus, $this->past, $eventIds, $addUnapproved, $isPublic, $this->searchTitle);
            $eventsCount = $eventService->findPublicEventsByFilteringCount($userId, $this->participateStatus, $this->past, $eventIds, $addUnapproved, $isPublic, $this->searchTitle);
            $event->setData(array('events' => $events, 'eventsCount' => $eventsCount, 'page' => $this->page));
            $this->setEventController($activeTab, $eventFilterForm, $eventController);
        }
    }

    public function setEventController($activeTab, $filterForm, $eventController)
    {
        $contentMenu = EVENT_BOL_EventService::getInstance()->getContentMenu();
        if($contentMenu->getElement($activeTab)!=null) {
            $contentMenu->getElement($activeTab)->setActive(true);
        }
        $eventController->addComponent('contentMenu', $contentMenu);
        if (isset($filterForm)) {
            $eventController->assign('filterForm', true);
            $eventController->addForm($filterForm);
            $filterFormElementsKey = array();
            foreach ($filterForm->getElements() as $element) {
                if ($element->getAttribute('type') != 'hidden') {
                    $filterFormElementsKey[] = $element->getAttribute('name');
                }
            }
            $eventController->assign('filterFormElementsKey', $filterFormElementsKey);
        }
    }

    /**
     * @param $name
     * @param $tab
     * @param int $selectedParticipationStatus
     * @param int $selectedDateStatus
     * @param int $selectedCategory
     * @param null $searchedTitle
     * @return Form
     */
    public function getEventFilterForm($name, $tab, $selectedParticipationStatus = 1, $selectedDateStatus = 1, $selectedCategory=1,$searchedTitle=null)
    {
        $form = new Form($name);
        $form->setMethod(Form::METHOD_GET);
        $searchTitle = new TextField('searchTitle');
        $searchTitle->addAttribute('placeholder',OW::getLanguage()->text('iiseventplus', 'search_title'));
        $searchTitle->addAttribute('class','event_search_title');
        $searchTitle->addAttribute('id','searchTitle');
        if($searchedTitle!=null) {
            $searchTitle->setValue($searchedTitle);
        }
        $searchTitle->setHasInvitation(false);
        $form->addElement($searchTitle);

        $dateStatus = new Selectbox('dateStatus');
        $option = array();
        $option[IISEVENTPLUS_BOL_Service::$DATE_ALL] = OW::getLanguage()->text('iiseventplus', 'date_all');
        $option[IISEVENTPLUS_BOL_Service::$DATE_LATEST] = OW::getLanguage()->text('iiseventplus', 'date_latest');
        $option[IISEVENTPLUS_BOL_Service::$DATE_PAST] = OW::getLanguage()->text('iiseventplus', 'date_past');
        $dateStatus->setHasInvitation(false);
        $dateStatus->setValue($selectedDateStatus);
        $dateStatus->setOptions($option);
        $dateStatus->addAttribute('id','dateStatus');
        $form->addElement($dateStatus);

        if ($tab == self::$EVENT_MY) {
            $participationStatus = new Selectbox('participationStatus');
            $option = array();
            $option[IISEVENTPLUS_BOL_Service::$PARTICIPATE_ALL] = OW::getLanguage()->text('iiseventplus', 'participate_all');
            $option[IISEVENTPLUS_BOL_Service::$PARTICIPATE_SURE] = OW::getLanguage()->text('iiseventplus', 'participate_sure');
            $option[IISEVENTPLUS_BOL_Service::$PARTICIPATE_MAYBE] = OW::getLanguage()->text('iiseventplus', 'participate_maybe');
            $option[IISEVENTPLUS_BOL_Service::$PARTICIPATE_NO] = OW::getLanguage()->text('iiseventplus', 'participate_no');
            $participationStatus->setHasInvitation(false);
            $participationStatus->setValue($selectedParticipationStatus);
            $participationStatus->setOptions($option);
            $participationStatus->addAttribute('id','participationStatus');
            $form->addElement($participationStatus);
        }
        $resultsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ADD_CATEGORY_FILTER_ELEMENT, array('form' => $form, 'selectedCategory' => $selectedCategory)));
        if(isset($resultsEvent->getData()['form'])) {
            $form = $resultsEvent->getData()['form'];
        }
        $submit = new Submit('save');
        $form->addElement($submit);
        $form->setAction(OW::getRouter()->urlForRoute('event.view_event_list', array('list' => $tab)));

        return $form;
    }

    /***
     * @param $name
     * @return string
     */
    public function getIconUrl($name){
        return OW::getPluginManager()->getPlugin('iiseventplus')->getStaticUrl(). 'images/'.$name.'.svg';
    }

    /***
     * @param $ext
     * @return string
     */
    public function getProperIcon($ext){
        $videoFormats = array('mov','mkv','mp4','avi','flv','ogg','mpg','mpeg');

        $wordFormats = array('docx','doc','docm','dotx','dotm');

        $excelFormats = array('xlsx','xls','xlsm');

        $zipFormats = array('zip','rar');

        $imageFormats =array('jpg','jpeg','gif','tiff','png');

        if(in_array($ext,$videoFormats)){
            return $this->getIconUrl('avi');
        }
        else if(in_array($ext,$wordFormats)){
            return $this->getIconUrl('doc');
        }
        else if(in_array($ext,$excelFormats)){
            return $this->getIconUrl('xls');
        }
        else if(in_array($ext,$zipFormats)){
            return $this->getIconUrl('zip');
        }
        else if(in_array($ext,$imageFormats)){
            return $this->getIconUrl('jpg');
        }
        else if(strcmp($ext,'pdf')==0){
            return $this->getIconUrl('pdf');
        }
        else if(strcmp($ext,'txt')==0){
            return $this->getIconUrl('txt');
        }
        else{
            return $this->getIconUrl('file');
        }
    }
    /*
      * add category filter element
    */
    public function addCategoryFilterElement(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['form'])) {
            $form = $params['form'];
            $categories = $this->getEventCategoryList();
            $categoryStatus = new Selectbox('categoryStatus');
            $option = array();
            $option[null] = OW::getLanguage()->text('iiseventplus','select_category');
            foreach ($categories as $category) {
                $option[$category->id] = $category->label;
            }
            $categoryStatus->setHasInvitation(false);
            if(isset($params['selectedCategory'])) {
                $categoryStatus->setValue($params['selectedCategory']);
            }else if(isset($params['eventId'])){
                $resultsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::GET_EVENT_SELECTED_CATEGORY_ID, array('eventId' => $params['eventId'])));
                if(isset($resultsEvent->getData()['selectedCategoryId'])) {
                    $categoryStatus->setValue($resultsEvent->getData()['selectedCategoryId']);
                }
            }
            $categoryStatus->setOptions($option);
            $categoryStatus->addAttribute('id','categoryStatus');
            $form->addElement($categoryStatus);
            $event->setData(array('form' => $form));
        }
    }

    /*
    * get event selected category id
    */
    public function getEventSelectedCategoryId(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['eventId'])){
            $categoryId = $this->getEventCategoryByEventId($params['eventId']);
            $event->setData(array('selectedCategoryId' => $categoryId));
        }
    }

    /*
     * add leave button in an event view
     */
    public function addLeaveButton(OW_Event $event)
    {
        if(class_exists("EVENT_BOL_EventService")) {
            $params = $event->getParams();
            if (isset($params['creatorId']) && OW::getUser()->getId() != $params['creatorId']
                && isset($params['eventId'])
            ) {
                $eventService = EVENT_BOL_EventService::getInstance();
                $eventsCount = $eventService->findPublicEventsByFilteringCount(OW::getUser()->getId(), null, null, null, true, false, null);
                $page = null;
                if (isset($params['page'])) {
                    $page = $params['page'];
                }
                $events = $eventService->findPublicEventsByFiltering($page, $eventsCount, OW::getUser()->getId(), null, null, null, true, false, null);
                $eventId = $params['eventId'];
                foreach ($events as $ev) {
                    if ($ev->getId() == $eventId) {
                        $button = array(
                            'leave' =>
                                array(
                                    'url' => OW::getRouter()->urlForRoute('iiseventplus.leave', array('eventId' => $eventId)),
                                    'label' => OW::getLanguage()->text('iiseventplus', 'leave_button_label'),
                                    'confirmMessage' => OW::getLanguage()->text('iiseventplus', 'leave_confirm_message')
                                )
                        );
                        $event->setData(array('leaveButton' => $button));
                        break;
                    }
                }
            }
        }
    }

    /*
    * get event selected category id
    */
    public function getEventSelectedCategoryLabel(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['eventId'])){
            $categoryId = $this->getEventCategoryByEventId($params['eventId']);
            if($categoryId!=null) {
                $category = $this->categoryDao->findById($categoryId);
                $event->setData(array('categoryLabel' => $category->getLabel(), 'categoryId' => $categoryId));
            }
        }
    }

    public function addFilterParametersToPaging(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['setForPaging'])){
            $pagingParams="";
            if(isset($_POST['searchTitle']) && $_POST['searchTitle']!=""){
                $pagingParams=$pagingParams."&searchTitle=".$_POST['searchTitle'];
            }
            if(isset($_POST['dateStatus']) && $_POST['dateStatus']!=""){
                $pagingParams=$pagingParams."&dateStatus=".$_POST['dateStatus'];
            }
            if(isset($_POST['participationStatus']) && $_POST['participationStatus']!=""){
                $pagingParams=$pagingParams."&participationStatus=".$_POST['participationStatus'];
            }
            if(isset($_POST['categoryStatus']) && $_POST['categoryStatus']!=""){
                $pagingParams=$pagingParams."&categoryStatus=".$_POST['categoryStatus'];
            }
            $event->setData(array('pagingParams' => $pagingParams));
        }
    }

    public function addCategoryToEvent(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['eventId']) && isset($params['categoryId']))
        {
            $this->eventInformationDao->addCategoryToEvent($params['eventId'],$params['categoryId']);
        }
    }

    /***
     * @param $eventId
     * @param $userId
     */
    public function leaveEvent($eventId , $userId){
        $this->eventInformationDao->leaveUserFromEvent($eventId,$userId);
    }

    public function getEventCategoryList()
    {
        return $this->categoryDao->findAll();
    }

    public function getCategoryById($id)
    {
        return $this->categoryDao->findById($id);
    }
    public function getEventInformationByCategoryId($categoryId)
    {
        return $this->eventInformationDao->getEventInformationByCategoryId($categoryId);
    }

    public function getEventIdListByCategoryID($categoryId)
    {
        if($categoryId!=null) {
            $eventInfoList = $this->getEventInformationByCategoryId($categoryId);
            $evetIdList = array();
            foreach ($eventInfoList as $eventInfo) {
                $evetIdList[] = $eventInfo->eventId;
            }
            return $evetIdList;
        }
    }


    public function getEventCategoryByEventId($eventId)
    {
        $eventInfo =  $this->eventInformationDao->getEventInformationByEventId($eventId);
        if(isset($eventInfo->categoryId)) {
            return $eventInfo->categoryId;
        }
        return null;
    }

    public function addEventCategory($label)
    {
        $category = new IISEVENTPLUS_BOL_Category();
        $category->label = $label;
        IISEVENTPLUS_BOL_CategoryDao::getInstance()->save($category);
    }

    public function deleteEventCategory( $categoryId )
    {
        $categoryId = (int) $categoryId;
        if ( $categoryId > 0 )
        {
            $this->eventInformationDao->deleteByCategoryId($categoryId);
            $this->categoryDao->deleteById($categoryId);
        }
    }

    private function getCategoryKey( $name )
    {
        return 'dept_' . trim($name);
    }


    public function getItemForm($id)
    {
        $item = $this->getCategoryById($id);
        $formName = 'edit-item';
        $submitLabel = 'edit';
        $actionRoute = OW::getRouter()->urlFor('IISEVENTPLUS_CTRL_Admin', 'editItem');

        $form = new Form($formName);
        $form->setAction($actionRoute);

        if ($item != null) {
            $idField = new HiddenField('id');
            $idField->setValue($item->id);
            $form->addElement($idField);
        }

        $fieldLabel = new TextField('label');
        $fieldLabel->setRequired();
        $fieldLabel->setInvitation(OW::getLanguage()->text('iiseventplus', 'label_category_label'));
        $fieldLabel->setValue($item->label);
        $fieldLabel->setHasInvitation(true);
        $validator = new IISEVENTPLUS_CLASS_LabelValidator();
        $language = OW::getLanguage();
        $validator->setErrorMessage($language->text('iiseventplus', 'label_error_already_exist'));
        $fieldLabel->addValidator($validator);
        $form->addElement($fieldLabel);

        $submit = new Submit('submit', 'button');
        $submit->setValue(OW::getLanguage()->text('iiseventplus', 'edit_item'));
        $form->addElement($submit);

        return $form;
    }

    public function editItem($id, $label)
    {
        $item = $this->getCategoryById($id);
        if ($item == null) {
            return;
        }
        if ($label == null) {
            $label = false;
        }
        $item->label = $label;

        $this->categoryDao->save($item);
        return $item;
    }

    public function getSearchBox(OW_Event $event)
    {

    }

    public function checkIfEventPlusIsActive(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['checkActive']))
        {
            $pluginIisSecurity = BOL_PluginDao::getInstance()->findPluginByKey('iiseventplus');
            if(isset($pluginIisSecurity) && $pluginIisSecurity->isActive()) {
                if( Ow::getUser()->isAuthenticated()) {
                    $event->setData(array('list' => IISEVENTPLUS_BOL_Service::$EVENT_MY));
                }else{
                    $event->setData(array('list' => IISEVENTPLUS_BOL_Service::$EVENT_GENERAL));
                }
            }
        }
    }

    /***
     * @param $eventId
     * @param int $first
     * @param $count
     * @return array<BOL_Attachment>
     */
    public function findFileList($eventId, $first=0, $count)
    {
        $eventFileList = $this->eventFileDao->findFileListByEventId($eventId, $first, $count);
        $attachmentList = array();
        foreach ( $eventFileList as $eventFile )
        {
            $attachment = BOL_AttachmentDao::getInstance()->findById($eventFile->attachmentId);
            if(isset($attachment) && $attachment->getId()>0) {
                $attachmentList[] = $attachment;
            }else{
                $this->deleteFileForEvent($eventId, $eventFile->attachmentId);
            }
        }

        return $attachmentList;

    }

    public function findFileListCount($eventId)
    {
        return $this->eventFileDao->findCountByEventId($eventId);

    }

    public function getUploadFileForm($eventId)
    {
        $plugin = OW::getPluginManager()->getPlugin('iiseventplus');

        $language = OW::getLanguage();

        OW::getDocument()->setHeading($language->text('iiseventplus', 'file_create_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_new');
        OW::getDocument()->setTitle($language->text('iiseventplus', 'file_create_page_title'));
        OW::getDocument()->setDescription($language->text('iiseventplus', 'file_create_page_description'));

        $form = new IISEVENTPLUS_FileUploadForm($eventId);
        $actionRoute = OW::getRouter()->urlFor('IISEVENTPLUS_CTRL_Base', 'addFile', array('eventId' => $eventId));
        $form->setAction($actionRoute);
        return $form;
    }

    public function addFileForEvent($eventId, $attachmentId){
        return $this->eventFileDao->addFileForEvent($eventId,$attachmentId);
    }

    public function deleteFileForEvent($eventId, $attachmentId){
        $this->eventFileDao->deleteEventFilesByAidAndEid($eventId,$attachmentId);
    }

    public function deleteFileForEventByEventId($eventId){
        $this->eventFileDao->deleteEventFilesByEventId($eventId);
    }

    public function findFileIdByAidAndGid($eventId, $attachmentId){
        return $this->eventFileDao->findFileIdByAidAndEid($eventId,$attachmentId);
    }
    public function deleteFiles(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['eventId'])) {
            $filesDto = $this->eventFileDao->getEventFilesByEventId($params['eventId']);
            foreach ($filesDto as $file) {
                try {
                    OW::getEventManager()->trigger(new OW_Event("feed.delete_item", array(
                        'entityType' => 'event-add-file',
                        'entityId' => $file->id
                    )));
                    OW::getEventManager()->call('notifications.remove', array(
                        'entityType' => 'event-add-file',
                        'entityId' => $file->id
                    ));
                    $this->deleteFileForEventByEventId($params['eventId']);
                    BOL_AttachmentService::getInstance()->deleteAttachmentById($file->attachmentId);
                } catch (Exception $e) {

                }
            }
        }
        else if(isset($params['allFiles'])) {
            $filesDto = $this->eventFileDao->findAllFiles();
            foreach ($filesDto as $file) {
                try {
                    BOL_AttachmentService::getInstance()->deleteAttachmentById($file->attachmentId);
                    OW::getEventManager()->trigger(new OW_Event("feed.delete_item", array(
                        'entityType' => 'event-add-file',
                        'entityId' => $file->id
                    )));
                    OW::getEventManager()->call('notifications.remove', array(
                        'entityType' => 'event-add-file',
                        'entityId' => $file->id
                    ));
                } catch (Exception $e) {

                }
            }
        }
    }

    public function addFileWidget(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['controller']) && isset($params['eventId'])){
            $bcw = new BASE_CLASS_WidgetParameter();
            $bcw->additionalParamList=array('entityId'=>$params['eventId']);
            $eventController = $params['controller'];
            $eventController->addComponent('eventFileList', new IISEVENTPLUS_MCMP_FileListWidget($bcw));
            $fileBoxInformation = array(
                'show_title' => true,
                'title' => OW_Language::getInstance()->text('iiseventplus', 'widget_files_title'),
                'wrap_in_box' => true,
                'icon' => 'ow_ic_info',
                'type' => "",
            );
            $eventController->assign('fileBoxInformation', $fileBoxInformation);
        }
    }

    public function onCollectNotificationActions( BASE_CLASS_EventCollector $e )
    {
        $e->add(array(
            'section' => 'event',
            'action' => 'event-add-file',
            'description' => OW::getLanguage()->text('iiseventplus', 'email_notifications_setting_file'),
            'selected' => true,
            'sectionLabel' => OW::getLanguage()->text('iiseventplus', 'email_notification_section_label'),
            'sectionIcon' => 'ow_ic_write'
        ));
    }

    public function onInviteUser(OW_Event $event){
        $invitationParams =  $event->getParams();

        $userId = $invitationParams['userId'];
        $inviterId = $invitationParams['inviterId'];
        $eventId = $invitationParams['eventId'];
        $eventTitle = $invitationParams['eventTitle'];

        $userService = BOL_UserService::getInstance();
        $displayName = $userService->getDisplayName($inviterId);
        $inviterUrl = $userService->getUserUrl($inviterId);

        $eventUrl = OW::getRouter()->urlForRoute('event.view', array('eventId' => $eventId));
        $invitedListUrl = OW::getRouter()->urlForRoute('event.view_event_list',array('list' => 'invited'));

        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($inviterId));

        $params = array(
            'pluginKey' => 'event',
            'entityType' => 'event_invitation',
            'entityId' => $eventId,
            'action' => 'event-invitation',
            'userId' => $userId,
            'time' => time()
        );

        $data = array(
            'avatar' => $avatars[$inviterId],
            'string' => array(
                'key' => 'iiseventplus+event_user_invitation_notification',
                'vars' => array(
                    'userName' => $displayName,
                    'userUrl' => $inviterUrl,
                    'eventTitle' => $eventTitle,
                    'eventUrl'=> $eventUrl
                )
            ),
            'url' => $invitedListUrl,
        );

        $e = new OW_Event('notifications.add', $params, $data);
        OW::getEventManager()->trigger($e);
    }

    public function onNotificationRender( OW_Event $e )
    {
        //how to show
        $params = $e->getParams();
        if ( $params['pluginKey'] != 'event' || $params['entityType'] != 'event_invitation')
        {
            return;
        }
        $data = $params['data'];

        if ( !isset($data['avatar']['urlInfo']['vars']['username']) )
        {
            return;
        }

        $userService = BOL_UserService::getInstance();
        $user = $userService->findByUsername($data['avatar']['urlInfo']['vars']['username']);
        if ( !$user )
        {
            return;
        }
        $e->setData($data);
    }

    public function getAttachmentUrl($name)
    {
        return OW::getStorage()->getFileUrl($this->getAttachmentDir($name));
    }

    public function getAttachmentDir($name)
    {
        return OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments' . DS .$name ;
    }

    public function manageAddFile($eventId, $item){
        $resultArr = array('result' => false, 'message' => 'General error');
        $bundle = IISSecurityProvider::generateUniqueId();

        $pluginKey = 'iiseventplus';
        if(isset($_POST['name']) && $_POST['name']!=""){
            $explodedName = explode('.',$item['name'] );
            $item['name'] = $_POST['name'].'.'.end($explodedName);
        }
        try {
            $dtoArr = BOL_AttachmentService::getInstance()->processUploadedFile($pluginKey, $item, $bundle);
            OW::getEventManager()->call('base.attachment_save_image', array('uid' => $bundle, 'pluginKey' => $pluginKey));
            $resultArr['result'] = true;
            $resultArr['url'] = $dtoArr['url'];
            $attachmentId = $dtoArr['dto']->id;
            $fileId = $this->addFileForEvent($eventId,$attachmentId);
            $eventDto = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
            if(!isset($eventDto)){
                return $resultArr;
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

            $users = EVENT_BOL_EventService::getInstance()->findAllUsersForEvent($eventId);

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
        return $resultArr;
    }
    public function getFileUrlByFileId($fileId){
        $file = $this->eventFileDao->findById($fileId);
        if(!isset($file) || !isset($file->attachmentId)){
            return null;
        }
        $item = BOL_AttachmentDao::getInstance()->findById($file->attachmentId);
        if(!isset($item)){
            return null;
        }
        $path = OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments' . DS . $item->fileName;
        $fileUrl = OW::getStorage()->getFileUrl( $path );
        return $fileUrl;
    }
    public function feedOnItemRender( OW_Event $e)
    {
        $params = $e->getParams();
        $data = $e->getData();
        if (!isset($data["string"]["key"]) || $data["string"]["key"] != "iiseventplus+feed_add_file_string")
            return;
        else {
            $event = explode('/', $data["string"]["vars"]["eventUrl"]);
            $eventId = end($event);
            $eventService = EVENT_BOL_EventService::getInstance();
            $data["string"]["vars"]["eventTitle"] = $eventService->findEvent($eventId)->title;
            $data["string"]["vars"]["eventUrl"] = $eventService->getEventUrl($eventId);
            $data["string"]["vars"]["fileUrl"] = $this->getFileUrlByFileId($params["action"]["entityId"]);
            $e->setData($data);
        }
    }

}

class IISEVENTPLUS_FileUploadForm extends Form
{
    public function __construct($groupId)
    {
        parent::__construct('fileUploadForm');

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

        $language = OW::getLanguage();

        $nameField = new TextField('name');
        $nameField->setLabel($language->text('iiseventplus', 'create_field_file_name_label'));
        $this->addElement($nameField);

        $fileField = new FileField('fileUpload');
        $fileField->setLabel($language->text('iiseventplus', 'create_field_file_upload_label'));
        $this->addElement($fileField);

        $groupIdElement = new HiddenField('id');
        $groupIdElement->setValue($groupId);
        $this->addElement($groupIdElement);

        $saveField = new Submit('save');
        $saveField->setValue(OW::getLanguage()->text('iiseventplus', 'create_submit_btn_label'));
        $this->addElement($saveField);
    }
}