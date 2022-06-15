<?php

/**
 * Copyright (c) 2016, Mohammad Agha Abbasloo
 * All rights reserved.
 */

/**
 * 
 *
 * @author Mohammad Aghaabbasloo
 * @package ow_plugins.iisgroupsplus
 * @since 1.0
 */
class IISGROUPSPLUS_BOL_Service
{
    const SET_MOBILE_USER_MANAGER_STATUS = 'iisgroupsplus.set.mobile.user.manager.status';
    const SET_USER_MANAGER_STATUS = 'iisgroupsplus.set.user.manager.status';
    const DELETE_USER_AS_MANAGER = 'iisgroupsplus.delete.user.as.manager';
    const DELETE_FILES = 'iisgroupsplus.delete.files';
    const ADD_FILE_WIDGET = 'iisgroupsplus.add.file.widget';
    const CHECK_USER_MANAGER_STATUS = 'iisgroupsplus.check.user.manager.status';
    const ON_UPDATE_GROUP_STATUS = 'iisgroupsplus.on.update.group.status';
    const CHECK_CAN_INVITE_ALL = 'iisgroupsplus.check.can.invite.all';
    const ADD_USERS_AUTOMATICALLY = 'iisgroupsplus.add.users.automatically';
    const SET_CHANNEL_GROUP = 'iisgroupsplus.set.channel.group';
    const SET_CHANNEL_FOR_GROUP = 'iisgroupsplus.set.channel.for.group';
    const ON_CHANNEL_ADD_WIDGET = 'iisgroupsplus.on.channel.add.widget';
    const ON_CHANNEL_LOAD = 'iisgroupsplus.on.channel.load';

    const WCC_CHANNEL = 'channel';
    const WCC_GROUP = 'group';

    const WCU_MANAGERS= 'manager';
    const WCU_PARTICIPANT = 'participant';

    private static $classInstance;

