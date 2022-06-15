<?php

class IISNEWSFEEDPLUS_CMP_ForwardPostFloatBox extends OW_Component
{
    /**
     * @var string
     */
    private $buttonLabel;
    /**
     * @var string
     */
    private $countLabel;
    /**
     * @var string
     */
    private $headingLabel;
    /**
     * @var array
     */
    private $idList;
    /**
     * @var array
     */
    private $currentId;

    /**
     * @var array
     */
    private $actionId;

    /*
     * @var string
     */
    private $privacy;

    /*
     * @var string
     */
    private $visibility;
    /*
     * @var string
     */
    private $feedType;

    /*
     * @var string
     */
    private $sectionId;

    /**
     * @var array
     */
    private $feedId;


    public function __construct($iconClass, $aid, $fid,$vis,$pri,$fty,$sid)
    {
        if(!isset($aid) || !isset($fid) || !isset($vis) || !isset($pri) || !isset($fty) || !isset($sid))
        {
            throw new Redirect404Exception();
        }
        if(!OW::getUser()->isAuthenticated())
        {
            throw new Redirect404Exception();
        }
        if($fty!='groups' && $fty!='user')
        {
            throw new Redirect404Exception();
        }
        $this->feedType=$fty;
        $this->sectionId=$sid;
        $this->feedId=$fid;
        if($fty=='groups')
        {
            $groupPlugin = BOL_PluginDao::getInstance()->findPluginByKey('groups');
            if (!isset($groupPlugin) || !$groupPlugin->isActive()) {
                throw new Redirect404Exception();
            }
            $group = GROUPS_BOL_Service::getInstance()->findGroupById($fid);
            if (!isset($group)) {
                throw new Redirect404Exception();
            }
            $canView = GROUPS_BOL_Service::getInstance()->isCurrentUserCanView($group);
            if (!$canView) {
                throw new Redirect404Exception();
            }
            $this->currentId=$fid;
            parent::__construct();
            $this->visibility = $vis;
            $this->privacy = $pri;
            $this->countLabel = OW::getLanguage()->text('iisnewsfeedplus', 'group_list_select_count_label');
            $this->buttonLabel = OW::getLanguage()->text('iisnewsfeedplus', 'group_list_select_button_label');
            $this->actionId=$aid;
        }
        else if($fty=='user')
        {
            /*
             * TODO
             * check user has access to the activity
             */
            parent::__construct();
            $this->visibility = $vis;
            $this->privacy = $pri;
            $this->countLabel = OW::getLanguage()->text('iisnewsfeedplus', 'group_list_select_count_label');
            $this->buttonLabel = OW::getLanguage()->text('iisnewsfeedplus', 'group_list_select_button_label');
            $this->actionId=$aid;
        }


    }

    /**
     * @param string $buttonLabel
     */
    public function setButtonLabel( $buttonLabel )
    {
        $this->buttonLabel = $buttonLabel;
    }

    /**
     * @param string $countLabel
     */
    public function setCountLabel( $countLabel )
    {
        $this->countLabel = $countLabel;
    }

    /**
     * @param string $generalLabel
     */
    public function setGeneralLabel( $generalLabel )
    {
        $this->headingLabel = $generalLabel;
    }

    public function onBeforeRender()
    {
        /*
         * select groups to forward
         */
        $this->assign('sectionId',$this->sectionId);
        $this->assign('sections', IISNEWSFEEDPLUS_BOL_Service::getInstance()->getForwardSections($this->sectionId,$this->actionId,$this->feedId,$this->visibility,$this->privacy,$this->feedType));
        /*
         * section 1: forward to group(s)
         */

        OW::getLanguage()->addKeyForJs('base', 'avatar_user_select_empty_list_message');
        OW::getLanguage()->addKeyForJs('iisnewsfeedplus', 'users_forward_success_message');
        OW::getLanguage()->addKeyForJs('iisnewsfeedplus', 'group_select_empty_list_message');
        OW::getLanguage()->addKeyForJs('iisnewsfeedplus', 'error_in_forward_progress');
        OW::getLanguage()->addKeyForJs('iisnewsfeedplus', 'groups_invite_success_message');
        if($this->sectionId==1) {
            $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION, array('check' => true)));
            if (isset($mobileEvent->getData()['isMobileVersion']) && $mobileEvent->getData()['isMobileVersion'] == true) {
                $this->assign('isMobileVersion', true);
            }
            parent::onBeforeRender();
            $this->idList = array();
            $contexId = UTIL_HtmlTag::generateAutoId('cmp');
            $this->assign('contexId', $contexId);

