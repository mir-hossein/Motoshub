<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iismobilesupport.bol
 * @since 1.0
 */
class IISMOBILESUPPORT_BOL_WebServiceGeneral
{
    private static $classInstance;
    private $eventWebService;
    private $groupWebService;
    private $userWebService;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
        $this->eventWebService = IISMOBILESUPPORT_BOL_WebServiceEvent::getInstance();
        $this->groupWebService = IISMOBILESUPPORT_BOL_WebServiceGroup::getInstance();
        $this->userWebService = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance();
        $this->notificationsWebService = IISMOBILESUPPORT_BOL_WebServiceNotifications::getInstance();
        $this->newsWebService = IISMOBILESUPPORT_BOL_WebServiceNews::getInstance();
        $this->friendsWebService = IISMOBILESUPPORT_BOL_WebServiceFriends::getInstance();
        $this->searchWebService = IISMOBILESUPPORT_BOL_WebServiceSearch::getInstance();
        $this->videoWebService = IISMOBILESUPPORT_BOL_WebServiceVideo::getInstance();
        $this->photoWebService = IISMOBILESUPPORT_BOL_WebServicePhoto::getInstance();
        $this->newsfeedWebService = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance();
        $this->mailboxWebService = IISMOBILESUPPORT_BOL_WebServiceMailbox::getInstance();
        $this->commentWebService = IISMOBILESUPPORT_BOL_WebServiceComment::getInstance();
        $this->forumWebService = IISMOBILESUPPORT_BOL_WebServiceForum::getInstance();
        $this->privacyWebService = IISMOBILESUPPORT_BOL_WebServicePrivacy::getInstance();
        $this->contactusWebService = IISMOBILESUPPORT_BOL_WebServiceContactUs::getInstance();
        $this->blogsWebService=IISMOBILESUPPORT_BOL_WebServiceBlogs::getInstance();
        $this->questionsWebService=IISMOBILESUPPORT_BOL_WebServiceQuestions::getInstance();
        $this->flagWebService=IISMOBILESUPPORT_BOL_WebServiceFlag::getInstance();
    }

    public function isFileClean($path) {
        if (!isset($path) || $path == null) {
            return false;
        }
        $checkFileCleanEvent = OW::getEventManager()->trigger(new OW_Event('iisclamav.is_file_clean', array('path' => $path)));
        if(isset($checkFileCleanEvent->getData()['clean'])){
            $isClean = $checkFileCleanEvent->getData()['clean'];
            if(!$isClean)
            {
                return false;
            }
        }
        return true;
    }

    public function getMobileConfig(){
        $data = array();
        $data['plugins'] = $this->getActivePlugins();
        $data['join_fields'] = $this->userWebService->getJoinFields();
        $data['group_fields'] = $this->groupWebService->getGroupFields();
        $data['event_fields'] = $this->eventWebService->getEventFields();
        $data['social_name'] = OW::getConfig()->getValue('base', 'site_name');
        return $data;
    }

    public function correctFileName() {
        if (isset($_FILES)) {
            $fileIndex = 0;
            if (isset($_FILES['file']) && isset($_FILES['file']['name'])) {
                $_FILES['file']['name'] = urldecode($_FILES['file']['name']);
            }
            while (isset($_FILES['file' . $fileIndex]) && isset($_FILES['file' . $fileIndex]['name'])) {
                $_FILES['file' . $fileIndex]['name'] = urldecode($_FILES['file' . $fileIndex]['name']);
                $fileIndex++;
            }
        }
    }

    public function getActivePlugins(){
        $data = array();
        $plugins = BOL_PluginService::getInstance()->findActivePlugins();
        foreach ($plugins as $plugin){
            if($plugin->isSystem == 0){
                $data[] = $plugin->key;
            }
        }
        return $data;
    }

    public function isSessionUserExpired(){
        if($this->checkPluginActive('iisuserlogin', true)){
            return IISUSERLOGIN_BOL_ActiveDetailsDao::getInstance()->isSessionExpired(session_id());
        }
        return false;
    }

    public function manageRequestHeader($type, $actionType = 'info'){
        if(!defined("ACCESS_WEB_SERVICE") || ACCESS_WEB_SERVICE == false){
            exit($this->makeJson(array("Config of web service is not set.")));
        }

        $accessToken = null;
        $fcmToken = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getFcmTokenFromPost();
        $fcmTokenString = '';
        if ($fcmToken != null) {
            $fcmTokenString = $fcmToken;
        }
        $logoutProcess = false;
        if(isset($_POST['access_token'])){
            $accessToken = $_POST['access_token'];
            $id = BOL_UserService::getInstance()->findUserIdByCookie(trim($_POST['access_token']));
            if ( !empty($id) )
            {
                if (!$this->isSessionUserExpired()){
                    OW::getUser()->login($id, false);
                }else{
                    OW::getLogger()->writeLog(OW_Log::INFO, 'mobile_native_user_auto_login', array('message' => 'login_cookie_expired', 'token' => $_POST['access_token'], 'fcmToken' => $fcmTokenString));
                    $logoutProcess = true;
                }
            }else{
                OW::getLogger()->writeLog(OW_Log::INFO, 'mobile_native_user_auto_login', array('message' => 'login_cookie_not_found', 'token' => $_POST['access_token'], 'fcmToken' => $fcmTokenString));
                $logoutProcess = true;
            }
        }else{
            OW::getLogger()->writeLog(OW_Log::INFO, 'mobile_native_user_auto_login', array('message' => 'login_cookie_not_sent', 'fcmToken' => $fcmTokenString));
            $logoutProcess = true;
        }
        if ($logoutProcess) {
            IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->logoutProcess();
        }
        $this->correctFileName();
        $validType = array('config', 'forgot_password', 'resend_sms_verification', 'verify_mobile_code', 'login', 'join');
        if(!in_array($type, $validType) && !OW::getUser()->isAuthenticated()){
            header('HTTP/1.0' . ' ' . '403 Forbidden');
            header('Status' . ' ' . '403 Forbidden');
            $this->generateWebserviceResult($type, $actionType);
        }
        if(OW::getUser()->isAuthenticated() && $accessToken != null && $fcmToken != null){
            IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->addNativeDevice(OW::getUser()->getId(), $fcmToken, $accessToken);
        }
    }

    public function getNecessaryPostedData() {
        $data = array();
        $first = 0;
        $commentPage = 0;
        $search = '';
        $data['count'] = $this->getPageSize();
        if (isset($_GET['first'])) {
            $first = $_GET['first'];
        }

        if (isset($_POST['first'])) {
            $first = $_POST['first'];
        }

        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }

        if (isset($_POST['search'])) {
            $search = $_POST['search'];
        }

        if (isset($_GET['searchValue'])) {
            $search = $_GET['searchValue'];
        }

        if (isset($_POST['searchValue'])) {
            $search = $_POST['searchValue'];
        }

        if (isset($_GET['comment_page'])) {
            $commentPage = $_GET['comment_page'];
        }

        if (isset($_POST['comment_page'])) {
            $commentPage = $_POST['comment_page'];
        }

        $data['search'] = $search;
        $data['comment_page'] = $commentPage;
        $data['first'] = $first;
        return $data;
    }

    public function checkPluginActive($key, $return = false){
        $plugin = BOL_PluginDao::getInstance()->findPluginByKey($key);
        if(!isset($plugin) || !$plugin->isActive()) {
            if($return){
                return false;
            }else{
                throw new Redirect404Exception();
            }
        }
        return true;
    }

    /***
     * Return native mobile menu
     * @return array
     */
    public function getMobileMenu(){
        $menu = array();
        $menuFetch = array();
        $menuFetch = array_merge($menuFetch, $this->getMenuTypeMainItems(true));
        $menuFetch = array_merge($menuFetch, $this->getMenuTypeBottomItems(true));
        foreach($menuFetch as $menuItem){
            $menu[] = array('label' => $menuItem['label'], 'prefix' => $menuItem['prefix']);
        }
        return $menu;
    }

    /***
     * Create forms from data
     * @param $fields
     * @return Form
     */
    public function getFormUsingDataArray($fields){
        $form = new Form('sample');

        foreach ($fields as $field){
            $fieldForm = null;
            if($field['type'] == 'text' ||
                $field['type'] == 'time' ||
                $field['type'] == 'datetime'){
                $fieldForm = new TextField($field['name']);
            }else if($field['type'] == 'select' || $field['type'] == 'fselect'){
                $fieldForm = new Selectbox($field['name']);
                $newFiledValue = array();
                foreach ($field['values'] as $itemFieldValue) {
                    if (isset($itemFieldValue['label'])) {
                        $newFiledValue[$itemFieldValue['value']] = $itemFieldValue['label'];
                    } else {
                        $newFiledValue[] = $itemFieldValue;
                    }
                }
                $fieldForm->addOptions($newFiledValue);
            }else if($field['type'] == 'date'){
                $fieldForm = new DateField($field['name']);
            }else if($field['type'] == 'captcha' || $field['name'] == 'captcha'){
                $fieldForm = new CaptchaField($field['name']);
            }
            if($field['type'] == 'text' ||
                $field['type'] == 'select' ||
                $field['type'] == 'fselect'){
                $fieldForm->setHasInvitation(false);
            }
            if(isset($field['required']) && !$field['required']){
                $fieldForm->setRequired(false);
            }else if(isset($field['required']) && $field['required']){
                $fieldForm->setRequired(true);
            }else if($fieldForm != null){
                $fieldForm->setRequired();
            }
            if ($fieldForm != null) {
                $form->addElement($fieldForm);
            }
        }

        return $form;
    }

    /***
     * Check data is valid
     * @param $fields
     * @param $useAuthenticated
     * @return array
     */
    public function checkDataFormValid($fields, $useAuthenticated = true){
        if($useAuthenticated && !OW::getUser()->isAuthenticated()){
            return array( 'valid' => false, 'errors' => array() );
        }


        $form = $this->getFormUsingDataArray($fields);
        if ( $form->getElement('csrf_token') != null){
            $form->deleteElement('csrf_token');
        }
        try{
            if(isset($_POST) && $form->isValid($_POST)){
                return array( 'valid' => true );
            }
        }catch (Exception $e){
            return array( 'valid' => false, 'errors' => $form->getErrors() );
        }
        return array( 'valid' => false, 'errors' => $form->getErrors() );
    }

    /***
     * @param bool $onlyMobile
     * @return array
     */
    public function getMenuTypeMainItems($onlyMobile = false){
        $menuTypeMainItems = array();
        $items = array();
        if (OW::getApplication()->getContext() == OW::CONTEXT_MOBILE || $onlyMobile) {
            $menuTypeMainItems = BOL_NavigationService::getInstance()->getMenuItems(BOL_NavigationService::getInstance()->findMenuItems(BOL_MobileNavigationService::MENU_TYPE_TOP));
        } else {
            $menuTypeMainItems = BOL_NavigationService::getInstance()->getMenuItems(BOL_NavigationService::getInstance()->findMenuItems(BOL_NavigationService::MENU_TYPE_MAIN));
        }

        foreach($menuTypeMainItems as $menuTypeMainItem){
            $items[] = array('label' => $menuTypeMainItem->getLabel(), 'url' => $menuTypeMainItem->getUrl(), 'prefix' => $menuTypeMainItem->getPrefix());
        }

        return $items;
    }

    /***
     * @param bool $onlyMobile
     * @return array
     */
    public function getMenuTypeBottomItems($onlyMobile = false){
        $menuTypeBottomItems = array();
        $items = array();
        if (OW::getApplication()->getContext() == OW::CONTEXT_MOBILE || $onlyMobile) {
            $menuTypeBottomItems = BOL_NavigationService::getInstance()->getMenuItems(BOL_NavigationService::getInstance()->findMenuItems(BOL_MobileNavigationService::MENU_TYPE_BOTTOM));
        } else {
            $menuTypeBottomItems = BOL_NavigationService::getInstance()->getMenuItems(BOL_NavigationService::getInstance()->findMenuItems(BOL_NavigationService::MENU_TYPE_BOTTOM));
        }

        foreach($menuTypeBottomItems as $menuTypeBottomItem){
            $items[] = array('label' => $menuTypeBottomItem->getLabel(), 'url' => $menuTypeBottomItem->getUrl(), 'prefix' => $menuTypeBottomItem->getPrefix());
        }

        return $items;
    }

    public function getPageSize(){
        return 10;
    }

    public function getPageNumber($first = 0){
        $count = $this->getPageSize();
        $page = (int) ($first/$count);
        if ($first % $count != 0) {
            $page += 1;
        }
        return $page + 1;
    }

    public function checkPrivacyAction($userId, $privacyAction, $module){
        if(OW::getUser()->isAdmin()){
            return true;
        }
        if(OW::getUser()->isAuthenticated()) {
            $viewerId = OW::getUser()->getId();
            $ownerMode = $userId == $viewerId;
            $modPermissions = OW::getUser()->isAuthorized($module);

            if($ownerMode){
                return true;
            }

            if (!$modPermissions) {
                $privacyParams = array('action' => $privacyAction, 'ownerId' => $userId, 'viewerId' => $viewerId);
                $event = new OW_Event('privacy_check_permission', $privacyParams);

                try {
                    OW::getEventManager()->trigger($event);
                } catch (RedirectException $e) {
                    return false;
                }
            }
        }

        $privacy = OW::getEventManager()->call('plugin.privacy.get_privacy',
            array('ownerId' => $userId, 'action' => $privacyAction)
        );

        if($privacy == 'only_for_me'){
            if(!OW::getUser()->isAuthenticated()){
                return false;
            }

            if(OW::getUser()->isAuthenticated() && $userId != OW::getUser()->getId()){
                return false;
            }
        }else if($privacy == 'friends_only'){
            if(!OW::getUser()->isAuthenticated()){
                return false;
            }

            if(OW::getUser()->isAuthenticated()){
                if(!IISMOBILESUPPORT_BOL_WebServiceFriends::getInstance()->isFriend($userId, OW::getUser()->getId())){
                    return false;
                }
            }
        }

        return true;
    }

    public function checkGuestAccess(){
        $baseConfigs = OW::getConfig()->getValues('base');
        if ( (int) $baseConfigs['guests_can_view'] === BOL_UserService::PERMISSIONS_GUESTS_CANT_VIEW && !OW::getUser()->isAuthenticated() )
        {
            return false;
        }

        return true;
    }

    /***
     * @param $array
     * @return null|string
     */
    public function makeJson($array){
        if($array == null){
            return null;
        }

        return json_encode($array);
    }

    public function getValidExtensions(){
        $list = json_decode(OW::getConfig()->getValue('base', 'attch_ext_list'), true);
        return implode(',', $list);
    }

    public function getFilesInfo(){
        $maxUploadSize = OW::getConfig()->getValue('base', 'attch_file_max_size_mb');
        return array(
            'max_upload_size_mb' => (int) $maxUploadSize,
        );
    }

    public function preparedFileList($group, $filesList){
        $preparedFilesList = array();
        if($group == null){
            return $preparedFilesList;
        }

        foreach ( $filesList as $item )
        {
            $preparedFilesList[$item->id] = $this->prepareFileInformation($item);
        }
        return $preparedFilesList;
    }

    public function prepareFileInformation($item){
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

        $fileName = $this->stripString($fileName);

        $fileNameArr = explode('.',$item->fileName);
        $fileNameExt = end($fileNameArr);

        $data['fileUrl'] = $this->getAttachmentUrl($item->fileName);
        $data['iconUrl'] = IISGROUPSPLUS_BOL_Service::getInstance()->getProperIcon(strtolower($fileNameExt));
        $data['truncatedFileName'] = $fileName;
        $data['fileName'] = $item->getOrigFileName();
        $data['createdDate'] =$item->addStamp;
        $data['canDelete'] = OW::getUser()->getId() == $item->getUserId();
        $data['userName'] = BOL_UserService::getInstance()->getDisplayName($item->getUserId());
        $data['userId'] = (int) $item->getUserId();
        $data['id'] = (int) $item->id;

        return $data;
    }

    public function populateInvitableUserList($idList, $key, $first, $count){
        $users = array();
        $usersObject = BOL_UserService::getInstance()->findUserListByIdList($idList);
        $displayNames = BOL_UserService::getInstance()->getDisplayNamesForList($idList);
        $usernames = BOL_UserService::getInstance()->getUserNamesForList($idList);
        $avatars = BOL_AvatarService::getInstance()->getAvatarsUrlList($idList);
        $counter = -1;
        foreach ($usersObject as $user){
            if(sizeof($users) >= $count){
                break;
            }
            $username = null;
            $displayName = null;
            if(isset($displayNames[$user->id])){
                $displayName = $displayNames[$user->id];
            }

            if(isset($usernames[$user->id])){
                $username = $usernames[$user->id];
            }

            $avatarUrl = null;
            if(isset($avatars[$user->id])){
                $avatarUrl = $avatars[$user->id];
            }
            $include = false;
            if($key == ''){
                $include = true;
            }else {
                $findChar = false;
                if(strpos($user->email, $key)!==false){
                    $findChar = true;
                } else if($username != null && strpos($username, $key)!==false){
                    $findChar = true;
                } else if($displayName != null && strpos($displayName, $key)!==false){
                    $findChar = true;
                }
                if($findChar){
                    $include = true;
                }
            }
            if($include){
                $counter++;
                if($counter < $first){
                    continue;
                }
                $users[] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->populateUserData($user, $avatarUrl, $displayName);
            }
        }
        return $users;
    }

    public function getAttachmentUrl($name)
    {
        return OW::getStorage()->getFileUrl($this->getAttachmentDir($name));
    }

    public function getAttachmentDir($name)
    {
        return OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'attachments' . DS .$name ;
    }

    public function stripString($string, $removeMultipleNewLines = true, $removeNewLine = false, $changeBrToNewLine = false){
        $string = str_replace('&nbsp;'," ", $string);

        // convert quote icon into hex
        $string = str_replace('&quot;',"\"", $string);

        // convert and icon into hex
        $string = str_replace('&amp;',"&", $string);
        $string = str_replace('<!--more-->',"", $string);

        // convert check icon into hex
        $string = str_replace('',"&#x2713;", $string);

        if ($changeBrToNewLine) {
            $string = preg_replace('#<br\s*?/?>#i', "\r\n", $string);
        }
        $string = $this->getDomTextContent($string);
        if($removeMultipleNewLines){
            //remove multiple new lines
            $string = preg_replace("/[\r\n]+/", "\r\n", $string);
            $string = preg_replace("/[\n]+/", "\n", $string);
            $string = preg_replace("/[\r]+/", "\r", $string);
        }
        $string = $this->brToNewLine($string);

        // remove additional character (used for rtl in web)
        $string = str_replace('&#8235;', '', $string);

        $string = preg_replace("'\r'","", $string);
        $string = preg_replace("'\n '","", $string);
        $string = preg_replace("'\n '","", $string);
        $string = preg_replace("' '","", $string);
        if($removeNewLine){
            $string = str_replace("\r\n"," ", $string);
            $string = str_replace("\r"," ", $string);
            $string = str_replace("\n"," ", $string);
            $string = preg_replace('/\s+/', ' ', $string);
        }
        $string = trim($string);
        return $string;
    }

    public function getDomTextContent($text){
        if(strpos($text, '<') !== false) {
            //DomDocument
            $text = '<div>'.$text.'</div>';
            $doc = new DOMDocument();
            @$doc->loadHTML(mb_convert_encoding($text, 'HTML-ENTITIES', 'UTF-8'));
            //$domDoc1 = preg_replace('~<(?:!DOCTYPE|/?(?:html|head|body))[^>]*>\s*~i', '', $doc->saveHTML());

            # remove <!DOCTYPE
            $doc->removeChild($doc->doctype);
            # remove <html><body></body></html>
            /*** get the links from the HTML ***/

            $links = $doc->getElementsByTagName('a');
            $linksReplaces = $this->getLinkReplaces($links);

            $doc = $this->getCircleBulletsReplaces($doc);
            $doc = $this->getNumberBulletsReplaces($doc);

            $element = $doc->firstChild->firstChild->firstChild;
            $text = $element->textContent;

            foreach ($linksReplaces as $key => $value) {
                $text = str_replace($key, $value, $text);
            }
        }
        return $text;
    }

    public function getLinkReplaces($links){
        $replaces = array();

        /*** loop over the links ***/
        foreach ($links as $tag)
        {
            if(isset($tag->childNodes) && isset($tag->childNodes->item(0)->nodeValue)) {
                $innerText = $tag->childNodes->item(0)->nodeValue;
                if (strpos($innerText, '...')) {
                    $arr = parse_url($innerText);
                    if (isset($arr['scheme'])) {
                        $replaces[$tag->childNodes->item(0)->nodeValue] = $tag->getAttribute('href');
                    }
                }
            }
        }
        return $replaces;
    }

    /***
     * @param DOMDocument $doc
     * @return DOMDocument
     */
    public function getCircleBulletsReplaces($doc){
        foreach ($doc->getElementsByTagName('ul') as $bulletsTag)
        {
            if(isset($bulletsTag->childNodes) && isset($bulletsTag->childNodes)) {
                foreach ($bulletsTag->childNodes as $bullet) {
                    $checkString = $this->removeNewLine($bullet->nodeValue);
                    if ($checkString != ''){
                        $bullet->nodeValue = '&#x2022; ' . $bullet->nodeValue ;
                    }
                }
            }
        }

        return $doc;
    }

    public function removeNewLine($checkString){
        $checkString = preg_replace("'\r'","", $checkString);
        $checkString = preg_replace("'\n '","", $checkString);
        $checkString = preg_replace("'\n '","", $checkString);
        $checkString = preg_replace("' '","", $checkString);
        $checkString = trim($checkString);
        $checkString = trim($checkString, '\n');
        return $checkString;
    }

    /***
     * @param DOMDocument $doc
     * @return DOMDocument
     */
    public function getNumberBulletsReplaces($doc){
        foreach ($doc->getElementsByTagName('ol') as $bulletsTag)
        {
            if(isset($bulletsTag->childNodes) && isset($bulletsTag->childNodes)) {
                $index = 1;
                foreach ($bulletsTag->childNodes as $bullet) {
                    $checkString = $this->removeNewLine($bullet->nodeValue);
                    if ($checkString != ''){
                        $bullet->nodeValue = $index.'. ' . $bullet->nodeValue ;
                        $index++;
                    }
                }
            }
        }

        return $doc;
    }

    public function brToNewLine($string){
        $string = str_replace("<br />","\r\n", $string);
        $string = str_replace("<br/>","\r\n", $string);
        $string = str_replace("<br>","\r\n", $string);
        $string = str_replace("</br>","\r\n", $string);
        return $string;
    }

    public function userAccessUsingPrivacy($privacy, $userId, $ownerId){
        if($privacy == null){
            return true;
        }

        if($userId == $ownerId){
            return true;
        }

        if($privacy == NEWSFEED_BOL_Service::PRIVACY_EVERYBODY){
            return true;
        }

        if($privacy == NEWSFEED_BOL_Service::PRIVACY_ONLY_ME && $userId != $ownerId){
            return false;
        }

        if($privacy == NEWSFEED_BOL_Service::PRIVACY_FRIENDS){
            $isFriend = IISMOBILESUPPORT_BOL_WebServiceFriends::getInstance()->isFriend($userId, $ownerId);
            if($isFriend){
                return true;
            }
        }

        return false;
    }

    public function generateWebserviceResult($type = null, $actionType = 'info') {
        $entryData = array(
            "check_login",
            "posted_data",
            "valid_extensions",
            "files_info"
        );
        if ($type != null) {
            $entryData[] = $type;
        }
        if ($actionType == 'info') {
            $entryData[] = "new_notifications_count";
            $entryData[] = "unread_conversations_count";
            $data = $this->populateWebServiceInformationData($entryData);
        } else {
            $data = $this->populateWebServiceActionData($entryData);
        }
        exit($this->makeJson($data));
    }

    public function uploadSingleFile() {
        if (!OW::getUser()->isAuthenticated()) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $videoId = null;
        $fileId = null;
        $attachId = null;
        $fileData = null;
        $fileUrl = null;
        $valid = true;
        $type = 'post';
        $pluginKey = 'iisnewsfeedplus';

        if (isset($_POST['type']) && in_array($_POST['type'], array('post', 'post_video', 'single_video'))) {
            $type = $_POST['type'];
        }

        if (isset($_POST['videoId'])) {
            $videoId = UTIL_HtmlTag::escapeHtml(UTIL_HtmlTag::stripTagsAndJs($_POST['videoId']));
        }

        if (isset($_POST['fileData'])) {
            $fileData = $_POST['fileData'];
        }

        if (isset($_POST['attachId'])) {
            // check $attachId is valid
            $attachmentList = BOL_AttachmentDao::getInstance()->findAttahcmentByBundle($pluginKey, $_POST['attachId']);
            if (!empty($attachmentList) && $attachmentList != null) {
                $attachId = UTIL_HtmlTag::escapeHtml(UTIL_HtmlTag::stripTagsAndJs($_POST['attachId']));
                foreach ($attachmentList as $attachmentItem) {
                    if ($attachmentItem->status == 1 || $attachmentItem->userId != OW::getUser()->getId()) {
                        return array('valid' => false, 'message' => 'input_error');
                    }
                }
            }
        }

        if (isset($_FILES) && isset($_FILES['file'])) {
            $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
            if ($isFileClean) {
                if ($type == 'post') {
                    $dtoObject = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->manageNewsfeedAttachment(OW::getUser()->getId(), $_FILES['file'], $attachId);
                    if (isset($dtoObject) || $dtoObject != null) {
                        $attachId = $dtoObject['bundle'];
                        if (isset($dtoObject['dto']['dto'])) {
                            $fileId = $dtoObject['dto']['dto']->id;
                        } else if (isset($dtoObject['dto'])) {
                            $fileId = $dtoObject['dto']->id;
                        }

                        if (isset($dtoObject['dto']['url'])) {
                            $fileUrl = $dtoObject['dto']['url'];
                        }
                    }
                }
            } else {
                return array('valid' => false, 'message' => 'virus_detected');
            }
        } else if ($fileData != null) {
            if ($type == 'single_video' && $videoId != null) {
                $result = IISMOBILESUPPORT_BOL_WebServiceVideo::getInstance()->setThumbnail($videoId, $fileData);
                $valid = $result['valid'];
                $fileUrl = $result['thumbnail'];
            } else if ($type == 'post_video' && $videoId != null) {
                $result = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->setPostVideoThumbnail($videoId, $fileData);
                $valid = $result['valid'];
                $fileUrl = $result['thumbnail'];
            }
            if (!$valid) {
                return array('valid' => $valid, 'message' => 'input_file_error');
            }
        } else {
            return array('valid' => $valid, 'message' => 'input_file_error');
        }

        return array(
            'valid' => $valid,
            'attachmentId' => $attachId,
            'fileId' => $fileId,
            'videoId' => $videoId,
            'fileUrl' => $fileUrl
        );
    }

    private function populateWebServiceInformationData($type = array()){
        $data = array();
        if(in_array("config", $type)){
            $data["config"] = $this->getMobileConfig();
        }

        if(in_array("groups", $type)){
            $data["groups"] = $this->groupWebService->getGroups('latest');
        }

        if(in_array("group_invite_list", $type)){
            $data["group_invite_list"] = $this->groupWebService->getInvitableUsers();
        }

        if(in_array("event_invite_list", $type)){
            $data["event_invite_list"] = $this->eventWebService->getInvitableUsers();
        }

        if(in_array("get_group", $type)){
            $data["get_group"] = $this->groupWebService->getGroup();
        }

        if(in_array("events", $type)){
            $data["events"] = $this->eventWebService->getEvents('latest');
        }

        if(in_array("get_event", $type)){
            $data["get_event"] = $this->eventWebService->getEvent();
        }

        if(in_array("edit_profile_fields", $type)){
            $data["edit_profile_fields"] = $this->userWebService->getEditProfileFields();
        }

        if(in_array("get_user_videos", $type)){
            $data["get_user_videos"] = $this->videoWebService->getUserVideos();
        }

        if(in_array("get_user_albums", $type)){
            $data["get_user_albums"] = $this->photoWebService->getUserAlbums();
        }

        if(in_array("get_photo", $type)){
            $data["get_photo"] = $this->photoWebService->getPhoto();
        }

        if(in_array("get_album_photos", $type)){
            $data["get_album_photos"] = $this->photoWebService->getAlbumPhotos();
        }

        if(in_array("get_video", $type)){
            $data["get_video"] = $this->videoWebService->getVideo();
        }

        if(in_array("user_profile", $type)){
            $data["user_profile"] = $this->userWebService->getUserInformation(true);
        }

        if(in_array("notifications", $type)){
            $data["notifications"] = $this->notificationsWebService->getNotifications();
        }

        if(in_array("new_notifications", $type)){
            $data["new_notifications"] = $this->notificationsWebService->getNewNotifications();
        }

        if(in_array("new_notifications_count", $type)){
            $data["new_notifications_count"] = $this->notificationsWebService->getNewNotificationsCount();
        }

        if(in_array("getNews", $type)){
            $data["news"] = $this->newsWebService->getNews();
        }

        if(in_array("get_news_item", $type)){
            $data["news_item"] = $this->newsWebService->getNewsItem();
        }

        if(in_array("get_dashboard", $type)){
            $data["get_dashboard"] = $this->newsfeedWebService->getDashboard();
        }

        if(in_array("get_post", $type)){
            $data["get_post"] = $this->newsfeedWebService->getPost();
        }

        if(in_array("get_messages", $type)){
            $data["get_messages"] = $this->mailboxWebService->getMessages();
        }

        if(in_array("get_user_message", $type)){
            $data["get_user_message"] = $this->mailboxWebService->getUserMessage();
        }

        if (in_array("unread_conversations_count", $type)) {
            $data["unread_conversations_count"] = $this->mailboxWebService->getUnreadConversationsCount();
        }

        if(in_array("search", $type)){
            $data["search"] = $this->searchWebService->search();
        }

        if(in_array("check_login", $type)){
            $data["check_login"] = $this->userWebService->checkLogin();
        }

        if(in_array("posted_data", $type)){
            $data["posted_data"] = $this->getNecessaryPostedData();
        }

        if(in_array("forums", $type)){
            $data["forums"] = $this->forumWebService->getForums();
        }

        if(in_array("topics", $type)){
            $data["topics"] = $this->forumWebService->getTopics();
        }

        if(in_array("topic", $type)){
            $data["topic"] = $this->forumWebService->getTopic();
        }

        if(in_array("comments", $type)){
            $data["comments"] = $this->commentWebService->getCommentsInformationFromRequest();
        }

        if(in_array("requests", $type)){
            $data["requests"] = $this->userWebService->getRequests();
        }

        if(in_array("search_friends", $type)){
            $data["search_friends"] = $this->userWebService->searchFriends();
        }

        if(in_array("user_privacy", $type)){
            $data["user_privacy"] = $this->privacyWebService->userPrivacy();
        }

        if(in_array("user_blogs", $type)){
            $data["user_blogs"] = $this->blogsWebService->getUserblogs();
        }

        if(in_array("blog", $type)){
            $data["blog"] = $this->blogsWebService->getBlog();
        }

        if(in_array("latest_blogs", $type)){
            $data["latest_blogs"] = $this->blogsWebService->getLatestBlogs();
        }

        if(in_array("valid_extensions", $type)){
            $data["valid_extensions"] = $this->getValidExtensions();
        }

        if(in_array("files_info", $type)){
            $data["files_info"] = $this->getFilesInfo();
        }

        return $data;
    }

    public function setMentionsOnText($text) {
        $text = str_replace('‌', '', $text);
        $regex_view = '((( |^|\n|\t|>|>|\(|\))@)(\w+))';
        preg_match_all('/'.$regex_view.'/', $text, $matches);
        $replacedString = array();
        if(isset($matches[4])){
            foreach($matches[4] as $match){
                $mentionedUser = BOL_UserService::getInstance()->findByUsername($match);
                if($mentionedUser){
                    if (!in_array($mentionedUser, $replacedString)) {
                        $text = str_replace('@'.$match, '@'.$match.':'.$mentionedUser->getId(), $text);
                        $replacedString[] = $mentionedUser;
                    }
                }
            }
        }
        return $text;
    }

    private function populateWebServiceActionData($type = array()){
        $data = array();
        if (in_array("fill_profile", $type)) {
            $data["fill_profile"] = $this->userWebService->fillProfileQuestion();
        }

        if(in_array("edit_profile", $type)){
            $data["edit_profile"] = $this->userWebService->editProfile();
        }

        if(in_array("resend_sms_verification", $type)){
            $data["resend_sms_verification"] = $this->userWebService->resend_sms_verification();
        }

        if(in_array("mark_user_message", $type)){
            $data["mark_user_message"] = $this->mailboxWebService->markUserMessage();
        }

        if(in_array("upload_single_file", $type)){
            $data["upload_single_file"] = $this->uploadSingleFile();
        }

        if(in_array("verify_mobile_code", $type)){
            $data["verify_mobile_code"] = $this->userWebService->verify_mobile_code();
        }

        if(in_array("add_news", $type)){
            $data["add_news"] = $this->newsWebService->addNews();
        }

        if(in_array("remove_news", $type)){
            $data["remove_news"] = $this->newsWebService->removeNews();
        }

        if(in_array("create_group", $type)){
            $data["create_group"] = $this->groupWebService->processCreateGroup();
        }

        if(in_array("edit_group", $type)){
            $data["edit_group"] = $this->groupWebService->processEditGroup();
        }

        if(in_array("join_group", $type)){
            $data["join_group"] = $this->groupWebService->joinGroup();
        }

        if(in_array("join_event", $type)){
            $data["join_event"] = $this->eventWebService->joinEvent();
        }

        if(in_array("remove_video", $type)){
            $data["remove_video"] = $this->videoWebService->removeVideo();
        }

        if(in_array("remove_photo", $type)){
            $data["remove_photo"] = $this->photoWebService->removePhoto();
        }

        if(in_array("terminate_session", $type)){
            $data["terminate_session"] = $this->userWebService->terminateSession();
        }

        if(in_array("terminate_all_session", $type)){
            $data["terminate_all_session"] = $this->userWebService->terminateAllSessions();
        }

        if(in_array("remove_album", $type)){
            $data["remove_album"] = $this->photoWebService->removeAlbum();
        }

        if(in_array("create_video", $type)){
            $data["create_video"] = $this->videoWebService->createVideo();
        }

        if(in_array("create_photo", $type)){
            $data["create_photo"] = $this->photoWebService->createPhoto();
        }

        if(in_array("create_album", $type)){
            $data["create_album"] = $this->photoWebService->createAlbum();
        }

        if(in_array("create_event", $type)){
            $data["create_event"] = $this->eventWebService->processCreateEvent();
        }

        if(in_array("edit_event", $type)){
            $data["edit_event"] = $this->eventWebService->processEditEvent();
        }

        if(in_array("accept_friend", $type)){
            $data["accept_friend"] = $this->friendsWebService->acceptFriendRequest();
        }

        if(in_array("friend_request", $type)){
            $data["friend_request"] = $this->friendsWebService->friendRequest();
        }

        if(in_array("cancel_request", $type)){
            $data["cancel_request"] = $this->friendsWebService->cancelRequest();
        }

        if(in_array("groups_invite_user", $type)){
            $data["groups_invite_user"] = $this->groupWebService->inviteUser();
        }

        if(in_array("leave_group", $type)){
            $data["leave_group"] = $this->groupWebService->leave();
        }

        if(in_array("delete_group", $type)){
            $data["delete_group"] = $this->groupWebService->deleteGroup();
        }

        if(in_array("groups_accept_invite", $type)){
            $data["groups_accept_invite"] = $this->groupWebService->acceptInvite();
        }

        if(in_array("remove_group_user", $type)){
            $data["remove_group_user"] = $this->groupWebService->removeUser();
        }

        if(in_array("add_group_file", $type)){
            $data["add_group_file"] = $this->groupWebService->addFile();
        }

        if(in_array("add_group_manager", $type)){
            $data["add_group_manager"] = $this->groupWebService->addGroupManager();
        }

        if(in_array("remove_group_manager", $type)){
            $data["remove_group_manager"] = $this->groupWebService->removeGroupManager();
        }

        if(in_array("delete_group_file", $type)){
            $data["delete_group_file"] = $this->groupWebService->deleteFile();
        }

        if(in_array("add_event_file", $type)){
            $data["add_event_file"] = $this->eventWebService->addFile();
        }

        if(in_array("delete_event_file", $type)){
            $data["delete_event_file"] = $this->eventWebService->deleteFile();
        }

        if(in_array("groups_cancel_invite", $type)){
            $data["groups_cancel_invite"] = $this->groupWebService->cancelInvite();
        }

        if(in_array("event_invite_user", $type)){
            $data["event_invite_user"] = $this->eventWebService->inviteUser();
        }

        if(in_array("event_accept_invite", $type)){
            $data["event_accept_invite"] = $this->eventWebService->acceptInvite();
        }

        if(in_array("event_cancel_invite", $type)){
            $data["event_cancel_invite"] = $this->eventWebService->cancelInvite();
        }

        if(in_array("event_change_status", $type)){
            $data["event_change_status"] = $this->eventWebService->changeStatus();
        }

        if(in_array("leave_event", $type)){
            $data["leave_event"] = $this->eventWebService->leave();
        }

        if(in_array("seen_notification", $type)){
            $data["seen_notification"] = $this->notificationsWebService->seenNotification();
        }

        if(in_array("login", $type)){
            $data["login"] = $this->userWebService->login();
        }

        if(in_array("logout", $type)){
            $data["logout"] = $this->userWebService->logout();
        }

        if(in_array("remove_friend", $type)){
            $data["remove_friend"] = $this->friendsWebService->removeFriend();
        }

        // should be placed before check_login
        if(in_array("change_password", $type)){
            $data["change_password"] = $this->userWebService->changePassword();
        }

        if(in_array("check_login", $type)){
            $data["check_login"] = $this->userWebService->checkLogin();
        }

        if(in_array("change_avatar", $type)){
            $data["change_avatar"] = $this->userWebService->changeAvatar();
        }

        if(in_array("block_user", $type)){
            $data["block_user"] = $this->userWebService->blockUser();
        }

        if(in_array("follow_user", $type)){
            $data["follow_user"] = $this->userWebService->follow();
        }

        if(in_array("unfollow_user", $type)){
            $data["unfollow_user"] = $this->userWebService->unFollow();
        }

        if(in_array("follow_group", $type)){
            $data["follow_group"] = $this->groupWebService->follow();
        }

        if(in_array("unfollow_group", $type)){
            $data["unfollow_group"] = $this->groupWebService->unFollow();
        }

        if(in_array("posted_data", $type)){
            $data["posted_data"] = $this->getNecessaryPostedData();
        }

        if(in_array("like", $type)){
            $data["like"] = $this->newsfeedWebService->like();
        }

        if(in_array("remove_feed", $type)){
            $data["remove_feed"] = $this->newsfeedWebService->removeAction();
        }

        if(in_array("remove_like", $type)){
            $data["remove_like"] = $this->newsfeedWebService->removeLike();
        }

        if(in_array("edit_post", $type)){
            $data["edit_post"] = $this->newsfeedWebService->editPost();
        }

        if(in_array("send_message", $type)){
            $data["send_message"] = $this->mailboxWebService->sendMessage();
        }

        if(in_array("send_post", $type)){
            $data["send_post"] = $this->newsfeedWebService->sendPost();
        }

        if(in_array("add_comment", $type)){
            $data["add_comment"] = $this->commentWebService->addComment();
        }

        if(in_array("like_comment", $type)){
            $data["like_comment"] = $this->commentWebService->likeComment();
        }

        if(in_array("unlike_comment", $type)){
            $data["unlike_comment"] = $this->commentWebService->unlikeComment();
        }

        if(in_array("remove_comment", $type)){
            $data["remove_comment"] = $this->commentWebService->removeComment();
        }

        if(in_array("change_privacy", $type)){
            $data["change_privacy"] = $this->newsfeedWebService->changePrivacy();
        }

        if(in_array("remove_message", $type)){
            $data["remove_message"] = $this->mailboxWebService->removeMessage();
        }

        if(in_array("clear_messages", $type)){
            $data["clear_messages"] = $this->mailboxWebService->clearMessages();
        }

        if(in_array("edit_message", $type)){
            $data["edit_message"] = $this->mailboxWebService->editMessage();
        }

        if(in_array("add_post_forum", $type)){
            $data["post_forum"] = $this->forumWebService->addPost();
        }

        if(in_array("add_topic_forum", $type)){
            $data["topic_forum"] = $this->forumWebService->addTopic();
        }

        if(in_array("lock_topic", $type)){
            $data["lock_topic"] = $this->forumWebService->lockTopic();
        }

        if(in_array("answer_option", $type)){
            $data["answer_option"] = $this->questionsWebService->addAnswer();
        }

        if(in_array("add_question_option", $type)){
            $data["add_question_option"] = $this->questionsWebService->addQuestionOption();
        }

        if(in_array("remove_question_option", $type)){
            $data["remove_question_option"] = $this->questionsWebService->removeQuestionOption();
        }

        if(in_array("unlock_topic", $type)){
            $data["unlock_topic"] = $this->forumWebService->unlockTopic();
        }

        if(in_array("delete_topic", $type)){
            $data["delete_topic"] = $this->forumWebService->deleteTopic();
        }

        if(in_array("delete_post_forum", $type)){
            $data["delete_post_forum"] = $this->forumWebService->deletePost();
        }

        if(in_array("save_user_privacy", $type)){
            $data["user_privacy"] = $this->privacyWebService->savePrivacy();
        }

        if(in_array("send_contact_us", $type)) {
            $data["contact_us"] = $this->contactusWebService->processSendContactUsMessage();
        }

        if(in_array("add_blog", $type)){
            $data["add_blog"] = $this->blogsWebService->addBlog();
        }

        if(in_array("remove_blog", $type)){
            $data["remove_blog"] = $this->blogsWebService->removeBlog();
        }

        if(in_array("forgot_password", $type)) {
            $data["forgot_password"] = $this->userWebService->processForgotPassword();
        }

        if(in_array("flagItem", $type)){
            $data["flagItem"] = $this->flagWebService->flagItem();
        }

        if(in_array("valid_extensions", $type)){
            $data["valid_extensions"] = $this->getValidExtensions();
        }

        if(in_array("files_info", $type)){
            $data["files_info"] = $this->getFilesInfo();
        }

        if(in_array("invite_user", $type)){
            $data["invite_user"] = $this->userWebService->inviteUser();
        }

        if(in_array("join", $type)){
            $data["join"] = $this->userWebService->joinAction();
        }

        if(in_array("forward", $type)){
            $data["forward"] = $this->newsfeedWebService->forwardAction();
        }

        return $data;
    }
}