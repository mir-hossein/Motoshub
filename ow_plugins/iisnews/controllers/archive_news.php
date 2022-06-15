<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisnews.controllers
 * @since 1.0
 */
class IISNEWS_CTRL_ArchiveNews extends OW_ActionController
{

    public function index( $params )
    {
        $plugin = OW::getPluginManager()->getPlugin('iisnews');

        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'iisnews', 'main_menu_item');

        if ( !OW::getUser()->isAdmin() && !OW::getUser()->isAuthorized('iisnews', 'view') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('iisnews', 'view');
            throw new AuthorizationException($status['msg']);
        }

        /*
          @var $service EntryService
         */
        $service = EntryService::getInstance();

        /*
          @var $userService BOL_UserService
         */
        $userService = BOL_UserService::getInstance();


        /* Check privacy permissions */
        /*
        $eventParams = array(
            'action' => 'news_view_news_entrys',
            'ownerId' => $author->getId(),
            'viewerId' => OW::getUser()->getId()
        );

        OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        */
        
        
        $displaySocialSharing = true;
        /*
        try {
            $eventParams = array(
                'action' => 'news_view_news_entrys',
                'ownerId' => $author->getId(),
                'viewerId' => 0
            );

            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        catch( RedirectException $ex )
        {
            $displaySocialSharing  = false;
        }
        */
        
        if ( $displaySocialSharing && !BOL_AuthorizationService::getInstance()->isActionAuthorizedForUser(0, 'iisnews', 'view')  )
        {
            $displaySocialSharing  = false;
        }
        
        $this->assign('display_social_sharing', $displaySocialSharing);


        $this->setPageHeading(OW::getLanguage()->text('iisnews', 'news'));
        $this->setPageHeadingIconClass('ow_ic_write');

        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? intval($_GET['page']) : 1;

        $rpp = (int) OW::getConfig()->getValue('iisnews', 'results_per_page');

        $first = ($page - 1) * $rpp;
        $count = $rpp;

        if ( !empty($_GET['month']) )
        {
            $l = OW::getLanguage();
            $archive_params = htmlspecialchars($_GET['month']);
            $arr = explode('-', $archive_params);
            $month = $arr[0];
            $year = $arr[1];
            $lbday =1;
            $ubday =null;
            $lbmonth=$month;
            $ubmonth = $month + 1;
            $monthTitle = $l->text('base', "month_{$month}");
            $yearTitle = $year;
            $lbChangeEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_DEFAULT_DATE_VALUE_SET, array('changeNewsJalaliToGregorian' => true, 'faYear' =>  (int)$year, 'faMonth'=> (int)$month ,'faDay'=> 1)));
            if($lbChangeEvent->getData() && isset($lbChangeEvent->getData()['gregorianYearNews'])){
                $year = $lbChangeEvent->getData()['gregorianYearNews'];
                $monthTitle =  OW::getLanguage()->text('iisjalali', 'date_time_month_short_fa_'.$month);
            }
            if($lbChangeEvent->getData() && isset($lbChangeEvent->getData()['gregorianMonthNews'])){
                $lbmonth = $lbChangeEvent->getData()['gregorianMonthNews'];
            }
            if($lbChangeEvent->getData() && isset($lbChangeEvent->getData()['gregorianDayNews'])){
                $lbday = $lbChangeEvent->getData()['gregorianDayNews'];
            }
            $ubChangeEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_DEFAULT_DATE_VALUE_SET, array('changeNewsJalaliToGregorian' => true, 'faYear' =>  (int)$year, 'faMonth'=> (int)$month+1 ,'faDay'=> 1)));
            if($ubChangeEvent->getData() && isset($ubChangeEvent->getData()['gregorianMonthNews'])){
                $ubmonth = $ubChangeEvent->getData()['gregorianMonthNews'];
            }
            if($ubChangeEvent->getData() && isset($ubChangeEvent->getData()['gregorianDayNews'])){
                $ubday = $ubChangeEvent->getData()['gregorianDayNews'];
            }
            if($ubmonth<$lbmonth){
                $ubyear= $year +1;
            }else{
                $ubyear= $year;
            }
            $lb = mktime(null, null, null, $lbmonth, $lbday, $year);
            $ub = mktime(null, null, null, $ubmonth, $ubday, $ubyear);
            $list = $service->findEntryListByPeriod($lb, $ub, $first, $count);

            $itemsCount = $service->countEntryByPeriod($lb, $ub);


            $arciveHeaderPart = ', ' . $monthTitle . " {$yearTitle} " . $l->text('base', 'archive');

            OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'archive_news_archive_title', array('year'=>$yearTitle,'month_name'=>$monthTitle)));
            OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'archive_news_archive_description', array('year'=>$yearTitle, 'month_name'=>$monthTitle) ));
        }
        else
        {
            $list = $service->findEntryList($first, $count);

            $itemsCount = $service->countEntry();

            OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'archive_news_title'));
            OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'archive_news_description' ));
        }

        $this->assign('archiveHeaderPart', (!empty($arciveHeaderPart) ? $arciveHeaderPart : ''));


        $entrys = array();

        $toolbars = array();

        $userService = BOL_UserService::getInstance();

        $authorIdList = array();

        $previewLength = 50;

        foreach ( $list as $item )
        {
            $dto = $item;
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

        $paging = new BASE_CMP_Paging($page, ceil($itemsCount / $rpp), 5);

        $this->addComponent('paging', $paging);

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
        $dateParsed = array();
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
        $this->assign('dateParsed', $dateParsed);
        $this->assign('archive', $archive);

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
            $id = $item->id;

            $toolbars[$id] = array(
                array(
                    'class' => 'ow_ipc_date',
                    'label' => UTIL_DateTime::formatDate($item->timestamp)
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
