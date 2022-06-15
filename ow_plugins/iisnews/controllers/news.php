<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisnews.controllers
 * @since 1.0
 */
class IISNEWS_CTRL_News extends OW_ActionController
{

    public function index($params)
    {
        if ( empty($params['list']) )
        {
            $params['list'] = 'latest';
        }

        $plugin = OW::getPluginManager()->getPlugin('iisnews');
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'iisnews', 'main_menu_item');

        $this->setPageHeading(OW::getLanguage()->text('iisnews', 'list_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_write');

        if ( !OW::getUser()->isAdmin() && !OW::getUser()->isAuthorized('iisnews', 'view') && !OW::getUser()->isAuthorized('iisnews'))
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('iisnews', 'view');
            throw new AuthorizationException($status['msg']);
        }
        if (  OW::getUser()->isAuthorized('iisnews') || OW::getUser()->isAuthorized('iisnews', 'add')  || OW::getUser()->isAdmin())
        {
            $this->assign('my_published_entrys_url', OW::getRouter()->urlForRoute('iisnews-manage-entrys'));
        }

        /*
          @var $service EntryService
         */
        $service = EntryService::getInstance();

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;

        $addNew_promoted = false;
        $addNew_isAuthorized = false;
        if (OW::getUser()->isAuthenticated())
        {
            if (OW::getUser()->isAuthorized('iisnews') || OW::getUser()->isAuthorized('iisnews', 'add') || OW::getUser()->isAdmin())
            {
                $addNew_isAuthorized = true;
            }
            else
            {
                $status = BOL_AuthorizationService::getInstance()->getActionStatus('iisnews', 'add');
                if ($status['status'] == BOL_AuthorizationService::STATUS_PROMOTED)
                {
                    $addNew_promoted = true;
                    $addNew_isAuthorized = true;
                    $script = '$("#btn-add-new-entry").click(function(){
                        OW.authorizationLimitedFloatbox('.json_encode($status['msg']).');
                        return false;
                    });';
                    OW::getDocument()->addOnloadScript($script);
                }
                else
                {
                    $addNew_isAuthorized = false;
                }
            }
        }
        $addNew_isAuthorized = false;
        if(OW::getUser()->isAuthorized('iisnews') || OW::getUser()->isAuthorized('iisnews', 'add') || OW::getUser()->isAdmin()){
            $addNew_isAuthorized = true;
        }
        $this->assign('addNew_isAuthorized', $addNew_isAuthorized);
        $this->assign('addNew_promoted', $addNew_promoted);

        $rpp = (int) OW::getConfig()->getValue('iisnews', 'results_per_page');

        $first = ($page - 1) * $rpp;

        $count = $rpp;

        $case = $params['list'];
        if ( !in_array($case, array( 'latest', 'browse-by-tag', 'most-discussed', 'top-rated', 'search-results' )) )
        {
            throw new Redirect404Exception();
        }
        $showList = true;
        $isBrowseByTagCase = $case == 'browse-by-tag';
        $isSearchResultsCase = $case == 'search-results';

        $contentMenu = $this->getContentMenu();
        if(!$isSearchResultsCase) {
            $contentMenu->getElement($case)->setActive(true);
        }
        $this->addComponent('menu', $contentMenu );
        $this->assign('listType', $case);

        $this->assign('isBrowseByTagCase', $isBrowseByTagCase);
        $this->assign('isSearchResultsCase', $isSearchResultsCase);
        if($isSearchResultsCase) {
            $q = UTIL_HtmlTag::escapeHtml($_GET['q']);
            $this->assign('q', $q );
        }
        $tagCount = null;
        if ( $isBrowseByTagCase )
        {
            $tagCount = 1000;
        }

        $tagSearch = new BASE_CMP_TagSearch(OW::getRouter()->urlForRoute('iisnews.list',
            array('list'=>'browse-by-tag')),'base+tag_search','tag' ,true,
            'news-entry', OW::getRouter()->urlForRoute('iisnews.list', array('list'=>'browse-by-tag')), $tagCount);
        $this->addComponent('tagSearch', $tagSearch);

        $entrySearch = new BASE_CMP_TagSearch(OW::getRouter()->urlForRoute('iisnews.list', array('list'=>'search-results')),
            'iisnews+search_entries','q');

        $this->addComponent('entrySearch', $entrySearch);

        $tagCount = null;
        if ( $isBrowseByTagCase )
        {
            $tagCount = 1000;
        }

        $tagCloud = new BASE_CMP_EntityTagCloud('news-entry', OW::getRouter()->urlForRoute('iisnews.list', array('list'=>'browse-by-tag')), $tagCount);

        if ( $isBrowseByTagCase )
        {
            $tagCloud->setTemplate(OW::getPluginManager()->getPlugin('base')->getCmpViewDir() . 'big_tag_cloud.html');

            $tag = !(empty($_GET['tag'])) ? strip_tags(UTIL_HtmlTag::stripTags($_GET['tag'])) : null;
            $this->assign('tag', $tag );

            if (empty($tag))
            {
                $showList = false;
            }
        }

        $this->addComponent('tagCloud', $tagCloud);

        $this->assign('showList', $showList);

        $list = array();
        $itemsCount = 0;

        list($list, $itemsCount) = $service->getEntryList($case, $first, $count);

        $entrys = array();

        $toolbars = array();

        $userService = BOL_UserService::getInstance();

        $authorIdList = array();

        $previewLength = 50;

        foreach ( $list as $item )
        {
            $dto = $item['dto'];
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('string' => $dto->getEntry())));
            if(isset($stringRenderer->getData()['string'])){
                $dto->setEntry($stringRenderer->getData()['string']);
            }
            $dto->setEntry($dto->getEntry());
            $dto->setTitle( UTIL_String::truncate( strip_tags($dto->getTitle()), 350, '...' )  );

            $text = explode("<!--more-->", $dto->getEntry());

            $isPreview = count($text) > 1;

            if ( !$isPreview )
            {
                $text = explode('<!--page-->', $text[0]);
                $showMore = count($text) > 1;
            }
            else
            {
                $showMore = true;
            }

            $text = $text[0];
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $text)));
            if (isset($stringRenderer->getData()['string'])) {
                $text = ($stringRenderer->getData()['string']);
            }
            if($dto->getImage()){
                $entrys[] = array(
                    'dto' => $dto,
                    'text' => $text,
                    'showMore' => $showMore,
                    'url' => OW::getRouter()->urlForRoute('user-entry', array('id'=>$dto->getId())),
                    'imageSrc' => $service->generateImageUrl($dto->getImage(), true),
                    'imageTitle' => $dto->getTitle()
                );
            }else {
                $entrys[] = array(
                    'dto' => $dto,
                    'text' => $text,
                    'showMore' => $showMore,
                    'url' => OW::getRouter()->urlForRoute('user-entry', array('id' => $dto->getId()))
                );
            }
            $authorIdList[] = $dto->authorId;
            $idList[] = $dto->getId();
        }

        if ( !empty($idList) )
        {
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($authorIdList, true, false);
            foreach ( $avatars as $avatar )
            {
                $userId = $avatar['userId'];
                $avatars[$userId]['url'] = BOL_UserService::getInstance()->getUserUrl($userId);
            }
            $this->assign('avatars', $avatars);

            $nlist = array();
            foreach ( $avatars as $userId => $avatar )
            {
                $nlist[$userId] = $avatar['title'];
            }
            $urls = BOL_UserService::getInstance()->getUserUrlsForList($authorIdList);
            $this->assign('toolbars', $this->getToolbar($idList, $list, $urls, $nlist));
        }

        $this->assign('list', $entrys);
        $this->assign('url_new_entry', OW::getRouter()->urlForRoute('entry-save-new'));

        $paging = new BASE_CMP_Paging($page, ceil($itemsCount / $rpp), 5);

        $this->addComponent('paging', $paging);

        $event = new OW_Event('news.add.component', array('newsController'=>$this));
        OW::getEventManager()->trigger($event);
        //<ARCHIVE-NAVIGATOR>


        $rows = $service->findArchiveData();
        $archive = array();

        $newRow = array();
        $convertedToJalali = false;
        foreach ( $rows as $row )
        {
            $eventData = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_DEFAULT_DATE_VALUE_SET, array('changeTojalali' => true, 'yearTochange' =>  (int) $row['y'], 'monthTochange'=> (int) $row['m'] ,'dayTochange'=> (int)$row['d'], 'monthWordFormat' =>false)));
            if($eventData->getData() && isset($eventData->getData()['changedYear'])) {
                $row['jy'] = $eventData->getData()['changedYear'];
                $convertedToJalali = true;
            }
            if($eventData->getData() && isset($eventData->getData()['changedMonth'])){
                $row['jm'] = $eventData->getData()['changedMonth'];
                $convertedToJalali = true;
            }
            if($eventData->getData() && isset($eventData->getData()['changedDay'])){
                $row['jd'] = $eventData->getData()['changedDay'];
                $convertedToJalali = true;
            }
            $newRow[] = $row;
        }
        $rows= $newRow;
        foreach ( $rows as $row )
        {
            if ( !array_key_exists($row['y'], $archive)  && !$convertedToJalali)
            {
                $archive[$row['y']] = array();
            }
            else if ( !array_key_exists($row['jy'], $archive)  && $convertedToJalali)
            {
                $archive[$row['jy']] = array();
            }
            $cfMonth =OW::getLanguage()->text('base', 'month_'.$row['m']);
            $cfYear = $row['y'];

            if($convertedToJalali){
                $changeMonthToWordFormatEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_DEFAULT_DATE_VALUE_SET, array('changeJalaliMonthToWord' => true, 'faYear' =>  (int) $row['jy'], 'faMonth'=> (int) $row['jm'] ,'faDay'=> (int)$row['jd'])));
                $cfMonth = $changeMonthToWordFormatEvent->getData()['jalaliWordMonth'];
                $cfYear = $row['jy'];
            }
            if(!$convertedToJalali) {
                $isExist=false;
                foreach ($archive as $key => $values)
                {
                    foreach ($values as $value)
                    {
                        if($value===$row['m'])
                        {
                            $isExist=true;
                        }
                    }
                }
                if(!$isExist)
                {
                    $dateParsed[$row['y']][$row['m']] = $cfMonth . ' ' . $cfYear;
                    $archive[$row['y']][] = $row['m'];
                }
            }
            else if($convertedToJalali){
                $isExist = false;
                foreach ($archive as $key => $values)
                {
                    foreach ($values as $value)
                    {
                        if($value===$row['jm'])
                        {
                            $isExist=true;
                        }
                    }
                }
                if(!$isExist)
                {
                    $dateParsed[$row['jy']][$row['jm']] = $cfMonth . ' ' . $cfYear;
                    $archive[$row['jy']][] = $row['jm'];
                }
            }
        }
        if(isset($dateParsed)) {
            $this->assign('dateParsed', $dateParsed);
        }
        if(isset($archive)) {
            $this->assign('archive', $archive);
        }
        //</ARCHIVE-NAVIGATOR>
    }

    /**
     * Get top menu for News entry list
     *
     * @return BASE_CMP_ContentMenu
     */
    private function getContentMenu()
    {
        $menuItems = array();

        $listNames = array(
            'latest' => array('iconClass' => 'ow_ic_latest'),
            'most-discussed' => array('iconClass' => 'ow_ic_most_discussed'),
            'top-rated' => array('iconClass' => 'ow_ic_most_popular'),
            'browse-by-tag' => array('iconClass' => 'ow_ic_tag')
        );

        foreach ( $listNames as $listKey => $listArr )
        {
            $menuItem = new BASE_MenuItem();
            $menuItem->setKey($listKey);
            $menuItem->setUrl(OW::getRouter()->urlForRoute('iisnews.list', array('list' => $listKey)));
            $menuItemKey = explode('-', $listKey);
            $listKey = "";
            foreach ($menuItemKey as $key)
            {
                $listKey .= strtoupper(substr($key, 0, 1)).substr($key, 1);
            }

            $menuItem->setLabel(OW::getLanguage()->text('iisnews', 'menuItem'.$listKey));
            $menuItem->setIconClass($listArr['iconClass']);
            $menuItems[] = $menuItem;
        }

        return new BASE_CMP_ContentMenu($menuItems);
    }

    private function getToolbar( $idList, $list, $ulist, $nlist )
    {
        if ( empty($idList) )
        {
            return array();
        }

        $info = array();

        $info['comment'] = BOL_CommentService::getInstance()->findCommentCountForEntityList('news-entry', $idList);

        $info['rate'] = BOL_RateService::getInstance()->findRateInfoForEntityList('news-entry', $idList);

        $info['tag'] = BOL_TagService::getInstance()->findTagListByEntityIdList('news-entry', $idList);

        $toolbars = array();

        foreach ( $list as $item )
        {
            $id = $item['dto']->id;

            $toolbars[$id] = array(
                array(
                    'class' => 'ow_ipc_date',
                    'label' => UTIL_DateTime::formatDate($item['dto']->timestamp)
                ),
            );

            if ( $info['rate'][$id]['avg_score'] > 0 )
            {
                $toolbars[$id][] = array(
                    'label' => OW::getLanguage()->text('iisnews', 'rate') . ' <span class="ow_txt_value">' . ( ( $info['rate'][$id]['avg_score'] - intval($info['rate'][$id]['avg_score']) == 0 ) ? intval($info['rate'][$id]['avg_score']) : sprintf('%.2f', $info['rate'][$id]['avg_score']) ) . '</span>',
                );
            }

            if ( !empty($info['comment'][$id]) )
            {
                $toolbars[$id][] = array(
                    'label' => OW::getLanguage()->text('iisnews', 'comments') . ' <span class="ow_txt_value">' . $info['comment'][$id] . '</span>',
                );
            }


            if ( empty($info['tag'][$id]) )
            {
                continue;
            }

            $value = "<span class='ow_wrap_normal'>" . OW::getLanguage()->text('iisnews', 'tags') . ' ';

            foreach ( $info['tag'][$id] as $tag )
            {
                $value .='<a href="' . OW::getRouter()->urlForRoute('iisnews.list', array('list'=>'browse-by-tag')) . "?tag={$tag}" . "\">{$tag}</a>, ";
            }

            $value = mb_substr($value, 0, mb_strlen($value) - 2);
            $value .= "</span>";
            $toolbars[$id][] = array(
                'label' => $value,
            );
        }

        return $toolbars;
    }
}