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
class IISMOBILESUPPORT_BOL_WebServiceEvent
{
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

    public function canUserAccessWithEntity($entityType, $entityId, $userId){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true)){
            return false;
        }
        $activity = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->getCreatorActivityOfAction($entityType, $entityId);
        if($activity == null){
            return false;
        }
        $feedIdFromActivities = NEWSFEED_BOL_ActionFeedDao::getInstance()->findByActivityIds(array($activity->id));
        $event = null;
        foreach ($feedIdFromActivities as $feedFromActivity){
            if($feedFromActivity->feedType=="event"){
                $event = EVENT_BOL_EventService::getInstance()->findEvent($feedFromActivity->feedId);
            }
        }
        if($event == null){
            return false;
        }

        $event = $this->canUserViewEvent($event->getId(), $userId);
        if($event == null){
            return false;
        }

        return true;
    }

    public function canUserCreateEvent(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);

        if(!$pluginActive){
            return false;
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'add_event') )
        {
            return false;
        }

        return true;
    }

    public function leave(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iiseventplus', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'view_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userId = OW::getUser()->getId();

        if (!isset($_GET['eventId']))
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $eventId = $_GET['eventId'];
        $event = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
        if($event == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $userInvitedBefore = EVENT_BOL_EventService::getInstance()->findEventUser($eventId, $userId);
        if($userInvitedBefore != null) {
            if($event->userId == $userId){
                EVENT_BOL_EventService::getInstance()->deleteEvent($eventId);
            }else{
                IISEVENTPLUS_BOL_Service::getInstance()->leaveEvent($eventId, $userId);
            }

            return array('valid' => true, 'event' => $this->populateEventData($event, false, array('comments')));
        }
        return array('valid' => false, 'message' => 'authorization_error');
    }

    public function acceptInvite(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'view_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $accepterUserId = OW::getUser()->getId();

        if (!isset($_GET['eventId']))
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $eventId = $_GET['eventId'];
        $event = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
        if($event == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $invite = EVENT_BOL_EventService::getInstance()->findEventInvite($eventId, $accepterUserId);
        if($invite != null ){
            $userInvitedBefore = EVENT_BOL_EventService::getInstance()->findEventUser($eventId, $accepterUserId);
            if($userInvitedBefore == null) {
                EVENT_BOL_EventService::getInstance()->addEventUser($accepterUserId, $eventId, EVENT_BOL_EventService::USER_STATUS_YES);
                EVENT_BOL_EventService::getInstance()->deleteUserEventInvites((int)$eventId, $accepterUserId);
                BOL_InvitationService::getInstance()->deleteInvitation(EVENT_CLASS_InvitationHandler::INVITATION_JOIN, $eventId, $accepterUserId);
                return array('valid' => true, 'id' => (int) $eventId);
            }
        }
        return array('valid' => false, 'message' => 'authorization_error');
    }

    public function cancelInvite(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'view_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $accepterUserId = OW::getUser()->getId();

        if (!isset($_GET['eventId']))
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $eventId = $_GET['eventId'];
        $event = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
        if($event == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $invite = EVENT_BOL_EventService::getInstance()->findEventInvite($eventId, $accepterUserId);
        if($invite != null ){
            $userInvitedBefore = EVENT_BOL_EventService::getInstance()->findEventUser($eventId, $accepterUserId);
            if($userInvitedBefore == null) {
                EVENT_BOL_EventService::getInstance()->deleteUserEventInvites((int)$eventId, $accepterUserId);
                BOL_InvitationService::getInstance()->deleteInvitation(EVENT_CLASS_InvitationHandler::INVITATION_JOIN, $eventId, $accepterUserId);
                return array('valid' => true, 'id' => (int) $eventId);
            }
        }
        return array('valid' => false, 'message' => 'authorization_error');
    }

    public function inviteUser(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'view_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }


        $inviterUserId = OW::getUser()->getId();

        if ( !isset($_GET['userId']) || !isset($_GET['eventId']) )
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $userId = $_GET['userId'];
        $eventId = $_GET['eventId'];
        $event = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
        if($event == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        $userInvitedBefore = EVENT_BOL_EventService::getInstance()->findEventUser($eventId, $userId);
        if($userInvitedBefore != null) {
            return array('valid' => true, 'message' => 'user_added_before');
        }

        if ( (int) $event->getUserId() === $inviterUserId || (int) $event->getWhoCanInvite() === EVENT_BOL_EventService::CAN_INVITE_PARTICIPANT )
        {
            $eventInvite = EVENT_BOL_EventService::getInstance()->findEventInvite($event->getId(), $userId);

            if($eventInvite != null){
                return array('valid' => true, 'message' => 'sent_before');
            }

            $invite = false;
            if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('friends', true)){
                $invite = true;
            }else {
                $isFriends = FRIENDS_BOL_Service::getInstance()->findFriendship($inviterUserId, $userId);
                if ($inviterUserId != $userId && isset($isFriends) && $isFriends->status == 'active') {
                    $invite = true;
                }
            }

            if($invite){
                $eventInvite = EVENT_BOL_EventService::getInstance()->inviteUser($event->getId(), $userId, $inviterUserId);
                if($eventInvite != null) {
                    $eventObj = new OW_Event('event.invite_user', array('userId' => $userId, 'inviterId' => $inviterUserId, 'eventId' => $event->getId(), 'imageId' => $event->getImage(), 'eventTitle' => $event->getTitle(), 'eventDesc' => $event->getDescription(), 'displayInvitation' => $eventInvite->displayInvitation));
                    OW::getEventManager()->trigger($eventObj);
                    return array('valid' => true);
                }
            }else{
                return array('valid' => false, 'message' => 'authorization_error');
            }
        }

        return array('valid' => false, 'message' => 'input_error');
    }

    public function getEvents($type){
        if($type != "latest"){
            return array();
        }
        $userId = null;
        if(isset($_GET['userId'])){
            $userId = $_GET['userId'];
        }else if(isset($_GET['username'])){
            $user = BOL_UserService::getInstance()->findByUsername($_GET['username']);
            if($user != null){
                $userId = $user->getId();
            }
        }

        if(OW::getUser()->isAuthenticated() && isset($_GET['my']) && $_GET['my']){
            $userId = OW::getUser()->getId();
        }

        $data = $this->getEventsByUserId($userId);
        return $data;

    }

    public function getEventsByUserId($userId = null){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if ( !OW::getUser()->isAuthorized('event', 'view_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if($userId != null){
            $checkPrivacy = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPrivacyAction($userId, 'event_view_attend_events', 'event');
            if(!$checkPrivacy){
                return array();
            }
        }
        $data = array();
        $page = null;
        if(isset($_GET['page'])){
            $page = $_GET['page'];
        }

        $first = null;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        if($page == null && $first != null){
            $page = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageNumber($first);
        }

        if($page == null){
            $page= 1;
        }

        $events = EVENT_BOL_EventService::getInstance()->findPublicEventsByFiltering($page, $count,$userId, null, null, array(), false,true, null);

        foreach ($events as $event){
            $data[] = $this->populateEventData($event);
        }
        return $data;
    }

    public function getEvent(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if ( !OW::getUser()->isAuthorized('event', 'view_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userId = OW::getUser()->getId();
        $eventId = null;
        if(isset($_GET['eventId'])){
            $eventId = $_GET['eventId'];
        }else{
            return array('valid' => false, 'message' => 'input_error');
        }

        $event = $this->canUserViewEvent($eventId, $userId);
        if($event == null){
            if (OW::getUser()->isAuthenticated()) {
                $event = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
                if($event == null){
                    return array('valid' => false, 'message' => 'authorization_error', 'id' => (int) $eventId);
                }
                $invite = EVENT_BOL_EventService::getInstance()->findEventInvite($eventId, OW::getUser()->getId());
                if($invite != null ){
                    return array(
                        'invite' => true,
                        "id" => (int) $event->id,
                        "title" => $event->title,
                        "location" => $event->location,
                        "startTimeStamp" => $event->startTimeStamp,
                        "endTimeStamp" => $event->endTimeStamp,
                        "createTimeStamp" => $event->createTimeStamp,
                        "whoCanView" => $event->whoCanView,
                        "whoCanInvite" => $event->whoCanInvite,
                    );
                }
            }
            return array('valid' => false, 'message' => 'authorization_error', 'id' => (int) $eventId);
        }

        return $this->populateEventData($event, false, array('comments'));
    }

    public function canUserViewEvent($eventId, $userId){
        $event = EVENT_BOL_EventService::getInstance()->findEvent($eventId);

        if($event == null){
            return null;
        }

        if(OW::getUser()->isAuthenticated() && OW::getUser()->isAdmin()){
            return $event;
        }

        if($event->whoCanView == EVENT_BOL_EventService::CAN_VIEW_ANYBODY){
            return $event;
        }else if ($userId == null){
            return null;
        }

        $userInvitedBefore = EVENT_BOL_EventService::getInstance()->findEventUser($eventId, $userId);
        if($userInvitedBefore == null) {
            return null;
        }
        return $event;
    }

    private function populateEventData($event, $loadEvents = true, $params = array()){
        if($event == null){
            return array();
        }

        $imageUrl = OW::getPluginManager()->getPlugin('base')->getStaticUrl(). 'css/images/' . 'default_event.png';
        $emptyImage = true;
        if($event->image){
            $imageUrl = EVENT_BOL_EventService::getInstance()->generateImageUrl($event->image);
            $emptyImage = false;
        }
        $categoryValue = "";
        $categoryStatus = '';

        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        $filesInformation = array();
        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iiseventplus', true)){
            $categoryId = IISEVENTPLUS_BOL_Service::getInstance()->getEventCategoryByEventId($event->id);
            if($categoryId!= null){
                $category = IISEVENTPLUS_BOL_Service::getInstance()->getCategoryById($categoryId);
                if($category != null){
                    $categoryStatus = $category->id;
                    $categoryValue = $category->label;
                }
            }

            $filesList = IISEVENTPLUS_BOL_Service::getInstance()->findFileList($event->id, $first, $count);
            $filesInformation = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->preparedFileList($event, $filesList);
        }

        $status = -1;
        $registered = false;
        if(OW::getUser()->isAuthenticated()){
            $eventUser = EVENT_BOL_EventService::getInstance()->findEventUser($event->id, OW::getUser()->getId());
            if ( $eventUser != null )
            {
                $registered = true;
                $status = (int) $eventUser->status;
            }
        }

        $comments = array();
        if (in_array('comments', $params)){
            $comments = IISMOBILESUPPORT_BOL_WebServiceComment::getInstance()->getCommentsInformation('event', $event->id);
        }

        $description = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($event->description, false);

        $users = array();
        $usersObj = EVENT_BOL_EventService::getInstance()->findAllUsersForEvent($event->id);
        $idList = array();
        $usersStatus = array();
        $listTypes = EVENT_BOL_EventService::getInstance()->getUserListsArray();
        foreach ( $usersObj as $eventUser )
        {
            $idList[] = $eventUser->userId;
            $usersStatus[$eventUser->userId] = $listTypes[$eventUser->status];
        }
        $usersObject = BOL_UserService::getInstance()->findUserListByIdList($idList);
        $usernames = BOL_UserService::getInstance()->getDisplayNamesForList($idList);
        $avatars = BOL_AvatarService::getInstance()->getAvatarsUrlList($idList);
        foreach ( $usersObject as $userObject )
        {
            $username = null;
            if(isset($usernames[$userObject->id])){
                $username = $usernames[$userObject->id];
            }

            $avatarUrl = null;
            if(isset($avatars[$userObject->id])){
                $avatarUrl = $avatars[$userObject->id];
            }
            $data = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->populateUserData($userObject, $avatarUrl, $username);
            $data['status'] = $usersStatus[$userObject->id];
            $users[] = $data;
        }

        $startTime = array('hour' => date('G', $event->getStartTimeStamp()), 'minute' => date('i', $event->getStartTimeStamp()));
        $startDate = date('Y', $event->getStartTimeStamp()) . '/' . date('n', $event->getStartTimeStamp()) . '/' . date('j', $event->getStartTimeStamp());

        $endDate = '';
        $editable = $this->canEditEvent($event->id) != null;
        if ( $event->getEndTimeStamp() !== null )
        {
            $endTime = array('hour' => date('G', $event->getEndTimeStamp()), 'minute' => date('i', $event->getEndTimeStamp()));
            $endTimeStamp = $event->getEndTimeStamp();
            if ( $event->getEndTimeDisable() )
            {
                $endTimeStamp = strtotime("-1 day", $endTimeStamp);
            }
            $endDate = date('Y', $endTimeStamp) . '/' . date('n', $endTimeStamp) . '/' . date('j', $endTimeStamp);
        }


        $canAddFile = $this->canAddFile($event);
        $canDeleteFile = $this->canDeleteFile($event);
        $canInvite = false;

        if ((int) $event->userId === OW::getUser()->getId()
            || (int) $event->whoCanInvite === EVENT_BOL_EventService::CAN_INVITE_PARTICIPANT ){
            $canInvite = true;
        }

        $registrable = false;
        if(!$registered && ((int) $event->whoCanView != EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY || OW::getUser()->isAdmin())) {
            $registrable = true;
        }

        $result =  array(
            "id" => (int) $event->id,
            "title" => $event->title,
            "entityId" => (int) $event->id,
            "entityType" => 'event',
            "can_add_file" => $canAddFile,
            "can_delete_file" => $canDeleteFile,
            "description" => $description,
            "can_invite_user" => $canInvite,
            "location" => $event->location,
            "user" => IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($event->userId),
            "status" => $status,
            "startTimeStamp" => $event->startTimeStamp,
            "endTimeStamp" => $event->endTimeStamp,
            "createTimeStamp" => $event->createTimeStamp,
            "whoCanView" => $event->whoCanView,
            "whoCanInvite" => $event->whoCanInvite,
            "start_time" => $startTime,
            "start_date" => $startDate,
            "registered" => $registered,
            "registrable" => $registrable,
            "end_time" => $endTime,
            "end_date" => $endDate,
            "imageUrl" => $imageUrl,
            'emptyImage' => $emptyImage,
            "categoryValue" => $categoryValue,
            "categoryStatus" => $categoryStatus,
            "editable" => $editable,
            "flagAble" => true,
        );

        if (in_array('comments', $params)){
            $result['comments'] = $comments;
        }

        if(!$loadEvents){
            $result['users'] = $users;
            $result['files'] = $filesInformation;
        }

        return $result;
    }

    public function joinEvent(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if ( !OW::getUser()->isAuthorized('event', 'view_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $eventId = null;
        $userId = OW::getUser()->getId();
        if(isset($_GET['eventId'])){
            $eventId = (int) $_GET['eventId'];
        }

        if($eventId == null){
            return array('valid' => false, 'message' => 'input_error');
        }
        $findUser = EVENT_BOL_EventService::getInstance()->findEventUser($eventId, $userId);
        if($findUser != null){
            return array('valid' => true, 'message' => 'add_before');
        }

        $event = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
        if($event == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $invite = EVENT_BOL_EventService::getInstance()->findEventInvite($eventId, $userId);

        if ( $invite == null && $event->whoCanView == EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY  && !OW::getUser()->isAdmin()) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        EVENT_BOL_EventService::getInstance()->addEventUser($userId, $eventId, EVENT_BOL_EventService::USER_STATUS_YES);
        EVENT_BOL_EventService::getInstance()->deleteUserEventInvites((int)$eventId, $userId);
        BOL_InvitationService::getInstance()->deleteInvitation(EVENT_CLASS_InvitationHandler::INVITATION_JOIN, $eventId, $userId);
        return array('valid' => true, 'message' => 'user_add', 'event' => $this->populateEventData($event, false, array('comments')));
    }

    public function getInvitableUsers(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);
        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'view_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }


        $currentUserId = OW::getUser()->getId();

        if ( !isset($_GET['eventId']) )
        {
            return array('valid' => false, 'message' => 'input_error');
        }

        $eventId = $_GET['eventId'];
        $event = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
        if($event == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userInvitedBefore = EVENT_BOL_EventService::getInstance()->findEventUser($eventId, $currentUserId);
        if($userInvitedBefore == null) {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $users = array();

        if ( (int) $event->getUserId() != $currentUserId &&
            (int) $event->getWhoCanInvite() != EVENT_BOL_EventService::CAN_INVITE_PARTICIPANT ){
            return $users;
        }

        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        $key = '';
        if(isset($_GET['key'])){
            $key = $_GET['key'];
        }

        $idList = EVENT_BOL_EventService::getInstance()->findUserListForInvite((int)$eventId);
        $users = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->populateInvitableUserList($idList, $key, $first, $count);
        return $users;
    }

    public function getEventFields(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $fields = array();
        $language = OW::getLanguage();
        $fields[] = array(
            'name' => 'title',
            'type' => 'text',
            'label' => $language->text('event', 'add_form_title_label'),
            'presentation' => 'text',
            'values' => array()
        );

        $fields[] = array(
            'name' => 'desc',
            'type' => 'text',
            'label' => $language->text('event', 'add_form_desc_label'),
            'presentation' => 'text',
            'values' => array()
        );

        $fields[] = array(
            'name' => 'location',
            'type' => 'text',
            'label' => $language->text('event', 'create_field_title_label'),
            'presentation' => 'text',
            'values' => array()
        );

        $whoCanViewValues[$language->text('event', 'add_form_who_can_view_option_anybody')] = EVENT_BOL_EventService::CAN_VIEW_ANYBODY;
        $whoCanViewValues[$language->text('event', 'add_form_who_can_view_option_invit_only')] = EVENT_BOL_EventService::CAN_VIEW_INVITATION_ONLY;
        $fields[] = array(
            'name' => 'who_can_view',
            'type' => 'select',
            'label' => $language->text('event', 'add_form_who_can_view_label'),
            'presentation' => 'radio',
            'values' => $whoCanViewValues
        );

        $whoCanInviteValues[$language->text('event', 'add_form_who_can_invite_option_participants')] = EVENT_BOL_EventService::CAN_INVITE_PARTICIPANT;
        $whoCanInviteValues[$language->text('event', 'add_form_who_can_invite_option_creator')] = EVENT_BOL_EventService::CAN_INVITE_CREATOR;
        $fields[] = array(
            'name' => 'who_can_invite',
            'type' => 'select',
            'label' => $language->text('event', 'add_form_who_can_invite_label'),
            'presentation' => 'radio',
            'values' => $whoCanInviteValues
        );

        $fields[] = array(
            'name' => 'start_date',
            'type' => 'date',
            'label' => $language->text('event', 'add_form_date_label'),
            'presentation' => 'date',
            'values' => array()
        );

        $fields[] = array(
            'name' => 'start_time',
            'type' => 'time',
            'label' => "",
            'presentation' => 'time',
            'values' => array()
        );

        $fields[] = array(
            'name' => 'end_date',
            'type' => 'date',
            'label' => $language->text('event', 'add_form_end_date_label'),
            'presentation' => 'date',
            'values' => array()
        );

        $fields[] = array(
            'name' => 'end_time',
            'type' => 'time',
            'label' => "",
            'presentation' => 'time',
            'values' => array()
        );

        if(IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iiseventplus', true)) {
            $categories = IISEVENTPLUS_BOL_Service::getInstance()->getEventCategoryList();
            if(sizeof($categories) > 0) {
                $values = array();
                $values[null] = OW::getLanguage()->text('iiseventplus', 'select_category');
                foreach ($categories as $category) {
                    $values[$category->label] = $category->id;
                }
                $fields[] = array(
                    'name' => 'categoryStatus',
                    'type' => 'select',
                    'label' => $language->text('iiseventplus', 'select_category'),
                    'presentation' => 'radio',
                    'values' => $values,
                    'required' => false
                );
            }
        }

            return $fields;
    }

    public function processCreateEvent(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'add_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $valid = true;
        $questions = $this->getEventFields();
        $formValidator = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkDataFormValid($questions);
        if($formValidator['valid'] == true){
            $result = $this->createEvent();
            if($result == null){
                $valid = false;
            }
            if($valid) {
                $eventInfo = $this->populateEventData($result, false, array('comments'));

                return array(
                    'valid' => true,
                    'message' => 'event_created',
                    'event' => $eventInfo);
            }
        }else{
            $valid = false;
        }

        if(!$valid){
            return array('valid' => false, 'message' => 'invalid_data');
        }
    }

    public function processEditEvent(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'add_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $eventId = null;
        if(isset($_GET['eventId'])){
            $eventId = (int) $_GET['eventId'];
        }

        if($eventId == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $valid = true;
        $questions = $this->getEventFields();
        $formValidator = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkDataFormValid($questions);
        if($formValidator['valid'] == true){
            $result = $this->editEvent($eventId);
            if($result == null){
                $valid = false;
            }
            if($valid) {
                $eventInfo = $this->populateEventData($result);

                return array(
                    'valid' => true,
                    'message' => 'event_edited',
                    'event' => $eventInfo);
            }
        }else{
            $valid = false;
        }

        if(!$valid){
            return array('valid' => false, 'message' => 'invalid_data');
        }
    }

    public function changeStatus(){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);

        if(!$pluginActive){
            return array('valid' => false, 'message' => 'plugin_not_found');
        }

        $eventService = EVENT_BOL_EventService::getInstance();
        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        if ( !OW::getUser()->isAuthorized('event', 'view_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userId = OW::getUser()->getId();
        $status = null;
        $eventId = null;
        if(isset($_GET['eventId'])){
            $eventId = $_GET['eventId'];
        }else{
            return array('valid' => false, 'message' => 'input_error');
        }

        if(isset($_GET['status'])){
            $status = (int) $_GET['status'];
        }else{
            return array('valid' => false, 'message' => 'input_error');
        }

        if(!in_array($status, array(1, 2, 3))){
            return array('valid' => false, 'message' => 'input_error');
        }

        $event = $this->canUserViewEvent($eventId, $userId);
        if($event == null){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $eventUser = $eventService->findEventUser($eventId, $userId);
        if ( $eventUser === null )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $eventUser->setStatus($status);
        $eventService->saveEventUser($eventUser);
        return array('valid' => true, 'status' => $status,'message' => 'status_changed');
    }

    public function canEditEvent($eventId){
        $userId = OW::getUser()->getId();

        $eventService = EVENT_BOL_EventService::getInstance();
        $event = $eventService->findEvent($eventId);
        if($event == null){
            return null;
        }

        $isModerator = BOL_AuthorizationService::getInstance()->isModerator($userId);
        if( $userId != $event->userId && !OW::getUser()->isAdmin() &&  !$isModerator)
        {
            return null;
        }

        return $event;
    }

    public function editEvent($eventId){
        if(!OW::getUser()->isAuthenticated()){
            return null;
        }

        $event = $this->canEditEvent($eventId);
        if($event == null){
            return null;
        }

        $serviceEvent = new OW_Event(EVENT_BOL_EventService::EVENT_BEFORE_EVENT_CREATE, array(), $_POST);
        OW::getEventManager()->trigger($serviceEvent);
        $data = $serviceEvent->getData();

        $dateArray = explode('/', $data['start_date']);
        $startStamp = mktime(0, 0, 0, $dateArray[1], $dateArray[2], $dateArray[0]);
        if ( $data['start_time'] != 'all_day' )
        {
            $hour = "12";
            $minute = "0";
            if(isset($data['start_time'])){
                $startTimeInfo = explode(':', $data['start_time']);
                if (sizeof($startTimeInfo) == 2) {
                    if ($startTimeInfo[0] < 23 && $startTimeInfo[0] >= 0){
                        $hour = $startTimeInfo[0];
                    }
                    if ($startTimeInfo[1] < 60 && $startTimeInfo[0] >= 0){
                        $minute = $startTimeInfo[1];
                    }
                }
            }
            $startStamp = mktime($hour, $minute, 0, $dateArray[1], $dateArray[2], $dateArray[0]);
        }

        $endDateFlag = false;
        if(!empty($data['end_date'])){
            $endDateFlag = true;
        }

        if ( $endDateFlag && !empty($data['end_date']) )
        {
            $dateArray = explode('/', $data['end_date']);
            $endStamp = mktime(0, 0, 0, $dateArray[1], $dateArray[2], $dateArray[0]);

            $endStamp = strtotime("+1 day", $endStamp);

            if ( $data['end_time'] != 'all_day' )
            {
                $hour = 0;
                $min = 0;

                if(isset($data['end_time'])){
                    $endTimeInfo = explode(':', $data['end_time']);
                    if (sizeof($endTimeInfo) == 2) {
                        if ($endTimeInfo[0] < 23 && $endTimeInfo[0] >= 0){
                            $hour = $endTimeInfo[0];
                        }
                        if ($endTimeInfo[1] < 60 && $endTimeInfo[0] >= 0){
                            $min = $endTimeInfo[1];
                        }
                    }
                }
                $dateArray = explode('/', $data['end_date']);
                $endStamp = mktime($hour, $min, 0, $dateArray[1], $dateArray[2], $dateArray[0]);
            }
        }

        if ( empty($endStamp) )
        {
            $endStamp = strtotime("+1 day", $startStamp);
            $endStamp = mktime(0, 0, 0, date('n',$endStamp), date('j',$endStamp), date('Y',$endStamp));
        }

        if ( !empty($endStamp) && $endStamp < $startStamp )
        {
            return null;
        }
        $event->setStartTimeStamp($startStamp);
        $event->setEndTimeStamp($endStamp);
        $event->setTitle(UTIL_HtmlTag::stripTagsAndJs($data['title']));
        $event->setLocation(UTIL_HtmlTag::autoLink(strip_tags($data['location'])));
        $event->setWhoCanView((int) $data['who_can_view']);
        $event->setWhoCanInvite((int) $data['who_can_invite']);
        $event->setDescription($data['desc']);
        $event->setEndDateFlag($endDateFlag);
        $event->setStartTimeDisable( $data['start_time'] == 'all_day' );
        $event->setEndTimeDisable( $data['end_time'] == 'all_day' );

        if (isset($_FILES['file'])){
            if ( !empty($_FILES['file']['name']) ){
                if ( (int) $_FILES['file']['error'] !== 0 ||
                    !is_uploaded_file($_FILES['file']['tmp_name']) ||
                    !UTIL_File::validateImage($_FILES['file']['name']) ){
                    // Do nothing
                }
                else{
                    $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
                    if ($isFileClean) {
                        $event->setImage(IISSecurityProvider::generateUniqueId());
                        EVENT_BOL_EventService::getInstance()->saveEventImage($_FILES['file']['tmp_name'], $event->getImage());
                    }
                }
            }
        }

        EVENT_BOL_EventService::getInstance()->saveEvent($event);

        $e = new OW_Event(EVENT_BOL_EventService::EVENT_AFTER_EVENT_EDIT, array('eventId' => $event->id));
        OW::getEventManager()->trigger($e);
        return $event;
    }

    public function createEvent(){
        if(!OW::getUser()->isAuthenticated()){
            return null;
        }

        $eventService = EVENT_BOL_EventService::getInstance();

        $serviceEvent = new OW_Event(EVENT_BOL_EventService::EVENT_BEFORE_EVENT_CREATE, array(), $_POST);
        OW::getEventManager()->trigger($serviceEvent);
        $data = $serviceEvent->getData();

        $dateArray = explode('/', $data['start_date']);
        $startStamp = mktime(0, 0, 0, $dateArray[1], $dateArray[2], $dateArray[0]);
        if ( $data['start_time'] != 'all_day' )
        {
            $hour = "12";
            $minute = "0";
            if(isset($data['start_time'])){
                $startTimeInfo = explode(':', $data['start_time']);
                if (sizeof($startTimeInfo) == 2) {
                    if ($startTimeInfo[0] < 23 && $startTimeInfo[0] >= 0){
                        $hour = $startTimeInfo[0];
                    }
                    if ($startTimeInfo[1] < 60 && $startTimeInfo[0] >= 0){
                        $minute = $startTimeInfo[1];
                    }
                }
            }

            $startStamp = mktime($hour, $minute, 0, $dateArray[1], $dateArray[2], $dateArray[0]);
        }

        $endDateFlag = false;
        if(!empty($data['end_date'])){
            $endDateFlag = true;
        }

        if ( $endDateFlag && !empty($data['end_date']) )
        {
            $dateArray = explode('/', $data['end_date']);
            $endStamp = mktime(0, 0, 0, $dateArray[1], $dateArray[2], $dateArray[0]);

            $endStamp = strtotime("+1 day", $endStamp);

            if ( $data['end_time'] != 'all_day' )
            {
                $hour = 0;
                $min = 0;

                if( $data['end_time'] != 'all_day' )
                {
                    $hour = "12";
                    $min = "0";
                    if(isset($data['end_time'])){
                        $endTimeInfo = explode(':', $data['end_time']);
                        if (sizeof($endTimeInfo) == 2) {
                            if ($endTimeInfo[0] < 23 && $endTimeInfo[0] >= 0){
                                $hour = $endTimeInfo[0];
                            }
                            if ($endTimeInfo[1] < 60 && $endTimeInfo[0] >= 0){
                                $min = $endTimeInfo[1];
                            }
                        }
                    }
                }
                $dateArray = explode('/', $data['end_date']);
                $endStamp = mktime($hour, $min, 0, $dateArray[1], $dateArray[2], $dateArray[0]);
            }
        }

        if ( empty($endStamp) )
        {
            $endStamp = strtotime("+1 day", $startStamp);
            $endStamp = mktime(0, 0, 0, date('n',$endStamp), date('j',$endStamp), date('Y',$endStamp));
        }

        if ( !empty($endStamp) && $endStamp < $startStamp )
        {
            return null;
        }

        $imagePosted = false;
        $filePath = null;

        if (isset($_FILES['file'])){
            if ( !empty($_FILES['file']['name']) ){
                if ( (int) $_FILES['file']['error'] !== 0 ||
                    !is_uploaded_file($_FILES['file']['tmp_name']) ||
                    !UTIL_File::validateImage($_FILES['file']['name']) ){
                    $imagePosted = false;
                }
                else{
                    $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
                    if ($isFileClean) {
                        $imagePosted = true;
                        $filePath = $_FILES['file']['tmp_name'];
                    }
                }
            }
        }
        $event = $eventService->createEvent($data, OW::getUser()->getId(), $startStamp, $endStamp, $imagePosted, $endDateFlag, $filePath);
        return $event;
    }


    public function addFile(){
        if ( !isset($_POST['eventId']) )
        {
            return false;
        }

        $eventId = $_POST['eventId'];
        $event = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
        if($event == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        if(!$this->canAddFile($event)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if (isset($_FILES['file']) && isset($_FILES['file']['tmp_name'])) {
            $isFileClean = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->isFileClean($_FILES['file']['tmp_name']);
            if (!$isFileClean) {
                return array('valid' => false, 'message' => 'virus_detected');
            }
        }

        $resultArr = IISEVENTPLUS_BOL_Service::getInstance()->manageAddFile($eventId, $_FILES['file']);
        if(!isset($resultArr) || !$resultArr['result']){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $filesList = IISEVENTPLUS_BOL_Service::getInstance()->findFileList($eventId, 0, 1);
        $filesInformation = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->preparedFileList($event, $filesList);

        return array('valid' => true, 'files' => $filesInformation);
    }

    public function deleteFile(){
        if ( !isset($_POST['eventId']) || !isset($_POST['id']) )
        {
            return false;
        }

        $eventId = $_POST['eventId'];
        $attachmentId = $_POST['id'];
        $event = EVENT_BOL_EventService::getInstance()->findEvent($eventId);
        if($event == null){
            return array('valid' => false, 'message' => 'input_error');
        }

        if(!$this->canDeleteFile($event)){
            return array('valid' => false, 'message' => 'authorization_error');
        }

        try {
            IISEVENTPLUS_BOL_Service::getInstance()->deleteFileForEvent($eventId, $attachmentId);
            return array('valid' => true, 'id' => $attachmentId);
        }
        catch (Exception $e){
            return array('valid' => false, 'message' => 'authorization_error');
        }
    }

    public function canAddFile($event){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);
        if(!$pluginActive){
            return false;
        }

        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iiseventplus', true);
        if(!$pluginActive){
            return false;
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'view_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        $userId = OW::getUser()->getId();
        if($event == null){
            return false;
        }

        $userInvitedBefore = EVENT_BOL_EventService::getInstance()->findEventUser($event->id, $userId);
        if($userInvitedBefore == null) {
            return false;
        }
        return true;
    }

    public function canDeleteFile($event){
        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('event', true);
        if(!$pluginActive){
            return false;
        }

        $pluginActive = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iiseventplus', true);
        if(!$pluginActive){
            return false;
        }

        if ( !OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('event', 'view_event') )
        {
            return array('valid' => false, 'message' => 'authorization_error');
        }

        if($event == null){
            return false;
        }

        $event = $this->canEditEvent($event->id);
        if($event == null){
            return false;
        }
        return true;
    }
}