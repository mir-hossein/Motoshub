<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 *  Events Service.
 * 
 * @author Sardar Madumarov <madumarov@gmail.com>, Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugins.event.bol
 * @since 1.0
 */
final class EVENT_BOL_EventService
{
    const USER_STATUS_YES = EVENT_BOL_EventUserDao::VALUE_STATUS_YES;
    const USER_STATUS_MAYBE = EVENT_BOL_EventUserDao::VALUE_STATUS_MAYBE;
    const USER_STATUS_NO = EVENT_BOL_EventUserDao::VALUE_STATUS_NO;

    const CAN_INVITE_PARTICIPANT = EVENT_BOL_EventDao::VALUE_WHO_CAN_INVITE_PARTICIPANT;
    const CAN_INVITE_CREATOR = EVENT_BOL_EventDao::VALUE_WHO_CAN_INVITE_CREATOR;

    const CAN_VIEW_ANYBODY = EVENT_BOL_EventDao::VALUE_WHO_CAN_VIEW_ANYBODY;
    const CAN_VIEW_INVITATION_ONLY = EVENT_BOL_EventDao::VALUE_WHO_CAN_VIEW_INVITATION_ONLY;

    const CONF_EVENT_USERS_COUNT = 'event_users_count';
    const CONF_EVENT_USERS_COUNT_ON_PAGE = 'event_users_count_on_page';
    const CONF_EVENTS_COUNT_ON_PAGE = 'events_count_on_page';
    const CONF_WIDGET_EVENTS_COUNT = 'events_widget_count';
    const CONF_WIDGET_EVENTS_COUNT_OPTION_LIST = 'events_widget_count_select_set';
    const CONF_DASH_WIDGET_EVENTS_COUNT = 'events_dash_widget_count';

    const EVENT_AFTER_EVENT_EDIT = 'event_after_event_edit';
    const EVENT_ON_DELETE_EVENT = 'event_on_delete_event';
    const EVENT_ON_CREATE_EVENT = 'event_on_create_event';
    const EVENT_ON_CHANGE_USER_STATUS = 'event_on_change_user_status';
    const EVENT_AFTER_CREATE_EVENT = 'event_after_create_event';
    
    const EVENT_BEFORE_IMAGE_UPDATE = 'event_before_image_update';
    const EVENT_AFTER_IMAGE_UPDATE = 'event_after_image_update';
    
    const EVENT_BEFORE_EVENT_CREATE = 'events.before_event_create';
    const EVENT_BEFORE_EVENT_EDIT = 'events.before_event_edit';
    const EVENT_COLLECT_TOOLBAR = 'events.collect_toolbar';

    const EVENT_CLEAR_INVITATIONS_INCOMPLETE = 'events.clear_invitations_incomplete';

    const ON_BEFORE_EVENT_VIEW_RENDER = 'iis.on.before.event.view.render';
    
    const MODERATION_STATUS_ACTIVE = 1;
    const MODERATION_STATUS_APPROVAL= 2;
    const MODERATION_STATUS_SUSPENDED = 3;

    /**
     * @var array
     */
    private $configs = array();
    /**
     * @var EVENT_BOL_EventDao
     */
    private $eventDao;
    /**
     * @var EVENT_BOL_EventUserDao
     */
    private $eventUserDao;
    /**
     * @var EVENT_BOL_EventInviteDao
     */
    private $eventInviteDao;
    /**
     * Singleton instance.
     *
     * @var EVENT_BOL_EventService
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return EVENT_BOL_EventService
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->eventDao = EVENT_BOL_EventDao::getInstance();
        $this->eventUserDao = EVENT_BOL_EventUserDao::getInstance();
        $this->eventInviteDao = EVENT_BOL_EventInviteDao::getInstance();

        $this->configs[self::CONF_EVENT_USERS_COUNT] = 10;
        $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE] = 15;
        $this->configs[self::CONF_DASH_WIDGET_EVENTS_COUNT] = 3;
        $this->configs[self::CONF_WIDGET_EVENTS_COUNT] = 3;
        $this->configs[self::CONF_EVENT_USERS_COUNT_ON_PAGE] = 30;
        $this->configs[self::CONF_WIDGET_EVENTS_COUNT_OPTION_LIST] = array(3 => 3, 5 => 5, 10 => 10, 15 => 15, 20 => 20);
    }

    /**
     * @return array
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * Saves event dto.
     *
     * @param EVENT_BOL_Event $event
     */
    public function saveEvent( EVENT_BOL_Event $event )
    {
        $this->eventDao->save($event);
    }

