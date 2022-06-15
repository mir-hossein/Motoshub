<?php

/**
 * iismainpage
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismainpage
 * @since 1.0
 */

class IISMAINPAGE_MCTRL_Index extends OW_MobileActionController
{
    public function index($params)
    {
        if (!OW::getUser()->isAuthenticated()) {
            $ru = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlForRoute('static_sign_in'), array('back_uri' => OW::getRequest()->getRequestUri()));
            OW::getApplication()->redirect($ru);
        }

        $service = IISMAINPAGE_BOL_Service::getInstance();

        $orders = OW::getConfig()->getValue('iismainpage', 'orders');
        $first_menu = 'dashboard';
        if($orders!=''){
            $orders = json_decode($orders, true);
            $first_menu = $orders[0];

            if(!$service->isPluginExist($first_menu) || $service->isDisabled($first_menu)){
                $first_menu = $orders[1];
            }

            if(!$service->isPluginExist($first_menu) || $service->isDisabled($first_menu)){
                $first_menu = $orders[2];
            }

            if(!$service->isPluginExist($first_menu) || $service->isDisabled($first_menu)){
                $first_menu = $orders[3];
            }

            if(!$service->isPluginExist($first_menu) || $service->isDisabled($first_menu)){
                $first_menu = $orders[4];
            }

            if(!$service->isPluginExist($first_menu) || $service->isDisabled($first_menu)){
                $first_menu = $orders[5];
            }

            if(!$service->isPluginExist($first_menu) || $service->isDisabled($first_menu)){
                $first_menu = $orders[6];
            }
        }

        if($first_menu=='dashboard'){
            $this->redirect(OW::getRouter()->urlForRoute('iismainpage.dashboard'));
        }else if($first_menu=='user-groups'){
            $this->redirect(OW::getRouter()->urlForRoute('iismainpage.user.groups'));
        }else if($first_menu=='friends'){
            $this->redirect(OW::getRouter()->urlForRoute('iismainpage.friends'));
        }else if($first_menu=='mailbox'){
            $this->redirect(OW::getRouter()->urlForRoute('iismainpage.mailbox'));
        }else if($first_menu=='settings'){
            $this->redirect(OW::getRouter()->urlForRoute('iismainpage.settings'));
        }else if($first_menu=='notifications'){
            $this->redirect(OW::getRouter()->urlForRoute('iismainpage.notifications'));
        }else if($first_menu=='photos'){
            $this->redirect(OW::getRouter()->urlForRoute('iismainpage.photos'));
        }else if($first_menu=='videos'){
            $this->redirect(OW::getRouter()->urlForRoute('iismainpage.videos'));
        }

