<?php
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_BOL_Service
{
    private static $classInstance;
    private $hashtagTagDao;
    private $hashtagEntityDao;
    private $regex_view = '((( |^|\n|\t|>|:|\(|\))#)((\w|\x{200C}|\x{200F}|&zwnj;){2,64}|([\x{0600}-\x{06FF}\x|\x{200C}|\x{200F}|&zwnj;]{2,64})))';

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
        $this->hashtagTagDao = IISHASHTAG_BOL_TagDao::getInstance();
        $this->hashtagEntityDao = IISHASHTAG_BOL_EntityDao::getInstance();
    }

    /***
     * @param $hashtag
     * @param $entityId
     * @param $entityType
     * @param null $context
     */
    public function add_hashtag($hashtag, $entityId, $entityType,$context = null){
        $hashtag = UTIL_HtmlTag::stripTags($hashtag);
        $hashtag = str_replace('&zwnj;', '‌', $hashtag); //Zero-width non-joiner
        $tags = $this->hashtagTagDao->getItemByTagText($hashtag);
        if(count($tags)>0) {
            $tag = $tags[0];
        }
        else{
            $tag = new IISHASHTAG_BOL_Tag();
            $tag->tag = $hashtag;
            $tag->count = 1;
            $this->hashtagTagDao->save($tag);
        }

        //add to entity
        if(!$this->hashtagEntityDao->itemExists($tag->id, $entityId, $entityType)) {
            $entity = new IISHASHTAG_BOL_Entity();
            $entity->tagId = $tag->id;
            $entity->entityId = $entityId;
            $entity->entityType = $entityType;
            $entity->context = $context;
            $this->hashtagEntityDao->save($entity);
        }

        //refresh tag count
        $this->refresh_total_hashtag_count($tag->id);
    }

    /***
     * @param $tagId
     * @return mixed
     */
    private function refresh_total_hashtag_count($tagId){
        $newTotalCount = $this->hashtagEntityDao->countEntitiesForTagId($tagId);
        $tag = $this->hashtagTagDao->findById($tagId);
        if( isset($tag) ) {
            $tag->count = $newTotalCount;
            $this->hashtagTagDao->save($tag);
        }

        //remove tags with zero reference
        $ex = new OW_Example();
        $ex->andFieldEqual('count', 0);
        $this->hashtagTagDao->deleteByExample($ex);

        return $newTotalCount;
    }

    /***
     * @return void
     */
    private function refresh_total_hashtag_count_for_all(){
        $query = 'UPDATE '.$this->hashtagTagDao->getTableName().' as tags SET `count`= (SELECT count(*) FROM '.$this->hashtagEntityDao->getTableName().' as entity WHERE entity.tagId=tags.id );';
        OW::getDbo()->query($query);

        //remove tags with zero reference
        $ex = new OW_Example();
        $ex->andFieldEqual('count', 0);
        $this->hashtagTagDao->deleteByExample($ex);
    }

    /***
     * @param $idList
     */
    public function deleteEntitiesByListIds($idList)
    {
        //delete tag entities
        $this->hashtagEntityDao->deleteByIdList($idList);

        //refresh changed tagIds
        $this->refresh_total_hashtag_count_for_all();
    }

    /***
     * @param $entityId
     * @param $entityType
     */
    public function deleteAllItemsByEntity($entityType, $entityId)
    {
        $example = new OW_Example();
        $example->andFieldEqual('entityType', $entityType);
        $example->andFieldEqual('entityId', $entityId);

        $idList = $this->hashtagEntityDao->findIdListByExample($example);
        $this->deleteEntitiesByListIds($idList);
    }

    /**
     * @param $entityType
     * @param $entityIds
     * @return array
     */
    public function findActionsByEntityIds( $entityType, $entityIds )
    {
        $example = new OW_Example();
        $example->andFieldEqual('entityType', $entityType);
        $example->andFieldInArray('entityId', $entityIds);

        return NEWSFEED_BOL_ActionDao::getInstance()->findListByExample($example);
    }

    /***
     * @param $hashtag
     * @param $count
     * @return array
     */
    public function findTags($hashtag,$count){
        $items = $this->hashtagTagDao->findTagList($hashtag,$count);
        $result = array ();
        foreach($items as $key => $item){
            $result[] = array (
                'tag'=>$item->tag,
                'count'=>$item->count);
        }
        return $result;
    }
    public function findTagsInAdvanceSearchPlugin($hashtag,$first,$count){
        $items = $this->hashtagTagDao->findTagListInAdvanceSearchPlugin($hashtag,$first,$count);
        $result = array ();
        foreach($items as $key => $item){
            $result[] = array (
                'tag'=>$item->tag,
                'count'=>$item->count);
        }
        return $result;
    }

    /***
     * @param $tag
     * @param $entityType
     * @return array
     */
    public function findEntitiesByTag($tag, $entityType){
        $hashtag = UTIL_HtmlTag::stripTags($tag);
        $tags = $this->hashtagTagDao->getItemByTagText($hashtag);
        $result = array();
        if(count($tags)>0) {
            $items = $this->hashtagEntityDao->findEntityList($tags[0]->id, $entityType);
            foreach ($items as $key => $item) {
                $result[$item->id] = array('type'=>$item->entityType,'context' => $item->context, 'id'=>$item->entityId);
            }
        }
        return $result;
    }

    /***
     * @param $tag
     * @return array
     */
    public function findGroupEntitiesByTag($tag){
        $hashtag = UTIL_HtmlTag::stripTags($tag);
        $tags = $this->hashtagTagDao->getItemByTagText($hashtag);
        $result = array();
        if(count($tags)>0) {
            $items = $this->hashtagEntityDao->findGroupEntityList($tags[0]->id);
            foreach ($items as $key => $item) {
                $result[$item->id] = array('type'=>$item->entityType,'context' => $item->context, 'id'=>$item->entityId);
            }
        }
        return $result;
    }

    /***
     * @param $hashtag
     * @return array
     */
    public function findEntityCountByTag($hashtag){
        $hashtag = UTIL_HtmlTag::stripTags($hashtag);
        $tags = $this->hashtagTagDao->getItemByTagText($hashtag);
        if(count($tags)>0) {
            $tagId = $tags[0]->id;
            $q = "SELECT `entityType`,`context`,count(*) FROM `" . OW_DB_PREFIX . "iishashtag_entity` WHERE `tagId`="
                .$tagId." GROUP BY `entityType`,`context`";
            $res = OW::getDbo()->queryForList($q);
            $res_array = array();
            foreach($res as $key=>$item){
                if($item["context"] == null) {
                    if(!isset($res_array[$item["entityType"]]))
                        $res_array[$item["entityType"]] = 0;
                    $res_array[$item["entityType"]] += (int)$item["count(*)"];
                }else {
                    if(!isset($res_array[$item["context"]]))
                        $res_array[$item["context"]] = 0;
                    $res_array[$item["context"]] += (int)$item["count(*)"];
                }
            }
            return $res_array;
        }
        else{
            return array();
        }

    }

    /***
     * @param $tag
     * @return array
     */
    public function getContentMenu($tag, $selectedTab)
    {
        $validLists = array();
        if(OW::getPluginManager()->isPluginActive('iisnews')
            && BOL_AuthorizationService::getInstance()->isActionAuthorized('iisnews', 'view'))
            $validLists[] = 'news';
        if(OW::getPluginManager()->isPluginActive('groups')
            && BOL_AuthorizationService::getInstance()->isActionAuthorized('groups', 'view'))
            $validLists[] = 'groups';
        if(OW::getPluginManager()->isPluginActive('newsfeed')
            && BOL_AuthorizationService::getInstance()->isActionAuthorized('iishashtag', 'view_newsfeed'))
            $validLists[] = 'newsfeed';
        if(OW::getPluginManager()->isPluginActive('event')
            && BOL_AuthorizationService::getInstance()->isActionAuthorized('event', 'view_event'))
            $validLists[] = 'event';
        if(OW::getPluginManager()->isPluginActive('video')
            && BOL_AuthorizationService::getInstance()->isActionAuthorized('video', 'view'))
            $validLists[] = 'video';
        if(OW::getPluginManager()->isPluginActive('photo')
            && BOL_AuthorizationService::getInstance()->isActionAuthorized('photo', 'view'))
            $validLists[] = 'photo';
        if(OW::getPluginManager()->isPluginActive('forum')
            && BOL_AuthorizationService::getInstance()->isActionAuthorized('forum', 'view'))
            $validLists[] = 'forum';
        if(OW::getPluginManager()->isPluginActive('blogs')
            && BOL_AuthorizationService::getInstance()->isActionAuthorized('blogs', 'view'))
            $validLists[] = 'blogs';

        //$classes = array('ow_ic_push_pin', 'ow_ic_clock', 'ow_ic_star', 'ow_ic_tag');

        $countArray1 = $this->findEntityCountByTag($tag);
        $countArray['newsfeed'] = array_key_exists('user-status', $countArray1)?intval($countArray1['user-status']):0;
        $countArray['news'] = array_key_exists('news-entry', $countArray1)?intval($countArray1['news-entry']):0;
        $countArray['groups'] = array_key_exists('groups', $countArray1)?intval($countArray1['groups']):0;
        $countArray['groups'] += array_key_exists('groups-status', $countArray1)?intval($countArray1['groups-status']):0;
        $countArray['groups'] += array_key_exists('groups-feed', $countArray1)?intval($countArray1['groups-feed']):0;
        $countArray['video'] = array_key_exists('video_comments', $countArray1)?intval($countArray1['video_comments']):0;
        $countArray['photo'] = array_key_exists('photo_comments', $countArray1)?intval($countArray1['photo_comments']):0;
        $countArray['event'] = array_key_exists('event', $countArray1)?intval($countArray1['event']):0;
        $countArray['forum'] = array_key_exists('forum-topic', $countArray1)?intval($countArray1['forum-topic']):0;
        $countArray['forum'] += array_key_exists('forum-post', $countArray1)?intval($countArray1['forum-post']):0;
        $countArray['blogs'] = array_key_exists('blog-post', $countArray1)?intval($countArray1['blog-post']):0;

        $language = OW::getLanguage();
        $menuItems = array();
        $order = 0;
        $defaultTab = -1;
        foreach ( $validLists as $key => $type )
        {
            if($defaultTab == -1 && $countArray[$type]>0)
                $defaultTab = $type;
            $item = new BASE_MenuItem();
            $item->setLabel($language->text('iishashtag', 'at').' '.$language->text('iishashtag', 'menu_' . $type).' ('.$countArray[$type].')');
            $item->setUrl(OW::getRouter()->urlForRoute('iishashtag.tag.tab', array('tag'=>$tag, 'tab' => $type)));
            $item->setKey($type);
            //$item->setIconClass($classes[$order]);
            $item->setOrder($order);
            array_push($menuItems, $item);
            $order++;
        }
        if(!isset($selectedTab) || !in_array($selectedTab, $validLists)) {
            if ($defaultTab == -1 && !empty($validLists))
                $defaultTab = $validLists[0];
            $selectedTab = $defaultTab;
        }

        return array("menu"=>$menuItems, "default"=>$selectedTab, "allCounts"=>$countArray);
    }

    /***
     * @return Form
     */
    public function getSearchForm(){
        $form = new Form("form");

        $textField = new TextField('txt');
        $textField->setLabel(OW::getLanguage()->text('iishashtag', 'search'))
            ->setRequired(true);
        $textField->addAttribute('placeholder', OW::getLanguage()->text('iishashtag', 'search_placeholder'));
        $form->addElement($textField);

        $submit = new Submit('submit');
        $submit->setValue(OW::getLanguage()->text('iishashtag', 'search'));
        $form->addElement($submit);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $data = $form->getValues();
            $tag = $data['txt'];
            OW::getApplication()->redirect(OW::getRouter()->urlForRoute('iishashtag.tag', array('tag' => $tag)));
        }
        return $form;
    }

    /***
     * @param $page
     * @param $tag
     */
    public function getPhotoList($page, $tag){
        $limit = OW::getConfig()->getValue('photo', 'photos_per_page');
        $first = ($page - 1) * $limit;
        $idList = $this->findEntitiesByTag($tag,"photo_comments");
        $ids = array();
        foreach ($idList as $element)
            $ids[] = $element['id'];
        $photoObjects = array();
        if(is_array($idList) && sizeof($idList)>0){
            $photoObjects = PHOTO_BOL_PhotoDao::getInstance()->getPhotoList('latest', $first, $limit, null, false, $ids);

            $existingEntityIds = array();
            foreach($photoObjects as $item){
                $existingEntityIds[] = $item['id'];
            }
            if(count($idList)>count($existingEntityIds)){
                $newsfeedService = NEWSFEED_BOL_Service::getInstance();
                $deletedEntityIds = array();
                foreach($idList as $key=>$id){
                    $id = $id['id'];
                    if(!in_array($id, $existingEntityIds)){
                        if( $newsfeedService->findAction("photo_comments", $id) === null ) {
                            $deletedEntityIds[] = $key;
                        }
                    }
                }
                IISHASHTAG_BOL_Service::getInstance()->deleteEntitiesByListIds($deletedEntityIds);
            }
        }
        $type = PHOTO_BOL_PhotoService::TYPE_PREVIEW;
        if ( $photoObjects )
        {
            if ( !in_array($type, PHOTO_BOL_PhotoService::getInstance()->getPhotoTypes()) )
            {
                $type = PHOTO_BOL_PhotoService::TYPE_PREVIEW;
            }

            foreach ( $photoObjects as $key => $photo )
            {
                $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $photo['description'])));
                if(isset($stringRenderer->getData()['string'])){
                    $photoObjects[$key]['description'] = ($stringRenderer->getData()['string']);
                }
                $photoObjects[$key]['url'] = PHOTO_BOL_PhotoService::getInstance()->getPhotoUrlByPhotoInfo($photo['id'], $type, $photo['hash'], !empty($photo['dimension']) ? $photo['dimension'] : FALSE);
            }
        }

        $photoList = $this->generatePhotoList($photoObjects);
        if ( !OW_DEBUG_MODE )
        {
            ob_end_clean();
        }

        $event = new OW_Event('photo.onReadyResponse', $_POST, $photoList);
        OW::getEventManager()->trigger($event);
        $result = $event->getData();

        $document = OW::getDocument();

        $result['scripts'] = array(
            'beforeIncludes' => $document->getScriptBeforeIncludes(),
            'scriptFiles' => $document->getScripts(),
            'onloadScript' => $document->getOnloadScript(),
            'styleDeclarations' => $document->getStyleDeclarations(),
            'styleSheets' => $document->getStyleSheets()
        );

        header('Content-Type: application/json');
        exit(json_encode($result));
    }

    /***
     * @param $photos
     * @return array
     */
    public function generatePhotoList( $photos )
    {
        $userIds = $userUrlList = $albumIdList = $albumUrlList = $displayNameList = $albumNameList = $entityIdList = array();

        $unique = IISSecurityProvider::generateUniqueId(time(), true);

        if ( $photos )
        {
            foreach ( $photos as $key => $photo )
            {
                $userIds[] = $photo['userId'];
                $albumIdList[] = $photo['albumId'];
                $entityIdList[] = $photo['id'];

                $photos[$key]['description'] = UTIL_HtmlTag::autoLink($photos[$key]['description']);
                $photos[$key]['unique'] = $unique;
            }

            $displayNameList = BOL_UserService::getInstance()->getDisplayNamesForList($userIds);

            foreach ( ($usernameList = BOL_UserService::getInstance()->getUserNamesForList($userIds)) as $id => $username )
            {
                $userUrlList[$id] = BOL_UserService::getInstance()->getUserUrlForUsername($username);
            }

            foreach ( ($albumNameList = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumNameListByIdList($albumIdList)) as $id => $album )
            {
                $albumUrlList[$id] = OW::getRouter()->urlForRoute('photo_user_album', array('user' => $usernameList[$album['userId']], 'album' => $id));
            }
        }

        return array('status' => 'success', 'data' => array(
            'photoList' => $photos,
            'displayNameList' => $displayNameList,
            'userUrlList' => $userUrlList,
            'albumNameList' => $albumNameList,
            'albumUrlList' => $albumUrlList,
            'rateInfo' => BOL_RateService::getInstance()->findRateInfoForEntityList('photo_rates', $entityIdList),
            'userScore' => BOL_RateService::getInstance()->findUserSocre(OW::getUser()->getId(), 'photo_rates', $entityIdList),
            'commentCount' => BOL_CommentService::getInstance()->findCommentCountForEntityList('photo_comments', $entityIdList),
            'unique' => $unique
        ));
    }


    /***
     * @param $content
     * @param $entityId
     * @param $entityType
     * @param null $context
     */
    private function findAndAddTagsFromContent($content, $entityId, $entityType,$context = null){
        $content=$this->fixHashtagPaste($content);
        preg_match_all('/'.$this->regex_view.'/u', $content, $matches);
        if(isset($matches[4])){
            foreach($matches[4] as $match){
                $this->add_hashtag($match, $entityId, $entityType,$context);
            }
        }
    }

    /***
     * @param $content
     * @return mixed|string
     */
    public function fixHashtagPaste($content)
    {
        $content=utf8_encode($content);
        $content=str_replace("#â«","#",$content);
        $content=utf8_decode($content);
        $content=str_replace("&nbsp;"," ",$content);
        return $content;
    }

    /***
     * @param OW_Event $e
     */
    public function onAddComment( OW_Event $e )
    {
        $params = $e->getParams();
        $comment = BOL_CommentService::getInstance()->findComment($params['commentId']);
        $content = $comment->getMessage();
        $entityId = $params['entityId'];
        $entityType = $params['entityType'];

        if(isset($params['pluginKey']) && $params['pluginKey'] == 'groups') {
            if (isset($params['entityType']) && in_array($entityType, array('group','groups-join'))) {
                $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);

                if($action == null) {
                    $groupId = $this->findGroupIdByEntityId($entityId);
                    if ($groupId == null) {
                        return;
                    } else {
                        $entityId = $groupId;
                        $entityType = 'groups-status';
                    }
                }else{
                    $entityId = $action->id;
                    $entityType = 'groups-feed';
                }
            }
        }
        else if(isset($params['pluginKey']) && $params['pluginKey'] == 'newsfeed') {
            if (isset($params['entityType']) && $params['entityType'] == 'groups-status') {
                $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($params['entityType'], $entityId);
                if($action == null){
                    $groupId = $this->findGroupIdByEntityId($params['entityId']);
                    if($groupId == null){
                        return;
                    }else{
                        $entityId = $groupId;
                        $entityType = 'groups-status';
                    }
                }else {
                    $entityId = $action->id;
                    $entityType = 'groups-feed';
                }
            }
        }

        $this->findAndAddTagsFromContent($content, $entityId, $entityType);
    }

    /***
     * @param $actionId
     * @param $entityId
     * @param $type
     * @return int|null
     */
    public function findGroupIdByActionId($actionId, $entityId, $type){
        $activityId = null;
        $activities = NEWSFEED_BOL_ActivityDao::getInstance()->findByActionIds(array($actionId));
        foreach($activities as $activity){
            if($activity->activityType=='create'){
                $activityId = $activity->id;
            }
        }
        if($activityId!=null){
            $feedList = NEWSFEED_BOL_Service::getInstance()->findFeedListByActivityids(array($activityId));
            $feedList = $feedList[$activityId];
            foreach ($feedList as $feed) {
                if ($feed->feedType == $type) {
                    return $feed->feedId;
                }
            }
        }else {
            $groupId = $this->findGroupIdByEntityId($entityId);
            if($groupId == null){
                return null;
            }else{
                return $groupId;
            }
        }

        return null;
    }

    /***
     * @param $actionId
     * @return int|null
     */
    public function findUserIdByActionId($actionId){
        $activityId = null;
        $activities = NEWSFEED_BOL_ActivityDao::getInstance()->findByActionIds(array($actionId));
        foreach($activities as $activity){
            if($activity->activityType=='create'){
                return $activity->userId;
            }
        }

        return null;
    }

    /***
     * @param $actionId
     * @return int|null
     */
    public function findActionStatusByActionId($actionId){
        $newsfeedService = NEWSFEED_BOL_Service::getInstance();
        $actionNewsfeed = $newsfeedService->findActionById($actionId);
        $actionData = json_decode($actionNewsfeed->data);
        if (!isset($actionData) || !isset($actionData->status)){
            return '';
        }
        return $actionData->status;
    }

    /***
     * @param $entityId
     * @return null
     */
    public function findGroupIdByEntityId($entityId){
        if($entityId == null){
            return null;
        }
        $groupStatus = NEWSFEED_BOL_StatusDao::getInstance()->findById($entityId);
        if($groupStatus == null || $groupStatus->feedType != 'groups'){
            return null;
        }else if($groupStatus != null && $groupStatus->feedType == 'groups'){
            return $groupStatus->feedId;
        }
    }

    /***
     * @param OW_Event $e
     */
    public function onEntityUpdate(OW_Event $e )
    {
        $params = $e->getParams();
        $entityId = $params['entityId'];
        $entityType = $params['entityType'];

        $pluginNewsfeed = BOL_PluginDao::getInstance()->findPluginByKey('newsfeed');
        if(!isset($pluginNewsfeed) || !$pluginNewsfeed->isActive()){
            return;
        }
        $pluginGroups = BOL_PluginDao::getInstance()->findPluginByKey('groups');
        $content = isset($params['newContent'])?$params['newContent']:'';
        if($entityType=='news-entry') {
            $entry = EntryService::getInstance()->findById($entityId);
            if (isset($entry) && !$entry->isDraft()) {
                $content = $entry->entry;
            }
        }elseif ($entityType=='blog-post'){
            $entry = PostService::getInstance()->findById($entityId);
            if (isset($entry) && !$entry->isDraft()) {
                $content = $entry->post;
            }
        }else if(isset($pluginGroups) && $pluginGroups->isActive() && $entityType==GROUPS_BOL_Service::FEED_ENTITY_TYPE) {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if (isset($action)) {
                $jsonTmp = json_decode($action->data, true);
                $content = $jsonTmp["content"]["vars"]["description"];
            }
            $entityType = "groups-status";
        }else if($entityType=='groups-status') {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if (isset($action)) {
                if (strcmp($e->getName(), 'hashtag.edit_newsfeed') == 0) {
                    $content = nl2br($params['text']);
                } else {
                    $jsonTmp = json_decode($action->data, true);
                    $content = nl2br($jsonTmp["status"]);
                }
                $entityId = $action->id;
            }else if (isset($params['actionId'])){
                $entityId = $params['actionId'];
            }
            $entityType = "groups-feed";
        }else if($entityType=='event') {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if (isset($action)) {
                $jsonTmp = json_decode($action->data, true);
                $content = $jsonTmp["content"]["vars"]["description"];
            }
        }else if($entityType=='video_comments') {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if (isset($action)) {
                $jsonTmp = json_decode($action->data, true);
                $content = $jsonTmp["content"]["vars"]["description"];
            }
        }else if($entityType=='forum-post'){
            $post = FORUM_BOL_ForumService::getInstance()->findPostById($entityId);
            if (isset($post)) {
                $content = strip_tags(UTIL_HtmlTag::stripTags($post->text));
            }
        }else if($entityType=='user-status') {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if (isset($action)) {
                $jsonTmp = json_decode($action->data, true);
                $content = nl2br($jsonTmp["status"]);
            }
        }else if($entityType=='photo_comments') {
            $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($entityId);
            if (isset($photo)) {
                $content = strip_tags(UTIL_HtmlTag::stripTags($photo->description));
            }
        }else{
            return;
        }
        $this->deleteAllItemsByEntity($entityType,$entityId);

        if($e->getName()!='feed.delete_item') {
            $this->findAndAddTagsFromContent($content, $entityId, $entityType);
            $comments = BOL_CommentService::getInstance()->findFullCommentList($params['entityType'], $params['entityId']);
            foreach ($comments as $comment) {
                $this->findAndAddTagsFromContent($comment->message, $entityId, $entityType);
            }
        }

        $this->refresh_total_hashtag_count_for_all();
    }

    /***
     * @param $content
     * @return mixed
     */
    private function findAndReplaceTagsFromView($content){
        $content = str_replace('&#8235;', '‌', $content);
        $content=$this->fixHashtagPaste($content);
        $replace1 = preg_replace_callback('/'.$this->regex_view.'(?=[^>]*(<|$))/u', function($matches) {
            $matches1 = $matches[4];
            $matches1 = str_replace('&zwnj;', '‌', $matches1); //Zero-width non-joiner
            if($matches1=='8235') {
                return $matches[0];
            }
            $url = OW::getRouter()->urlForRoute('iishashtag.tag', array('tag'=>$matches1));
            //Proper rendering for English/Persian hashtags
            if(preg_match("/^\w*$/",$matches1)) {
                return $matches[3] . '<a class="iishashtag_tag english_tag" href="' . $url . '">#' . $matches1 . '</a>';
            }else{
                return $matches[3] . '<a class="iishashtag_tag persian_tag" href="' . $url . '">#&#8235;' . $matches1 . '</a>';
            }
        },  $content);
        return $replace1;
    }

    /***
     * @param $event
     * @param $key
     * @return mixed
     */
    private function findAndProcessKeyFromEvent($event, $key){
        $data = $event->getData();
        if(isset($data[$key])) {
            $string = $data[$key];
        }else{
            $params = $event->getParams();
            if (isset($params[$key])) {
                $string = $params[$key];
            }
        }

        if(isset($string)){
            $string = $this->correctHomeUrlVariable($string);
            $string = $this->findAndReplaceTagsFromView($string);
            $data[$key] = $string;
        }
        return $data;
    }

    /***
     * @param OW_Event $event
     * @return mixed
     */
    public function renderNewsfeed( OW_Event $event )
    {
        $data = $this->findAndProcessKeyFromEvent($event, 'content');
        $event->setData($data);
        return $data;
    }

    /***
     * @param BASE_CLASS_EventProcessCommentItem $event
     * @return mixed
     */
    public function renderComments( BASE_CLASS_EventProcessCommentItem $event )
    {
        $string = $event->getDataProp('content');
        
        $string = $this->correctHomeUrlVariable($string);
        $string = $this->findAndReplaceTagsFromView($string);
        
        $event->setDataProp('content', $string);
    }

    /***
     * @param OW_Event $event
     * @return mixed
     */
    public function renderString( OW_Event $event )
    {
        $data = $this->findAndProcessKeyFromEvent($event, 'string');
        $event->setData($data);
        return $data;
    }

    /***
     * @param $string
     * @return mixed
     */
    public function correctHomeUrlVariable($string)
    {
        return preg_replace('/\$\$BASE_URL\$\$/', OW_URL_HOME, $string);
    }

    /**
     * @param OW_Event $event
     */
    public function onCommentDelete(OW_Event $event){
        $params = $event->getParams();
        /** @var BOL_Comment $comment */
        $comment = $params['comment'];
        if(isset($comment)){
            preg_match_all('/'.$this->regex_view.'/u', $comment->getMessage(), $commentMatches);
            if(!isset($commentMatches))
                return;
            $this->onEntityUpdate(new OW_Event('', array('entityType'=>$params['entityType'],'entityId'=>$params['entityId'],'pluginKey'=>$params['pluginKey'])));
        }
    }

    /***
     * @param $entityId
     * @param $entityType
     * @return null|string
     */
    public function getEntityContent($entityId,$entityType){
        $pluginGroups = BOL_PluginDao::getInstance()->findPluginByKey('groups');
        $content = null;
        if ($entityType == 'news-entry') {
            $entry = EntryService::getInstance()->findById($entityId);
            if ($entry == null) return null;
            $content = $entry->entry;
        } else if (isset($pluginGroups) && $pluginGroups->isActive() && $entityType == GROUPS_BOL_Service::FEED_ENTITY_TYPE) {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if ($action == null) return null;
            $jsonTmp = json_decode($action->data, true);
            $content = $jsonTmp["content"]["vars"]["description"];
            $entityType = "groups-status";
        } else if ($entityType == 'groups-status') {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if ($action == null) return null;
            $jsonTmp = json_decode($action->data, true);
            $content = nl2br($jsonTmp["status"]);
            $entityType = "groups-feed";
        } else if ($entityType == 'event') {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if ($action == null) return null;
            $jsonTmp = json_decode($action->data, true);
            $content = $jsonTmp["content"]["vars"]["description"];
        } else if ($entityType == 'video_comments') {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if ($action == null) return null;
            $jsonTmp = json_decode($action->data, true);
            $content = $jsonTmp["content"]["vars"]["description"];
        } else if ($entityType == 'forum-topic') {
            return null;
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if ($action == null) return null;
            $jsonTmp = json_decode($action->data, true);
            $content = $jsonTmp["content"]["vars"]["description"];
            $postDto = FORUM_BOL_ForumService::getInstance()->findTopicFirstPost($entityId);
            if ($postDto == null) return null;
            $entityType = 'forum-post';
            $entityId = $postDto->getId();
        } else if ($entityType == 'user-status') {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if ($action == null) return null;
            $jsonTmp = json_decode($action->data, true);
            $content = nl2br($jsonTmp["status"]);
        } else {
            return null;
        }

        return $content;
    }

    /***
     * @param OW_Event $event
     */
    public function feedHashtag(OW_Event $event){
        $params = $event->getParams();
        $entityType = $params['entityType'];
        $entityId = $params['entityId'];
        $context = null;
        if(isset($params['context']))
            $context = $params['context'];
        if($entityType=='photo_comments' || $entityType=='multiple_photo_upload') {
            $action = NEWSFEED_BOL_ActionDao::getInstance()->findAction($entityType, $entityId);
            if ($action == null) return;
            $jsonTmp = json_decode($action->data, true);
            $content = $jsonTmp["status"];
            $this->findAndAddTagsFromContent($content, $entityId, $entityType,$context);
        }
    }

    /***
     * @param $idList
     * @return mixed
     */
    public function checkGroupItemsForDisplay($idList){
        $groupIds = array();
        $feedIds = array();
        foreach($idList as $item){
            if ($item['type'] == 'groups-feed') {
                $feedIds[] = $item['id'];
            } else {
                $groupIds[] = $item['id'];
            }
        }

        $groupService = GROUPS_BOL_Service::getInstance();

        $groups = $groupService->findGroupsWithIds($groupIds);
        $groupFeeds = NEWSFEED_BOL_ActionDao::getInstance()->findByIdList($feedIds);

        //delete removed ids both from db and $idList
        if(count($idList)>count($groups)+count($groupFeeds)){
            $newsfeedService = NEWSFEED_BOL_Service::getInstance();
            $deletedEntityIds = array();
            foreach($idList as $key=>$item){
                $id = $item['id'];
                if (in_array($id, $groupIds)) {
                    $g = $groupService->findGroupById($id);
                    if (!isset($g)) {
                        $deletedEntityIds[] = $key;
                        unset($idList[$key]);
                    }
                } else {
                    if ($newsfeedService->findActionById($id) === null) {
                        $deletedEntityIds[] = $key;
                        unset($idList[$key]);
                    }
                }
            }
            $this->deleteEntitiesByListIds($deletedEntityIds);
        }

        //set group and newssfeed to idList
        foreach($idList as $key=>$item){
            if(!isset($item['context'])) {
                if ($item['type'] == 'groups-feed') {
                    $idList[$key]['feed'] = NEWSFEED_BOL_ActionDao::getInstance()->findById($item['id']);
                    $idList[$key]['obj'] = $groupService->findGroupById($this->findGroupIdByActionId($idList[$key]['feed']->id, $idList[$key]['feed']->entityId, "groups"));
                } else {
                    $idList[$key]['obj'] = $groupService->findGroupById($item['id']);
                }
            }else{
                if ($item['context'] == 'groups-feed') {
                    $idList[$key]['feed'] = NEWSFEED_BOL_ActionDao::getInstance()->findById($item['id']);
                    $idList[$key]['obj'] = $groupService->findGroupById($this->findGroupIdByActionId($idList[$key]['feed']->id, $idList[$key]['feed']->entityId, "groups"));
                } else {
                    $action = NEWSFEED_BOL_Service::getInstance()->findAction($item['type'],$item['id']);
                    $idList[$key]['feed'] = $action;
                    $data = json_decode($action->data);
                    if (isset($data->contextFeedId))
                        $idList[$key]['obj'] = $groupService->findGroupById($data->contextFeedId);
                }
            }
            if(!isset($idList[$key]['obj']) || !$groupService->isCurrentUserCanView($idList[$key]['obj'])){
                unset($idList[$key]);
            }
        }
        return $idList;
    }

    /***
     * @param $idList
     * @return array
     */
    public function checkNewsfeedItemsForDisplay($idList){
        $ids = array();
        foreach ($idList as $index => $element) {
            if(!isset($ids[$element['type']]))
                $ids[$element['type']] = array();
            $ids[$element['type']][$index] = $element['id'];
        }
        $actions = array();
        foreach ($ids as $key=>$item) {
            $actions = array_merge($actions,IISHASHTAG_BOL_Service::getInstance()->findActionsByEntityIds($key, $item));
        }
        $actionIdList = array();
        $existingEntityIds = array();
        foreach($actions as $action){
            array_unshift($actionIdList,$action->getId());
            $existingEntityIds[] = $action->entityId;
        }
        if(count($idList)>count($existingEntityIds)){
            $newsfeedService = NEWSFEED_BOL_Service::getInstance();
            $deletedEntityIds = array();
            foreach($ids as $key=>$list){
                foreach ($list as $index => $id) {
                    if (!in_array($id, $existingEntityIds)) {
                        if ($newsfeedService->findAction($key, $id) === null) {
                            $deletedEntityIds[] = $index;
                        }
                    }
                }
            }
            IISHASHTAG_BOL_Service::getInstance()->deleteEntitiesByListIds($deletedEntityIds);
        }
        return array( 'existingEntityIds' => $existingEntityIds, 'actionIdList' => $actionIdList);
    }

    /***
     * @param $entries
     * @return array
     */
    public function checkNewsItemsForDisplay($entries){
        $entryService = EntryService::getInstance();
        //get list
        $list = array ();
        foreach($entries as $key=>$dto){
            if ($dto->isDraft())
                continue;
            $info[$dto->id]['dto'] = $dto;

            $list[] = array(
                'dto' => $dto,
                //'commentCount' => $info[$dto->id] ['commentCount'],
            );
        }

        $entries = array();
        $authorIdList = array();
        foreach ( $list as $item )
        {
            $dto = $item['dto'];
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('string' => $dto->getEntry())));
            if(isset($stringRenderer->getData()['string'])){
                $dto->setEntry($stringRenderer->getData()['string']);
            }
            $dto->setEntry($dto->getEntry());
            $dto->setTitle( UTIL_String::truncate( strip_tags($dto->getTitle()), 150, '...' )  );

            $text = EntryService::getInstance()->processEntryText($dto->getEntry());
            $sentenceCorrected = false;
            $previewLength = 300;
            $truncated = false;
            if ( mb_strlen($text) > $previewLength )
            {
                $truncated=true;
                $sentence = strip_tags($text);
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
                if($sentenceCorrected) {
                    $text = $sentence . '...';
                }
                else{
                    $text = UTIL_String::truncate(strip_tags($text), $previewLength, "...");
                }
            }

            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $text)));
            if (isset($stringRenderer->getData()['string'])) {
                $text = ($stringRenderer->getData()['string']);
            }

            $new_entry = array(
                'dto' => $dto,
                'text' => $text,
                'showMore' => $truncated,
                'url' => OW::getRouter()->urlForRoute('user-entry', array('id'=>$dto->getId())),
                'toolbar' => array(
                    array(
                        'class' => 'ow_ipc_date',
                        'label' => UTIL_DateTime::formatDate($item['dto']->timestamp)
                    ),
                )
            );
            if($dto->getImage()){
                $new_entry['imageSrc'] = $entryService->generateImageUrl($dto->getImage(), true);
                $new_entry['imageTitle'] = $dto->getTitle();
            }else{
                $new_entry['imageSrc'] = $entryService->generateDefaultImageUrl();
                $new_entry['imageTitle'] = $dto->getTitle();
            }

            array_unshift($entries, $new_entry);
            $authorIdList[] = $dto->authorId;
        }
        return $entries;
    }

    /***
     * @param $posts
     * @return array
     */
    public function checkBlogPostsForDisplay($posts){
        //get list
        $list = array ();
        foreach($posts as $key=> $dto){
            if ($dto->isDraft())
                continue;
            $info[$dto->id]['dto'] = $dto;

            $list[] = array(
                'dto' => $dto,
                //'commentCount' => $info[$dto->id] ['commentCount'],
            );
        }

        $posts = array();
        $authorIdList = array();
        foreach ( $list as $item )
        {
            $dto = $item['dto'];
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('string' => $dto->getPost())));
            if(isset($stringRenderer->getData()['string'])){
                $dto->setPost($stringRenderer->getData()['string']);
            }
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $dto->getPost())));
            if (isset($stringRenderer->getData()['string'])) {
                $dto->setPost($stringRenderer->getData()['string']);
            }
            $dto->setPost($dto->getPost());
            $dto->setTitle( UTIL_String::truncate( strip_tags($dto->getTitle()), 150, '...' )  );

            $text = $dto->getPost();

            $avatars=BOL_AvatarService::getInstance()->getDataForUserAvatars(array($dto-> getAuthorId()),true,false); //TODO it should be check like text for exploding or not
            foreach ( $avatars as $avatar )
            {
                $userId = $avatar['userId'];
                $avatars[$userId]['url'] = BOL_UserService::getInstance()->getUserUrl($userId);
            }

            if(strlen($text)>250){
                $text = UTIL_String::truncate( strip_tags($dto->getPost()), 250, '...' );
                $showMore = true;
            }
            else {
                $text = explode("<!--more-->", $dto->getPost());
                $isPreview = count($text) > 1;
                if (!$isPreview) {
                    $text = explode('<!--page-->', $text[0]);
                    $showMore = count($text) > 1;
                } else {
                    $showMore = true;
                }
                $text = $text[0];
            }

            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $text)));
            if (isset($stringRenderer->getData()['string'])) {
                $text = ($stringRenderer->getData()['string']);
            }

            $new_entry = array(
                'dto' => $dto,
                'text' => $text,
                'avatars' => $avatars,
                'showMore' => $showMore,
                'url' => OW::getRouter()->urlForRoute('user-post', array('id'=>$dto->getId())),
                'toolbar' => array(
                    array(
                        'class' => 'ow_ipc_date',
                        'label' => UTIL_DateTime::formatDate($item['dto']->timestamp)
                    ),
                )
            );
            array_unshift($posts, $new_entry);
            $authorIdList[] = $dto->authorId;
        }
        return $posts;
    }

    /***
     * @param $idList
     * @return array
     */
    public function checkForumItemsForDisplay($idList){
        $forumService = FORUM_BOL_ForumService::getInstance();

        $ids = array();
        foreach ($idList as $element)
            $ids[] = $element['id'];
        $postDtoList = $forumService->findPostListByIds($ids);
        //delete removed ids
        $existingEntityIds = array();
        foreach($postDtoList as $item){
            $existingEntityIds[] = $item->id;
        }
        if(count($idList)>count($existingEntityIds)){
            $deletedEntityIds = array();
            foreach($idList as $key=>$item){
                if(!in_array($item['id'], $existingEntityIds)){
                    $deletedEntityIds[] = $key;
                }
            }
            $this->deleteEntitiesByListIds($deletedEntityIds);
        }

        //prepare topic posts
        $postList = array();
        foreach ( $postDtoList as $postDto )
        {
            if(!$forumService->canUserViewForumTopic(OW::getUser()->getId(), $postDto->topicId))
                continue;

            $post = array(
                'id' => $postDto->id,
                'topicId' => $postDto->topicId,
                'userId' => $postDto->userId,
                'text' => $forumService->formatQuote($postDto->text),
                'createStamp' => UTIL_DateTime::formatDate($postDto->createStamp),
                'postUrl' => $forumService->getPostUrl($postDto->topicId, $postDto->id),
                'edited' => array()
            );

            $postList[$postDto->id] = $post;
            $postIds[] = $postDto->id;
        }

        return array( 'existingEntityIds' => $existingEntityIds, 'postList' => $postList);
    }
}