    public function createEvent($data, $userId, $startStamp, $endStamp, $imagePosted, $endDateFlag, $filePath){
        $event = new EVENT_BOL_Event();
        $event->setStartTimeStamp($startStamp);
        $event->setEndTimeStamp($endStamp);
        $event->setCreateTimeStamp(time());
        $event->setTitle(UTIL_HtmlTag::stripTagsAndJs($data['title']));
        $event->setLocation(UTIL_HtmlTag::autoLink(strip_tags($data['location'])));
        $event->setWhoCanView((int) $data['who_can_view']);
        $event->setWhoCanInvite((int) $data['who_can_invite']);
        $event->setDescription($data['desc']);
        $event->setUserId($userId);
        $event->setEndDateFlag( $endDateFlag );
        $event->setStartTimeDisable( $data['start_time'] == 'all_day' );
        $event->setEndTimeDisable( $data['end_time'] == 'all_day' );

        if ( $imagePosted )
        {
            $event->setImage(IISSecurityProvider::generateUniqueId());
        }

        $serviceEvent = new OW_Event(EVENT_BOL_EventService::EVENT_ON_CREATE_EVENT, array(
            'eventDto' => $event,
            "imageValid" => $imagePosted,
            "imageTmpPath" => $filePath
        ));
        OW::getEventManager()->trigger($serviceEvent);

        $this->saveEvent($event);

        if ( $imagePosted )
        {
            $this->saveEventImage($filePath, $event->getImage());
        }

        $categoryStatus = null;
        if(isset($data['categoryStatus'])){
            $categoryStatus = $data['categoryStatus'];
        }
        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ADD_CATEGORY_TO_EVENT, array('eventId' => $event->getId(), 'categoryId' => $categoryStatus)));

        $eventUser = new EVENT_BOL_EventUser();
        $eventUser->setEventId($event->getId());
        $eventUser->setUserId($userId);
        $eventUser->setTimeStamp(time());
        $eventUser->setStatus(EVENT_BOL_EventService::USER_STATUS_YES);
        $this->saveEventUser($eventUser);

        $serviceEvent = new OW_Event(EVENT_BOL_EventService::EVENT_AFTER_CREATE_EVENT, array(
            'eventId' => $event->id,
            'eventDto' => $event
        ), array(

        ));
        OW::getEventManager()->trigger($serviceEvent);

        $event = EVENT_BOL_EventService::getInstance()->findEvent($event->getId());

        if ( $event->status == EVENT_BOL_EventService::MODERATION_STATUS_ACTIVE )
        {
            BOL_AuthorizationService::getInstance()->trackAction('event', 'add_event');
        }

        return $event;
    }

    /**
     * Makes and saves event standard image and icon.
     *
     * @param string $imagePath
     * @param integer $imageId
     */
    public function saveEventImage( $tmpPath, $imageId )
    {
        $event = new OW_Event(self::EVENT_BEFORE_IMAGE_UPDATE, array(
            "tmpPath" => $tmpPath,
            "eventId" => $imageId
        ), array(
            "tmpPath" => $tmpPath
        ));
        OW::getEventManager()->trigger($event);
        $data = $event->getData();
        $imagePath = $data["tmpPath"];
        
        $storage = OW::getStorage();
        
        if ( $storage->fileExists($this->generateImagePath($imageId)) )
        {
            $storage->removeFile($this->generateImagePath($imageId));
            $storage->removeFile($this->generateImagePath($imageId, false));
        }

        $pluginfilesDir = OW::getPluginManager()->getPlugin('event')->getPluginFilesDir();

        $tmpImgPath = $pluginfilesDir . 'img_' .IISSecurityProvider::generateUniqueId() . '.jpg';
        $tmpIconPath = $pluginfilesDir . 'icon_' . IISSecurityProvider::generateUniqueId() . '.jpg';

        $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN, array('source' => $imagePath, 'destination' => $tmpImgPath)));
        if(isset($checkAnotherExtensionEvent->getData()['destination'])){
            $tmpImgPath = $checkAnotherExtensionEvent->getData()['destination'];
        }

        $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN, array('source' => $imagePath, 'destination' => $tmpIconPath)));
        if(isset($checkAnotherExtensionEvent->getData()['destination'])){
            $tmpIconPath = $checkAnotherExtensionEvent->getData()['destination'];
        }

        $image = new UTIL_Image($imagePath);
        $image->resizeImage(400, null)->saveImage($tmpImgPath)
            ->resizeImage(100, 100, true)->saveImage($tmpIconPath);
        
        $storage->copyFile($tmpIconPath, $this->generateImagePath($imageId));
        $storage->copyFile($tmpImgPath,$this->generateImagePath($imageId, false));

        OW::getEventManager()->trigger(new OW_Event(self::EVENT_AFTER_IMAGE_UPDATE, array(
            "tmpPath" => $tmpPath,
            "eventId" => $imageId
        )));

        OW::getStorage()->removeFile($imagePath);
        OW::getStorage()->removeFile($tmpImgPath);
        OW::getStorage()->removeFile($tmpIconPath);
    }

    /**
     * Deletes event.
     *
     * @param integer $eventId
     */
    public function deleteEvent( $eventId )
    {
        $eventDto = $this->eventDao->findById((int) $eventId);

        if ( $eventDto === null )
        {
            return;
        }
        BOL_CommentService::getInstance()->deleteEntityComments('event', $eventId);
        $eventIisEventPlusFiles = new OW_Event('iiseventplus.delete.files', array('eventId'=>$eventId));
        OW::getEventManager()->trigger($eventIisEventPlusFiles);
        $e = new OW_Event(self::EVENT_ON_DELETE_EVENT, array('eventId' => (int) $eventId));
        OW::getEventManager()->trigger($e);

        if( !empty($eventDto->image) )
        {
            $storage = OW::getStorage();
            $storage->removeFile($this->generateImagePath($eventDto->image));
            $storage->removeFile($this->generateImagePath($eventDto->image, false));
        }

        $this->eventUserDao->deleteByEventId($eventId);
        $this->eventInviteDao->deleteByEventId($eventId);
        $this->eventDao->deleteById($eventId);
        OW::getLogger()->writeLog(OW_Log::INFO, 'delete_event', ['actionType'=>OW_Log::DELETE, 'enType'=>'event', 'enId'=>$eventId]);
        BOL_InvitationService::getInstance()->deleteInvitationByEntity('event', $eventId);
        BOL_InvitationService::getInstance()->deleteInvitationByEntity('event-invitation', $eventId);
    }

    /**
     * Returns event image and icon path.
     *
     * @param integer $imageId
     * @param boolean $icon
     * @return string
     */
    public function generateImagePath( $imageId, $icon = true )
    {
        $imagesDir = OW::getPluginManager()->getPlugin('event')->getUserFilesDir();
        $ext = '.jpg';
        $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN, array('fullPath' => $imagesDir . ( $icon ? 'event_icon_' : 'event_image_' ) . $imageId)));
        if(isset($checkAnotherExtensionEvent->getData()['ext'])){
            $ext = $checkAnotherExtensionEvent->getData()['ext'];
        }
        return $imagesDir . ( $icon ? 'event_icon_' : 'event_image_' ) . $imageId . $ext;
    }

    /**
     * Returns event image and icon url.
     * 
     * @param integer $imageId
     * @param boolean $icon
     * @param $returnPath
     * @return string
     */
    public function generateImageUrl( $imageId, $icon = true, $returnPath = false )
    {
        return OW::getStorage()->getFileUrl($this->generateImagePath($imageId, $icon), $returnPath);
    }

    /**
     * Returns default event image url.
     */
    public function generateDefaultImageUrl()
    {
        return OW::getPluginManager()->getPlugin('event')->getStaticUrl() . 'img/default_event_image.png';
    }

    /**
     * Finds event by id.
     *
     * @param integer $id
     * @return EVENT_BOL_Event
     */
    public function findEvent( $id )
    {
        return $this->eventDao->findById((int) $id);
    }

    /**
     * Returns event users with provided status.
     *
     * @param integer $eventId
     * @param integer $status
     * @return array<EVENT_BOL_EventUser>
     */
    public function findEventUsers( $eventId, $status, $page, $usersCount = null )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $usersCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENT_USERS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventUserDao->findListByEventIdAndStatus($eventId, $status, $first, $count);
    }

    /**
     * Returns event users with provided status.
     *
     * @param integer $eventId
     * @return array<EVENT_BOL_EventUser>
     */
    public function findAllUsersForEvent( $eventId )
    {

        return $this->eventUserDao->findListByEventId($eventId);
    }

    /**
     * Returns users count for provided event and status.
     *
     * @param integer $eventId
     * @param integer $status
     * @return integer
     */
    public function findEventUsersCount( $eventId, $status )
    {
        return (int) $this->eventUserDao->findUsersCountByEventIdAndStatus($eventId, $status);
    }

    /**
     * Saves event user objects.
     *
     * @param EVENT_BOL_EventUser $eventUser
     */
    public function saveEventUser( EVENT_BOL_EventUser $eventUser )
    {
        $this->eventUserDao->save($eventUser);
    }

    /**
     * Saves event user objects.
     *
     * @param EVENT_BOL_EventUser $eventUser
     */
    public function addEventUser( $userId, $eventId, $status, $timestamp = null )
    {
        $statusList = array( EVENT_BOL_EventUserDao::VALUE_STATUS_YES, EVENT_BOL_EventUserDao::VALUE_STATUS_MAYBE, EVENT_BOL_EventUserDao::VALUE_STATUS_NO );

        if( (int) $userId <= 0 || $eventId <=0 || !in_array($status, $statusList) )
        {
            return null;
        }

        $event = $this->findEvent($eventId);

        if( empty($event) )
        {
            return null;
        }

        if ( !isset($timestamp) )
        {
            $timestamp = time();
        }

        $eventUser = $this->findEventUser($eventId, $userId);

        if ( empty($eventUser) )
        {
            $eventUser = new EVENT_BOL_EventUser();

            $eventUser->eventId = $eventId;
            $eventUser->userId = $userId;
            $eventUser->timeStamp = $timestamp;
        }

        $eventUser->status = $status;
        
        $this->eventUserDao->save($eventUser);

        return $eventUser;
    }

    /**
     * Finds event-user object.
     *
     * @param integer $eventId
     * @param integer $userId
     * @return EVENT_BOL_EventUser
     */
    public function findEventUser( $eventId, $userId )
    {
        return $this->eventUserDao->findObjectByEventIdAndUserId($eventId, $userId);
    }

    /**
     * Checks if user can view and join event.
     *
     * @param integer $eventId
     * @param integer $userId
     * @return boolean
     */
    public function canUserView( $eventId, $userId )
    {
        $event = $this->eventDao->findById($eventId);
        /* @var $event EVENT_BOL_Event */
        if ( $event === null )
        {
            return false;
        }

        $userEvent = $this->eventUserDao->findObjectByEventIdAndUserId($eventId, $userId);

        if ( $event->getWhoCanView() === self::CAN_VIEW_INVITATION_ONLY && $userEvent === null )
        {
            return false;
        }

        return true;
    }

    /**
     * Checks if user can invite to event.
     *
     * @param integer $eventId
     * @param integer $userId
     * @return boolean
     */
    public function canUserInvite( $eventId, $userId )
    {
        $event = $this->eventDao->findById($eventId);
        /* @var $event EVENT_BOL_Event */
        if ( $event === null || ( $event->getWhoCanInvite() == self::CAN_INVITE_CREATOR && $userId != $event->getUserId() ) )
        {
            return false;
        }

        $userEvent = $this->eventUserDao->findObjectByEventIdAndUserId($eventId, $userId);

        if ( $userEvent === null || $userEvent->getStatus() != self::USER_STATUS_YES )
        {
            return false;
        }

        return true;
    }

    /**
     * Returns all latest events list ids
     *
     * @param integer $first
     * @param integer $count
     * @return array<EVENT_BOL_Event>
     */
    public function findAllLatestPublicEventsIds( $first, $count )
    {
        return $this->eventDao->findAllLatestPublicEventsIds($first, $count);
    }

    /**
     * Returns latest events list.
     *
     * @param integer $page
     * @return array<EVENT_BOL_Event>
     */
    public function findPublicEvents( $page, $eventsCount = null, $past = false )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findPublicEvents($first, $count, $past);
    }


    /**
     * @author Mohammad Agha Abbasloo
     * @param integer $page
     * @param $eventsCount
     * @param $userId
     * @param $userStatus
     * @param $past
     * @param array <eventIds>
     * @param $addUnapproved
     * @param $isPublic
     * @param $searchTitle
     * @return array<EVENT_BOL_Event>
     */
    public function findPublicEventsByFiltering($page, $eventsCount = null,$userId=null,$userStatus, $past ,$eventIds = array(), $addUnapproved , $isPublic, $searchTitle)
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            if($eventsCount == null){
                $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            }else{
                $count = (int) $eventsCount;
            }
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findPublicEventsByFiltering($userId,$userStatus,$first, $count, $past , $eventIds, $addUnapproved, $isPublic, $searchTitle);
    }

    public function findPublicEventsByFilteringInAdvanceSearch($first,$eventsCount = null,$userId=null,$userStatus, $past ,$eventIds = array(), $addUnapproved , $isPublic, $searchTitle)
    {
        return $this->eventDao->findPublicEventsByFiltering($userId,$userStatus,$first, $eventsCount, $past , $eventIds, $addUnapproved, $isPublic, $searchTitle);
    }
    /**
     * @author Mohammad Agha Abbasloo
     * @param $userId
     * @param $userStatus
     * @param $past
     * @param array <eventIds>
     * @param $addUnapproved
     * @param $isPublic
     * @return count of events number
     */
    public function findPublicEventsByFilteringCount($userId=null,$userStatus, $past ,$eventIds = array(), $addUnapproved = false,$isPublic=true,$searchTitle)
    {
        return (int)$this->eventDao->findPublicEventsByFilteringCount($userId,$userStatus, $past , $eventIds, $addUnapproved,$isPublic,$searchTitle);
    }


    /**
     * @author Mohammad Agha Abbasloo
     * @param integer $page
     * @param $eventsCount
     * @param $userId
     * @param $userStatus
     * @param $past
     * @param array <eventIds>
     * @param $addUnapproved
     * @param $isPublic
     * @param $searchTitle
     * @return array<EVENT_BOL_Event>
     */
    public function findEventsForUser($page, $eventsCount = null,$userId=null,$userStatus, $past ,$eventIds = array(), $addUnapproved , $isPublic, $searchTitle)
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findUserEvents($userId,$userStatus,$first, $count, $past , $eventIds, $addUnapproved, $isPublic, $searchTitle);
    }

    /**
     * @author Mohammad Agha Abbasloo
     * @param $userId
     * @param $userStatus
     * @param $past
     * @param array <eventIds>
     * @param $addUnapproved
     * @param $isPublic
     * @return count of events number
     */
    public function findEventsForUserCount($userId=null,$userStatus, $past ,$eventIds = array(), $addUnapproved = false,$isPublic=true,$searchTitle)
    {
        return (int)$this->eventDao->findUserEventsCount($userId,$userStatus, $past , $eventIds, $addUnapproved,$isPublic,$searchTitle);
    }


    /**
     * @author Mohammad Agha Abbasloo
     * @param $eventsCount
     * @param $userId
     * @return array<EVENT_BOL_Event>
     */
    public function findUpComingEventsForUser($eventsCount = null,$userId=null)
    {
        if(!isset($eventsCount))
        {
            $eventsCount = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
        }
        return $this->eventDao->findUpComingEventsForUser($eventsCount, $userId);
    }

    /**
     * @author Mohammad Agha Abbasloo
     * @param $userId
     * @return integer
     */
    public function findUpComingEventsForUserCount($userId=null)
    {
        return (int)$this->eventDao->findUpComingEventsForUserCount($userId);
    }

    /**
     * Returns latest events count.
     *
     * @return integer
     */
    public function findPublicEventsCount( $past = false )
    {
        return $this->eventDao->findPublicEventsCount($past);
    }

    /**
     * Invites user to event.
     *
     * @param integer $eventId
     * @param integer $userId
     * @param integer $inviterId
     *
     * @return EVENT_BOL_EventInvite
     */
    public function inviteUser( $eventId, $userId, $inviterId )
    {
        $event = $this->findEvent($eventId);

        if ( $event === null )
        {
            return false;
        }

        $eventInvite = new EVENT_BOL_EventInvite();
        $eventInvite->setEventId($eventId);
        $eventInvite->setInviterId($inviterId);
        $eventInvite->setUserId($userId);
        $eventInvite->setTimeStamp(time());
        $eventInvite->setDisplayInvitation(true);

        $this->eventInviteDao->save($eventInvite);

        return $eventInvite;
    }

    /**
     * Returns event invitation for user.
     *
     * @param integer $eventId
     * @param integer $userId
     * @return EVENT_BOL_EventInvite
     */
    public function findEventInvite( $eventId, $userId )
    {
        return $this->eventInviteDao->findObjectByUserIdAndEventId($eventId, $userId);
    }

    /**
     * Finds events for user
     *
     * @param integer $userId
     * @return array
     */
    public function findUserEvents( $userId, $page, $eventsCount = null )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findUserCreatedEvents($userId, $first, $count);
    }

    /**
     * Returns user created events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function findUsersEventsCount( $userId )
    {
        return $this->eventDao->findUserCretedEventsCount($userId);
    }

    /**
     * Returns list of user participating events.
     *
     * @param integer $userId
     * @param integer $page
     * @param integer $count
     * @return array
     */
    public function findUserParticipatedEvents( $userId, $page, $eventsCount = null, $addUnapproved = true  )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findUserEventsWithStatus($userId, self::USER_STATUS_YES, $first, $count, $addUnapproved );
    }

    /***
     * @param $ids
     * @param $page
     * @param null $eventsCount
     * @param bool $addUnapproved
     * @return array
     */
    public function findEventsWithIds( $ids, $page, $eventsCount = null, $addUnapproved = true  )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findEventsWithIds($ids, $first, $count, $addUnapproved );
    }

    /**
     * Returns user participated events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function findUserParticipatedEventsCount( $userId, $addUnapproved = true )
    {
        return $this->eventDao->findUserEventsCountWithStatus($userId, self::USER_STATUS_YES, $addUnapproved);
    }

    /**
     * Returns list of user participating public events.
     *
     * @param integer $userId
     * @param integer $page
     * @param integer $count
     * @return array
     */
    public function findUserParticipatedPublicEvents( $userId, $page, $eventsCount = null )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findPublicUserEventsWithStatus($userId, self::USER_STATUS_YES, $first, $count);
    }

    /**
     * Returns user participated public events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function findUserParticipatedPublicEventsCount( $userId )
    {
        return $this->eventDao->findPublicUserEventsCountWithStatus($userId, self::USER_STATUS_YES);
    }

    /**
     * Returns user participated public events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function hideInvitationByUserId( $userId )
    {
        return $this->eventInviteDao->hideInvitationByUserId($userId);
    }

    /**
     * Prepares data for ipc listing.
     *
     * @param array<EVENT_BOL_Event> $events
     * @return array
     */
    public function getListingData( array $events )
    {
        $resultArray = array();

        /* @var $eventItem EVENT_BOL_Event */
        foreach ( $events as $eventItem )
        {
            $title = UTIL_String::truncate(strip_tags($eventItem->getTitle()), 300, "...") ;
            $sentenceCorrected = false;
            $showMore = false;
            if ( mb_strlen($eventItem->getDescription()) > 300 )
            {
                $sentence = strip_tags($eventItem->getDescription());
                $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_HALF_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
                if(isset($event->getData()['correctedSentence'])){
                    $sentence = $event->getData()['correctedSentence'];
                    $sentenceCorrected = true;
                }
                $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
                if(isset($event->getData()['correctedSentence'])){
                    $sentence = $event->getData()['correctedSentence'];
                    $sentenceCorrected = true;
                }
            }
            if($sentenceCorrected){
                $content = $sentence.'...';
                $showMore = true;
            }
            else{
                $content = UTIL_String::truncate(strip_tags($eventItem->getDescription()), 300, "...");
            }
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $content)));
            if (isset($stringRenderer->getData()['string'])) {
                $content = ($stringRenderer->getData()['string']);
            }

            $resultArray[$eventItem->getId()] = array(
                'content' => $content,
                'title' => $title,
                'showMore' => $showMore,
                'eventUrl' => OW::getRouter()->urlForRoute('event.view', array('eventId' => $eventItem->getId())),
                'imageSrc' => ( $eventItem->getImage() ? $this->generateImageUrl($eventItem->getImage(), true) : $this->generateDefaultImageUrl() ),
                'imageTitle' => $title
            );
        }

        return $resultArray;
    }

    public function getEventUrl( $eventId )
    {
        return OW::getRouter()->urlForRoute('event.view', array('eventId' => (int)$eventId));
    }
    
    /**
     * Prepares data for ipc listing with toolbar.
     *
     * @param array<EVENT_BOL_Event> $events
     * @return array
     */
    public function getListingDataWithToolbar( array $events, $toolbarList = array() )
    {
        $resultArray = $this->getListingData($events);
        $userService = BOL_UserService::getInstance();

        $idArray = array();

        /* @var $event EVENT_BOL_Event */
        foreach ( $events as $event )
        {
            $idArray[] = $event->getUserId();
        }

        $usernames = $userService->getDisplayNamesForList($idArray);
        $urls = $userService->getUserUrlsForList($idArray);

        $language = OW::getLanguage();
        /* @var $eventItem EVENT_BOL_Event */
        foreach ( $events as $eventItem )
        {
            $resultArray[$eventItem->getId()]['toolbar'][] = array('label' => $usernames[$eventItem->getUserId()], 'href' => $urls[$eventItem->getUserId()], 'class' => 'ow_icon_control ow_ic_user');
            $resultArray[$eventItem->getId()]['toolbar'][] = array('label' => UTIL_DateTime::formatSimpleDate($eventItem->getStartTimeStamp(),$eventItem->getStartTimeDisable()), 'class' => 'ow_ipc_date');

            if ( !empty($toolbarList[$eventItem->getId()]) )
            {
                $resultArray[$eventItem->getId()]['toolbar'] = array_merge($resultArray[$eventItem->getId()]['toolbar'], $toolbarList[$eventItem->getId()]);
            }
            
            /* if( !empty($isInviteList) )
            {
                $resultArray[$eventItem->getId()]['toolbar'][] = array('label' => $language->text('event', 'ignore_request'),'href' => 'event.invite_acept');
                $resultArray[$eventItem->getId()]['toolbar'][] = array('label' => $language->text('event', 'accept_request'),'href' => 'event.invite_decline');
            }*/
        }
        //printVar($resultArray);
        return $resultArray;
    }

    public function getUserListsArray()
    {
        return array(
            self::USER_STATUS_YES => 'yes',
            self::USER_STATUS_MAYBE => 'maybe',
            self::USER_STATUS_NO => 'no'
        );
    }

    /**
     * Returns user invited events.
     *
     * @param integer $userId
     * @param integer $page
     * @param integer $eventsCount
     * @return array<EVENT_BOL_Event>
     */
    public function findUserInvitedEvents( $userId, $page, $eventsCount = null )
    {
        if ( $page === null )
        {
            $first = 0;
            $count = (int) $eventsCount;
        }
        else
        {
            $page = ( $page === null ) ? 1 : (int) $page;
            $count = $this->configs[self::CONF_EVENTS_COUNT_ON_PAGE];
            $first = ( $page - 1 ) * $count;
        }

        return $this->eventDao->findUserInvitedEvents($userId, $first, $count);
    }

    /**
     * Returns user invited events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function findUserInvitedEventsCount( $userId )
    {
        return $this->eventDao->findUserInvitedEventsCount($userId);
    }

    /**
     * Returns displayed user invited events count.
     *
     * @param integer $userId
     * @return integer
     */
    public function findDisplayedUserInvitationCount( $userId )
    {
        return $this->eventDao->findDisplayedUserInvitationCount($userId);
    }

    /**
     * Deletes all event invites for provided user.
     *
     * @param integer $eventId
     * @param integer $userId
     */
    public function deleteUserEventInvites( $eventId, $userId )
    {
        $this->eventInviteDao->deleteByUserIdAndEventId($eventId, $userId);
    }

    /**
     * Deletes all user events.
     *
     * @param integer $userId
     */
    public function deleteUserEvents( $userId )
    {
        $events = $this->eventDao->findAllUserEvents($userId);

        /* @var $event EVENT_BOL_Event */
        foreach ( $events as $event )
        {
            $this->deleteEvent($event->getId());
        }
    }

    /**
     * returns invited userId list.
     *
     * @param integer $eventId
     */
    public function findInviteUserListByEventId( $eventId )
    {
        $inviteList = $this->eventInviteDao->findInviteListByEventId($eventId);

        $userList = array();

        foreach ( $inviteList as $invite )
        {
            $userList[] = $invite->userId;
        }

        return $userList;
    }

    public function findUserListForInvite( $eventId, $first = 0, $count = 100)
    {
        $friendList = null;

        if ( OW::getEventManager()->call('plugin.friends') )
        {
            $count = 1000;
            $friendList = OW::getEventManager()->call('plugin.friends.get_friend_list', array('userId' => OW::getUser()->getId()));

            if ( empty($friendList) || !is_array($friendList) )
            {
                $count = 100;
                $friendList = array();
            }
        }
        return $this->eventInviteDao->findUserListForInvite($eventId, $first, $count, $friendList );
    }
    
    public function getContentMenu()
    {
        $menuItems = array();

        if ( OW::getUser()->isAuthenticated() )
        {
            $listNames = array(
                'joined' => array('iconClass' => 'ow_ic_friends'),
                'invited' => array('iconClass' => 'ow_ic_invite'),
                'past' => array('iconClass' => 'ow_ic_reply'),
                'latest' => array('iconClass' => 'ow_ic_calendar')
            );
        }
        else
        {
            $listNames = array(
                'past' => array('iconClass' => 'ow_ic_reply'),
                'latest' => array('iconClass' => 'ow_ic_calendar')
            );
        }
        
        $i=1;
        foreach ( $listNames as $listKey => $listArr )
        {
            $menuItem = new BASE_MenuItem();
            $menuItem->setKey($listKey);
            $menuItem->setUrl(OW::getRouter()->urlForRoute('event.view_event_list', array('list' => $listKey)));
            $menuItem->setLabel(OW::getLanguage()->text('event', 'common_list_type_' . $listKey . '_label'));
            $menuItem->setOrder($i++);
            $menuItem->setIconClass($listArr['iconClass']);
            $menuItems[] = $menuItem;
        }
        
        $event = new BASE_CLASS_EventCollector('event.add_content_menu_item');
        OW::getEventManager()->getInstance()->trigger($event);
        
        $data = $event->getData();
        
        if ( !empty($data) && is_array($data) )
        {
            $menuItems = array_merge($menuItems, $data);
        }
        $validListsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ADD_LIST_TYPE_TO_EVENT,array('menuItems' => $menuItems)));
        if(isset($validListsEvent->getData()['menuItems'])){
            $menuItems = $validListsEvent->getData()['menuItems'];
        }
        $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
        if(isset($mobileEvent->getData()['isMobileVersion'])&& $mobileEvent->getData()['isMobileVersion']==true) {
            return new BASE_MCMP_ContentMenu($menuItems);
        }

        return new BASE_CMP_ContentMenu($menuItems);
    }
    
    public function clearEventInvitations( $eventId )
    {        
        BOL_InvitationService::getInstance()->deleteInvitationByEntity('event', (int)$eventId);
        BOL_InvitationService::getInstance()->deleteInvitationByEntity('event-invitation', (int)$eventId);
        BOL_InvitationService::getInstance()->deleteInvitationByEntity(EVENT_CLASS_InvitationHandler::INVITATION_JOIN, (int)$eventId);
        
        $this->eventInviteDao->deleteByEventId($eventId);
    }
    
    public function findCronExpiredEvents( $first, $count )
    {
        return $this->eventDao->findExpiredEventsForCronJobs($first, $count);
    }
    
    public function findByIdList( $idList )
    {
        return $this->eventDao->findByIdList($idList);
    }

    public function findUsersCountByEventIdListAndStatus( $eventIdList, $status ){
        return $this->eventUserDao->findUsersCountByEventIdListAndStatus($eventIdList, $status );
    }

    public function findUsersCountByEventIdList( $eventIdList ){
        return $this->eventUserDao->findUsersCountByEventIdList( $eventIdList );
    }
}