        $this->redirect(OW::getRouter()->urlForRoute('iismainpage.dashboard'));
    }

    public function dashboard($params)
    {
        if (!OW::getUser()->isAuthenticated() || !OW::getPluginManager()->isPluginActive('newsfeed')) {
            throw new Redirect404Exception();
        }
        OW::getDocument()->setHeading(OW::getLanguage()->text('base', 'dashboard_heading'));
        $this->assign('userId', OW::getUser()->getId());

        $menuCmp = new IISMAINPAGE_MCMP_Menu('dashboard');
        $this->addComponent('menuCmp', $menuCmp);

        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iismainpage')->getStaticCssUrl() . 'iismainpage.css');
    }

    public function friends($params)
    {
        if (!OW::getUser()->isAuthenticated() || !OW::getPluginManager()->isPluginActive('friends')) {
            throw new Redirect404Exception();
        }
        OW::getDocument()->setHeading(OW::getLanguage()->text('friends', 'notification_section_label'));
        $friendsService = FRIENDS_BOL_Service::getInstance();
        $userId = OW::getUser()->getId();
        $count = IISMAINPAGE_BOL_Service::$item_count;

        $data = $friendsService->findUserFriendsInList($userId, 0, $count);

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'mobile_user_list.js');

        $cmp = new IISMAINPAGE_MCMP_FriendList('latest', $data, true);
        $this->addComponent('list', $cmp);
        $this->assign('listType', 'latest');

        if(OW::getPluginManager()->isPluginActive('iisadvancesearch')){
            $this->assign('find_friends_url', OW::getRouter()->urlForRoute('iisadvancesearch.list.users', array('type'=>'new')));
        }

        OW::getDocument()->addOnloadScript("
            window.mobileUserList = new OW_UserList(" . json_encode(array(
                'component' => 'IISMAINPAGE_MCMP_FriendList',
                'listType' => 'latest',
                'excludeList' => $data,
                'node' => '.owm_user_list',
                'showOnline' => true,
                'count' => $count,
                'responderUrl' => OW::getRouter()->urlForRoute('iismainpage.friends_responder')
            )) . ");
        ", 50);

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iismainpage')->getStaticJsUrl() . 'iismainpage.js');

        $menuCmp = new IISMAINPAGE_MCMP_Menu('friends');
        $this->addComponent('menuCmp', $menuCmp);

        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iismainpage')->getStaticCssUrl() . 'iismainpage.css');
    }

    public function friends_responder($params)
    {
        if (!OW::getRequest()->isAjax()) {
            throw new Redirect404Exception();
        }
        $excludeList = empty($_POST['excludeList']) ? array() : $_POST['excludeList'];
        $showOnline = empty($_POST['showOnline']) ? false : $_POST['showOnline'];
        $count = empty($_POST['count']) ? IISMAINPAGE_BOL_Service::$item_count : (int)$_POST['count'];
        $start = count($excludeList);

        $userId = OW::getUser()->getId();
        $userService = FRIENDS_BOL_Service::getInstance();
        $data = $userService->findUserFriendsInList($userId, $start, $count);

        echo json_encode($data);
        exit;
    }

    public function userGroups($params)
    {
        if (!OW::getUser()->isAuthenticated() || !OW::getPluginManager()->isPluginActive('groups')) {
            throw new Redirect404Exception();
        }
        OW::getDocument()->setHeading(OW::getLanguage()->text('groups', 'group_list_menu_item_my'));
        $groupService = GROUPS_BOL_Service::getInstance();
        $userId = OW::getUser()->getId();
        $count = IISMAINPAGE_BOL_Service::$item_count;

        $tplList = $groupService->findMyGroups($userId, 0, $count);
        $data = array();
        foreach ($tplList as $key => $item) {
            $data[] = $item->id;
        }
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'mobile_user_list.js');

        $cmp = new IISMAINPAGE_MCMP_GroupList('latest', $data, true);
        $this->addComponent('list', $cmp);
        $this->assign('listType', 'latest');

        if(count($tplList) >= $count) {
            OW::getDocument()->addOnloadScript("
            window.mobileUserList = new OW_UserList(" . json_encode(array(
                    'component' => 'IISMAINPAGE_MCMP_GroupList',
                    'listType' => 'latest',
                    'excludeList' => $data,
                    'node' => '.owm_group_list',
                    'showOnline' => true,
                    'count' => $count,
                    'responderUrl' => OW::getRouter()->urlForRoute('iismainpage.user.groups_responder')
                )) . ");
            ", 50);
        }

        $menuCmp = new IISMAINPAGE_MCMP_Menu('user-groups');
        $this->addComponent('menuCmp', $menuCmp);

        if(OW::getUser()->isAuthenticated() && GROUPS_BOL_Service::getInstance()->isCurrentUserCanCreate()){
            $this->assign('groupAddLink', OW::getRouter()->urlForRoute('groups-create'));
        }

        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iismainpage')->getStaticCssUrl() . 'iismainpage.css');
    }

    public function userGroups_responder($params)
    {
        if (!OW::getRequest()->isAjax()) {
            throw new Redirect404Exception();
        }
        $excludeList = empty($_POST['excludeList']) ? array() : $_POST['excludeList'];
        $showOnline = empty($_POST['showOnline']) ? false : $_POST['showOnline'];
        $count = empty($_POST['count']) ? IISMAINPAGE_BOL_Service::$item_count : (int)$_POST['count'];
        $start = count($excludeList);

        $userId = OW::getUser()->getId();
        $groupService = GROUPS_BOL_Service::getInstance();
        $data = $groupService->findMyGroups($userId, $start, $count);

        echo json_encode($data);
        exit;
    }

    public function mailbox($params){
        if (!OW::getUser()->isAuthenticated() || !OW::getPluginManager()->isPluginActive('mailbox')) {
            throw new Redirect404Exception();
        }
       OW::getDocument()->setHeading(OW::getLanguage()->text('mailbox', 'messages_console_title'));
        //--JS for loading
//        $js = "$('.owm_sidebar_top_block').append('<div id=\"console_preloader\"></div>');";
//        $js .= 'OW.bind(\'mailbox.ready\', function(readyStatus){ if (readyStatus == 2) $(\'.iismainpage #console_preloader\').hide()})';
//        OW::getDocument()->addOnloadScript($js);
        //--

//        $cmp = new MAILBOX_MCMP_ConsoleConversationsPage();
//        $this->addComponent('cmp', $cmp);
        $activeModes = MAILBOX_BOL_ConversationService::getInstance()->getActiveModeList();
        $currentSubMenu = 'mail';
        if(isset($params['type'])){
            $currentSubMenu = $params['type'];
        }else{
            if(in_array('mail', $activeModes)) {
                $currentSubMenu = 'mail';
            }
            if(in_array('chat', $activeModes)) {
                $currentSubMenu = 'chat';
            }
        }
        $conversationItemList = array();
        $userId = OW::getUser()->getId();
        $validLists = array();
        if(in_array('mail', $activeModes) && 'mail' == $currentSubMenu) {
            $conversationItemList = MAILBOX_BOL_ConversationDao::getInstance()->findConversationItemListByUserId($userId, array('mail'), 0, 1000);
        }

        if(in_array('chat', $activeModes) && 'chat' == $currentSubMenu) {
            $conversationItemList = MAILBOX_BOL_ConversationDao::getInstance()->findConversationItemListByUserId($userId, array('chat'), 0, 1000);
        }

        if(in_array('chat', $activeModes)) {
            $validLists[] = 'chat';
        }
        if(in_array('mail', $activeModes)) {
            $validLists[] = 'mail';
        }


        foreach($conversationItemList as $i => $conversation)
        {
            $conversationItemList[$i]['timeStamp'] = (int)$conversation['initiatorMessageTimestamp'];
            $conversationItemList[$i]['lastMessageSenderId'] = $conversation['initiatorMessageSenderId'];
            $conversationItemList[$i]['isSystem'] = $conversation['initiatorMessageIsSystem'];
            $conversationItemList[$i]['text'] = $conversation['initiatorText'];

            $conversationItemList[$i]['lastMessageId'] = $conversation['initiatorLastMessageId'];
            $conversationItemList[$i]['recipientRead'] = $conversation['initiatorRecipientRead'];
            $conversationItemList[$i]['lastMessageRecipientId'] = $conversation['initiatorMessageRecipientId'];
            $conversationItemList[$i]['lastMessageWasAuthorized'] = $conversation['initiatorMessageWasAuthorized'];
        }

        $conversationData = MAILBOX_BOL_ConversationService::getInstance()->getConversationItemByConversationIdListForApi( $conversationItemList );
        $this->assign('conversationData', $conversationData);

        if(count($validLists)>1) {
            $subMenuItems = array();
            $order = 0;
            foreach ($validLists as $type) {
                $item = new BASE_MenuItem();
                $item->setLabel(OW::getLanguage()->text('iismainpage', $type));
                $item->setUrl(OW::getRouter()->urlForRoute('iismainpage.mailbox.type', array('type' => $type)));
                $item->setKey($type);
                $item->setOrder($order);
                array_push($subMenuItems, $item);
                $order++;
            }

            $subMenu = new BASE_MCMP_ContentMenu($subMenuItems);
            $el = $subMenu->getElement($currentSubMenu);
            $el->setActive(true);
            $this->addComponent('subMenu', $subMenu);
        }

        $menuCmp = new IISMAINPAGE_MCMP_Menu('mailbox');
        $this->addComponent('menuCmp', $menuCmp);

        OW::getDocument()->addOnloadScript("add_mailbox_search_events('".OW::getRouter()->urlForRoute('iismainpage.mailbox_responder')."')");
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('iismainpage')->getStaticJsUrl() . 'iismainpage.js');
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iismainpage')->getStaticCssUrl() . 'iismainpage.css');
    }

    /***
     * @author Atena Gholamzadeh
     * @param $params
     * @throws Redirect404Exception
     */
    public function mailbox_responder($params)
    {
        if (!OW::getRequest()->isAjax()) {
            throw new Redirect404Exception();
        }
        $q = empty($_POST['q']) ? array() : UTIL_HtmlTag::stripTagsAndJs($_POST['q']);
        $userId = OW::getUser()->getId();

        $result = [];
        $convIds = [];
        $messageResults = MAILBOX_BOL_ConversationService::getInstance()->searchMessagesList($userId, $q);
        $avatarService = BOL_AvatarService::getInstance();

        foreach ($messageResults as $item){
            $opponentId = $item['senderId'];
            if($opponentId == $userId){
                $opponentId = $item['recipientId'];
            }
            $convId = $item['conversationId'];
            $convIds[] = $convId;
            $item['opponentId']=$opponentId;
            $avatar = BOL_AvatarService::getInstance()->getAvatarUrl($opponentId);
            $item['avatarUrl']= isset($avatar) ? $avatar : $avatarService->getDefaultAvatarUrl(2);
            $item['opponentUrl']= BOL_UserService::getInstance()->getUserUrl($opponentId);
            $item['opponentName']= BOL_UserService::getInstance()->getDisplayName($opponentId);
            $item['text'] = MAILBOX_BOL_ConversationService::getInstance()->json_decode_text($item['text']);
            $item['timeString'] = UTIL_DateTime::formatDate((int)$item['timeStamp'], true);
            $item['mode'] = MAILBOX_BOL_ConversationService::getInstance()->getConversationMode((int)$convId);
            if ($item['mode'] == 'chat') {
                $item['conversationUrl'] = OW::getRouter()->urlForRoute('mailbox_chat_conversation', array('userId'=>$opponentId));
            }else {
                $item['conversationUrl'] = OW::getRouter()->urlForRoute('mailbox_mail_conversation', array('convId'=>$convId));
            }
            array_push($result, $item);
        }
        $titleResults = MAILBOX_BOL_ConversationService::getInstance()->searchMailTopicList($userId, $q);
        foreach ($titleResults as $obj){
            $item = [];
            $opponentId = $obj->initiatorId;
            if($opponentId == $userId){
                $opponentId = $obj->interlocutorId;
            }
            $convId = $obj->id;
            if(in_array($convId, $convIds)){
                continue;
            }
            $item['opponentId']=$opponentId;
            $avatar = BOL_AvatarService::getInstance()->getAvatarUrl($opponentId);
            $item['avatarUrl']= isset($avatar) ? $avatar : $avatarService->getDefaultAvatarUrl(2);
            $item['opponentUrl']= BOL_UserService::getInstance()->getUserUrl($opponentId);
            $item['opponentName']= BOL_UserService::getInstance()->getDisplayName($opponentId);
            $item['text'] = $obj->subject;
            $item['timeString'] = UTIL_DateTime::formatDate((int)$item['lastMessageTimestamp'], true);
            $item['mode'] = MAILBOX_BOL_ConversationService::getInstance()->getConversationMode((int)$convId);
            if ($item['mode'] == 'chat') {
                $item['conversationUrl'] = OW::getRouter()->urlForRoute('mailbox_chat_conversation', array('userId'=>$opponentId));
            }else {
                $item['conversationUrl'] = OW::getRouter()->urlForRoute('mailbox_mail_conversation', array('convId'=>$convId));
            }
            array_push($result, $item);
        }

        $list=array("result"=>"ok", "q"=>$q, "results" => $result);
        echo json_encode($list);
        exit;
    }

    public function settings($params){
        if (!OW::getUser()->isAuthenticated()) {
            throw new Redirect404Exception();
        }
        OW::getDocument()->setHeading(OW::getLanguage()->text('iismainpage', 'settings'));
        $cmp = new BASE_MCMP_ConsoleProfilePage();
        $this->addComponent('cmp', $cmp);

        $menuCmp = new IISMAINPAGE_MCMP_Menu('settings');
        $this->addComponent('menuCmp', $menuCmp);

        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iismainpage')->getStaticCssUrl() . 'iismainpage.css');
    }

    public function notifications($params){
        if (!OW::getUser()->isAuthenticated() || !OW::getPluginManager()->isPluginActive('notifications')) {
            throw new Redirect404Exception();
        }
        OW::getDocument()->setHeading(OW::getLanguage()->text('base', 'notifications'));

        $menuCmp = new IISMAINPAGE_MCMP_Menu('notifications');
        $this->addComponent('menuCmp', $menuCmp);

        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iismainpage')->getStaticCssUrl() . 'iismainpage.css');

        $cmp = new BASE_MCMP_ConsoleNotificationsPage();
        $this->addComponent('cmp', $cmp);
    }

    public function photos($params)
    {
        if (!OW::getUser()->isAuthenticated() || !OW::getPluginManager()->isPluginActive('photo') ||
            IISMAINPAGE_BOL_Service::getInstance()->isDisabled('photos')) {
            throw new Redirect404Exception();
        }
        OW::getDocument()->setHeading(OW::getLanguage()->text('iismainpage', 'public_photos'));
        $photoService = PHOTO_BOL_PhotoService::getInstance();
        $count = IISMAINPAGE_BOL_Service::$item_count;

        $photoList = $photoService->findPhotoList( 'latest',1, $count);
        $data = array();
        foreach ($photoList as $item) {
            $data[] = $item['id'];
        }
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'mobile_user_list.js');

        $cmp = new IISMAINPAGE_MCMP_PhotoList('latest', $photoList);
        $this->addComponent('list', $cmp);

        if(count($photoList) >= $count) {
            OW::getDocument()->addOnloadScript("
            window.mobileUserList = new OW_UserList(" . json_encode(array(
                    'component' => 'IISMAINPAGE_MCMP_PhotoList',
                    'listType' => 'latest',
                    'excludeList' => $data,
                    'node' => '.owm_photo_list',
                    'responderUrl' => OW::getRouter()->urlForRoute('iismainpage.photos_responder')
                )) . ");
            ", 50);
        }

        $menuCmp = new IISMAINPAGE_MCMP_Menu('photos');
        $this->addComponent('menuCmp', $menuCmp);

        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iismainpage')->getStaticCssUrl() . 'iismainpage.css');
    }

    public function photos_responder($params)
    {
        if (!OW::getRequest()->isAjax()) {
            throw new Redirect404Exception();
        }
        $excludeList = empty($_POST['excludeList']) ? array() : $_POST['excludeList'];
        $count = IISMAINPAGE_BOL_Service::$item_count;
        $page = ceil(count($excludeList)/$count)+1;
        $photoService = PHOTO_BOL_PhotoService::getInstance();
        $data = $photoService->findPhotoList( 'latest',$page, $count);

        echo json_encode($data);
        exit;
    }

    public function videos($params)
    {
        if (!OW::getUser()->isAuthenticated() || !OW::getPluginManager()->isPluginActive('video') ||
            IISMAINPAGE_BOL_Service::getInstance()->isDisabled('videos')) {
            throw new Redirect404Exception();
        }
        OW::getDocument()->setHeading(OW::getLanguage()->text('video', 'video'));
        $clipService = VIDEO_BOL_ClipService::getInstance();
        $count = IISMAINPAGE_BOL_Service::$item_count;

        $clipList = $clipService->findClipsList('latest', 1, $count);
        $data = array();
        foreach ($clipList as $item) {
            $data[] = $item['id'];
        }
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'mobile_user_list.js');

        $cmp = new IISMAINPAGE_MCMP_VideoList('latest', $clipList);
        $this->addComponent('list', $cmp);
        $this->assign('listType', 'latest');

        if(count($clipList) >= $count) {
            OW::getDocument()->addOnloadScript("
            window.mobileUserList = new OW_UserList(" . json_encode(array(
                    'component' => 'IISMAINPAGE_MCMP_VideoList',
                    'listType' => 'latest',
                    'excludeList' => $data,
                    'node' => '.owm_video_list',
                    'responderUrl' => OW::getRouter()->urlForRoute('iismainpage.videos_responder')
                )) . ");
            ", 50);
        }

        $menuCmp = new IISMAINPAGE_MCMP_Menu('videos');
        $this->addComponent('menuCmp', $menuCmp);

        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iismainpage')->getStaticCssUrl() . 'iismainpage.css');
    }

    public function videos_responder($params)
    {
        if (!OW::getRequest()->isAjax()) {
            throw new Redirect404Exception();
        }
        $excludeList = empty($_POST['excludeList']) ? array() : $_POST['excludeList'];
        $count = IISMAINPAGE_BOL_Service::$item_count;
        $page = ceil(count($excludeList)/$count)+1;
        $clipService = VIDEO_BOL_ClipService::getInstance();
        $data = $clipService->findClipsList( 'latest',$page, $count);

        echo json_encode($data);
        exit;
    }

}