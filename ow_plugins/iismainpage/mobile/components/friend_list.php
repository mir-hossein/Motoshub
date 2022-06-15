<?php

/**
 * iismainpage
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismainpage
 * @since 1.0
 */
class IISMAINPAGE_MCMP_FriendList extends OW_MobileComponent
{
    protected $showOnline = true, $list = array();
    protected $listKey;

    public function __construct($listKey, $list, $showOnline)
    {
        parent::__construct();

        $this->list = $list;
        $this->showOnline = $showOnline;

        $this->setTemplate(OW::getPluginManager()->getPlugin('iismainpage')->getMobileCmpViewDir().'friend_list.html');
    }


    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $this->process($this->list, $this->showOnline);
    }

    public function getContextMenu()
    {
        return null;
    }

    protected function process( $idList, $showOnline )
    {
        $userService = BOL_UserService::getInstance();

        if ( empty($idList) )
        {
            $idList = array();
        }
        
        $userList = array();

        $dtoList = BOL_UserService::getInstance()->findUserListByIdList($idList);
        $tmpUserList = array();
        foreach ( $dtoList as $dto )
        {
            $tmpUserList[$dto->id] = array('dto' => $dto,
                'actions' => $this->onCollectProfileActions($dto->id));
        }
        
        foreach ( $idList as $id )
        {
            $userList[$id] = $tmpUserList[$id];
        }

        $avatars = array();
        $usernameList = array();
        $displayNameList = array();
        $onlineInfo = array();

        if ( !empty($idList) )
        {
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList);
            
            foreach ( $avatars as $userId => $avatarData )
            {
                $displayNameList[$userId] = isset($avatarData['title']) ? $avatarData['title'] : '';
                //$avatars[$userId]['label'] = mb_substr($avatars[$userId]['label'], 0, 1);
            }
            $usernameList = $userService->getUserNamesForList($idList);
            if ( $showOnline )
            {
                $onlineInfo = $userService->findOnlineStatusForUserList($idList);
            }

            $ownerIdList = array();
            foreach ( $onlineInfo as $userId => $isOnline )
            {
                $ownerIdList[$userId] = $userId;
            }
            $eventParams = array(
                'action' => 'base_view_my_presence_on_site',
                'ownerIdList' => $ownerIdList,
                'viewerId' => OW::getUser()->getId()
            );
            $permissions = OW::getEventManager()->getInstance()->call('privacy_check_permission_for_user_list', $eventParams);
            foreach ( $onlineInfo as $userId => $isOnline )
            {
                if ( isset($permissions[$userId]['blocked']) && $permissions[$userId]['blocked'] == true )
                    $onlineInfo[$userId] = false;
            }
        }

        $this->assign('onlineInfo', $onlineInfo);
        $this->assign('usernameList', $usernameList);
        $this->assign('avatars', $avatars);
        $this->assign('displayNameList', $displayNameList);
        $this->assign('list', $userList);
    }
    public function onCollectProfileActions($userId)
    {
        $mailboxPlugin = BOL_PluginDao::getInstance()->findPluginByKey('mailbox');
        if(!isset($mailboxPlugin) || !$mailboxPlugin->isActive()) {
            return [];
        }
        $activeModes = MAILBOX_BOL_ConversationService::getInstance()->getActiveModeList();
        $actions = array();
        if (in_array('mail', $activeModes))
        {
            $linkId = IISSecurityProvider::generateUniqueId('send_message');
            $actions[] = (array(
                "label" => OW::getLanguage()->text('mailbox', 'auth_action_label_send_message'),
                "href" => OW::getRouter()->urlForRoute('mailbox_compose_mail_conversation', array('opponentId'=>$userId)),
                "id" => $linkId
            ));
        }

        if (in_array('chat', $activeModes))
        {
            $allowChat = OW::getEventManager()->call('base.online_now_click', array('userId' => OW::getUser()->getId(), 'onlineUserId' => $userId));
            if ($allowChat) {
                $linkId = IISSecurityProvider::generateUniqueId('send_chat');

                $actions[] = (array(
                    "label" => OW::getLanguage()->text('base', 'user_list_chat_now'),
                    "href" => OW::getRouter()->urlForRoute('mailbox_chat_conversation', array('userId' => $userId)),
                    "id" => $linkId
                ));
            }
        }
        return $actions;
    }
}