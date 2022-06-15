<?php

/**
 * @author Yaser Alimardani <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisgroupsplus
 * @since 1.0
 */
class IISGROUPSPLUS_CMP_PendingInvitation extends BASE_CLASS_Widget
{

    public function __construct( BASE_CLASS_WidgetParameter $params )
    {
        parent::__construct();
        $groupId = null;
        if(isset($params->additionalParamList) && isset($params->additionalParamList['entityId'])){
            $groupId = $params->additionalParamList['entityId'];
        }
        $users = GROUPS_BOL_Service::getInstance()->findAllInviteList($groupId);
        $usersInformation = array();
        $userCount = 0;
        if($users!=null){
            $userCount = 10;
            $counter = 0;
            foreach($users as $user){
                if(in_array($user->userId,$usersInformation)){
                    continue;
                }
                if($counter<$userCount){
                    $counter++;
                    $usersInformation[] = $user->userId;
                }
            }
        }
        $more = false;
        if(sizeof($users)>$userCount){
            $more = true;
        }
        $this->assign('more', $more);
        $data = array();
        if ( !empty($usersInformation) )
        {
            $data = BOL_AvatarService::getInstance()->getDataForUserAvatars($usersInformation);
        }
        $this->assign("data", $data);
        $this->assign("userIdList", $usersInformation);
        $this->assign('ShowPendingAllUsersUrl', "OW.ajaxFloatBox('IISGROUPSPLUS_CMP_PendingUsers', {groupId: '".$groupId."'} , {width:700, iconClass: 'ow_ic_add'});");
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_WRAP_IN_BOX => true,
            self::SETTING_TITLE => OW_Language::getInstance()->text('iisgroupsplus', 'pending_invitation'),
            self::SETTING_ICON => self::ICON_USER
        );
    }

    public static function getAccess()
    {
        return self::ACCESS_MEMBER;
    }
}