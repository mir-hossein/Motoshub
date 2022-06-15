<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisnews.controllers
 * @since 1.0
 */
class IISNEWS_CTRL_UserNews extends OW_ActionController
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

        /*
          @var $author BOL_User
         */
        if ( !empty($params['user']) )
        {
            $author = $userService->findByUsername($params['user']);
        }
        else
        {
            $author = $userService->findUserById(OW::getUser()->getId());
        }

        if ( empty($author) )
        {
            throw new Redirect404Exception();
        }

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

        $displayName = $userService->getDisplayName($author->getId());

        $this->assign('author', $author);
        $this->assign('username', $author->getUsername());
        $this->assign('displayname', $displayName);

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
            $list = $service->findUserEntryListByPeriod($author->getId(), $lb, $ub, $first, $count);

            $itemsCount = $service->countUserEntryByPeriod($author->getId(), $lb, $ub);


            $arciveHeaderPart = ', ' . $monthTitle . " {$yearTitle} " . $l->text('base', 'archive');

            OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'user_news_archive_title', array('month_name'=>$monthTitle, 'display_name'=>$displayName)));
            OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'user_news_archive_description', array('year'=>$yearTitle, 'month_name'=>$monthTitle, 'display_name'=>$displayName) ));
        }
        else
        {
            $list = $service->findUserEntryList($author->getId(), $first, $count);

            $itemsCount = $service->countUserEntry($author->getId());

            OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'user_news_title', array('display_name'=>$displayName)));
            OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'user_news_description', array('display_name'=>$displayName) ));
        }

        $this->assign('archiveHeaderPart', (!empty($arciveHeaderPart) ? $arciveHeaderPart : ''));

        $entrys = array();

        $commentInfo = array();

        $idList = array();

        foreach ( $list as $dto ) /* @var dto Entry */
        {
            $idList[] = $dto->getId();
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('string' => $dto->getEntry())));
            if(isset($stringRenderer->getData()['string'])){
                $dto->setEntry($stringRenderer->getData()['string']);
            }
            $dto_entry = BASE_CMP_TextFormatter::fromBBtoHtml($dto->getEntry());

            $dto->setEntry($dto_entry);
            $parts = explode('<!--more-->', $dto->getEntry());

            if (!empty($parts))
            {
                $text = $parts[0];
                //$text = UTIL_HtmlTag::sanitize($text);
            }
            else
            {
                $text = $dto->getEntry();
            }

            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $text)));
            if (isset($stringRenderer->getData()['string'])) {
                $text = ($stringRenderer->getData()['string']);
            }

            $entrys[] = array(
                'id' => $dto->getId(),
                'href' => OW::getRouter()->urlForRoute('user-entry', array('id' => $dto->getId())),
                'title' => UTIL_String::truncate($dto->getTitle(), 350, '...'),
                'text' => $text,
                'truncated' => (count($parts) > 1) ? true: false,
            );
        }

        if ( !empty($idList) )
        {
            $commentInfo = BOL_CommentService::getInstance()->findCommentCountForEntityList('news-entry', $idList);
            $this->assign('commentInfo', $commentInfo);

            $tagsInfo = BOL_TagService::getInstance()->findTagListByEntityIdList('news-entry', $idList);
            $this->assign('tagsInfo', $tagsInfo);

            $tb = array();

            foreach ( $list as $dto ) /* @var dto Entry */
            {

                $tb[$dto->getId()] = array(
                    array(
                        'label' => UTIL_DateTime::formatDate($dto->timestamp)
                    ),
                );

                //if ( $commentInfo[$dto->getId()] )
                //{
                    $tb[$dto->getId()][] = array(
                        'href' => OW::getRouter()->urlForRoute('entry', array('id' => $dto->getId())),
                        'label' => OW::getLanguage()->text('iisnews', 'toolbar_comments') . '<span class="ow_outline">' . $commentInfo[$dto->getId()] . '</span> '
                    );
                //}

                if ( $tagsInfo[$dto->getId()] )
                {
                    $tags = &$tagsInfo[$dto->getId()];
                    $t = OW::getLanguage()->text('iisnews', 'tags');
                    for ( $i = 0; $i < (count($tags) > 3 ? 3 : count($tags)); $i++ )
                    {
                        $t .= " <a href=\"" . OW::getRouter()->urlForRoute('iisnews.list', array('list'=>'browse-by-tag')) . "?tag={$tags[$i]}\">{$tags[$i]}</a>" . ( $i != 2 ? ',' : '' );
                    }

                    $tb[$dto->getId()][] = array('label' => mb_substr($t, 0, mb_strlen($t) - 1));
                }
            }

            $this->assign('tb', $tb);
        }

        $this->assign('list', $entrys);

        $info = array(
            'lastEntry' => $service->findUserLastEntry($author->getId()),
            'author' => $author,
        );

        $this->assign('info', $info);

        $paging = new BASE_CMP_Paging($page, ceil($itemsCount / $rpp), 5);

        $this->assign('paging', $paging->render());

        $rows = $service->findUserArchiveData($author->getId());

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

        $this->assign('my_drafts_url', OW::getRouter()->urlForRoute('iisnews-manage-drafts'));

        if (OW::getUser()->isAuthenticated())
        {
        $isOwner = ( $params['user'] == OW::getUser()->getUserObject()->getUsername() ) ? true : false;
        }
        else
        {
            $isOwner = false;
        }

        $this->assign('isOwner', $isOwner);
    }
}
