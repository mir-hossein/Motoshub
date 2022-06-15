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

require_once OW_DIR_SYSTEM_PLUGIN . 'base' . DS . 'controllers' . DS . 'edit.php';

class IISMOBILESUPPORT_BOL_WebServiceUser
{
    use BASE_CLASS_UploadTmpAvatarTrait;

    private static $classInstance;

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
    }

    public function login(){
        $adminApproveUser = false;

        if(!isset($_POST['username']) || !isset($_POST['password'])){
            return array('valid' => false, 'message' => 'input_error', 'admin_check' => $adminApproveUser);
        }

        $username = $_POST['username'];
        $username = trim($username);
        $password = $_POST['password'];

        $iisblockingipEvent = OW::getEventManager()->trigger(new OW_Event('iismobilesupport.on.login.attempt'));
        if(isset($iisblockingipEvent->getData()['lock']) && $iisblockingipEvent->getData()['lock']){
            return array("valid" => false, "message" => "authorization_error", 'admin_check' => $adminApproveUser);
        }

        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iispasswordchangeinterval', true)) {
            $user = BOL_UserService::getInstance()->findByUsername($username);
            if ($user != null) {
                $passwordValidation = IISPASSWORDCHANGEINTERVAL_BOL_PasswordValidationDao::getInstance()->getCurrentUser($user->getId());
                if ($passwordValidation != null && !$passwordValidation->valid) {
                    if (IISPASSWORDCHANGEINTERVAL_BOL_Service::getInstance()->isTokenExpired($passwordValidation->tokenTime)) {
                        IISPASSWORDCHANGEINTERVAL_BOL_Service::getInstance()->resendLinkToUserByUserId(true, $user->getId());
                    }
                    return array(
                        "valid" => false,
                        "message" => 'password_expired',
                        'admin_check' => $adminApproveUser);
                }
            }
        }

        $result = OW::getUser()->authenticate(new BASE_CLASS_StandardAuth($username, $password));

        if ( $result->isValid() )
        {
            $userId = OW::getUser()->getId();
            $day = IISMOBILESUPPORT_BOL_Service::getInstance()->COOKIE_SAVE_DAY;
            $loginCookie = BOL_UserService::getInstance()->saveLoginCookie($userId, (time() + 86400 * $day));
            $adminApproveUser = IISMOBILESUPPORT_BOL_Service::getInstance()->currentUserApproved();
            $fcmToken = $this->getFcmTokenFromPost();
            if($fcmToken != null){
                $this->addNativeDevice($userId, $_POST['fcmToken'], $loginCookie->getCookie());
            }
            $_POST['access_token'] = $loginCookie->getCookie();
            if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisuserlogin', true)){
                IISUSERLOGIN_BOL_Service::getInstance()->updateActiveDetails();
            }
            $securityData = $this->getUserProfileSecurityData(OW::getUser()->getUserObject());
            OW::getEventManager()->trigger(new OW_Event('iismobilesupport.on.login.success'));
            return array("valid" => true, "cookies" => array('ow_login' => $loginCookie->getCookie()), "message" => "success", 'admin_check' => $adminApproveUser, 'security' => $securityData);
        } else{
            OW::getEventManager()->trigger(new OW_Event('iismobilesupport.on.login.failed'));
            return array("valid" => false, "message" => "authorization_error", 'admin_check' => $adminApproveUser);
        }
    }

    public function getFcmTokenFromPost(){
        if(isset($_POST['fcmToken']) &&
            !empty($_POST['fcmToken']) &&
            $_POST['fcmToken'] != null &&
            $_POST['fcmToken'] != "null"){
            return $_POST['fcmToken'];
        }

        return null;
    }

    public function logout(){
        $this->logoutProcess();
        return array("valid" => true, "message" => "logout_successfully");
    }

    public function logoutProcess(){
        $service = IISMOBILESUPPORT_BOL_Service::getInstance();
        $fcmToken = $this->getFcmTokenFromPost();
        if ($fcmToken != null) {
            $service->deleteDevice($fcmToken);
        }
        // Todo: do not need to logout user
        OW_Auth::getInstance()->getAuthenticator()->logout();
    }

    public function addNativeDevice($userId, $token, $cookie){

        if($token == null || $cookie == null || $userId == null){
            return;
        }
        $service = IISMOBILESUPPORT_BOL_Service::getInstance();
        $type = IISMOBILESUPPORT_BOL_Service::getInstance()->nativeFcmKey;
        $addDevice = false;
        $device = $service->findDevice($token);
        if($device){
            if($device->userId !=  $userId || $device->cookie != $cookie){
                $service->deleteDevice($token);
                $addDevice = true;
            }
        }else {
            $addDevice = true;
        }
        if($addDevice){
            $service->saveDevice($userId, $token, $type, $cookie);
        }
    }

    public function blockUser() {
        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        if(!isset($_POST['userId'])){
            return array('valid' => false, 'message' => 'input_error');
        }
        $userId = $_POST['userId'];
        $blocked = BOL_UserService::getInstance()->isBlocked($userId, OW::getUser()->getId());
        if (!$blocked) {
            BOL_UserService::getInstance()->block($userId);
        } else {
            BOL_UserService::getInstance()->unblock($userId);
        }
        return array('valid' => true, 'isBlocked' => !$blocked, 'message' => 'changed');
    }

    public function changeAvatar(){
        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $userId = OW::getUser()->getId();
        $image = null;

        if (isset($_FILES['file'])){
            if ( (int) $_FILES['file']['error'] !== 0 ||
                !is_uploaded_file($_FILES['file']['tmp_name']) ||
                !UTIL_File::validateImage($_FILES['file']['name']) ){
                return array('valid' => false, 'message' => 'authorization_error');
            }
            else{
                $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
                if ($isFileClean) {
                    $image = $_FILES['file'];
                } else {
                    return array('valid' => false, 'message' => 'virus_detected');
                }
            }
        } else {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $extInfo = pathinfo($image['name']);
        if(isset($extInfo['extension']) && strtolower($extInfo['extension']) == 'png') {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $key = BOL_AvatarService::getInstance()->getAvatarChangeSessionKey();
        if (!isset($key) || $key == null){
            BOL_AvatarService::getInstance()->setAvatarChangeSessionKey();
        }

        $uploadResult = $this->uploadTmpAvatar($image);
        if (!isset($uploadResult['result']) || !$uploadResult['result'] || !isset($uploadResult['url']) || !isset($uploadResult['key'])) {
            return array('valid' => false, 'message' => 'authorization_error', 'result' => $uploadResult);
        }

        $event = new OW_Event('base.before_avatar_change', array(
            'userId' => $userId,
            'avatarId' => null,
            'upload' => true,
            'crop' => false,
            'isModerable' => true
        ));
        OW::getEventManager()->trigger($event);

        $avatarSet = BOL_AvatarService::getInstance()->setUserAvatar($userId, $uploadResult['url'], array('isModerable' => true, 'trackAction' => true ));

        if ( $avatarSet )
        {
            $avatar = BOL_AvatarService::getInstance()->findByUserId($userId);

            if ( $avatar )
            {
                $event = new OW_Event('base.after_avatar_change', array(
                    'userId' => $userId,
                    'avatarId' => $avatar->id,
                    'upload' => true,
                    'crop' => false
                ));
                OW::getEventManager()->trigger($event);
            }

            BOL_AvatarService::getInstance()->deleteUserTempAvatar($uploadResult['key']);
        } else {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        return array('valid' => true, 'message' => 'changed', 'avatarUrl' => BOL_AvatarService::getInstance()->getAvatarUrlByAvatarDto($avatar));
    }

    public function changePassword(){
        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $userId = OW::getUser()->getId();

        if(!isset($_POST['oldPassword']) || !isset($_POST['newPassword'])){
            return array('valid' => false, 'message' => 'input_error');
        }
        $oldPassword = UTIL_HtmlTag::stripJs($_POST['oldPassword']);
        $newPassword = UTIL_HtmlTag::stripJs($_POST['newPassword']);
        $validOldPassword = BOL_UserService::getInstance()->isValidPassword( $userId, $oldPassword );
        if (!$validOldPassword) {
            return array('valid' => false, 'message' => 'authorization_error');
        }
        BOL_UserService::getInstance()->updatePassword( $userId, $newPassword );
        return array('valid' => true, 'message' => 'changed');
    }

    public function checkLogin(){
        $valid = false;
        $message = 'authorization_error';
        $forcedToChangePassword = false;
        $service = IISMOBILESUPPORT_BOL_Service::getInstance();
        $versionInfo = $service->getAppInformation(IISMOBILESUPPORT_BOL_Service::getInstance()->nativeFcmKey, '');
        $userInfo = null;
        $adminApproveUser = IISMOBILESUPPORT_BOL_Service::getInstance()->currentUserApproved();
        $fillQuestions = array();
        if(OW::getUser()->isAuthenticated()){
            $questions = BOL_QuestionService::getInstance()->getEmptyRequiredQuestionsList(OW::getUser()->getId());
            if (!empty($questions)) {
                $fillQuestions = $this->prepareQuestions($questions);
            }
            $userInfo = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById(OW::getUser()->getId());
            $valid = true;
            $message = 'authenticate_before';

            if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iispasswordchangeinterval', true)) {
                $passwordValidation = IISPASSWORDCHANGEINTERVAL_BOL_Service::getInstance()->getCurrentUser();
                $forcedToChangePassword = IISPASSWORDCHANGEINTERVAL_BOL_Service::getInstance()->isChangable($passwordValidation);
                if ($passwordValidation != null && !$passwordValidation->valid) {
                    $valid = false;
                    $message = 'password_expired';
                }
            }
        }

        return array('valid' => $valid,
            'message' => $message,
            'admin_check' => $adminApproveUser,
            'fillQuestions' => $fillQuestions,
            'user' => $userInfo,
            'version' => $versionInfo,
            'password_change' => $forcedToChangePassword,
        );
    }

    public function getUserInformation($includeProfileInformation = false, $detailInfo = false){
        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        $userId = null;
        if(isset($_GET['userId'])){
            $userId = $_GET['userId'];
        }else if(isset($_GET['username'])){
            $user = BOL_UserService::getInstance()->findByUsername($_GET['username']);
            if($user != null){
                $userId = $user->getId();
            }
        }else if(OW::getUser()->isAuthenticated()){
            $userId = OW::getUser()->getId();
        }

        if($userId == null){
            return array();
        }

        return $this->getUserInformationById($userId, $includeProfileInformation, $detailInfo);
    }

    public function getUserInformationById($userId, $includeProfileInformation = false, $detailInfo = false){
        if($userId == null){
            return array();
        }

        $user = BOL_UserService::getInstance()->findUserById($userId);
        $data = $this->getUserInformationByObject($user, $includeProfileInformation, $detailInfo);

        return $data;
    }

    public function getUsersInfoByIdList($userIds) {
        $users = array();
        $usersObject = BOL_UserService::getInstance()->findUserListByIdList($userIds);
        $usernames = BOL_UserService::getInstance()->getDisplayNamesForList($userIds);
        $avatars = BOL_AvatarService::getInstance()->getAvatarsUrlList($userIds);
        foreach ($usersObject as $userObject) {
            $username = null;
            if (isset($usernames[$userObject->id])) {
                $username = $usernames[$userObject->id];
            }

            $avatarUrl = null;
            if (isset($avatars[$userObject->id])) {
                $avatarUrl = $avatars[$userObject->id];
            }
            $userData = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->populateUserData($userObject, $avatarUrl, $username);
            $users[] = $userData;
        }
        return $users;
    }

    public function getUserInformationByObject($user, $includeProfileInformation = false, $detailInfo = false){
        if($user == null){
            return array();
        }
        $data = $this->populateUserData($user, null, null, $detailInfo);

        $useResponseTypes = false;
        $responseTypes = array();
        if (isset($_POST['data_types'])) {
            $useResponseTypes = true;
            $responseTypes = $_POST['data_types'];
            $responseTypes = explode(",", $responseTypes);
        }

        $showProfileQuestions = !$useResponseTypes || in_array('profile_info', $responseTypes) ? true : false;
        $showFriends = !$useResponseTypes || in_array('friends', $responseTypes) ? true : false;
        $showPosts = !$useResponseTypes || in_array('posts', $responseTypes) ? true : false;
        $showVideos = !$useResponseTypes || in_array('videos', $responseTypes) ? true : false;
        $showAlbums = !$useResponseTypes || in_array('albums', $responseTypes) ? true : false;
        $showBlogs = !$useResponseTypes || in_array('blogs', $responseTypes) ? true : false;
        $showEvents = !$useResponseTypes || in_array('events', $responseTypes) ? true : false;
        $showGroups = !$useResponseTypes || in_array('groups', $responseTypes) ? true : false;
        $showSessions = !$useResponseTypes || in_array('sessions', $responseTypes) ? true : false;
        $showSuggests = !$useResponseTypes || in_array('suggests', $responseTypes) ? true : false;
        $showMutuals = !$useResponseTypes || in_array('mutuals', $responseTypes) ? true : false;

        if($includeProfileInformation){
            if ($showProfileQuestions) {
                $data['profileInformation'] = $this->getUserProfileInformation($user->id);
            }
            if ($showFriends) {
                $data['friends'] = IISMOBILESUPPORT_BOL_WebServiceFriends::getInstance()->getUserFriends($user->id);
            }
            if ($showPosts) {
                $data['posts'] = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->userProfilePosts($user->id);
            }
            if ($showGroups) {
                $data['groups'] = IISMOBILESUPPORT_BOL_WebServiceGroup::getInstance()->getGroupsByUserId($user->id);
            }
            if ($showEvents) {
                $data['events'] = IISMOBILESUPPORT_BOL_WebServiceEvent::getInstance()->getEventsByUserId($user->id);
            }
            if ($showVideos) {
                $data['videos'] = IISMOBILESUPPORT_BOL_WebServiceVideo::getInstance()->getUserVideosById($user->id);
            }
            if ($showAlbums) {
                $data['albums'] = IISMOBILESUPPORT_BOL_WebServicePhoto::getInstance()->getUserAlbumsByUserId($user->id);
            }
            if ($showBlogs) {
                $data['blogs'] = IISMOBILESUPPORT_BOL_WebServiceBlogs::getInstance()->getUserBlogsWithId($user->id);
            }
            $data['friends_count'] = IISMOBILESUPPORT_BOL_WebServiceFriends::getInstance()->getUserFriendsCount($user->id);
            $data['user_status'] = $this::getInstance()->getUserStatus($user->id);

            if(OW::getUser()->isAuthenticated() && OW::getUser()->getId() == $user->id){
                if ($showSessions) {
                    $data['session'] = $this->getUserSessionInformation($user->id);
                }
                $data['requests_count'] = $this->getRequestsCount($user->id);
                if ($showSuggests) {
                    $data['suggests'] = IISMOBILESUPPORT_BOL_WebServiceSuggest::getInstance()->getUserSuggest($user->id);
                }
            }else if(OW::getUser()->isAuthenticated() && OW::getUser()->getId() != $user->id){
                if ($showMutuals) {
                    $data['mutual'] = IISMOBILESUPPORT_BOL_WebServiceMutual::getInstance()->getUserMutual(OW::getUser()->getId(), $user->id);
                }
                $data['isBlocked'] = BOL_UserService::getInstance()->isBlocked($user->id, OW::getUser()->getId());
                $data['online'] = $this->isUserOnline($user->id);
                $data['follower'] = $this->isUserFollower($user->id);
                $data['followable'] = $this->isUserFollowable($user->id,  $data['isBlocked'], $data['follower']);
            }

            $data['security'] = $this->getUserProfileSecurityData($user);
        }
        return $data;
    }

    public function isUserFollowable($userId, $blocked = null, $follower = null) {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return false;
        }
        if(!OW::getUser()->isAuthenticated()) {
            return false;
        }
        if ($blocked == null) {
            $blocked = BOL_UserService::getInstance()->isBlocked($userId, OW::getUser()->getId());
        }
        if ($blocked) {
            return false;
        }
        return true;
    }

    public function follow() {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $userId = null;

        if(isset($_GET['userId'])){
            $userId = (int) $_GET['userId'];
        }
        if ($userId == null) {
            return array('valid' => false, 'message' => 'input_error');
        }
        if(!$this->isUserFollowable($userId)){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        NEWSFEED_BOL_Service::getInstance()->addFollow(OW::getUser()->getId(), 'user', $userId);
        return array('valid' => true, 'follow' => true, 'userId' => $userId);
    }

    public function unFollow() {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $userId = null;

        if(isset($_GET['userId'])){
            $userId = (int) $_GET['userId'];
        }
        if ($userId == null) {
            return array('valid' => false, 'message' => 'input_error');
        }
        if(!$this->isUserFollowable($userId)){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        NEWSFEED_BOL_Service::getInstance()->removeFollow(OW::getUser()->getId(), 'user', $userId);
        return array('valid' => true, 'follow' => false, 'userId' => $userId);
    }

    public function isUserFollower($userId) {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)){
            return false;
        }
        if (!OW::getUser()->isAuthenticated()) {
            return false;
        }
        $currentUserId = OW::getUser()->getId();
        return NEWSFEED_BOL_Service::getInstance()->isFollow($currentUserId, 'user', $userId);
    }

    public function isUserOnline($userId){
        $onlineObj = BOL_UserService::getInstance()->findOnlineUserById($userId);
        if ($onlineObj != null) {
            return true;
        }
        return false;
    }

    public function searchFriends(){
        $userId = null;
        if(isset($_GET['userId']) && is_numeric($_GET['userId'])){
            $userId = $_GET['userId'];
        }

        if($userId == null && OW::getUser()->isAuthenticated()){
            $userId = OW::getUser()->getId();
        }

        if($userId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $canSeeFriends = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($userId, 'friends_view', 'friends');
        if(!$canSeeFriends){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $search = '';
        if(isset($_GET['search'])){
            $search = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($_GET['search'], true, true);
        }

        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        $first = 0;
        if(isset($_GET['first'])){
            $first = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($_GET['first'], true, true);
            $first = (int) $first;
        }

        $param = array(
            'search' => $search,
            'userId' => $userId,
            'count' => $count,
            'first' => $first
        );

        $event = OW::getEventManager()->trigger(new OW_Event('plugin.friends.get_friend_list_by_display_name', $param));
        $friendsIds = $event->getData();
        $users = array();
        if(!empty($friendsIds)) {
            $usersObject = BOL_UserService::getInstance()->findUserListByIdList($friendsIds);
            $usernames = BOL_UserService::getInstance()->getDisplayNamesForList($friendsIds);
            $avatars = BOL_AvatarService::getInstance()->getAvatarsUrlList($friendsIds, 2);
            foreach ($usersObject as $userObject) {
                $username = null;
                if (isset($usernames[$userObject->id])) {
                    $username = $usernames[$userObject->id];
                }

                $avatarUrl = null;
                if (isset($avatars[$userObject->id])) {
                    $avatarUrl = $avatars[$userObject->id];
                }
                $users[] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->populateUserData($userObject, $avatarUrl, $username, false, true);
            }
        }

        return $users;
    }

    public function populateUserData($user, $avatarUrl = null, $displayName = null, $detailInfo = false, $checkOnline = false){
        $data = array();
        if($avatarUrl == null){
            $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($user->id, 2);
            $avatarUrl = empty($avatarUrl) ? BOL_AvatarService::getInstance()->getDefaultAvatarUrl(2) : $avatarUrl;
        }
        if($displayName == null){
            $displayName = BOL_UserService::getInstance()->getDisplayName($user->id);
        }

        $data['avatarUrl'] = $avatarUrl;
        $data['email'] = $user->getEmail();
        $data['id'] = (int) $user->id;
        $data['username'] = $user->getUsername();
        $data['name'] = $displayName;
        if($detailInfo){
            $data['profileUrl'] = OW::getRouter()->urlForRoute('base_user_profile', array('username' => $user->getUsername()));
        }
        if($checkOnline){
            $data['online'] = $this->isUserOnline($user->id);
        }
        return $data;
    }

    public function getRequests(){
        if(!OW::getUser()->isAuthenticated()){
            return array();
        }
        $invitation = array();
        $invitation['items'] = $this->prepareRequests(OW::getUser()->getId());
        return $invitation;
    }

    public function getUserStatus($userId) {
        $settings = BOL_ComponentEntityService::getInstance()->findSettingList('profile-BASE_CMP_AboutMeWidget', $userId, array('content'));
        $content = '';
        if (isset($settings)) {
            $content = empty($settings['content']) ? '' : $settings['content'];
        }
        return $content;
    }

    public function getRequestsCount($userId){
        $listCount = 0;
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('groups', true);
        if($pluginActive){
            $groupSize = GROUPS_BOL_Service::getInstance()->findInvitedGroupsCount($userId);
            $listCount += (int) $groupSize;
        }

        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);
        if($pluginActive){
            $eventSize = EVENT_BOL_EventService::getInstance()->findUserInvitedEventsCount($userId);
            $listCount += (int) $eventSize;
        }

        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('friends', true);
        if($pluginActive){
            $listCount += sizeof(FRIENDS_BOL_Service::getInstance()->findRequestList($userId, time(), 0, 100));
        }

        return $listCount;
    }

    public function prepareRequests($userId){
        $data = array();

        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        $generalService = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance();
        $friendsInvitation = FRIENDS_BOL_Service::getInstance()->findRequestList($userId, time(), $first, $count);
        foreach ($friendsInvitation as $friendInvitation){
            $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($friendInvitation->userId);
            $avatarUrl = empty($avatarUrl) ? BOL_AvatarService::getInstance()->getDefaultAvatarUrl() : $avatarUrl;
            $data[] = array(
                'title' => $generalService->stripString(BOL_UserService::getInstance()->getDisplayName($friendInvitation->userId)),
                'description' => '',
                'image' => $avatarUrl,
                'type' => 'friend',
                'invitation' => true,
                'id' =>  (int) $friendInvitation->userId,
            );
        }

        $groupsInvitation = GROUPS_BOL_Service::getInstance()->findInvitedGroups($userId, $first, $count);
        foreach ($groupsInvitation as $groupInvitation){
            $data[] = array(
                'title' => $generalService->stripString($groupInvitation->title),
                'description' => $generalService->stripString($groupInvitation->description),
                'image' => GROUPS_BOL_Service::getInstance()->getGroupImageUrl($groupInvitation),
                'type' => 'group',
                'invitation' => true,
                'id' =>  (int) $groupInvitation->id,
            );
        }

        $page = null;
        if(isset($_GET['page'])){
            $page = $_GET['page'];
        }

        if($page == null && $first != null){
            $page = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageNumber($first);
        }

        if($page == null){
            $page= 1;
        }

        $eventInvitations = EVENT_BOL_EventService::getInstance()->findUserInvitedEvents($userId, $page, null);
        foreach ($eventInvitations as $eventInvitation){
            $data[] = array(
                'title' => $generalService->stripString($eventInvitation->title),
                'description' => $generalService->stripString($eventInvitation->description),
                'image' => EVENT_BOL_EventService::getInstance()->generateImageUrl($eventInvitation->image),
                'type' => 'event',
                'invitation' => true,
                'id' =>  (int) $eventInvitation->id,
            );
        }

        return $data;
    }

    public function getUserSessionInformation($userId){
        $sessions = array();
        $loginDetails = array();
        $securityPluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisuserlogin', true);
        if($securityPluginActive){
            $details = IISUSERLOGIN_BOL_Service::getInstance()->getUserLoginDetails($userId);
            if($details != null) {
                foreach ($details as $detail) {
                    $loginDetails[] = array(
                        'time' => $detail->time,
                        'browser' => $detail->browser,
                        'ip' => $detail->ip,
                        'id' => (int) $detail->id,
                    );
                }
            }
            $first = 0;
            $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
            if(isset($_GET['first'])){
                $first = (int) $_GET['first'];
            }
            $page = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageNumber($first);
            $accessToken = null;
            if(isset($_POST['access_token'])){
                $accessToken = $_POST['access_token'];
            }
            if($accessToken == null){
                return array('valid' => false, 'message' => 'authorization_error');
            }
            $sessionDetails = IISUSERLOGIN_BOL_ActiveDetailsDao::getInstance()->getUserActiveDetailsWithoutEmptyLoginCookie($userId, $page, $count);
            $currentSession = IISUSERLOGIN_BOL_ActiveDetailsDao::getInstance()->getItemByLoginCookie($accessToken);
            if($currentSession == null || $currentSession->userId != OW::getUser()->getId()){
                return array('valid' => false, 'message' => 'authorization_error');
            }

            if($sessionDetails != null) {
                foreach ($sessionDetails as $sessionDetail) {
                    $id = (int) $sessionDetail->id;
                    $sessions[] = array(
                        'time' => $sessionDetail->time,
                        'browser' => $sessionDetail->browser,
                        'ip' => $sessionDetail->ip,
                        'current' => $currentSession->sessionId == $sessionDetail->sessionId,
                        'id' => (int) $id
                    );
                }
            }
        }

        return array('logins' => $loginDetails, 'sessions' => $sessions);
    }

    public function terminateSession(){
        $securityPluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisuserlogin', true);
        if(!$securityPluginActive){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $sessionId = null;
        if(isset($_POST['id'])){
            $sessionId = $_POST['id'];
        }

        if($sessionId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $accessToken = null;
        if(isset($_POST['access_token'])){
            $accessToken = $_POST['access_token'];
        }
        if($accessToken == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $service = IISUSERLOGIN_BOL_Service::getInstance();
        $session = IISUSERLOGIN_BOL_ActiveDetailsDao::getInstance()->findById($sessionId);
        if($session == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $loginCookie = $session->loginCookie;

        if($loginCookie == $accessToken){
            return array('valid' => false, 'message' => 'current_session');
        }

        $userId = OW::getUser()->getId();
        if($session->userId != $userId){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $loginCookies = array();
        if(!isset($session->loginCookie)){
            return array('valid' => false, 'message' => 'authorization_error');
        }else{
            $loginCookies[] = $session->loginCookie;
        }
        if(!empty($loginCookies)){
            BOL_LoginCookieDao::getInstance()->deleteByCookies($loginCookies);
        }
        $result = $service->terminateDevice($session->id, $userId);
        if($result){
            return array('valid' => true, 'id' => (int) $session->id);
        }else{
            return array('valid' => false, 'message' => 'authorization_error');
        }
    }

    public function terminateAllSessions(){
        $securityPluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisuserlogin', true);
        if(!$securityPluginActive){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $requestedId = null;
        if(isset($_POST['userId'])){
            $requestedId = $_POST['userId'];
        }

        $accessToken = $this->getAccessTokenFromPost();
        if($accessToken == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $currentSession = $this->getCurrentSessionUsingAccessToken($accessToken);
        if($currentSession == null || $currentSession->userId != OW::getUser()->getId()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if($requestedId == null || $requestedId != OW::getUser()->getId()){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $this->deleteExpiredLoginCookies($requestedId, $currentSession->sessionId);

        $result = IISUSERLOGIN_BOL_ActiveDetailsDao::getInstance()->deleteAllOtherDevices($requestedId, $currentSession->sessionId);
        if($result){
            return array('valid' => true);
        }else{
            return array('valid' => true, 'message' => 'there_is_no_session');
        }
    }

    public function deleteExpiredLoginCookies($requestedId, $currentSessionId){
        $allSessions = IISUSERLOGIN_BOL_ActiveDetailsDao::getInstance()->getAllOtherDevices($requestedId, $currentSessionId);
        $loginCookies = array();
        foreach ($allSessions as $allSession){
            if(!empty($allSession->loginCookie)){
                $loginCookies[] = $allSession->loginCookie;
            }
        }
        if(!empty($loginCookies)){
            BOL_LoginCookieDao::getInstance()->deleteByCookies($loginCookies);
        }
    }

    public function getCurrentSessionUsingAccessToken($accessToken){
        return IISUSERLOGIN_BOL_ActiveDetailsDao::getInstance()->getItemByLoginCookie($accessToken);
    }

    public function getAccessTokenFromPost(){
        $accessToken = null;
        if(isset($_POST['access_token'])){
            $accessToken = $_POST['access_token'];
        }

        return $accessToken;
    }

    public function getUserProfileSecurityData($user){
        if($user == null){
            return null;
        }
        $securityData = array();
        $securityData['view'] = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->canUserSeeFeed(OW::getUser()->getId(), $user->id);
        $securityData['view_friends'] = $securityData['view'] && IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($user->id, 'friends_view', 'friends');
        $securityData['view_videos'] = $securityData['view'] && IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($user->id, 'video_view_video', 'video');
        $securityData['view_events'] = $securityData['view'] && IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($user->id, 'event_view_attend_events', 'event');
        $securityData['view_albums'] = $securityData['view'] && IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($user->id, 'photo_view_album', 'photo');
        $securityData['view_groups'] = $securityData['view'] && IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($user->id, 'view_my_groups', 'groups');
        $securityData['view_blogs'] = $securityData['view'] && IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($user->id, 'blogs_view_blog_posts', 'blogs');
        $securityData['send_post'] = $securityData['view'] && IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->canUserSendPostOnFeed(OW::getUser()->getId(), $user->id);
        $securityData['send_post_privacy'] = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->getDefaultPrivacyOfUsersPosts($user);
        if ($user->id != OW::getUser()->getId()) {
            $securityData['friendship'] = IISMOBILESUPPORT_BOL_WebServiceFriends::getInstance()->getFriendshipInformation(OW::getUser()->getId(), $user->id);
        }
        $securityData['add_group'] = IISMOBILESUPPORT_BOL_WebServiceGroup::getInstance()->canUserCreateGroup();
        $securityData['add_event'] = IISMOBILESUPPORT_BOL_WebServiceEvent::getInstance()->canUserCreateEvent();
        $securityData['add_photo'] = IISMOBILESUPPORT_BOL_WebServicePhoto::getInstance()->canUserCreatePhoto();
        $securityData['add_video'] = IISMOBILESUPPORT_BOL_WebServiceVideo::getInstance()->canUserCreateVideo();
        $securityData['add_blog'] = IISMOBILESUPPORT_BOL_WebServiceBlogs::getInstance()->canUserCreateBlog();
        $securityData['add_news'] = IISMOBILESUPPORT_BOL_WebServiceNews::getInstance()->canUserManageNews();
        return $securityData;
    }

    public function getUserProfileInformation($userId){
        if(!IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->canUserSeeFeed(OW::getUser()->getId(), $userId)){
            return array();
        }
        $validQuestions = array();
        $questionsSectionsFetch = BOL_UserService::getInstance()->getUserViewQuestions($userId);
        $questionsFetch = array();
        $questionsData = array();
        foreach($questionsSectionsFetch['questions'] as $questionsSectionFetch){
            foreach($questionsSectionFetch as $questionSectionFetch){
                $questionsFetch[] = $questionSectionFetch;
            }
        }
        $securityPluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissecurityessentials', true);
        if((OW::getUser()->isAuthenticated() && $userId == OW::getUser()->getId()) || !$securityPluginActive){
            $validQuestions = $questionsFetch;
        }else{
            $service = IISSECURITYESSENTIALS_BOL_Service::getInstance();
            foreach ($questionsFetch as $question) {
                $privacy = $service->getQuestionPrivacy($userId, $question['id']);
                if ($privacy == null) {
                    $validQuestions[] = $question;
                } else if ($service->checkPrivacyOfObject($privacy, $userId, null, false)) {
                    $validQuestions[] = $question;
                }
            }
        }

        foreach ($validQuestions as $question){
            $label = OW::getLanguage()->text('base', 'questions_question_' . $question['name'] . '_label');
            $value = "";
            if(isset($questionsSectionsFetch['data'][$userId][$question['name']])){
                $value = $questionsSectionsFetch['data'][$userId][$question['name']];
                if(is_array($value)){
                    foreach ($value as $item){
                        $value = $item;
                    }
                }
            }
            $value = strip_tags($value);
            $questionsData[] = $this->prepareQuestion($question, $value, array());
        }

        return $questionsData;
    }

    public function getYearFromString($year) {
        $yearChangedEvent =  OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_DEFAULT_DATE_VALUE_SET, array('yearRange' => true, 'year' => $year)));
        if($yearChangedEvent->getData() && isset($yearChangedEvent->getData()['year']) && isset($yearChangedEvent->getData()['persian_year'])){
            $year = $yearChangedEvent->getData()['persian_year'];
        }
        return $year;
    }

    /***
     * @param $question
     * @param null $userValue
     * @param array $availableValues
     * @return array
     */
    public function prepareQuestion($question, $userValue = null, $availableValues = array()) {
        $label = OW::getLanguage()->text('base', 'questions_question_' . $question['name'] . '_label');
        $fromDateYear = null;
        $ToDateYear = null;
        if (isset($question['type']) && $question['type'] == 'datetime') {
            $fromDateYear = $this->getYearFromString(1930);
            $ToDateYear = $this->getYearFromString(2001);
        }
        if (isset($question['custom'])) {
            $customData = $question['custom'];
            $customData = json_decode($customData);
            if (isset($customData->year_range)) {
                if (isset($customData->year_range->from)) {
                    $fromDateYear = $this->getYearFromString($customData->year_range->from);
                }
                if (isset($customData->year_range->to)) {
                    $ToDateYear = $this->getYearFromString($customData->year_range->to);
                }
            }
        }
        return array(
            'name' => $question['name'],
            'type' => $question['type'],
            'label' => $label,
            'presentation' => $question['presentation'],
            'values' => $availableValues,
            'user_value' => $userValue,
            'value' => $userValue,
            'fromDateYear' => $fromDateYear,
            'toDateYear' => $ToDateYear
        );
    }

    public function useOptionalFields() {
        // disable only required fields and get all fields (optional + required)
        if (isset($_POST['optional_fields']) && $_POST['optional_fields'] == 'true') {
            return true;
        }
        return false;
    }

    /***
     * @param $questionsFetch
     * @param array $questionsData
     * @return array
     */
    public function prepareQuestions($questionsFetch, $questionsData = array()) {
        $questions = array();
        $questionValues = array();
        $questionNames = array();
        $questionsUnique = array();
        foreach ($questionsFetch as $questionFetch){
            if(!in_array($questionFetch['name'], $questionNames)){
                $questionNames[] = $questionFetch['name'];
                $questionsUnique[] = $questionFetch;
            }
        }
        $questionValuesFetch = BOL_QuestionService::getInstance()->findQuestionsValuesByQuestionNameList($questionNames);
        foreach ($questionValuesFetch as $key => $questionValueFetch){
            $questionValue = array();
            $values = $questionValueFetch['values'];
            foreach ($values as $value){
                $questionOptionValue['value'] = $value->value;
                $questionOptionValue['label'] = BOL_QuestionService::getInstance()->getQuestionValueLang($key, $value->value);
                $questionValue[] = $questionOptionValue;
            }
            $questionValues[$key] = $questionValue;
        }
        foreach ($questionsUnique as $questionUnique){
            if(OW::getUser()->isAuthenticated() && $questionUnique['name'] == 'field_mobile' && IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissms', true)){
                $currentUserId = OW::getUser()->getId();
                $mobileService = IISSMS_BOL_Service::getInstance();
                $token = $mobileService->getUserToken($currentUserId);
                if($token==null){
                    $mobileService->renewUserToken($currentUserId, $mobileService->getUserMobile($currentUserId));
                }
            }
            if($questionUnique['required'] == 1 || $this->useOptionalFields()){
                $values = array();
                if(isset($questionValues[$questionUnique['name']])){
                    $values = $questionValues[$questionUnique['name']];
                } else if ($questionUnique['type'] === 'boolean') {
                    $values[] = array(
                        'label' => OW::getLanguage()->text('admin', 'permissions_index_yes'),
                        'value' => '1'
                    );
                }

                $user_value = null;
                if (!empty($questionsData) && isset($questionsData[$questionUnique['name']])) {
                    $user_value = $questionsData[$questionUnique['name']];
                }
                $questions[] = $this->prepareQuestion($questionUnique, $user_value, $values);
            }
        }
        return $questions;
    }

    public function getJoinFields(){
        $questionsFetch = array();
        $accountTypes = BOL_QuestionService::getInstance()->findAllAccountTypes();
        foreach ($accountTypes as $accountType){
            $questionsFetch = array_merge($questionsFetch, BOL_QuestionService::getInstance()->findSignUpQuestionsForAccountType($accountType->name));
        }
        $questionsFetch = array_merge($questionsFetch, BOL_QuestionService::getInstance()->findBaseSignUpQuestions());

        $questions = $this->prepareQuestions($questionsFetch);
        return $questions;
    }

    public function getEditProfileForm($userId) {
        $questions = array();
        $accountTypes = BOL_QuestionService::getInstance()->findAllAccountTypes();
        foreach ($accountTypes as $accountType){
            $questions = array_merge($questions, BOL_QuestionService::getInstance()->findEditQuestionsForAccountType($accountType->name));
        }

        $onBeforeProfileEditFormBuildEventResults = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PROFILE_EDIT_FORM_BUILD, array('questions' => $questions)));
        if(isset($onBeforeProfileEditFormBuildEventResults->getData()['questions'])){
            $questions = $onBeforeProfileEditFormBuildEventResults->getData()['questions'];
        }

        $editForm = new EditQuestionForm('editForm', $userId);
        $editForm->setId('editForm');

        $questionArray = array();
        $section = null;
        $questionNameList = array();

        foreach ($questions as $sort => $question) {
            if ($section !== $question['sectionName']) {
                $section = $question['sectionName'];
            }
            $questionArray[$section][$sort] = $questions[$sort];
            $questionNameList[] = $questions[$sort]['name'];
        }
        $questionData = BOL_QuestionService::getInstance()->getQuestionData(array($userId), $questionNameList);
        $questionValues = BOL_QuestionService::getInstance()->findQuestionsValuesByQuestionNameList($questionNameList);
        $editForm->addQuestions($questions, $questionValues, !empty($questionData[$userId]) ? $questionData[$userId] : array());

        return array('form' => $editForm, 'questionsData' => $questionData, 'questionValues' => $questionValues, 'questionArray' => $questionArray, 'questions' => $questions);
    }

    public function getEditProfileFields(){
        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authenticated_before');
        }
        $userId = OW::getUser()->getId();

        $editFormData = $this->getEditProfileForm($userId);
        $questionsFetch = $editFormData['questions'];
        $questionsData = $editFormData['questionsData'];
        if (isset($questionsData[$userId])) {
            $questionsData = $questionsData[$userId];
        } else {
            $questionsData = array();
        }
        $questions = $this->prepareQuestions($questionsFetch, $questionsData);
        return $questions;
    }

    /***
     * Join user process
     * @return array
     */
    public function joinAction(){
        if(OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authenticated_before');
        }
        $result = array('valid' => false, 'message' => 'invalid_data');
        if ( (int) OW::getConfig()->getValue('base', 'who_can_join') === BOL_UserService::PERMISSIONS_JOIN_BY_INVITATIONS )
        {
            $code = null;
            if ( isset($_GET['code']) )
            {
                $code = strip_tags($_GET['code']);
            }

            try
            {
                $event = new OW_Event(OW_EventManager::ON_JOIN_FORM_RENDER, array('code' => $code));
                OW::getEventManager()->trigger($event);
                $result = array('valid' => false, 'message' => 'only_invitation_join');
            }
            catch ( JoinRenderException $ex )
            {
                //ignore;
            }

        }

        if(isset($_POST['email']) && !UTIL_Validator::isEmailValid($_POST['email'])){
            return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'email');
        }

        if(isset($_POST['password']) && (strlen($_POST['password']) < 8)){
            return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'password');
        }

        if(isset($_POST['username']) && !UTIL_Validator::isUserNameValid($_POST['username'])){
            return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'username');
        }

        if(isset($_POST['birthdate']) && !UTIL_Validator::isUserNameValid($_POST['birthdate'])){
            $date = UTIL_DateTime::parseDate($_POST['birthdate'], UTIL_DateTime::DEFAULT_DATE_FORMAT);
            if ( $date === null ){
                return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'birthdate');
            }

            if ( !UTIL_Validator::isDateValid($date[UTIL_DateTime::PARSE_DATE_MONTH], $date[UTIL_DateTime::PARSE_DATE_DAY], $date[UTIL_DateTime::PARSE_DATE_YEAR]) ){
                return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'birthdate');
            }


            $changeToGregorianDateEventParams['faYear'] = $date[UTIL_DateTime::PARSE_DATE_YEAR];
            $changeToGregorianDateEventParams['faMonth'] = $date[UTIL_DateTime::PARSE_DATE_MONTH];
            $changeToGregorianDateEventParams['faDay'] = $date[UTIL_DateTime::PARSE_DATE_DAY];
            $changeToGregorianDateEventParams['changeNewsJalaliToGregorian'] = true;
            $changeToGregorianDateEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::CHANGE_DATE_FORMAT_TO_GREGORIAN, $changeToGregorianDateEventParams));
            if($changeToGregorianDateEvent->getData()!=null && sizeof($changeToGregorianDateEvent->getData())>0){
                $newDateData = $changeToGregorianDateEvent->getData();
                if (isset($newDateData['gregorianYearNews']) && isset($newDateData['gregorianMonthNews']) && isset($newDateData['gregorianDayNews'])) {
                    $_POST['birthdate'] = $newDateData['gregorianYearNews'] . '/' .$newDateData['gregorianMonthNews'] . '/' .$newDateData['gregorianDayNews'];
                }
            }

            $date = new DateTime($_POST['birthdate']);
            $date = $date->getTimestamp();
            if (time() - $date < 365 * 24 * 60 * 60) {
                return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'birthdate');
            }
        }

        $valid = true;
        $questions = $this->getJoinFields();
        $formValidator = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkDataFormValid($questions, false);
        if($formValidator['valid'] == true){
            $result = $this->createUser($questions);
            if($result['user'] == null){
                $valid = false;
            }else{
                $user = $result['user'];
                $userData = array(
                    "id" => $user->id,
                    "username" => $user->username,
                    "email" => $user->email,
                );

                return array(
                    'valid' => $valid,
                    'message' => 'user_created',
                    'user' => $userData
                );
            }
        }else{
            return array('valid' => false, 'message' => 'error_input_Data', 'errors' => $formValidator['errors']);
        }

        if(!$valid){
            return $result;
        }
    }

    /***
     * Edit user profile
     * @return array
     */
    public function editProfile(){
        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authenticated_need');
        }
        $result = array('valid' => false, 'message' => 'invalid_data');

        if(isset($_POST['email']) && !UTIL_Validator::isEmailValid($_POST['email'])){
            return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'email');
        }

        if(isset($_POST['username']) && !UTIL_Validator::isUserNameValid($_POST['username'])){
            return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'username');
        }

        if(isset($_POST['birthdate']) && !UTIL_Validator::isUserNameValid($_POST['birthdate'])){
            $date = UTIL_DateTime::parseDate($_POST['birthdate'], UTIL_DateTime::DEFAULT_DATE_FORMAT);
            if ( $date === null ){
                return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'birthdate');
            }

            if ( !UTIL_Validator::isDateValid($date[UTIL_DateTime::PARSE_DATE_MONTH], $date[UTIL_DateTime::PARSE_DATE_DAY], $date[UTIL_DateTime::PARSE_DATE_YEAR]) ){
                return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'birthdate');
            }
            $date = new DateTime($_POST['birthdate']);
            $date = $date->getTimestamp();
            if (time() - $date < 365 * 24 * 60 * 60) {
                return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'birthdate');
            }
        }
        $userId = OW::getUser()->getId();
        $editFormData = $this->getEditProfileForm($userId);
        $questionArray = $editFormData['questionArray'];
        $editForm = $editFormData['form'];
        if ( $editForm->getElement('csrf_token') != null){
            $editForm->deleteElement('csrf_token');
        }
        if ( !isset($_POST['parentEmail'])){
            $_REQUEST['parentEmail'] = '';
        }

        foreach ( $questionArray as $section ) {
            foreach ($section as $key => $question) {
                if (isset($question['presentation']) && isset($_POST[$question['name']]) && $question['presentation'] == 'multicheckbox') {
                    $_POST[$question['name']] = array_filter(explode(',', $_POST[$question['name']]));
                }
            }
        }

        if ( $editForm->isValid($_POST) ) {
            $data = $editForm->getValues();
            $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array('userId' => $userId, 'method' => 'native', 'params' => $data,'forEditProfile'=>true));
            OW::getEventManager()->trigger($event);
            foreach ( $questionArray as $section )
            {
                foreach ( $section as $key => $question )
                {
                    switch ( $question['presentation'] )
                    {
                        case 'multicheckbox':

                            if ( is_array($data[$question['name']]) )
                            {
                                $answer = array();
                                foreach ($data[$question['name']] as $key => $value )
                                {
                                    $answer[] = (int)$value;
                                }
                                $data[$question['name']] = json_encode($answer);
                            }
                            else
                            {
                                $data[$question['name']] = 0;
                            }

                            break;
                    }
                }
            }
            $changesList = BOL_QuestionService::getInstance()->getChangedQuestionList($data, $userId);
            if ( BOL_QuestionService::getInstance()->saveQuestionsData($data, $userId) )
            {
                $isNeedToModerate = BOL_QuestionService::getInstance()->isNeedToModerate($changesList);
                $event = new OW_Event(OW_EventManager::ON_USER_EDIT, array('userId' => $userId, 'method' => 'native', 'moderate' => $isNeedToModerate));
                OW::getEventManager()->trigger($event);

                if ( BOL_UserService::getInstance()->isApproved($userId) )
                {
                    $changesList = array();
                }

                BOL_PreferenceService::getInstance()->savePreferenceValue('base_questions_changes_list', json_encode($changesList), $userId);
                $result = array('valid' => true, 'message' => 'edited', 'user' => $this->getUserInformationById($userId));
            }
            if (isset($_POST['user_status'])) {
                $userStatus = UTIL_HtmlTag::stripTagsAndJs($_POST['user_status']);
                $userStatus = trim($userStatus);
                if (!empty($userStatus)) {
                    BOL_ComponentEntityService::getInstance()->saveComponentSettingList('profile-BASE_CMP_AboutMeWidget', $userId, array('content' => $userStatus));
                    BOL_ComponentEntityService::getInstance()->clearEntityCache(BOL_ComponentEntityService::PLACE_PROFILE, $userId);
                }
            }
        } else {
            $result = array('valid' => false, 'message' => 'invalid_data', 'errors' => $editForm->getErrors());
        }

        return $result;
    }

    /***
     * edit profile user process
     * @return array
     */
    public function fillProfileQuestion(){
        if(!OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authentication_need');
        }
        $result = array('valid' => false, 'message' => 'invalid_data');

        if(isset($_POST['email']) || isset($_POST['password']) || isset($_POST['username'])){
            return $result;
        }

        if(isset($_POST['birthdate']) && !UTIL_Validator::isUserNameValid($_POST['birthdate'])){
            $date = UTIL_DateTime::parseDate($_POST['birthdate'], UTIL_DateTime::DEFAULT_DATE_FORMAT);
            if ( $date === null ){
                return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'birthdate');
            }

            if ( !UTIL_Validator::isDateValid($date[UTIL_DateTime::PARSE_DATE_MONTH], $date[UTIL_DateTime::PARSE_DATE_DAY], $date[UTIL_DateTime::PARSE_DATE_YEAR]) ){
                return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'birthdate');
            }
            $date = new DateTime($_POST['birthdate']);
            $date = $date->getTimestamp();
            if (time() - $date < 365 * 24 * 60 * 60) {
                return array('valid' => false, 'message' => 'error_input_Data', 'field' => 'birthdate');
            }
        }

        $user = OW::getUser()->getUserObject();
        if (!$user) {
            return array('valid' => false, 'message' => 'authentication_need');
        }

        $accountType = BOL_QuestionService::getInstance()->findAccountTypeByName($user->accountType);

        if ( empty($accountType) )
        {
            return array('valid' => false, 'message' => 'authentication_need');
        }

        try{
            $event = new OW_Event( OW_EventManager::ON_BEFORE_USER_COMPLETE_PROFILE, array( 'user' => $user ) );
            OW::getEventManager()->trigger($event);
        } catch (Exception $e){
            return array('valid' => false, 'message' => 'input_data');
        }

        $form = new EditQuestionForm('requiredQuestionsForm', $user->id);

        $questions = BOL_QuestionService::getInstance()->getEmptyRequiredQuestionsList($user->id);

        if ( empty($questions) )
        {
            return array('valid' => false, 'message' => 'empty_questions');
        }

        $section = null;
        $questionArray = array();
        $questionNameList = array();

        foreach ( $questions as $sort => $question )
        {
            if ( $section !== $question['sectionName'] )
            {
                $section = $question['sectionName'];
            }

            $questionArray[$section][$sort] = $questions[$sort];
            $questionNameList[] = $questions[$sort]['name'];
            if ($questions[$sort]['presentation'] == 'multicheckbox' && isset($_POST[$questions[$sort]['name']])) {
                $_POST[$questions[$sort]['name']] = array_filter(explode(',', $_POST[$questions[$sort]['name']]));
            }
        }

        $questionValues = BOL_QuestionService::getInstance()->findQuestionsValuesByQuestionNameList($questionNameList);

        $form->addQuestions($questions, $questionValues, array());
        if ( $form->getElement('csrf_token') != null){
            $form->deleteElement('csrf_token');
        }
        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                if ( BOL_QuestionService::getInstance()->saveQuestionsData($form->getValues(), $user->getId()) ){
                    $event = new OW_Event(OW_EventManager::ON_AFTER_USER_COMPLETE_PROFILE, array( 'userId' => $user->getId() ));
                    OW::getEventManager()->trigger($event);
                    return array('valid' => true, 'message' => 'edited', 'user' => $this->getUserInformationById($user->getId()));
                }
            } else {
                return array('valid' => false, 'message' => 'input_error', 'form_error' => $form->getErrors());
            }
        }

        return array('valid' => false, 'message' => 'input_error');
    }

    /***
     * Resend sms verification code again
     * @return array
     * @throws Redirect404Exception
     */
    public function resend_sms_verification(){
        if(!OW::getUser()->isAuthenticated()){
            return array("valid" => false, 'message' => 'authentication_need');
        }

        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissms',true)){
            $IISSMSService = IISSMS_BOL_Service::getInstance();
            $token = $IISSMSService->getUserToken(OW::getUser()->getId());
            if($token != null && $token->try > $IISSMSService->getMaxTokenPossibleTry()){
                return array("valid" => false, 'message' => 'many_wrong_try');
            }
            $userMobile = $IISSMSService->getUserMobile(OW::getUser()->getId());
            if($userMobile == null){
                return array("valid" => false, 'message' => 'mobile_number_null');
            }
            if($token->valid){
                return array("valid" => false, 'message' => 'validate_before');
            }
            $token = $IISSMSService->renewUserToken(OW::getUser()->getId(), $userMobile);
            if($token != null){
                return array("valid" => true, 'message' => 'send_token');
            }
        }
    }

    /***
     * Verify mobile code verification sent by user
     * @return array
     * @throws Redirect404Exception
     */
    public function verify_mobile_code(){
        if(!OW::getUser()->isAuthenticated()){
            return array("valid" => false, 'message' => 'authentication_need');
        }

        if(!isset($_POST['code']) || empty($_POST['code'])){
            return array("valid" => false, 'message' => 'empty_code');
        }

        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissms', true)){
            $mobileCheckCode = IISSMS_BOL_Service::getInstance()->isMobileCodeValid($_POST['code']);
            if($mobileCheckCode){
                IISSMS_BOL_Service::getInstance()->validateMobileToken();
                return array("valid" => true, 'message' => 'validate_successfully');
            }else{
                IISSMS_BOL_Service::getInstance()->wrongTokenInsertProcess();
                return array("valid" => false, 'message' => 'wrong_code');
            }
        }else{
            return array("valid" => false, 'message' => 'plugin_not_found');
        }
    }

    public function inviteUser() {
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisinvite', true)){
            return array("valid" => false, 'message' => 'plugin_not_found');
        }

        $emails = null;
        $smss = null;
        if(isset($_POST['email'])){
            $emails = $_POST['email'];
        }
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissms', true) && isset($_POST['number'])) {
            $smss = $_POST['number'];
        }

        $language = OW::getLanguage();
        $emptyError = $language->text('admin', 'invite_members_min_limit_message');

        if (!isset($emails) && !isset($smss)) {
            return array('valid' => false, 'message' => $emptyError);
        }

        $result = IISINVITE_BOL_Service::getInstance()->sendInvitation($emails, $smss);

        if (isset($result['valid'])) {
            if (!$result['valid']) {
                if (isset($result['email'])) {
                    $error = $language->text('iisinvite', 'wrong_email_format_error', array('email' => trim($result['email'])));
                    return array('valid' => false, 'error' => array('email' => $error));
                }
                if (isset($result['number'])) {
                    $error = $language->text('iisinvite', 'wrong_mobile_format_error', array('phone' => trim($result['number'])));
                    return array('valid' => false, 'error' => array('number' => $error));
                }
                if (isset($result['limit'])) {
                    return array('valid' => false, 'message' => $result['limit']);
                }
            } else if(isset($result['registered_users']) && isset($result['invalidNumbers']) && isset($result['sentInvitationsNumber'])) {
                return array('valid' => true);
            }
        }

        return array('valid' => false, 'message' => $emptyError);
    }

    public function createUser($questions = array())
    {
        $username = null;
        $email = null;
        $password = null;
        $verifyMobile = false;

        if(isset($_POST['email'])){
            $email = $_POST['email'];
        }

        if(isset($_POST['password'])){
            $password = $_POST['password'];
        }

        if(isset($_POST['username']) && !empty($_POST['username'])){
            $username = $_POST['username'];
        }else if(isset($_POST['field_mobile'])){
            $username = $_POST['field_mobile'];
            $verifyMobile = true;
        }

        if(empty($username) || empty($email) || empty($password)){
            return array('valid' => false, 'message' => 'error_input_Data', 'user' => null);
        }

        $user = BOL_UserService::getInstance()->findByUsername($username);
        if ($user == null) {
            $user = BOL_UserService::getInstance()->findByEmail($email);
            if ($user == null) {
                $accountType = BOL_QuestionService::getInstance()->getDefaultAccountType()->name;
                $user = BOL_UserService::getInstance()->createUser($username, $password, $email, $accountType, true);
                OW_User::getInstance()->login($user->getId());
                $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array('userId' => $user->getId(), 'method' => 'service'));
                OW::getEventManager()->trigger($event);
                if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iissms', true) && $verifyMobile){
                    IISSMS_BOL_Service::getInstance()->renewUserToken($user->getId(), $_POST['field_mobile']);
                    OW_User::getInstance()->login($user->getId());
                }
            }else{
                return array('valid' => false, 'message' => 'email_exists', 'user' => null);
            }
        }else{
            return array('valid' => false, 'message' => 'username_exists', 'user' => null);
        }

        $questionService = BOL_QuestionService::getInstance();
        $data = array();
        foreach ($questions as $question){
            if(isset($question['name']) && !in_array($question['name'], array('password')) && isset($_POST[$question['name']])){
                if (isset($question['presentation']) && $question['presentation'] == 'multicheckbox' && isset($_POST[$question['name']])) {
                    $_POST[$question['name']] = array_filter(explode(',', $_POST[$question['name']]));
                }
                $data[$question['name']] = $_POST[$question['name']];
            }
        }

        $questionService->saveQuestionsData($data, $user->getId());
        return array('valid' => true, 'message' => 'create_user', 'user' => $user);
    }

    public function processForgotPassword()
    {
        if(OW::getUser()->isAuthenticated()){
            return array('valid' => false, 'message' => 'authorization_error');
        }
        $emailOrPhone = null;
        if(isset($_GET['email'])){
            $emailOrPhone = trim($_GET['email']);
        }
        if(!isset($emailOrPhone)){
            return array('valid' => false, 'email_exist' => false, 'message' => 'input_error');
        }
        return $this->processSendForgotPassword($emailOrPhone);
    }


    public function processSendForgotPassword($emailOrPhone)
    {
        try{
            $event = new OW_Event('base.forgot_password.form_process', array('data' => array('email' => $emailOrPhone)));
            OW_EventManager::getInstance()->trigger($event);
            $result = $event->getData();
            if(!isset($result) || !isset($result['processed']) || !$result['processed']) {
                if(!UTIL_Validator::isEmailValid($emailOrPhone)) {
                    return array('valid' => false, 'email_exist' => false);
                }
                $userService=BOL_UserService::getInstance();
                $user = $userService->findByEmail($emailOrPhone);

                if ( $user === null )
                {
                    return array('valid' => false, 'user_exists' => false);
                }
                $userService->processResetForm(array('email' => $emailOrPhone));
            }
            return array('valid' => true);
        } catch (LogicException $e){
            return array('valid' => false, 'remaining_block_time' => 10);
        }
    }

}