    private  $groupInformationDao;
    private  $groupManagersDao;
    private  $categoryDao;
    private  $groupFileDao;
    private  $channelDao;
    private  $groupSettingDao;
    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }


    private function __construct()
    {
        $this->groupInformationDao = IISGROUPSPLUS_BOL_GroupInformationDao::getInstance();
        $this->groupManagersDao = IISGROUPSPLUS_BOL_GroupManagersDao::getInstance();
        $this->categoryDao = IISGROUPSPLUS_BOL_CategoryDao::getInstance();
        $this->groupFileDao = IISGROUPSPLUS_BOL_GroupFilesDao::getInstance();
        $this->channelDao = IISGROUPSPLUS_BOL_ChannelDao::getInstance();
        $this->groupSettingDao = IISGROUPSPLUS_BOL_GroupSettingDao::getInstance();
    }

    public static function getForcedGroupSubmitFormJS()
    {
        return "$(document).ready(function () {
            $('input[name=\"addNewForcedGroupButton\"], input[name=\"editNewForcedGroupButton\"]').on('click', function (e) {
            $(e.target).addClass(\"ow_inprogress\");
                var profileQuestionFiltersList = {}
                var all_inputs = $('input[name^=\"profileQuestionFilter\"]');
                $.each(all_inputs, function(index, value){
                    if ($(value).is(\":checked\"))
                    profileQuestionFiltersList[$(value).attr('name')] = 1
                });
                var gId = $(e.target).closest('form').find('input[name=\"gId\"]').val()
                $.ajax( {
                    url: '" . OW::getRouter()->urlFor('IISGROUPSPLUS_CTRL_ForcedGroups', 'addAllUsersToGroup') . "',
                    type: 'POST',
                    data: { gId: gId, profileQuestionFiltersList: profileQuestionFiltersList, forcedStay: $('input[name=\"forcedStay\"]').is(\":checked\") },
                    dataType: 'json',
                    success: function( result )
                    {           
                        $(e.target).removeClass(\"ow_inprogress\");         
                        if  (result['result'] === 'success'){
                            OW.info(result['message']);
                            if (result['refresh'])
                                if ($(e.target).attr('name') === 'addNewForcedGroupButton')
                                    window.location.reload();
                                else
                                    window.location = result['forcedGroupsURL'];
                        }
                        else
                            OW.error(result['message']);
                    }
                });
            });
        });
        
        $('input[id^=\"select_all_options_\"]').on('change', function (e) {
            if  ($(e.target).is(':checked'))
                $(e.target).closest('tr').find('input[type=\"checkbox\"]').attr(\"checked\", \"checked\");
            else
                $(e.target).closest('tr').find('input[type=\"checkbox\"]').removeAttr(\"checked\");
        });
        ";
    }

    public function addGroupFilterForm(OW_Event $event)
    {
        $params = $event->getParams();
        $tab = 'latest';
        $categoryStatus=null;
        $searchTitle=null;
       if (isset($params['tab'])) {
            $tab = $params['tab'];
        }
        if (isset($params['categoryStatus']) && !empty(trim($params['categoryStatus']))) {
            $categoryStatus = $params['categoryStatus'];
        }
        if (isset($params['searchTitle']) && !empty(trim($params['searchTitle']))) {
            $searchTitle = $params['searchTitle'];
        }

        if (isset($params['url']) && !empty(trim($params['url']))) {
            $url = $params['url'];
        }
        $plugin = OW::getPluginManager()->getPlugin('iisgroupsplus');
        OW::getDocument()->addScript($plugin->getStaticJsUrl() . 'iisgroupsplus.js');
        $event->setData(array('groupFilterForm' => $this->getGroupFilterForm('GroupFilterForm', $tab,$categoryStatus,$searchTitle,$url)));
    }


    public function getResultForListItemGroup(OW_Event $event)
    {
        $params = $event->getParams();

        $groupService = GROUPS_BOL_Service::getInstance();
        $groupController = $params['groupController'];
        $tab='';
        $categoryStatus='';
        $searchTitle='';
        $latest='';
        $popular='';
        $activeTab=1;
        $groupIds = array();
        $page =1;
        $first = $params['first'];
        $url = $params['url'];
        $count = $params['count'];
        $perPage = $params['perPage'];
        if(isset($params['page'])){
            $page = $params['page'];
        }
        if (OW::getRequest()->isPost()) {
            $categoryStatus = $_POST['categoryStatus'];
            $searchTitle = $_POST['searchTitle'];
            $first = ($page - 1) * $perPage;
            $count = $perPage;
        }

        if(isset($_GET['categoryStatus'])){
            $categoryStatus = $_GET['categoryStatus'];
            $first = ($page - 1) * $perPage;
            $count = $perPage;
        }

        if(isset($_GET['searchTitle'])){
            $searchTitle = $_GET['searchTitle'];
        }

        if(isset($params['activeTab'])){
            $tab = $params['activeTab'];
        }
        if(isset($params['popular'])){
            $popular = $params['popular'];
        }
        if(isset($params['onlyActive'])){
            $onlyActive = $params['onlyActive'];
        }
        if(isset($params['latest'])){
            $latest = $params['latest'];
        }
        $userId='';
        if(isset($params['userId'])){
            $userId = $params['userId'];
        }

        $resultsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ADD_GROUP_FILTER_FORM, array('tab' => $tab, 'categoryStatus' =>$categoryStatus, 'searchTitle' => $searchTitle,'url'=>$url)));
        if (isset($resultsEvent->getData()['groupFilterForm'])) {
            $groupFilterForm = $resultsEvent->getData()['groupFilterForm'];
        }
        if($categoryStatus!=null) {
            $groupIds = $this->getGroupIdListByCategoryID($categoryStatus);
            if($groupIds==null){
                $groupIds[]=-1;
            }
        }
        $groups = $groupService->findGroupsByFiltering($popular,$onlyActive,$latest,$first,$count,$userId,$groupIds,$searchTitle);
        $groupsCount =$groupService->findGroupsByFilteringCount($popular,$onlyActive,$latest,$userId,$groupIds,$searchTitle);
        $params = array('groups' => $groups, 'groupsCount' => $groupsCount, 'page'=>$page);
        if(isset($searchTitle) && !empty($searchTitle))
        {
            $params['searchTitle'] = $searchTitle;
        }
        if(isset($categoryStatus) && !empty($categoryStatus))
        {
            $params['categoryStatus'] = $categoryStatus;
        }
        $event->setData($params);
        $this->setGroupController($activeTab, $groupFilterForm, $groupController);
    }

    public function setGroupController($activeTab, $filterForm, $groupController)
    {
        if (isset($filterForm)) {
            $groupController->assign('filterForm', true);
            $groupController->addForm($filterForm);
            $filterFormElementsKey = array();
            foreach ($filterForm->getElements() as $element) {
                if ($element->getAttribute('type') != 'hidden') {
                    $filterFormElementsKey[] = $element->getAttribute('name');
                }
            }
            $groupController->assign('filterFormElementsKey', $filterFormElementsKey);
        }
    }

    /**
     * @param $name
     * @param $tab
     * @param int $selectedCategory
     * @param null $searchedTitle
     * @return Form
     */
    public function getGroupFilterForm($name, $tab, $selectedCategory=null,$searchedTitle=null,$url=null)
    {
        $form = new Form($name);
        if(isset($url)) {
            $form->setAction($url);
        }
        $form->setMethod(Form::METHOD_GET);
        $searchTitle = new TextField('searchTitle');
        $searchTitle->addAttribute('placeholder',OW::getLanguage()->text('iisgroupsplus', 'search_title'));
        $searchTitle->addAttribute('class','group_search_title');
        $searchTitle->addAttribute('id','searchTitle');
        if($searchedTitle!=null) {
            $searchTitle->setValue($searchedTitle);
        }
        $searchTitle->setHasInvitation(false);
        $form->addElement($searchTitle);

        $resultsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ADD_GROUP_CATEGORY_FILTER_ELEMENT, array('form' => $form, 'selectedCategory' => $selectedCategory)));
        if(isset($resultsEvent->getData()['form'])) {
            $form = $resultsEvent->getData()['form'];
        }
        //$submit = new Submit('save');
        //$form->addElement($submit);

        return $form;
    }
    /***
     * @param $name
     * @return string
     */
    public function getIconUrl($name){
        if (IISSecurityProvider::themeCoreDetector()){
            return OW::getPluginManager()->getPlugin('iisgroupsplus')->getStaticUrl(). 'images/'.$name.'.png';
        }else{
            return OW::getPluginManager()->getPlugin('iisgroupsplus')->getStaticUrl(). 'images/'.$name.'.svg';
        }
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

        if (IISSecurityProvider::themeCoreDetector()){
            $imageFormats =array('jpg','jpeg','gif','tiff');
        }else{
            $imageFormats =array('jpg','jpeg','gif','tiff','png');
        }


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
        else if(strcmp($ext,'png')==0){
            return $this->getIconUrl('png');
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
    public function addGroupCategoryFilterElement(OW_Event $event)
    {
        $params = $event->getParams();
        $data = $event->getData();
        if(isset($params['form'])) {
            $form = $params['form'];
            $categories = $this->getGroupCategoryList();
            $categoryStatus = new Selectbox('categoryStatus');
            $option = array();
            $option[null] = OW::getLanguage()->text('iisgroupsplus','select_category');
            foreach ($categories as $category) {
                $option[$category->id] = $category->label;
            }
            $categoryStatus->setHasInvitation(false);
            if(isset($params['selectedCategory'])) {
                $categoryStatus->setValue($params['selectedCategory']);
            }else if(isset($params['groupId'])){
                $resultsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::GET_GROUP_SELECTED_CATEGORY_ID, array('groupId' => $params['groupId'])));
                if(isset($resultsEvent->getData()['selectedCategoryId'])) {
                    $categoryStatus->setValue($resultsEvent->getData()['selectedCategoryId']);
                }
            }
            $categoryStatus->setOptions($option);
            $categoryStatus->addAttribute('id','categoryStatus');
            $form->addElement($categoryStatus);
            $data['form'] = $form;
            $data['hasCategoryFilter'] = true;
            $event->setData($data);
        }
    }

    /*
    * get group selected category id
    */
    public function getGroupSelectedCategoryId(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['groupId'])){
            $categoryId = $this->getGroupCategoryByGroupId($params['groupId']);
            $event->setData(array('selectedCategoryId' => $categoryId));
        }
    }

    /*
    * get group selected category id
    */
    public function getGroupSelectedCategoryLabel(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['groupId'])){
            $categoryId = $this->getGroupCategoryByGroupId($params['groupId']);
            if($categoryId!=null) {
                $category = $this->categoryDao->findById($categoryId);
                if($category != null){
                    $event->setData(array('categoryLabel' => $category->getLabel(),'categoryStatus'=>$categoryId));
                }
            }
        }
    }


    public function addCategoryToGroup(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['groupId']) && isset($params['categoryId']))
        {
            $this->groupInformationDao->addCategoryToGroup($params['groupId'],$params['categoryId']);
        }
    }


    public function getGroupCategoryList()
    {
        return $this->categoryDao->findAll();
    }

    public function getCategoryById($id)
    {
        return $this->categoryDao->findById($id);
    }
    public function getGroupInformationByCategoryId($categoryId)
    {
        return $this->groupInformationDao->getGroupInformationByCategoryId($categoryId);
    }

    public function getGroupIdListByCategoryID($categoryId)
    {
        if($categoryId!=null) {
            $groupInfoList = $this->getGroupInformationByCategoryId($categoryId);
            $groupIdList = array();
            foreach ($groupInfoList as $groupInfo) {
                $groupIdList[] = $groupInfo->groupId;
            }
            return $groupIdList;
        }
    }


    public function getGroupCategoryByGroupId($groupId)
    {
        $groupInfo =  $this->groupInformationDao->getGroupInformationByGroupId($groupId);
        if(isset($groupInfo->categoryId)) {
            return $groupInfo->categoryId;
        }
        return null;
    }

    public function addGroupCategory($label)
    {
        $category = new IISGROUPSPLUS_BOL_Category();
        $category->label = $label;
        IISGROUPSPLUS_BOL_CategoryDao::getInstance()->save($category);
    }

    public function deleteGroupCategory( $categoryId )
    {
        $categoryId = (int) $categoryId;
        if ( $categoryId > 0 )
        {
            $this->groupInformationDao->deleteByCategoryId($categoryId);
            $this->categoryDao->deleteById($categoryId);
        }
    }

    public function getItemForm($id)
    {
        $item = $this->getCategoryById($id);
        $formName = 'edit-item';
        $submitLabel = 'edit';
        $actionRoute = OW::getRouter()->urlFor('IISGROUPSPLUS_CTRL_Admin', 'editItem');

        $form = new Form($formName);
        $form->setAction($actionRoute);

        if ($item != null) {
            $idField = new HiddenField('id');
            $idField->setValue($item->id);
            $form->addElement($idField);
        }

        $fieldLabel = new TextField('label');
        $fieldLabel->setRequired();
        $fieldLabel->setInvitation(OW::getLanguage()->text('iisgroupsplus', 'label_category_label'));
        $fieldLabel->setValue($item->label);
        $fieldLabel->setHasInvitation(true);
        $validator = new IISGROUPSPLUS_CLASS_LabelValidator();
        $language = OW::getLanguage();
        $validator->setErrorMessage($language->text('iisgroupsplus', 'label_error_already_exist'));
        $fieldLabel->addValidator($validator);
        $form->addElement($fieldLabel);

        $submit = new Submit('submit', 'button');
        $submit->setValue(OW::getLanguage()->text('base', 'ow_ic_save'));
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

    public function addWidgetToOthers(OW_Event $event)
    {
        $params = $event->getParams();

        if ( !isset($params['place']) || !isset($params['section']) )
        {
            return;
        }
        try
        {
            $widgetService = BOL_ComponentAdminService::getInstance();
            $widget = $widgetService->addWidget('IISGROUPSPLUS_CMP_PendingInvitation', false);
            $widgetUniqID = $params['place'] . '-' . $widget->className;

            //*remove if exists
            $widgets = $widgetService->findPlaceComponentList($params['place']);
            foreach ( $widgets as $w )
            {
                if($w['uniqName'] == $widgetUniqID)
                    $widgetService->deleteWidgetPlace($widgetUniqID);
            }
            //----------*/

            //add
            $placeWidget = $widgetService->addWidgetToPlace($widget, $params['place'], $widgetUniqID);
            $widgetService->addWidgetToPosition($placeWidget, $params['section'], -1);
        }
        catch ( Exception $e ) { }
    }

    public function onGroupToolbarCollect( BASE_CLASS_EventCollector $e )
    {
        $params = $e->getParams();
        if ( !OW::getUser()->isAuthenticated() || !isset($params['groupId']) )
        {
            return;
        }
        $groupId = $params['groupId'];
        $users = GROUPS_BOL_Service::getInstance()->findAllInviteList($groupId);
        if($users!=null && sizeof($users)>0) {
            $e->add(array(
                'label' => OW::getLanguage()->text('iisgroupsplus', 'pending_invitation'),
                'href' => '#',
                'click' => "javascript:OW.ajaxFloatBox('IISGROUPSPLUS_CMP_PendingUsers', {groupId: '".$groupId."'} , {width:700, iconClass: 'ow_ic_add'});"
            ));
        }
    }

    public function setUserManagerStatus(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['contextParentActionKey']) && isset($params['userId']) &&
            isset($params['groupOwnerId'])&& isset($params['groupId']) && isset($params['contextActionMenu'])){
            if ($params['userId'] != $params['groupOwnerId']) {
                $contextAction = new BASE_ContextAction();
                $contextAction->setParentKey($params['contextParentActionKey']);
                if ($params['groupOwnerId'] != $params['userId']) {
                    $groupManager = $this->groupManagersDao->getGroupManagerByUidAndGid($params['groupId'],$params['userId']);
                    if(isset($groupManager)){
                        $contextAction->setKey('delete_user_as_manager');
                        $contextAction->setLabel(OW::getLanguage()->text('iisgroupsplus', 'remove_group_user_manager_label'));
                        $callbackUri = OW::getRequest()->getRequestUri();
                        $deleteUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('IISGROUPSPLUS_CTRL_Groups', 'deleteUserAsManager', array(
                            'groupId' => $params['groupId'],
                            'userId' => $params['userId']
                        )), array(
                            'redirectUri' => urlencode($callbackUri)
                        ));

                        $contextAction->setUrl('javascript://');
                        $contextAction->addAttribute('data-message', OW::getLanguage()->text('iisgroupsplus', 'delete_group_user_confirmation'));
                        $contextAction->addAttribute('onclick', "return confirm_redirect($(this).data().message, '$deleteUrl')");
                    }else {
                        $contextAction->setKey('add_user_as_manager');
                        $contextAction->setLabel(OW::getLanguage()->text('iisgroupsplus', 'add_group_user_as_manager_label'));
                        $callbackUri = OW::getRequest()->getRequestUri();
                        $addUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('IISGROUPSPLUS_CTRL_Groups', 'addUserAsManager', array(
                            'groupId' => $params['groupId'],
                            'userId' => $params['userId']
                        )), array(
                            'redirectUri' => urlencode($callbackUri)
                        ));
                        $contextAction->setUrl('javascript://');
                        $contextAction->addAttribute('data-message', OW::getLanguage()->text('base', 'are_you_sure'));
                        $contextAction->addAttribute('onclick', "return confirm_redirect($(this).data().message, '$addUrl')");
                    }
                } else {
                    $contextAction->setUrl('javascript://');
                    $contextAction->addAttribute('data-message', OW::getLanguage()->text('iisgroupsplus', 'group_owner_delete_error'));
                    $contextAction->addAttribute('onclick', "OW.error($(this).data().message); return false;");
                }
                $params['contextActionMenu']->addAction($contextAction);
            }
        }
    }

    public function deleteUserManager($groupId,$userId){
        if(!isset($groupId) || !isset($userId) ){
            return;
        }
        $this->groupManagersDao->deleteGroupManagerByUidAndGid($groupId,$userId);
    }

    public function addUserAsManager($groupId,$userId){
        if(!isset($groupId) || !isset($userId) ){
            return;
        }
        $this->groupManagersDao->addUserAsManager($groupId,$userId);
    }

    public function checkUserManagerStatus(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['userId'])){
            $userId = $params['userId'];
        }
        else{
            $userId = OW::getUser()->getId();
        }

        if(isset($params['groupId'])){
            $isManager = false;
            $userGroupManager = $this->groupManagersDao->getGroupManagerByUidAndGid($params['groupId'],$userId);
            if(isset($userGroupManager)){
                $isManager = true;
            }

            $event->setData(array('isUserManager'=>$isManager));
        }
    }
    public function deleteUserAsManager(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['groupId']) && isset($params['userId']) ){
            $this->groupManagersDao->deleteGroupManagerByUidAndGid($params['groupId'],$params['userId']);
        }
    }

    public function setMobileUserManagerStatus(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['contextMenu']) && isset($params['userId']) &&
            isset($params['groupOwnerId'])&& isset($params['groupId'])){
            if ($params['userId'] != $params['groupOwnerId']) {
                if ($params['groupOwnerId'] != $params['userId']) {
                    $groupManager = $this->groupManagersDao->getGroupManagerByUidAndGid($params['groupId'],$params['userId']);
                    if(isset($groupManager)){
                        $callbackUri = OW::getRequest()->getRequestUri();
                        $deleteUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('IISGROUPSPLUS_CTRL_Groups', 'deleteUserAsManager', array(
                            'groupId' => $params['groupId'],
                            'userId' => $params['userId']
                        )), array(
                            'redirectUri' => urlencode($callbackUri)
                        ));
                        array_unshift($params['contextMenu'], array(
                            'label' => OW::getLanguage()->text('iisgroupsplus', 'remove_group_user_manager_label'),
                            'attributes' => array(
                                'onclick' => 'return confirm_redirect($(this).data(\'confirm-msg\'), \''.$deleteUrl.'\');',
                                "data-confirm-msg" => OW::getLanguage()->text('iisgroupsplus', 'delete_group_user_confirmation')
                            ),
                            "class" => "owm_red_btn",
                            "order" => "2"
                        ));

                    }else {
                        $callbackUri = OW::getRequest()->getRequestUri();
                        $addUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('IISGROUPSPLUS_CTRL_Groups', 'addUserAsManager', array(
                            'groupId' => $params['groupId'],
                            'userId' => $params['userId']
                        )), array(
                            'redirectUri' => urlencode($callbackUri)
                        ));

                        array_unshift($params['contextMenu'], array(
                            'label' => OW::getLanguage()->text('iisgroupsplus', 'add_group_user_as_manager_label'),
                            'attributes' => array(
                                'onclick' => "return confirm_redirect('".OW::getLanguage()->text('base', 'are_you_sure')."','$addUrl');"
                            ),
                            "class" => "owm_red_btn",
                            "order" => "2"
                        ));
                    }
                }
                $event->setData(array('contextMenu'=>$params['contextMenu']));
            }
        }
    }

    /***
     * @param $groupId
     * @param int $first
     * @param $count
     * @return array<BOL_Attachment>
     */
    public function findFileList($groupId, $first=0, $count, $searchTitle=null)
    {
        $attachmentIds=array();
        $trueAttachmentIds=array();
        $attachmentResults=array();
        $attachmentIds = $this->groupFileDao->findAttachmentIdListByGroupId($groupId, $first, $count);
        if(sizeof($attachmentIds)>0) {
            $attachmentList = BOL_AttachmentDao::getInstance()->findAttachmentsByIds($attachmentIds);
            foreach ($attachmentList as $attachment) {
                if (in_array($attachment->id, $attachmentIds)) {
                    if (isset($searchTitle) && $searchTitle != '') {
                        if (strpos($attachment->origFileName, $searchTitle) !== false) {
                            $attachmentResults[] = $attachment;
                        }
                    } else {
                        $attachmentResults[] = $attachment;
                    }
                    $trueAttachmentIds[] = $attachment->id;
                }
            }

            $falseAttachmentIds = array_diff($attachmentIds, $trueAttachmentIds);
            if ($falseAttachmentIds != null) {
                foreach ($falseAttachmentIds as $falseAttachmentId) {
                    $this->deleteFileForGroup($groupId, $falseAttachmentId);
                }
            }
        }
        return $attachmentResults;

    }

    public function findFileListCount($groupId)
    {
        return $this->groupFileDao->findCountByGroupId($groupId);

    }

    public function getUploadFileForm($groupId)
    {
        $plugin = OW::getPluginManager()->getPlugin('iisgroupsplus');

        $language = OW::getLanguage();

        OW::getDocument()->setHeading($language->text('iisgroupsplus', 'file_create_heading'));
        OW::getDocument()->setHeadingIconClass('ow_ic_new');
        OW::getDocument()->setTitle($language->text('iisgroupsplus', 'file_create_page_title'));
        OW::getDocument()->setDescription($language->text('iisgroupsplus', 'file_create_page_description'));

        $form = new IISGROUPSPLUS_FileUploadForm($groupId);
        $actionRoute = OW::getRouter()->urlFor('IISGROUPSPLUS_CTRL_Groups', 'addFile', array('groupId' => $groupId));
        $form->setAction($actionRoute);
        return $form;
    }

    public function addFileForGroup($groupId, $attachmentId){
        return $this->groupFileDao->addFileForGroup($groupId,$attachmentId);
    }

    public function deleteFileForGroup($groupId, $attachmentId){
        $fileId = $this->findFileIdByAidAndGid($groupId, $attachmentId);
        BOL_AttachmentService::getInstance()->deleteAttachmentById($attachmentId);
        OW::getEventManager()->trigger(new OW_Event("feed.delete_item", array(
            'entityType' => 'groups-add-file',
            'entityId' => $fileId
        )));
        OW::getEventManager()->trigger(new OW_Event('notifications.remove', array(
            'entityType' => 'groups-add-file',
            'entityId' => $fileId
        )));
        $this->groupFileDao->deleteGroupFilesByAidAndGid($groupId,$attachmentId);
    }

    public function deleteFileForGroupByGroupId($groupId){
        $this->groupFileDao->deleteGroupFilesByGroupId($groupId);
    }

    public function findFileIdByAidAndGid($groupId, $attachmentId){
        return $this->groupFileDao->findFileIdByAidAndGid($groupId,$attachmentId);
    }
    public function deleteFiles(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['groupId'])) {
            $filesDto = $this->groupFileDao->getGroupFilesByGroupId($params['groupId']);
            foreach ($filesDto as $file) {
                try {
                    OW::getEventManager()->trigger(new OW_Event("feed.delete_item", array(
                        'entityType' => 'groups-add-file',
                        'entityId' => $file->id
                    )));
                    OW::getEventManager()->call('notifications.remove', array(
                        'entityType' => 'groups-add-file',
                        'entityId' => $file->id
                    ));
                    $this->deleteFileForGroupByGroupId($params['groupId']);
                    BOL_AttachmentService::getInstance()->deleteAttachmentById($file->attachmentId);
                } catch (Exception $e) {

                }
            }
        }
        else if(isset($params['allFiles'])) {
            $filesDto = $this->groupFileDao->findAllFiles();
            foreach ($filesDto as $file) {
                try {
                    BOL_AttachmentService::getInstance()->deleteAttachmentById($file->attachmentId);
                    OW::getEventManager()->trigger(new OW_Event("feed.delete_item", array(
                        'entityType' => 'groups-add-file',
                        'entityId' => $file->id
                    )));
                    OW::getEventManager()->call('notifications.remove', array(
                        'entityType' => 'groups-add-file',
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
        if(isset($params['controller']) && isset($params['groupId'])){
            $bcw = new BASE_CLASS_WidgetParameter();
            $bcw->additionalParamList=array('entityId'=>$params['groupId']);
            $groupController = $params['controller'];
            $groupController->addComponent('groupFileList', new IISGROUPSPLUS_MCMP_FileListWidget($bcw));
            $fileBoxInformation = array(
                'show_title' => true,
                'title' => OW_Language::getInstance()->text('iisgroupsplus', 'widget_files_title'),
                'wrap_in_box' => true,
                'icon' => 'ow_ic_info',
                'type' => "",
            );
            $groupController->assign('fileBoxInformation', $fileBoxInformation);
        }
    }

    public function onCollectNotificationActions( BASE_CLASS_EventCollector $e )
    {
        $e->add(array(
            'section' => 'groups',
            'action' => 'groups-add-file',
            'description' => OW::getLanguage()->text('iisgroupsplus', 'email_notifications_setting_file'),
            'selected' => true,
            'sectionLabel' => OW::getLanguage()->text('iisgroupsplus', 'email_notification_section_label'),
            'sectionIcon' => 'ow_ic_write'
        ));
        $e->add(array(
            'section' => 'groups',
            'action' => 'groups-update-status',
            'description' => OW::getLanguage()->text('iisgroupsplus', 'email_notifications_setting_status'),
            'selected' => true,
            'sectionLabel' => OW::getLanguage()->text('iisgroupsplus', 'email_notification_section_label'),
            'sectionIcon' => 'ow_ic_write'
        ));
    }

    public function onGroupUserInvitation(OW_Event $event){
        $invitationParams =  $event->getParams();

        $groupId = $invitationParams['groupId'];
        $userId = $invitationParams['userId'];
        $inviterId = $invitationParams['inviterId'];
        $inviteId = $invitationParams['inviteId'];

        $userService = BOL_UserService::getInstance();
        $groupService = GROUPS_BOL_Service::getInstance();

        $displayName = $userService->getDisplayName($inviterId);
        $inviterUrl = $userService->getUserUrl($inviterId);

        $groupTitle = $groupService->findGroupById($groupId)->title;
        $groupUrl = OW::getRouter()->urlForRoute('groups-view', array('groupId' => $groupId));

        $invitationUrl = OW::getRouter()->urlForRoute('groups-invite-list');

        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($inviterId));

        $params = array(
            'pluginKey' => 'groups',
            'entityType' => 'user_invitation',
            'entityId' => $inviteId,
            'action' => 'groups-invitation',
            'userId' => $userId,
            'time' => time()
        );

        $data = array(
            'groupId'=>$groupId,
            'avatar' => $avatars[$inviterId],
            'string' => array(
                'key' => 'iisgroupsplus+group_user_invitation_notification',
                'vars' => array(
                    'userName' => $displayName,
                    'userUrl' => $inviterUrl,
                    'groupTitle' => $groupTitle,
                    'groupUrl'=> $groupUrl
                )
            ),
            'url' => $invitationUrl,
        );

        $e = new OW_Event('notifications.add', $params, $data);
        OW::getEventManager()->trigger($e);

    }

    public function onNotificationRender( OW_Event $e )
    {
        //how to show
        $params = $e->getParams();
        if ( $params['pluginKey'] != 'groups' || $params['entityType'] != 'user_invitation')
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

    public function onUpdateGroupStatus(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['feedId']) && (isset($params['feedType']) && $params['feedType']=='groups') && isset($params['status'])) {
            $groupService = GROUPS_BOL_Service::getInstance();
            $group = $groupService->findGroupById($params['feedId']);
            if ($group) {
                $groupUrl = $groupService->getGroupUrl($group);
                /*
                  * send notification to group members
                 */
                $userId = OW::getUser()->getId();
                $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));
                $avatar = $avatars[$userId];
                $userUrl = BOL_UserService::getInstance()->getUserUrl($userId);
                $entityId = isset($params['statusId'])?$params['statusId']:$params['feedId'];
                $defaultEntityType='groups-status';
                if(isset($params['entityType']))
                {
                    $defaultEntityType=$params['entityType'];
                }
                $notificationParams = array(
                    'pluginKey' => 'groups',
                    'action' => 'groups-update-status',
                    'entityType' => $defaultEntityType,
                    'entityId' => $entityId,
                    'userId' => null,
                    'time' => time()
                );

                $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
                if(isset($pluginNewsfeed) && $pluginNewsfeed->isActive()) {
                    $action = NEWSFEED_BOL_Service::getInstance()->findAction($defaultEntityType, $params['statusId']);
                    $actionId = $action->id;
                    $mainUrl = OW::getRouter()->urlForRoute('newsfeed_view_item', array('actionId' => $actionId));
                }
                $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $groupUrl)));
                if(isset($stringRenderer->getData()['string'])){
                    $groupUrl = $stringRenderer->getData()['string'];
                }
                $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $avatar['src'])));
                if(isset($stringRenderer->getData()['string'])){
                    $avatar['src'] = $stringRenderer->getData()['string'];
                }

                $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWSFEED_STATUS_STRING_WRITE,array('string' => $avatar['url'])));
                if(isset($stringRenderer->getData()['string'])){
                    $avatar['url'] = $stringRenderer->getData()['string'];
                }

                $notificationData = array(
                    'string' => array(
                        "key" => 'iisgroupsplus+notif_update_status_string',
                        "vars" => array(
                            'groupTitle' => $group->title,
                            'groupUrl' => $groupUrl,
                            'userName' => BOL_UserService::getInstance()->getDisplayName($userId),
                            'userUrl' => $userUrl,
                            'status' =>  UTIL_String::truncate($params['status'], 120, '...')
                        )
                    ),
                    'avatar' => $avatar,
                    'content' => '',
                    'url' => isset($mainUrl)?$mainUrl:$groupUrl
                );

                $userIds = GROUPS_BOL_Service::getInstance()->findGroupUserIdList($group->id);

                foreach ($userIds as $uid) {
                    if ($uid == OW::getUser()->getId()) {
                        continue;
                    }

                    $notificationParams['userId'] = $uid;

                    $event = new OW_Event('notifications.add', $notificationParams, $notificationData);
                    OW::getEventManager()->trigger($event);
                }
            }
        }
    }

    public function deleteWidget( OW_Event $event )
    {
        BOL_ComponentAdminService::getInstance()->deleteWidget('IISGROUPSPLUS_CMP_PendingInvitation');
    }

    public function pluginDeactivate( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['pluginKey'] != 'iisgroupsplus' )
        {
            return;
        }
        if ( OW::getConfig()->getValue('groups', 'is_iisgroupsplus_connected') )
        {
            $event = new OW_Event('iisgroupsplus.delete_widget');
            OW::getEventManager()->trigger($event);
        }
    }

    public function pluginUninstall( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['pluginKey'] != 'iisgroupsplus' )
        {
            return;
        }
        if ( OW::getConfig()->getValue('groups', 'is_iisgroupsplus_connected') )
        {
            $event = new OW_Event('iisgroupsplus.delete_widget');
            OW::getEventManager()->trigger($event);
        }
    }

    public function onCanInviteAll(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['checkAccess'])){
            $hasAccess=false;
            $directInvite=false;
            if(OW::getUser()->isAuthorized('iisgroupsplus', 'all-search')){
                $hasAccess=true;
            }
            if(OW::getUser()->isAuthorized('iisgroupsplus', 'direct-add')){
                $directInvite = true;
            }
            $event->setData(array('hasAccess'=>$hasAccess,'directInvite' =>$directInvite));
        }else if (OW::getUser()->isAuthorized('iisgroupsplus', 'all-search')) {
            $numberOfUsers = BOL_UserService::getInstance()->count(true);
            $users = BOL_UserService::getInstance()->findList(0, $numberOfUsers, true);
            $userIds = array();
            foreach ($users as $user) {

                $userApproveConfig=OW::getConfig()->getValue('base', 'mandatory_user_approve');
                $usersEmailVerifyConfig=OW::getConfig()->getValue('base', 'confirm_email');
                $userEmailStatus=$user->emailVerify == '0';
                $userDisapproveStatus = BOL_UserService::getInstance()->findUnapprovedStatusForUserList(array($user->getId()));
                if ($user->getId() == OW::getUser()->getId() ||
                    ($userApproveConfig && $userDisapproveStatus[$user->getId()]==true) ||
                    ($usersEmailVerifyConfig && $userEmailStatus)) {
                    continue;
                }

                $userIds[] = $user->getId();
            }
            if (sizeof($userIds) > 0) {
                $event->setData(array('userIds' => $userIds));
            }
        }
    }

    public function addUsersAutomatically( OW_Event $event )
    {
        $params = $event->getParams();
        if(isset($params['userIds']) && isset($params['groupId'])){
            $groupId = $params['groupId'];
            $userIds = $params['userIds'];
            $inviterUserId = isset($params['inviter'])? $params['inviter']:OW::getUser()->getId();
            $groups = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($inviterUserId));

            $notificationParams = array(
                'pluginKey' => 'groups',
                'action' => 'groups-invitation',
                'entityType' => 'groups-join',
                'entityId' => (int)$groupId,
                'userId' => null,
                'time' => time()
            );

            $notificationData = array(
                'string' => array(
                    'key' => 'iisgroupsplus+joined_notification_string',
                    'vars' => array(
                        'groupTitle' => $groups->title,
                        'groupUrl' => OW::getRouter()->urlForRoute('groups-view', array('groupId' => (int)$groupId)),
                        'userName' => BOL_UserService::getInstance()->getDisplayName($inviterUserId),
                        'userUrl' => BOL_UserService::getInstance()->getUserUrl($inviterUserId)
                    )
                ),
                'avatar' => $avatars[$inviterUserId],
                'content' => '',
                'url' => OW::getRouter()->urlForRoute('groups-view', array('groupId' =>(int)$groupId))
            );
            foreach ($userIds as $userId){
                $notificationParams['userId'] = $userId;
                $event = new OW_Event('notifications.add', $notificationParams, $notificationData);
                OW::getEventManager()->trigger($event);
                GROUPS_BOL_Service::getInstance()->addUser($groupId, $userId);
            }
        }
    }

    public function memberListPageRender(OW_Event $event){
        $params = $event->getParams();
        $groupDto = $params['groupDto'];
        $managerList = array();
        if (isset($groupDto)) {
            $managers = IISGROUPSPLUS_BOL_GroupManagersDao::getInstance()->getGroupManagersByGroupId($groupDto->getId());
            foreach ($managers as $manager){
                $managerList[] = BOL_UserDao::getInstance()->findById($manager->userId);
            }
        }

        $adminList = array();
        $adminList[] = BOL_UserDao::getInstance()->findById($groupDto->userId);
        if(isset($managerList) && is_array($managerList)){
            foreach ($managerList as $manager){
                $userExists = false;
                foreach($adminList as $admin){
                    if($admin->getId() === $manager->getId()){
                        $userExists = true;
                        break;
                    }
                }
                if(!$userExists){
                    $adminList[] = $manager;
                }
            }
        }
        $adminListCount = sizeof($adminList);

        $adminListCmp = new GROUPS_UserList($groupDto, $adminList, $adminListCount, 20);

        $extraComponents = array(
            array(
                'label'=>'iisgroupsplus+group_managers',
                'name' => 'managerList',
                'component' => $adminListCmp
            )
        );
        $event->setData($extraComponents);
    }

    public function setChannelGroup( OW_Event $event )
    {
        $params = $event->getParams();
        $data = array();
        $channelField = new RadioField('whoCanCreateContent');
        $channelField->setRequired();
        $channelField->addOptions(
            array(
                IISGROUPSPLUS_BOL_Service::WCC_GROUP => OW::getLanguage()->text('iisgroupsplus', 'form_who_can_create_content_participants'),
                IISGROUPSPLUS_BOL_Service::WCC_CHANNEL => OW::getLanguage()->text('iisgroupsplus', 'form_who_can_create_content_creators')
            )
        );
        $channelField->setLabel(OW::getLanguage()->text('iisgroupsplus', 'who_can_create_content'));
        if(isset($params['groupId']) && isset($params['form']) ){
            $groupId = $params['groupId'];
            $channel = IISGROUPSPLUS_BOL_ChannelDao::getInstance()->findIsExistGroupId($groupId);
            $form = $params['form'];

            if (isset($channel)){
                $data['isChannel']=true;
                $channelField->setValue(IISGROUPSPLUS_BOL_Service::WCC_CHANNEL);
            }
            else{
                $data['isChannel']=false;
                $channelField->setValue(IISGROUPSPLUS_BOL_Service::WCC_GROUP);
            }
            $form->addElement($channelField);
            $data['form'] = $form;
            $event->setData($data);

        }
        else if (isset($params['form'])) {
            $channelField->setValue(IISGROUPSPLUS_BOL_Service::WCC_GROUP);
            $form = $params['form'];
            $form->addElement($channelField);
            $data['form'] = $form;
            $data['isChannel'] = false;
            $event->setData($data);
        }

    }


    public function canCreateTopic(OW_Event $event)
    {
        $config = OW::getConfig();
        if(!$config->configExists('iisgroupsplus', 'showAddTopic')||($config->configExists('iisgroupsplus', 'showAddTopic')&&!$config->getValue('iisgroupsplus', 'showAddTopic')))
            return;
        $params = $event->getParams();
        $data = array();
        $data['accessCreateTopic']=true;
        if(isset($params['groupId']))
        {
            $groupId=$params['groupId'];
            $userId=OW::getUser()->getId();
            $groupSetting=$this->groupSettingDao->findByGroupId($params['groupId']);
            if(isset($groupSetting))
            {
                $isManager=$this->groupManagersDao->getGroupManagerByUidAndGid($groupId,$userId);
                if($groupSetting->getWhoCanCreateTopic()==IISGROUPSPLUS_BOL_Service::WCU_MANAGERS && !isset($isManager))
                {
                    $data['accessCreateTopic']=false;
                }
            }
        }
        $event->setData($data);
    }

    public function canUploadInFileWidget(OW_Event $event)
    {
        $config = OW::getConfig();
        if(!$config->configExists('iisgroupsplus', 'showFileUploadSettings')||($config->configExists('iisgroupsplus', 'showFileUploadSettings')&&!OW::getConfig()->getValue('iisgroupsplus', 'showFileUploadSettings')))
            return;
        $params = $event->getParams();
        $data = array();
        $data['accessUploadFile']=true;
        if(isset($params['groupId']))
        {
            $groupId=$params['groupId'];
            $userId=OW::getUser()->getId();
            $groupSetting=$this->groupSettingDao->findByGroupId($params['groupId']);
            if(isset($groupSetting))
            {
                $isManager=$this->groupManagersDao->getGroupManagerByUidAndGid($groupId,$userId);
                if($groupSetting->getWhoCanUploadFile()==IISGROUPSPLUS_BOL_Service::WCU_MANAGERS && !isset($isManager))
                {
                    $data['accessUploadFile']=false;
                }
            }
        }
        $event->setData($data);
    }

    public function addGroupSettingElements( OW_Event $event )
    {
        $params = $event->getParams();
        $data = array();
        /**
         * file upload setting
         */

        if (isset($params['form'])) {
            $form = $params['form'];

            if (isset($params['groupId'])) {
                $groupId = $params['groupId'];
                $groupSetting = IISGROUPSPLUS_BOL_GroupSettingDao::getInstance()->findByGroupId($groupId);
            }
            $config = OW::getConfig();
            if ($config->configExists('iisgroupsplus', 'showFileUploadSettings')&& $config->getValue('iisgroupsplus', 'showFileUploadSettings')) {
                $whoCanUploadFileInFileWidgetField = new RadioField('whoCanUploadInFileWidget');
                $whoCanUploadFileInFileWidgetField->setRequired();
                $whoCanUploadFileInFileWidgetField->addOptions(
                    array(
                        IISGROUPSPLUS_BOL_Service::WCU_PARTICIPANT => OW::getLanguage()->text('iisgroupsplus', 'who_can_setting_participant'),
                        IISGROUPSPLUS_BOL_Service::WCU_MANAGERS => OW::getLanguage()->text('iisgroupsplus', 'who_can_setting_manager')
                    )
                );
                $whoCanUploadFileInFileWidgetField->setLabel(OW::getLanguage()->text('iisgroupsplus', 'who_can_upload_file_widget'));
                if (isset($groupSetting)) {
                    $whoCanUploadFileInFileWidgetField->setValue($groupSetting->getWhoCanUploadFile());
                } else {
                    $whoCanUploadFileInFileWidgetField->setValue(IISGROUPSPLUS_BOL_Service::WCU_PARTICIPANT);
                }
                $data['uploadFile'] = true;
                $form->addElement($whoCanUploadFileInFileWidgetField);

            }
            /**
             * topic create setting
             */
            if ($config->configExists('iisgroupsplus', 'showAddTopic') && $config->getValue('iisgroupsplus', 'showAddTopic')) {
                $whoCanCreateTopic = new RadioField('whoCanCreateTopic');
                $whoCanCreateTopic->setRequired();
                $whoCanCreateTopic->addOptions(
                    array(
                        IISGROUPSPLUS_BOL_Service::WCU_PARTICIPANT => OW::getLanguage()->text('iisgroupsplus', 'who_can_setting_participant'),
                        IISGROUPSPLUS_BOL_Service::WCU_MANAGERS => OW::getLanguage()->text('iisgroupsplus', 'who_can_setting_manager')
                    )
                );
                $whoCanCreateTopic->setLabel(OW::getLanguage()->text('iisgroupsplus', 'who_can_create_topic'));

                $forumConnected = false;
                $is_forum_connected = OW::getConfig()->getValue('groups', 'is_forum_connected');

                if (OW::getPluginManager()->isPluginActive('forum') || $is_forum_connected) {
                    $forumConnected = true;
                }
                if (isset($groupSetting)) {
                    $whoCanCreateTopic->setValue($groupSetting->getWhoCanCreateTopic());
                } else {
                    $whoCanCreateTopic->setValue(IISGROUPSPLUS_BOL_Service::WCU_PARTICIPANT);
                }
                if ($forumConnected) {
                    $data['createTopic'] = true;
                    $form->addElement($whoCanCreateTopic);
                }
            }
            $data['form'] = $form;
            $event->setData($data);
        }
    }
    public function setChannelForGroup( OW_Event $event ){
        $params = $event->getParams();
        if(isset($params['groupId']) && isset($params['isChannel']))
        {
            if ($params['isChannel'] == IISGROUPSPLUS_BOL_Service::WCC_CHANNEL)
                $this->channelDao->addChannel($params['groupId']);
            else
                $this->channelDao->deleteByGroupId($params['groupId']);
        }
    }

    public function setGroupSetting( OW_Event $event ){
        $params = $event->getParams();
        if(isset($params['groupId']) && isset($params['values']) )
        {
            $groupId=$params['groupId'];
            $values=$params['values'];
            $whoCanUploadFile=IISGROUPSPLUS_BOL_Service::WCU_PARTICIPANT;
            if(isset($values['whoCanUploadInFileWidget']) && in_array($values['whoCanUploadInFileWidget'],array(IISGROUPSPLUS_BOL_Service::WCU_PARTICIPANT,IISGROUPSPLUS_BOL_Service::WCU_MANAGERS)))
            {
                $whoCanUploadFile=$values['whoCanUploadInFileWidget'];
            }

            $whoCanCreateTopic=IISGROUPSPLUS_BOL_Service::WCU_PARTICIPANT;
            if(isset($values['whoCanCreateTopic']) && in_array($values['whoCanCreateTopic'],array(IISGROUPSPLUS_BOL_Service::WCU_PARTICIPANT,IISGROUPSPLUS_BOL_Service::WCU_MANAGERS)))
            {
                $whoCanCreateTopic=$values['whoCanCreateTopic'];
            }
            $this->groupSettingDao->addSetting($groupId,$whoCanUploadFile,$whoCanCreateTopic);
        }
    }

    public function deleteGroupSetting(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['groupId'])) {
            $this->deleteGroupSettingByGroupId($params['groupId']);
        }
    }

    public function onChannelAddWidget( OW_Event $event ){
        $params = $event->getParams();
        $groupId = null;
        if(isset($params['groupId'])){
            $groupId = $params['groupId'];
        }
        else if (isset($params['feedType']) && isset($params['feedId']) && $params['feedType'] == 'groups') {
                    $groupId = $params['feedId'];
            }
        else if (isset($params['action']) && $params['action']->getActivity("create")!=null ) {
            $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
            $actionFeed = $actionFeedDao->findByActivityIds(array($params['action']->getActivity("create")->id));
            if (!empty($actionFeed) && $actionFeed[0]->feedType == "groups"){
                $groupId = $actionFeed[0]->feedId;
            }
        }
            if (isset($groupId)){
                $isChannel = $this->channelDao->findIsExistGroupId($groupId);
                $isManager = $this->groupManagersDao->getGroupManagerByUidAndGid($groupId,OW::getUser()->getId());
                $group = GROUPS_BOL_Service::getInstance()->findGroupById($groupId);
                if ($group != null) {
                    $isCreator = $group->userId == OW::getUser()->getId() ? true : false;
                    if (isset($isChannel) && !isset($isManager) && !$isCreator && !OW::getUser()->isAuthorized('groups'))
                        $event->setData(array("channelParticipant" => true));
                    else
                        $event->setData(array("channelParticipant" => false));
                } else {
                    $event->setData(array("channelParticipant" => false));
                }
            }

    }

    public function deleteGroupSettingByGroupId($groupId){
        $this->groupSettingDao->deleteByGroupId($groupId);
    }

    public function manageAddFile($groupId, $item){
        $resultArr = array('result' => false, 'message' => 'General error');
        $bundle = IISSecurityProvider::generateUniqueId();

        $pluginKey = 'iisgroupsplus';
        if(isset($_POST['name']) && $_POST['name']!=""){
            $itemName = explode('.',$item['name'] );
            $item['name'] = $_POST['name'].'.'.end($itemName);
        }
        try {
            $dtoArr = BOL_AttachmentService::getInstance()->processUploadedFile($pluginKey, $item, $bundle);
            OW::getEventManager()->call('base.attachment_save_image', array('uid' => $bundle, 'pluginKey' => $pluginKey));
            $resultArr['result'] = true;
            $resultArr['url'] = $dtoArr['url'];
            $attachmentId = $dtoArr['dto']->id;
            $fileId = $this->addFileForGroup($groupId,$attachmentId);
            $groupService = GROUPS_BOL_Service::getInstance();
            $group = $groupService->findGroupById($groupId);
            $url = $groupService->getGroupUrl($group);

            $private = $group->whoCanView == GROUPS_BOL_Service::WCV_INVITE;
            $visibility = $private
                ? 14 // VISIBILITY_FOLLOW + VISIBILITY_AUTHOR + VISIBILITY_FEED
                : 15; // Visible for all (15)
            /*
             * add feed action to group
             */
            $fileActivityFeedConfig=json_decode(OW::getConfig()->getValue('iisgroupsplus','groupFileAndJoinAndLeaveFeed'));
            if(isset($fileActivityFeedConfig) && in_array('fileFeed',$fileActivityFeedConfig)){
                $data = array(
                    'time' => time(),
                    'string' => array(
                        "key" => 'iisgroupsplus+feed_add_file_string',
                        "vars" => array(
                            'groupTitle' => $group->title,
                            'groupUrl' => $url,
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
                    'feedType' => 'groups',
                    'feedId' => $group->id,
                    'entityType' => 'groups-add-file',
                    'entityId' => $fileId,
                    'pluginKey' => 'groups',
                    'userId' => OW::getUser()->getId(),
                    'visibility' => $visibility
                ), $data);

                OW::getEventManager()->trigger($event);
            }

            /*
             * send notification to group members
             */

            $userId = OW::getUser()->getId();
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));
            $avatar = $avatars[$userId];
            $userUrl = BOL_UserService::getInstance()->getUserUrl($userId);
            $notificationParams = array(
                'pluginKey' => 'groups',
                'action' => 'groups-add-file',
                'entityType' => 'groups-add-file',
                'entityId' => $fileId,
                'userId' => null,
                'time' => time()
            );

            $notificationData = array(
                'string' => array(
                    "key" => 'iisgroupsplus+notif_add_file_string',
                    "vars" => array(
                        'groupTitle' => $group->title,
                        'groupUrl' => $url,
                        'userName' => BOL_UserService::getInstance()->getDisplayName($userId),
                        'fileName' => $dtoArr['dto']->origFileName,
                        'userUrl' => $userUrl
                    )
                ),
                'avatar' => $avatar,
                'content' => '',
                'url' => $this->getAttachmentUrl($dtoArr['dto']->fileName)
            );

            $userIds = GROUPS_BOL_Service::getInstance()->findGroupUserIdList($group->id);

            foreach ( $userIds as $uid )
            {
                if ( $uid == OW::getUser()->getId() )
                {
                    continue;
                }

                $notificationParams['userId'] = $uid;

                $event = new OW_Event('notifications.add', $notificationParams, $notificationData);
                OW::getEventManager()->trigger($event);
            }
            OW::getFeedback()->info(OW::getLanguage()->text('iisgroupsplus', 'add_file_successful'));

        } catch (Exception $e) {
            $resultArr['message'] = $e->getMessage();
            OW::getFeedback()->error($resultArr['message']);
        }

        return $resultArr;
    }

    public function getAttachmentUrl($name)
    {
        return OW::getStorage()->getFileUrl($this->getAttachmentDir($name));
    }

    public function getAttachmentDir($name)
    {
        return OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments' . DS .$name ;
    }

    public function onChannelLoad( OW_Event $event ){
        $params = $event->getParams();
        $groupId = null;
        if(isset($params['groupId']) ){
            $groupId = $params['groupId'];
        }
        else if (isset($params['action']) && $params['action']->getActivity("create")!=null ) {
            $actionFeedDao = NEWSFEED_BOL_ActionFeedDao::getInstance();
            $actionFeeds = $actionFeedDao->findByActivityIds(array($params['action']->getActivity("create")->id));
            if (!empty($actionFeeds)){
                foreach ($actionFeeds as $actionFeed){
                    if($actionFeed->feedType == "groups"){
                        $groupId = $actionFeed->feedId;
                        break;
                    }
                }
            }
        }
        if (isset($groupId)){
            $isChannel = $this->channelDao->findIsExistGroupId($groupId);
            if( isset($isChannel)){
                $event->setData(array("isChannel" => true));
            }
        }
    }

    public function isGroupChannel(OW_Event $event )
    {
        $params = $event->getParams();
        if(isset($params['feedId']) && isset($params['feedType']) && $params['feedType']=='groups'){
            $isChannel = $this->channelDao->findIsExistGroupId($params['feedId']);
            if( isset($isChannel)){
                $event->setData(array("isChannel" => true));
            }
        }
    }

    public function getFileUrlByFileId($fileId){
        $file = $this->groupFileDao->findById($fileId);
        if(!isset($file)){
            return null;
        }
        $item = BOL_AttachmentDao::getInstance()->findById($file->attachmentId);
        $path = OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments' . DS . $item->fileName;
        $fileUrl = OW::getStorage()->getFileUrl( $path );
        return $fileUrl;
    }

    public function feedOnItemRenderActivity( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();

        if(!isset($data["string"]["key"]) || $data["string"]["key"]!= "iisgroupsplus+feed_add_file_string")
            return;
        else {
            $g = explode('/', $data["string"]["vars"]["groupUrl"]);
            $groupId = end($g);
            $groupService = GROUPS_BOL_Service::getInstance();
            $group = $groupService->findGroupById($groupId);
            if(isset($group)) {
                $data["string"]["vars"]["groupTitle"] = $group->title;
                $data["string"]["vars"]["groupUrl"] = $groupService->getGroupUrl($group);
                $data["string"]["vars"]["fileUrl"] = IISGROUPSPLUS_BOL_Service::getInstance()->getFileUrlByFileId($params["action"]["entityId"]);
                $event->setData($data);
            }
            else
                return;
        }

    }

    public function addConsoleItem( BASE_CLASS_EventCollector $event )
    {
        if(OW::getUser()->isAuthenticated() && OW::getUser()->isAuthorized('iisgroupsplus', 'add-forced-groups')) {
            $event->add(array('label' => OW::getLanguage()->text('iisgroupsplus', 'forced_groups'), 'url' => OW_Router::getInstance()->urlForRoute('iisgroupsplus.forced-groups')));
        }
    }

    public static function getFilteredUsersList($profileQuestionFilters)
    {
        if (isset($profileQuestionFilters) && $profileQuestionFilters != null) {
            $q = "SELECT DISTINCT `userId` FROM `" . OW_DB_PREFIX . "base_question_data` as table1 WHERE";
            foreach ($profileQuestionFilters as $filter_name => $filter_value) {
                if (isset($filter_value) && $filter_value) {
                    $q .= "\n table1.userId IN ( SELECT DISTINCT `userId` FROM `" . OW_DB_PREFIX . "base_question_data` WHERE(`questionName` = '" . $filter_name . "' And `intValue` in(";
                    foreach ($filter_value as $value)
                        $q .= $value . ",";
                    $q = rtrim($q, ',');
                    $q .= "))) AND";
                }
            }
            $q = rtrim($q, 'AND');
            return OW::getDbo()->queryForList($q);
        }
        else{
            $numberOfUsers = BOL_UserService::getInstance()->count(true);
            $users = BOL_UserService::getInstance()->findList(0, $numberOfUsers, true);
            $userIds = array();
            foreach ($users as $user){
                $userIds[] = array('userId' => $user->id);
            }
            return $userIds;
        }
    }

    public function onUserRegistered(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['forEditProfile']) && $params['forEditProfile']==true){
            return;
        }
        if(isset($params['userId'])){
            $user = BOL_UserService::getInstance()->findUserById($params['userId']);
            if($user != null){
                $userId = $params['userId'];
                $config = OW::getConfig();
                $list = $config->getValue('iisgroupsplus', 'forced_groups');
                $list = json_decode($list, true);
                foreach ($list as $gId=>$configs){
                    $groupConditions = $configs['conditions'];
                    $forcedGroupFilters = array();
                    if (!empty($groupConditions)) {
                        foreach ($groupConditions as $filter_name=>$filter_value){
                            if (isset($filter_value)){
                                $filter_parts = explode("__", $filter_name);
                                $forcedGroupFilters[$filter_parts[1]][] = $filter_parts[2];
                            }
                        }
                    }

                    $allProfileQuestions = array();
                    $accountTypes = BOL_QuestionService::getInstance()->findAllAccountTypes();
                    foreach ($accountTypes as $accountType){
                        $allProfileQuestions = array_merge(
                            $allProfileQuestions, BOL_QuestionService::getInstance()->findSignUpQuestionsForAccountType($accountType->name));
                    }
                    $allProfileQuestionNames = array();
                    foreach ($allProfileQuestions as $profileQuestion){
                        $allProfileQuestionNames[] = $profileQuestion['name'];
                    }
                    foreach ($forcedGroupFilters as $profileQuestionFilterName=>$profileQuestionFilterValue){
                        if  (!in_array($profileQuestionFilterName, $allProfileQuestionNames))
                            unset($forcedGroupFilters[$profileQuestionFilterName]);
                    }

                    $listOfFilteredUsers = IISGROUPSPLUS_BOL_Service::getFilteredUsersList($forcedGroupFilters);
                    if (isset($listOfFilteredUsers)) {
                        foreach ($listOfFilteredUsers as $index => $item) {
                            $listOfFilteredUSerIds[] = $listOfFilteredUsers[$index]['userId'];
                        }
                        if (isset($listOfFilteredUSerIds)) {
                            if (in_array ( $userId, $listOfFilteredUSerIds )) {
                                $group = GROUPS_BOL_Service::getInstance()->findGroupById($gId);
                                if (isset($group)) {
                                    $eventIisGroupsPlusAddAutomatically = new OW_Event('iisgroupsplus.add.users.automatically', array('groupId' => $gId, 'userIds' => [$userId], 'inviter' => 1));
                                    OW::getEventManager()->trigger($eventIisGroupsPlusAddAutomatically);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function onBeforeUserLeave(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['userId'])){
            $gId = $params['groupId'];
            $userId = $params['userId'];
            $user = BOL_UserService::getInstance()->findUserById($userId);
            if($user != null){
                $config = OW::getConfig();
                $list = $config->getValue('iisgroupsplus', 'forced_groups');
                $list = json_decode($list, true);
                if( isset($list[$gId]) && !$list[$gId]['canLeave']){
                    $event->setData(['cancel'=>true]);
                }
            }
        }
    }

    public function onCommentNotification( OW_Event $event )
    {
        $plugin_newsfeed = BOL_PluginService::getInstance()->findPluginByKey("newsfeed");
        if (!isset($plugin_newsfeed) || !$plugin_newsfeed->isActive())
        {
            return;
        }
        $params = $event->getParams();

        if ($params['pluginKey'] != 'groups' && $params['entityType'] != 'groups-add-file')
        {
            return;
        }

        $userId = $params['userId'];
        $commentId = $params['commentId'];

        $userService = BOL_UserService::getInstance();

        $action = NEWSFEED_BOL_Service::getInstance()->findAction($params['entityType'], $params['entityId']);

        if ( empty($action) )
        {
            return;
        }

        $actionData = json_decode($action->data, true);
        $status = empty($actionData['data']['status'])
            ? empty($actionData['string']) ? null : $actionData['string']
            : $actionData['data']['status'];

        if ( empty($actionData['data']['userId']) )
        {
            $cActivities = NEWSFEED_BOL_Service::getInstance()->findActivity( NEWSFEED_BOL_Service::SYSTEM_ACTIVITY_CREATE . ':' . $action->id);
            $cActivity = reset($cActivities);

            if ( empty($cActivity) )
            {
                return;
            }

            $ownerId = $cActivity->userId;
        }
        else
        {
            $ownerId = $actionData['data']['userId'];
        }

        $comment = BOL_CommentService::getInstance()->findComment($commentId);

        $contentImage = null;

        if ( !empty($comment->attachment) )
        {
            $attachment = json_decode($comment->attachment, true);

            if ( !empty($attachment["thumbnail_url"]) )
            {
                $contentImage = $attachment["thumbnail_url"];
            }
            if ( $attachment["type"] == "photo" )
            {
                $contentImage = $attachment["url"];
            }
        }

        $url = OW::getRouter()->urlForRoute('newsfeed_view_item', array('actionId' => $action->id));

        if ( $ownerId != $userId )
        {
            $avatar = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId), true, true, true, false);

            $stringKey = empty($status)
                ? 'iisgroupsplus+email_notifications_empty_status_comment'
                : 'iisgroupsplus+email_notifications_status_comment';
            $attachmentUrl=isset($status['vars']['fileUrl']) ? $status['vars']['fileUrl'] : null;
            $status = OW::getLanguage()->text('iisgroupsplus','feed_add_file_string',$status['vars']);
            $event = new OW_Event('notifications.add', array(
                'pluginKey' => 'iisgroupsplus',
                'entityType' => 'status_comment',
                'entityId' => $commentId,
                'userId' => $ownerId,
                'action' => 'newsfeed-status_comment'
            ), array(
                'format' => "text",
                'avatar' => $avatar[$userId],
                'string' => array(
                    'key' => $stringKey,
                    'vars' => array(
                        'userName' => $userService->getDisplayName($userId),
                        'userUrl' => $userService->getUserUrl($userId),
                        'status' => UTIL_String::truncate(UTIL_HtmlTag::stripTags($status), 20, '...'),
                        'url' => $url
                    )
                ),
                'attachmentUrl' => $attachmentUrl ,
                'content' => $comment->getMessage(),
                'contentImage' => $contentImage,
                'url' => $url
            ));

            OW::getEventManager()->trigger($event);
        }
    }


    public function deleteComment( OW_Event $e )
    {
        $params = $e->getParams();
        $commentId = $params['commentId'];

        $event = new OW_Event('feed.delete_activity', array(
            'entityType' => $params['entityType'],
            'entityId' => $params['entityId'],
            'activityType' => 'comment',
            'activityId' => $commentId
        ));
        OW::getEventManager()->trigger($event);

        if ($params['pluginKey']!='groups' || empty($params['entityType']) || ($params['entityType'] !== 'groups-add-file') )
            return;

        OW::getEventManager()->call('notifications.remove', array(
            'entityType' => 'status_comment',
            'entityId' => $commentId
        ));
        OW::getEventManager()->call('notifications.remove', array(
            'entityType' => 'base_profile_wall',
            'entityId' => $commentId
        ));
    }

    public function onUnregisterUser( OW_Event $event )
    {
        $params = $event->getParams();
        $userId = $params['userId'];
        $this->groupManagersDao->deleteGroupManagerByUserId($userId);
    }
}

class IISGROUPSPLUS_FileUploadForm extends Form
{
    public function __construct($groupId)
    {
        parent::__construct('fileUploadForm');

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

        $language = OW::getLanguage();

        $nameField = new TextField('name');
        $nameField->setLabel($language->text('iisgroupsplus', 'create_field_file_name_label'));
        $this->addElement($nameField);

        $fileField = new FileField('fileUpload');
        $fileField->setLabel($language->text('iisgroupsplus', 'create_field_file_upload_label'));
        $this->addElement($fileField);

        $groupIdElement = new HiddenField('id');
        $groupIdElement->setValue($groupId);
        $this->addElement($groupIdElement);

        $saveField = new Submit('save');
        $saveField->setValue(OW::getLanguage()->text('iisgroupsplus', 'create_submit_btn_label'));
        $this->addElement($saveField);
    }
}

class IISGROUPSPLUS_UserList extends BASE_CMP_Users
{
    /**
     *
     * @var GROUPS_BOL_Group
     */
    protected $groupDto;

    public function __construct( GROUPS_BOL_Group $groupDto, $list, $itemCount, $usersOnPage, $showOnline = true)
    {
        parent::__construct($list, $itemCount, $usersOnPage, $showOnline);
        $this->groupDto = $groupDto;
    }

    public function getContextMenu($userId)
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            return null;
        }

        $isOwner = $this->groupDto->userId == OW::getUser()->getId();
        $isGroupModerator = OW::getUser()->isAuthorized('groups');
        $eventIisGroupsPlusManager = new OW_Event('iisgroupsplus.check.user.manager.status', array('groupId'=>$this->groupDto->getId()));
        OW::getEventManager()->trigger($eventIisGroupsPlusManager);
        if(isset($eventIisGroupsPlusManager->getData()['isUserManager'])){
            $isGroupModerator=$eventIisGroupsPlusManager->getData()['isUserManager'];
        }
        $contextActionMenu = new BASE_CMP_ContextAction();

        $contextParentAction = new BASE_ContextAction();
        $contextParentAction->setKey('group_user_' . $userId);
        $contextActionMenu->addAction($contextParentAction);

        if ( ($isOwner || $isGroupModerator) && ($isGroupModerator || $userId != OW::getUser()->getId()) && $userId!=$this->groupDto->userId)
        {
            $contextAction = new BASE_ContextAction();
            $contextAction->setParentKey($contextParentAction->getKey());
            $contextAction->setKey('delete_group_user');
            $contextAction->setLabel(OW::getLanguage()->text('groups', 'delete_group_user_label'));

            if ( $this->groupDto->userId != $userId )
            {
                $callbackUri = OW::getRequest()->getRequestUri();
                $urlParams = array(
                    'redirectUri' => urlencode($callbackUri)
                );
                $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                    array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$userId,'isPermanent'=>true,'activityType'=>'deleteUser_group')));
                if(isset($iisSecuritymanagerEvent->getData()['code'])){
                    $urlParams['code'] = $iisSecuritymanagerEvent->getData()['code'];

                }
                $deleteUrl = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('GROUPS_CTRL_Groups', 'deleteUser', array(
                    'groupId' => $this->groupDto->id,
                    'userId' => $userId
                )),$urlParams );

                $contextAction->setUrl($deleteUrl);

                $contextAction->addAttribute('data-message', OW::getLanguage()->text('groups', 'delete_group_user_confirmation'));
                $contextAction->addAttribute('onclick', "return confirm($(this).data().message)");
            }
            else
            {
                $contextAction->setUrl('javascript://');
                $contextAction->addAttribute('data-message', OW::getLanguage()->text('groups', 'group_owner_delete_error'));
                $contextAction->addAttribute('onclick', "OW.error($(this).data().message); return false;");
            }

            $contextActionMenu->addAction($contextAction);
            $eventIisGroupsplus = new OW_Event('iisgroupsplus.set.user.manager.status', array('contextParentActionKey'=>$contextParentAction->getKey(),
                'userId'=>$userId,'groupOwnerId'=>$this->groupDto->userId,'groupId'=>$this->groupDto->id,'contextActionMenu'=>$contextActionMenu));
            OW::getEventManager()->trigger($eventIisGroupsplus);
        }

        return $contextActionMenu;
    }

    public function getFields( $userIdList )
    {
        $fields = array();

        $qs = array();

        $qBdate = BOL_QuestionService::getInstance()->findQuestionByName('birthdate');

        if ( $qBdate !== null && $qBdate->onView )
            $qs[] = 'birthdate';

        $qSex = BOL_QuestionService::getInstance()->findQuestionByName('sex');

        if ( $qSex !== null && $qSex->onView )
            $qs[] = 'sex';

        $questionList = BOL_QuestionService::getInstance()->getQuestionData($userIdList, $qs);

        foreach ( $questionList as $uid => $question )
        {

            $fields[$uid] = array();

            $age = '';

            if ( !empty($question['birthdate']) )
            {
                $date = UTIL_DateTime::parseDate($question['birthdate'], UTIL_DateTime::MYSQL_DATETIME_DATE_FORMAT);

                $age = UTIL_DateTime::getAge($date['year'], $date['month'], $date['day']);
            }

            $sexValue = '';
            if ( !empty($question['sex']) )
            {
                $sex = $question['sex'];

                for ( $i = 0; $i < 64; $i++ )
                {
                    $val = $i+1;
                    if ( (int) $sex == $val )
                    {
                        $sexValue .= BOL_QuestionService::getInstance()->getQuestionValueLang('sex', $val) . ', ';
                    }
                }

                if ( !empty($sexValue) )
                {
                    $sexValue = substr($sexValue, 0, -2);
                }
            }

            if ( !empty($sexValue) && !empty($age) )
            {
                $fields[$uid][] = array(
                    'label' => '',
                    'value' => $sexValue . ' ' . $age
                );
            }
        }

        return $fields;
    }
}