            $userGroups = GROUPS_BOL_Service::getInstance()->findUserGroupList(OW::getUser()->getId());
            $groups = array();
            foreach ($userGroups as $userGroup) {
                if (isset($this->currentId) && $userGroup->id == $this->currentId) {
                    continue;
                }
                $event = OW::getEventManager()->trigger(new OW_Event('iisgroupsplus.on.channel.add.widget',array('groupId' => $userGroup->id)));
                if(isset($event->getData()['channelParticipant']) && $event->getData()['channelParticipant']==true) {
                    continue;
                }
                $imageUrl = GROUPS_BOL_Service::getInstance()->getGroupImageUrl($userGroup);
                $groups[$userGroup->id] = array('groupId' => $userGroup->id, 'src' => OW::getRouter()->urlForRoute('groups-view', array('groupId' => $userGroup->id)), 'label' => $userGroup->title, 'imageUrl' => $imageUrl);
                $this->idList[] = $userGroup->id;
            }

            $arrayToAssign = array();
            $jsArray = array();

            foreach ($this->idList as $id) {
                $linkId = UTIL_HtmlTag::generateAutoId('group-select');

                if (!empty($groups[$id])) {
                    $groups[$id]['url'] = 'javascript://';
                }

                $arrayToAssign[$id] = array(
                    'id' => $id,
                    'title' => $groups[$id]['label'],
                    'linkId' => $linkId,
                );

                $groups[$id]['id'] = $id;
                $groups[$id]['title'] = $groups[$id]['label'];
                $groups[$id]['linkId'] = $linkId;

                $jsArray[$id] = array(
                    'linkId' => $linkId,
                    'entityId' => $id
                );
            }
            $this->assign('groups', $groups);
            $forwardResponder = OW::getRouter()->urlFor('IISNEWSFEEDPLUS_CTRL_Forward', 'forward');
            OW::getDocument()->addOnloadScript("
            var cmp = new forwardSelect(" . json_encode($jsArray) . ", '" . $contexId . "', '" . $forwardResponder . "','" . $this->actionId . "', '" . $this->currentId . "', '" . $this->privacy . "', '" . $this->visibility . "', '" . $this->feedType . "','groups');
            cmp.init();  ");
            $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
            if(isset($event->getData()['isMobileVersion']) && $event->getData()['isMobileVersion']==true) {

                OW::getDocument()->addOnloadScript("
$('#instant_search_txt_input').on('change input',function () {
    var q = $(this).val();
    $('.asl_groups .ow_group_list_item').each(function(i,obj){
        if(obj.innerText.indexOf(q)>=0)
            obj.style.display = 'block'
        else
            obj.style.display = 'none'
    });
});
        ");

            }else{
                OW::getDocument()->addOnloadScript("
$('#instant_search_txt_input').on('change input',function () {
    var q = $(this).val();
    $('.asl_groups .ow_group_list_item').each(function(i,obj){
        if(obj.innerText.indexOf(q)>=0)
            obj.style.display = 'inline-block'
        else
            obj.style.display = 'none'
    });
});
        ");
            }
            $this->assign('groupList', $arrayToAssign);

            $langs = array(
                'countLabel' => $this->countLabel,
                'startCountLabel' => (!empty($this->countLabel) ? str_replace('#count#', '0', $this->countLabel) : null),
                'buttonLabel' => $this->buttonLabel,
                'startButtonLabel' => str_replace('#count#', '0', $this->buttonLabel)
            );
            $this->assign('langs', $langs);
        }
        /*
         * section 2: forward to user(s)
         */
        else if($this->sectionId==2)
        {
            parent::onBeforeRender();
            $this->countLabel = OW::getLanguage()->text('base', 'avatar_user_list_select_count_label');
            $contexId = UTIL_HtmlTag::generateAutoId('cmp');
            $this->assign('contexId', $contexId);
            $this->idList = OW::getEventManager()->call('plugin.friends.get_friend_list', array('userId' => OW::getUser()->getId()));
            if ( empty($this->idList) )
            {
                return;
            }

            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($this->idList, true, false, false);
            $this->assign('avatars', $avatars);

            $displayNames = BOL_UserService::getInstance()->getDisplayNamesForList($this->idList);
            $usernames = BOL_UserService::getInstance()->getUserNamesForList($this->idList);
            $orderdList = BOL_UserService::getInstance()->getRecentlyActiveOrderedIdList($this->idList);

            $this->idList = array();

            foreach( $orderdList as $list )
            {
                $this->idList[] =  $list['id'];
            }

            $arrayToAssign = array();
            $jsArray = array();

            foreach ( $this->idList as $id )
            {
                $linkId = UTIL_HtmlTag::generateAutoId('user-select');
                $cssClass="ow_item_set2";
                if ( !empty($avatars[$id]) )
                {
                    $avatars[$id]['url'] = 'javascript://';
                }

                $iisSecurityEssentialPlugin = BOL_PluginDao::getInstance()->findPluginByKey('iissecurityessentials');
                /*
                 * disable selecting user who doesn't allow anyone to write on his/her wall
                 */
                if(isset($iisSecurityEssentialPlugin) && $iisSecurityEssentialPlugin->isActive()) {
                    $whoCanPostPrivacy = IISSECURITYESSENTIALS_BOL_Service::getInstance()->getActionValueOfPrivacy('who_post_on_newsfeed', $id);
                    if ($whoCanPostPrivacy == 'only_for_me') {
                        $linkId ='';
                        $cssClass="ow_item_set2 ow_item_disabled";
                        if ( !empty($avatars[$id]) )
                        {
                            $avatars[$id]['url'] = '';
                        }
                    }
                }
                $arrayToAssign[$id] = array(
                    'id' => $id,
                    'title' => empty($displayNames[$id]) ? '_DISPLAY_NAME_' : $displayNames[$id],
                    'linkId' => $linkId,
                    'username' => $usernames[$id],
                    'class' =>$cssClass
                );

                $jsArray[$id] = array(
                    'linkId' => $linkId,
                    'entityId' => $id
                );
            }

            $forwardResponder = OW::getRouter()->urlFor('IISNEWSFEEDPLUS_CTRL_Forward', 'forward');
            OW::getDocument()->addOnloadScript("
            var cmp = new forwardSelect(" . json_encode($jsArray) . ", '" . $contexId . "', '" . $forwardResponder . "','" . $this->actionId . "', '" . $this->currentId . "', '" . $this->privacy . "', '" . $this->visibility . "', '" . $this->feedType . "','user');
            cmp.init();  ");
            $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
            if(isset($event->getData()['isMobileVersion']) && $event->getData()['isMobileVersion']==true) {
                OW::getDocument()->addOnloadScript("
                    $('#instant_search_txt_input').on('change input',function () {
                        var q = $(this).val();
                        $('.asl_users .owm_user_list_item').each(function(i,obj){
                            if(obj.innerText.indexOf(q)>=0)
                                obj.style.display = 'block'
                            else
                                obj.style.display = 'none'
                        });
                    });
                ");
            }else {
                OW::getDocument()->addOnloadScript("
                    $('#instant_search_txt_input').on('change input',function () {
                        var q = $(this).val();
                        $('.asl_users .ow_user_list_item').each(function(i,obj){
                            if(obj.innerText.indexOf(q)>=0)
                                obj.style.display = 'inline-block'
                            else
                                obj.style.display = 'none'
                        });
                    });
                ");
            }

            $this->assign('users', $arrayToAssign);

            $langs = array(
                'countLabel' => $this->countLabel,
                'startCountLabel' => (!empty($this->countLabel) ? str_replace('#count#', '0', $this->countLabel) : null ),
                'buttonLabel' => $this->buttonLabel,
                'startButtonLabel' => str_replace('#count#', '0', $this->buttonLabel)
            );
            $this->assign('langs', $langs);
        }
    }
}


