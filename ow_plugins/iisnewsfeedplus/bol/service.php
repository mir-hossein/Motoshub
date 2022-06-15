<?php

class IISNEWSFEEDPLUS_BOL_Service
{
    private static $classInstance;

    const ORDER_BY_ACTIVITY='activity';
    const ORDER_BY_ACTION='action';
    const FORWARDABLE_TYPES = array('user-status', 'groups-status', 'photo_comments','multiple_photo_upload');
    const IMAGE_EXTENSIONS = array("jpg","jpeg","png","gif","bmp");
    const VIDEO_EXTENSIONS = array("mp4", "3gp", "avi");
    const AUDIO_EXTENSIONS = array("mp3","aac");


    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }


    private function __construct()
    {
    }

    /***
     * @param OW_Event $event
     */
    public function addAttachmentInputFieldsToNewsfeed(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['form'])) {
            $form = $this->addAttachmentInputsFieldToForm($params['form']);
        }

        $this->attachmentRender($event, 'newsfeed');
        $uid = IISSecurityProvider::generateUniqueId();
        $attachmentCmp = new BASE_CLASS_FileAttachment('iisnewsfeedplus', $uid);
        $attachmentCmp->setInputSelector('#newsfeedplusAttachmentsBtn');
        $attachmentCmp->setDropAreasSelector('form[name="newsfeed_update_status"]');
        $params['component']->addComponent('attachments', $attachmentCmp);
    }

    /***
     * @param $form
     * @param null $dataValue
     * @return mixed
     */
    public function addAttachmentInputsFieldToForm($form, $dataValue = null)
    {
        $attachmentFileData = new HiddenField('attachment_feed_data');
        $attachmentFileData->addAttribute("id", "attachment_feed_data");
        $attachmentFileData->setValue($dataValue);
        $form->addElement($attachmentFileData);

        $attachmentPreviewData = new HiddenField('attachment_preview_data');
        $attachmentPreviewData->addAttribute("id", "attachment_preview_data");
        $attachmentPreviewData->setValue($dataValue);
        $form->addElement($attachmentPreviewData);

        return $form;
    }

    public function attachmentRender(OW_Event $event, $type = "")
    {
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getStaticJsUrl() . 'iisnewsfeedplus.js');
        OW::getDocument()->addStyleSheet(OW_PluginManager::getInstance()->getPlugin("iisnewsfeedplus")->getStaticCssUrl() . 'iisnewsfeedplus.css');
        if ($type == "newsfeed") {
            if (OW::getApplication()->getContext() == OW::CONTEXT_MOBILE) {

                OW::getDocument()->addOnloadScript('$(\'.owm_newsfeed_block .owm_newsfeed_status_update_add_cont \').append(\'<span class="ow_smallmargin iisnewsfeedplus_attachment"><span class="iisnewsfeedplus_attachment" onclick="addAttachment()"><span class="buttons clearfix"><a class="iisnewsfeedplus_attachment" id="newsfeedplusAttachmentsBtn"></a></span></span></span>\');');
            } else {
                OW::getDocument()->addOnloadScript('$(\'.ow_status_update_btn_block .ow_attachment_icons\').append(\'<span class="ow_smallmargin iisnewsfeedplus_attachment"><span class="iisnewsfeedplus_attachment" onclick="addAttachment()"><span class="buttons clearfix"><a class="iisnewsfeedplus_attachment" id="newsfeedplusAttachmentsBtn"></a></span></span></span>\');');
            }
        }
        $css = '
            .iisnewsfeedplus_attachment{
                background-image: url("' . OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getStaticUrl() . 'img/attachment.svg' . '");}
            ';
        OW::getDocument()->addStyleDeclaration($css);
    }


    public function saveAttachments(OW_Event $event){
        $data = $event->getData();
        $attachmentDao = BOL_AttachmentDao::getInstance();
        $attachmentService = BOL_AttachmentService::getInstance();
        if ( isset($_POST['attachment_feed_data']) && !empty($_POST['attachment_feed_data']) ) {
            $attachmentIds=array();
            $previewIdList=array();
            $attachmentData = $_POST['attachment_feed_data'];
            $attachmentsArray = explode('-', $attachmentData);
            if ( isset($_POST['attachment_preview_data']) && !empty($_POST['attachment_preview_data']) ) {
                $attachmentsPreviewArray = explode('-', $_POST['attachment_preview_data']);
            }
            foreach ($attachmentsArray as $attachment){
                $attachmentSplit = explode(':', $attachment);
                if(!isset($attachmentSplit) || !isset($attachmentSplit[1])){
                    continue;
                }
                $file = $attachmentDao->findById($attachmentSplit[1]);
                if(!isset($file) || $file->userId!=OW::getUser()->getId()){
                    continue;
                }
                $attachmentService->updateStatusForBundle('iisnewsfeedplus',$file->bundle,1);
                if(isset($attachmentsPreviewArray) && in_array($attachmentSplit[1],$attachmentsPreviewArray))
                {
                    $previewIdList[]=$attachmentSplit[1];
                }
                $attachmentIds[]=$attachmentSplit[1];
            }
            if(sizeof($attachmentIds) > 0) {
                $data["attachmentIdList"] = $attachmentIds;
            }
            if(sizeof($previewIdList) > 0) {
                $data["previewIdList"] = $previewIdList;
            }
            $event->setData($data);
        }
    }

    public function appendAttachmentsToFeed(OW_Event $event)
    {
        $params = $event->getParams();
        $data = $event->getData();

        if (!isset($params["data"]["attachmentIdList"])) {
            return;
        }

        $attachmentIdList = $params["data"]["attachmentIdList"];
        $previewIdList = array();
        if (isset($params["data"]["previewIdList"])) {
            $previewIdList = $params["data"]["previewIdList"];
        }
        if (sizeof($attachmentIdList) == 0 )
        {
            return;
        }

        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getStaticJsUrl() . 'photoswipe.min.js');
        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getStaticJsUrl() . 'photoswipe-ui-default.min.js');
        OW::getDocument()->addStyleSheet( OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getStaticCssUrl() . 'photoswipe.min.css');
        OW::getDocument()->addStyleSheet( OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getStaticCssUrl() . 'default-skin.min.css');
        OW::getLanguage()->addKeyForJs('iisnewsfeedplus', 'download');

        $attachmentDao = BOL_AttachmentDao::getInstance();
        $attachmentsList = $attachmentDao->findByIdList($attachmentIdList);

        $attachmentPreviewItems = array();
        $attachmentNoPreviewItems = array();

        foreach ($attachmentsList as $attachment) {
            $itemType = IISNEWSFEEDPLUS_BOL_Service::getInstance()->getItemType($attachment);
            if ($itemType != '' && in_array($attachment->id,$previewIdList)) {
                $attachmentPreviewItems[] = $attachment;
            } else {
                $attachmentNoPreviewItems[] = $attachment;
            }
        }
        $itemsAttachmentPreviewData = new IISNEWSFEEDPLUS_CMP_RenderAttachmentPreview($attachmentPreviewItems);
        $AttachmentsPreviewHtml = $itemsAttachmentPreviewData->render();
        $attachmentItemsNoPreview = new IISNEWSFEEDPLUS_CMP_RenderAttachmentNoPreview($attachmentNoPreviewItems);
        $AttachmentsNoPreviewHtml = $attachmentItemsNoPreview->render();

        $data["attachmentHTML"] = $AttachmentsPreviewHtml . $AttachmentsNoPreviewHtml;
        $event->setData($data);

        OW::getDocument()->addOnloadScript('thumbnailCreator();');
        IISSecurityProvider::addMediaElementPlayerAfterRender();
    }

    public function onBeforeActionDelete( OW_Event $event )
    {
        $params = $event->getParams();
        $attachmentService = BOL_AttachmentService::getInstance();
        if(isset($params['actionId'])) {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findById($params['actionId']);
            $newsfeedData = json_decode($action->data);
            if(isset($newsfeedData->attachmentIdList)){
                foreach ($newsfeedData->attachmentIdList as $attachmentId) {
                    $attachmentService->deleteAttachmentById($attachmentId);
                    $this->deleteThumbnailsById($attachmentId);
                }
            }
        }
    }

    public function deleteThumbnailsById($attachmentId){
        $thumbnail=$this->getThumbnailFileDir($attachmentId.'.png');
        if(OW::getStorage()->fileExists($thumbnail)){
            OW::getStorage()->removeFile($thumbnail);
        }
    }

    public function onBeforeUpdateStatusFormRenderer(OW_Event $event){
        $params = $event->getParams();
        if(isset($params['form'])) {
            $form = $params['form'];
            if ($form->getElement('status') != null) {
                $form->getElement('status')->setRequired(true);
            }
            $form->bindJsFunction(Form::BIND_SUCCESS, "function(data){refreshAttachClass();}");

        }
    }

    public function onFeedItemAddRoleData(OW_Event $event)
    {
        $data = $event->getData();
        $params = $event->getParams();
        if (isset($params['usersInfo']) && isset($params['usersInfo']["roleLabels"]) &&
            isset($params['usersInfo']["roleLabels"][$params['userId']])) {
            $data["roleLabel"] = $params['usersInfo']["roleLabels"][$params['userId']];
            $event->setData($data);
        }
    }
    public function newsfeedDefualtLinkIconRenderer(OW_Event $event){
        $eventData = $event->getParams();
        if($eventData['data']['content']['thumbnail_url']==null && $eventData['data']['content']['type']=='link'){
            $eventData['data']['content']["thumbnail_url"]=OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getStaticUrl().'img/defualt.svg';
        }
        $event->setData(array('data' => $eventData));
    }

    public function getCreatorActivityOfAction($entityType, $entityId){
        if(!IISSecurityProvider::checkPluginActive('newsfeed', true)){
            return null;
        }

        $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
        if($action == null){
            return null;
        }
        $activities = NEWSFEED_BOL_ActivityDao::getInstance()->findIdListByActionIds(array($action->getId()));
        foreach($activities as $activityId){
            $activity = NEWSFEED_BOL_Service::getInstance()->findActivity($activityId)[0];
            if($activity->activityType=='create'){
                return $activity;
            }
        }
        return null;
    }

    public function getCreatorActivityOfActionById($actionId){
        if(!IISSecurityProvider::checkPluginActive('newsfeed', true)){
            return null;
        }

        $activities = NEWSFEED_BOL_ActivityDao::getInstance()->findIdListByActionIds(array($actionId));
        foreach($activities as $activityId){
            $activity = NEWSFEED_BOL_Service::getInstance()->findActivity($activityId)[0];
            if($activity->activityType=='create'){
                return $activity;
            }
        }
        return null;
    }


    public function editPost($text, $eid, $etype){
        if(!$this->canEditPost($eid, $etype)){
            return array('actionId' => -1, 'status' => '');
        }

        $text = strip_tags($text);

        $text = json_encode($text);
        $text = str_replace('\u202b', '', $text);
        $text = json_decode($text);
        $originalText = $text;
        $renderedText = $text;
        $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $renderedText)));
        if (isset($stringRenderer->getData()['string'])) {
            $renderedText = ($stringRenderer->getData()['string']);
        }

        $text = UTIL_HtmlTag::autoLink($text);
        //$text = nl2br($text);

        $renderedText = UTIL_HtmlTag::autoLink($renderedText);
        $renderedText = nl2br($renderedText);

        if(empty($text)){
            return array('actionId' => -1, 'status' => '');
        }

        $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($etype, $eid);
        $actionData = $action->data;
        $actionJsonData = json_decode($actionData);
        if(isset($actionJsonData->content->vars->status)){
            $actionJsonData->content->vars->status = $text;
        }

        if(isset($actionJsonData->content->vars->title)){
            unset($actionJsonData->content->vars->title);
        }

        if(isset($actionJsonData->content->vars->description)){
            unset($actionJsonData->content->vars->description);
        }

        if(isset($actionJsonData->content->vars->url)){
            unset($actionJsonData->content->vars->url);
        }

        if(isset($actionJsonData->content->vars->image)){
            unset($actionJsonData->content->vars->image);
        }

        if(isset($actionJsonData->content->vars->thumbnail)){
            unset($actionJsonData->content->vars->thumbnail);
        }

        if(isset($actionJsonData->data->status)) {
            $actionJsonData->data->status = $text;
        }

        if($etype == 'user-status' && $action->pluginKey == 'newsfeed' && isset($actionJsonData->content->format)){
            $actionJsonData->content->format = 'text';
        }

        if(isset($actionJsonData->status)) {
            $actionJsonData->status = $text;
        }
        $action->data = json_encode($actionJsonData);
        NEWSFEED_BOL_ActionDao::getInstance()->save($action);
        OW::getLogger()->writeLog(OW_Log::INFO, 'edit_action', ['actionType'=>OW_Log::UPDATE, 'enType'=>'newsfeed', 'enId'=>$action->id]);
        OW::getEventManager()->trigger(new OW_Event('hashtag.edit_newsfeed', array('entityId' => $eid,'entityType' => $etype,'text'=>$originalText,'pluginKey'=>'newsfeed')));
        return array('actionId' => $action->getId(), 'status' => $renderedText, 'text' => $text);
    }

    public function getText($eid, $etype, $getFullText=false){
        $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($etype, $eid);
        $data = $action->data;
        $data = json_decode($data);
        if(!isset($data->data->status)){
            return '';
        }
        $status = $data->data->status;
        if ($getFullText)
            $status = IISSecurityProvider::getDomTextContent($status);
        $status = strip_tags($status);
        return $status;
    }


    public function getEditPostForm($text, $eid, $etype){
        $form = new Form('edit_post');
        $form->setAjax(true);
        $action = NEWSFEED_BOL_Service::getInstance()->findAction($etype,$eid);
        $form->bindJsFunction(Form::BIND_SUCCESS, 'function(data){if(!data.error){closeEditNewsfeedComponent(data.status, data.actionId);OW.trigger(\'base.newsfeed_content.edited\', {entityType:\''.$etype.'\',entityId:\''.$eid.'\',itemId:\''.$action->getId().'\'});OW.info("'. OW::getLanguage()->text('iisnewsfeedplus', 'edit_post_successfully') .'");}else{OW.error("Parser error");}}');
        $actionRoute = OW::getRouter()->urlForRoute('iisnewsfeedplus.edit.post');
        $form->setAction($actionRoute);

        $field = new Textarea('status');
        $field->setId('newsfeed_update_status_info_edit_id');
        $field->setRequired();
        $field->setValue($text);
        $form->addElement($field);

        $field = new HiddenField('eid');
        $field->setValue($eid);
        $form->addElement($field);

        $field = new HiddenField('etype');
        $field->setValue($etype);
        $form->addElement($field);

        $submit = new Submit('submit', 'button');
        $submit->setValue(OW::getLanguage()->text('base', 'ow_ic_save'));
        $form->addElement($submit);

        return $form;
    }

    public function canEditPost($eid, $etype){
        if ($eid == null || $etype == null || empty($eid) || empty($etype) ){
            return false;
        }

        if(!in_array($etype, array('user-status', 'groups-status', 'photo_comments'))){
            return false;
        }

        $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($etype, $eid);
        if($action == null){
            return false;
        }

        if($action->pluginKey != 'newsfeed'){
            return false;
        }

        $activity = $this->getCreatorActivityOfAction($etype, $eid);
        if($activity == null){
            return false;
        }

        $isFeedOwner = $activity->userId == OW::getUser()->getId();
        if(!$isFeedOwner){
            return false;
        }

        return true;
    }

    public function genericItemRender(OW_Event $event)
    {
        if(!IISSecurityProvider::checkPluginActive('newsfeed', true)){
            return;
        }

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getStaticJsUrl() . 'iisnewsfeedplus.js');
        OW::getDocument()->addStyleSheet(OW_PluginManager::getInstance()->getPlugin("iisnewsfeedplus")->getStaticCssUrl() . 'iisnewsfeedplus.css');

        $params = $event->getParams();
        $data = $event->getData();

        if(!isset($params['action']['entityId']) || !isset($params['action']['entityType'])){
            return;
        }

        $entityId = null;
        $entityType = null;
        $canEdit = false;
        if(isset($params['action']) &&
            isset($params['action']['userId']) &&
            isset($params['action']['entityType']) &&
            isset($params['action']['entityId'])){

            $entityId = $params['action']['entityId'];
            $entityType = $params['action']['entityType'];
            if($this->canEditPost($entityId, $entityType)){
                $canEdit = true;
            }
        }

        $oldText = $this->getText($entityId, $entityType);
        if(empty($oldText)){
            $canEdit = false;
        }

        if ($canEdit) {
            array_unshift($data['contextMenu'], array(
                'label' => OW::getLanguage()->text('iisnewsfeedplus', 'edit_post'),
                "class" => "newsfeed_edit_btn",
                'attributes' => array(
                    'onclick' => 'showEditNewsfeedComponent($(this).data().eid, $(this).data().etype)',
                    "data-etype" => $entityType,
                    "data-eid" => $entityId
                )
            ));
        }
        $this->addForwardFeature($data['contextMenu'],$params['feedType'],$params['action']['id'],$entityType,$entityId);
        $event->setData($data);
    }

    /***
     * @param $entityType
     * @param $entityId
     * @param $feedType
     * @return bool
     */
    public function canForwardPost($entityType, $entityId, $feedType)
    {
        if(!isset($feedType))
        {
            return false;
        }
        return $this->canForwardPostByEntityIdAndEntityType($entityType, $entityId);
    }

    /***
     * @param $entityType
     * @param $entityId
     * @return bool
     */
    public function canForwardPostByEntityIdAndEntityType($entityType, $entityId)
    {
        if(!isset($entityId)  || !OW::getUser()->isAuthenticated())
        {
            return false;
        }
        if(!in_array($entityType, self::FORWARDABLE_TYPES)){
            return false;
        }

        /*
         * check if action exists
         */
        $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
        if($action == null){
            return false;
        }

        if($action->pluginKey != 'newsfeed'){
            return false;
        }

        /*
         * check if creation activity
         */
        $activity = $this->getCreatorActivityOfAction($entityType, $entityId);
        if($activity == null){
            return false;
        }

        /*
         * check action feed
         */
        $actionFeed = NEWSFEED_BOL_ActionFeedDao::getInstance()->findByActivityIds(array($activity->id))[0];
        if(!isset($actionFeed))
        {
            return false;
        }

        /*
         * check newsfeed belongs to a group or a user
         */
        if($actionFeed->feedType!='groups' && $actionFeed->feedType!='user')
        {
            return false;
        }

        if($actionFeed->feedType=='groups') {
            /*
             * check if group plugin is active
             */
            $groupPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
            if (!isset($groupPlugin) || !$groupPlugin->isActive()) {
                return;
            }
            /*
             * check if source group exists
             */
            $group = GROUPS_BOL_Service::getInstance()->findGroupById($actionFeed->feedId);
            if (!isset($group)) {
                return;
            }

            /*
             * check if current user has access to source group
             */

            $canView = GROUPS_BOL_Service::getInstance()->isCurrentUserCanView($group);
            if (!$canView) {
                return false;
            }
            return true;
        }
        else if($actionFeed->feedType=='user') {
            /*
             * check if current user is owner of the activity
             */
            if ($activity->userId == OW::getUser()->getId()) {
                return true;
            }
            /*
             * check if current user has access to this activity
             */
            $activityOwnerId = $activity->userId;
            $activityPrivacy = $activity->privacy;

            /*
             * activity is private
             */
            if ($activity->userId != OW::getUser()->getId())
            {
                switch ( $activityPrivacy)
                {
                    case 'only_for_me' :
                        return false;
                        break;
                    case 'everybody' :
                        /*
                         * all users have access to a general status
                         */
                        return true;
                        break;
                    case 'friends_only' :
                        /*
                         * check if current user is a friend of owner of the activity
                         */
                        $friendsPlugin = BOL_PluginDao::getInstance()->findPluginByKey('friends');
                        if (!isset($friendsPlugin) || !$friendsPlugin->isActive()) {
                            return false;
                        }
                        $service = FRIENDS_BOL_Service::getInstance();
                        $isFriends = $service->findFriendship(Ow::getUser()->getId(), $activityOwnerId);
                        if (isset($isFriends) && $isFriends->status == 'active') {
                            return true;
                        }else {
                            return false;
                        }
                        break;
                    default:
                        return false;
                }
            }
        }
        else
        {
            return false;
        }
    }

    public function addForwardFeature(&$contextMenu,$feedType,$actionId,$entityType,$entityId)
    {

        if(!$this->canForwardPost($entityType, $entityId, $feedType))
        {
            return;
        }
        $activity = $this->getCreatorActivityOfAction($entityType, $entityId);
        $actionFeed = NEWSFEED_BOL_ActionFeedDao::getInstance()->findByActivityIds(array($activity->id))[0];
        $sectionId=1;
        array_unshift($contextMenu, array(
            'label' => OW::getLanguage()->text('iisnewsfeedplus', 'forward_post'),
            "class" => "newsfeed_forward_btn",
            'attributes' => array(
                'onclick' => 'showUserGroupsComponent($(this).data().aid, $(this).data().fid,$(this).data().vis,$(this).data().pri,$(this).data().fty,$(this).data().sid,$(this).data().title)',
                "data-aid" => $actionId,
                "data-fid" => $actionFeed->feedId,
                "data-vis" => $activity->visibility,
                "data-pri" => $activity->privacy,
                "data-fty" =>$actionFeed->feedType,
                "data-sid" =>$sectionId,
                "data-title" =>""
            )
        ));
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getStaticJsUrl() . 'group_user_select.js');
    }

    /**
     * @param $sectionId
     * @param $actionId
     * @param $feedId
     * @param $visibility
     * @param $privacy
     * @param $feedType
     * @return array
     */
    public function getForwardSections($sectionId,$actionId,$feedId,$visibility,$privacy,$feedType)
    {
        $sections = array();
        $title="";
        for ($i = 1; $i <= 2; $i++) {
            $url = "javascript:forwardNewsfeedComponent.close();showUserGroupsComponent(" . $actionId.",".$feedId.",".$visibility.",'".$privacy."','".$feedType."',".$i.",'".$title. "');";
            $sections[] = array(
                'sectionId' => $i,
                'active' => $sectionId == $i ? true : false,
                'url' => $url,
                'label' => $this->getPageHeaderLabel($i)
            );
        }
        return $sections;
    }

    public function getPageHeaderLabel($sectionId)
    {
        if ($sectionId == 1) {
            return OW::getLanguage()->text('iisnewsfeedplus', 'forward_to_group');
        } else if ($sectionId == 2) {
            return OW::getLanguage()->text('iisnewsfeedplus', 'forward_to_user');
        }
    }

    public function getFeedForwardType($sectionId)
    {
        if ($sectionId == 1) {
            return 'groups';
        } else if ($sectionId == 2) {
            return 'user';
        }
    }

    public function attachmentAddParameters(OW_Event $event)
    {
        $params=$event->getParams();
        if(!isset($params['oldParams']) || !isset($params['pluginKey']) || $params['pluginKey']!='iisnewsfeedplus')
        {
            return;
        }
        $newparams = $params['oldParams'];
        $newparams['photoPreviewFeature']=true;
        $previewExtensions = array_merge(self::VIDEO_EXTENSIONS,self::AUDIO_EXTENSIONS,self::IMAGE_EXTENSIONS);
        $newparams['previewExtensions']=$previewExtensions;
        $event->setData(array('newParams'=>$newparams));
    }

    public function getThumbnailFileDir($FileName)
    {
        return OW::getPluginManager()->getPlugin('iisnewsfeedplus')->getUserFilesDir() . $FileName;
    }

    public function getThumbnailFilePath($FileName)
    {
        return OW::getStorage()->getFileUrl($this->getThumbnailFileDir($FileName));
    }

    public function afterStatusComponentAddition(OW_Event $event)
    {
        $params = $event->getParams();
        $eventData= $event->getData();
        $uri = OW::getRequest()->getRequestUri();
        $allow_sort = true;
        if(OW::getConfig()->configExists('iisnewsfeedplus', 'allow_sort')){
            $allow_sort = OW::getConfig()->getValue('iisnewsfeedplus', 'allow_sort');
        }
        $attr = OW::getRequestHandler()->getHandlerAttributes();
        if ($allow_sort && preg_match('/newsfeed\/\d*\??.*$/', $uri) == 0 &&
            ($attr[OW_RequestHandler::ATTRS_KEY_CTRL]=='BASE_CTRL_ComponentPanel' && $attr[OW_RequestHandler::ATTRS_KEY_ACTION]=='dashboard')) {
            if (isset($params['feedType']) && isset($params['feedId'])) {
                $options = array();
                $options[self::ORDER_BY_ACTIVITY]['text'] = OW::getLanguage()->text('iisnewsfeedplus', 'sort_by_activity');
                $options[self::ORDER_BY_ACTION]['text'] = OW::getLanguage()->text('iisnewsfeedplus', 'sort_by_action');
                $options[self::ORDER_BY_ACTIVITY]['value']=self::ORDER_BY_ACTIVITY;
                $options[self::ORDER_BY_ACTION]['value']=self::ORDER_BY_ACTION;
                if (isset($_COOKIE['newsfeed_order']) && ($_COOKIE['newsfeed_order']==self::ORDER_BY_ACTION || $_COOKIE['newsfeed_order']==self::ORDER_BY_ACTIVITY)) {
                    $options[$_COOKIE['newsfeed_order']]['selected'] = true;
                }else if(OW::getConfig()->configExists('iisnewsfeedplus', 'newsfeed_list_order')) {
                    $options[OW::getConfig()->getValue('iisnewsfeedplus', 'newsfeed_list_order')]['selected'] = true;
                }
                $eventData['options']=$options;
                $event->setData($eventData);
            }
        }
    }

    public function canForwardPostEvent(OW_Event $event){
        $params = $event->getParams();
        if (!isset($params['entityId']) || !isset($params['entityType'])) {
            $event->setData(array('forwardable' => false));
            return;
        }
        $event->setData(array('forwardable' => $this->canForwardPostByEntityIdAndEntityType($params['entityType'], $params['entityId'])));
    }

    public function changeNewsfeedActionQuery(OW_Event $event)
    {
        $order = null;
        if (isset($_COOKIE['newsfeed_order'])) {
            $order = $_COOKIE['newsfeed_order'];
        } else if (isset($_POST['newsfeed_order'])) {
            $order = $_POST['newsfeed_order'];
        }
        if ($order != null) {
            $order = UTIL_HtmlTag::stripTagsAndJs($order);
            $order = UTIL_HtmlTag::escapeHtml($order);
            if (OW::getConfig()->configExists('iisnewsfeedplus', 'newsfeed_list_order'))
            {
                if (OW::getConfig()->getValue('iisnewsfeedplus', 'newsfeed_list_order') == self::ORDER_BY_ACTION) {
                    $orderBy = ' ORDER BY MAX(`b`.`id`) DESC ';
                }else {
                    $orderBy = ' ORDER BY MAX(`b`.`timeStamp`) DESC ';
                }
            }

            if ($order == self::ORDER_BY_ACTION)
            {
                $orderBy = ' ORDER BY MAX(`b`.`id`) DESC ';
            } else if ($order == self::ORDER_BY_ACTIVITY)
            {
                $orderBy = ' ORDER BY MAX(`b`.`timeStamp`) DESC ';
            }
            if(isset($orderBy))
            {
                $event->setData(array('orderBy'=>$orderBy));
            }
        }
    }

    public function getItemType($attachment)
    {
        $itemType = null;
        if (isset(pathinfo($attachment->getOrigFileName())['extension'])) {
            $extension= strtolower(pathinfo($attachment->getOrigFileName())['extension']);
            if (!empty($extension)) {
            }
            if (in_array(trim($extension), self::VIDEO_EXTENSIONS)) {
                $itemType = 'video';
            } elseif (in_array(trim($extension), self::AUDIO_EXTENSIONS)) {
                $itemType = 'audio';
            } elseif (in_array(trim($extension), self::IMAGE_EXTENSIONS)) {
                $itemType = 'image';
            }
        }
        return $itemType;
    }

}
