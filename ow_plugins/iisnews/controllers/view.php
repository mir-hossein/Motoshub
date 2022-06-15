<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisnews.controllers
 * @since 1.0
 */
class IISNEWS_CTRL_View extends OW_ActionController
{

    public function index( $params )
    {

        $username = !empty($params['user']) ? $params['user'] : '';

        $id = $params['id'];

        $plugin = OW::getPluginManager()->getPlugin('iisnews');

        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'iisnews', 'main_menu_item');

        $service = EntryService::getInstance();

        $userService = BOL_UserService::getInstance();

        $this->assign('user', ((OW::getUser()->getId() !== null) ? $userService->findUserById(OW::getUser()->getId()) : null));

        $entry = $service->findById($id);

        if ( $entry === null )
        {
            throw new Redirect404Exception();
        }

        $eventForEnglishFieldSupport = new OW_Event('iismultilingualsupport.show.data.in.multilingual', array('entity' => $entry,'entityType'=>'news','display'=>'view'));
        OW::getEventManager()->trigger($eventForEnglishFieldSupport);
        if ($entry->isDraft() && $entry->authorId != OW::getUser()->getId())
        {
            throw new Redirect404Exception();
        }
        $stringRenderer = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ,array('string' =>  $entry->entry)));
        if(isset($stringRenderer->getData()['string'])){
            $entry->entry = ($stringRenderer->getData()['string']);
        }
        $entry->entry = BASE_CMP_TextFormatter::fromBBtoHtml($entry->entry);

        $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $entry->entry)));
        if (isset($stringRenderer->getData()['string'])) {
            $entry->entry = ($stringRenderer->getData()['string']);
        }

        $entry->setTitle( strip_tags($entry->getTitle()) );
        if ( $entry->getImage() )
        {
            $this->assign('imgsrc', $service->generateImageUrl($entry->getImage(), true));
        }
        if ( !OW::getUser()->isAuthorized('iisnews', 'view')  && !OW::getUser()->isAdmin())
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('iisnews', 'view');
            throw new AuthorizationException($status['msg']);
        }

        if ( ( OW::getUser()->isAuthenticated() && OW::getUser()->getId() != $entry->getAuthorId() ) && !OW::getUser()->isAuthorized('iisnews', 'view') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('iisnews', 'view');
            throw new AuthorizationException($status['msg']);
        }

        /* Check privacy permissions */
        /*
        if ( $entry->authorId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('iisnews') )
        {
            $eventParams = array(
                'action' => 'news_view_news_entrys',
                'ownerId' => $entry->authorId,
                'viewerId' => OW::getUser()->getId()
            );

            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
         */

        $parts = explode('<!--page-->', $entry->getEntry());

        $page = !empty($_GET['page']) ? $_GET['page'] : 1;

        $count = count($parts);

        if ( strlen($username) > 0 )
        {
            $author = $userService->findByUsername($username);
        }
        else
        {
            $author = $userService->findUserById($entry->getAuthorId());
            $isAuthorExists = !empty($author);
            if ( $isAuthorExists )
            {
                $username = $author->getUsername();
            }
        }

        $this->assign('isAuthorExists', $isAuthorExists);

        if ( $isAuthorExists )
        {
            $displayName = $userService->getDisplayName($author->getId());

            $this->assign('username', $userService->getUserName($author->getId()));
            $this->assign('displayname', $displayName);

            $url = OW::getRouter()->urlForRoute('user-iisnews', array('user' => $username));

            $pending_approval_text = '';
            if ($entry->getStatus() == EntryService::POST_STATUS_APPROVAL)
            {
                $pending_approval_text = '<span class="ow_remark ow_small">('.OW::getLanguage()->text('base', 'pending_approval').')</span>';
            }
            $this->setPageHeading(htmlspecialchars($entry->getTitle()));
            $this->setPageHeadingIconClass('ow_ic_write');

            OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'news_entry_title', array('entry_title' => htmlspecialchars($entry->getTitle()))));

            $entry_body = UTIL_String::truncate($entry->getEntry(), 350, '...');
            $entryTagsArray = BOL_TagService::getInstance()->findEntityTags($entry->getId(), 'news-entry');
            $entryTags = "";

            foreach ( $entryTagsArray as $tag )
            {
                $entryTags .= $tag->label . ", ";
            }
            $entryTags = substr($entryTags, 0, -2);
            OW::getDocument()->setDescription(OW::getLanguage()->text('iisnews', 'news_entry_description', array('entry_body' => htmlspecialchars(strip_tags($entry_body)), 'tags' => htmlspecialchars($entryTags))));
            //OW::getDocument()->setKeywords(OW::getLanguage()->text('nav', 'page_default_keywords').", ".$entryTags);
        }



        $info = array(
            'dto' => $entry,
            'text' => $parts[$page - 1]
        );

        $this->assign('info', $info);

        if ( $isAuthorExists )
        {
            //iisnews navigation
            $prev = $service->findAdjacentUserEntry($author->getId(), $entry->getId(), 'prev');
            $next = $service->findAdjacentUserEntry($author->getId(), $entry->getId(), 'next');

            if ( !empty($prev) )
            {
                $prevUser = $userService->findUserById($prev->getAuthorId());
            }

            if ( !empty($next) )
            {
                $nextUser = $userService->findUserById($next->getAuthorId());
            }

            $this->assign('adjasentUrl',
                array(
                    'next' => (!empty($nextUser) ) ? OW::getRouter()->urlForRoute('user-entry', array('id' => $next->getId(), 'user' => $nextUser->getUsername())) : '',
                    'prev' => (!empty($prevUser) ) ? OW::getRouter()->urlForRoute('user-entry', array('id' => $prev->getId(), 'user' => $prevUser->getUsername())) : '',
                    'index' => OW::getRouter()->urlForRoute('user-iisnews', array('user' => $author->getUsername()))
                )
            );
        }
        else
        {
            $this->assign('adjasentUrl', null);
        }
        //~news navigation
        //toolbar

        $tb = array();

        $toolbarEvent = new BASE_CLASS_EventCollector('news.collect_entry_toolbar_items', array(
            'entryId' => $entry->id,
            'entryDto' => $entry
        ));

        OW::getEventManager()->trigger($toolbarEvent);

        foreach ( $toolbarEvent->getData() as $toolbarItem )
        {
            array_push($tb, $toolbarItem);
        }

        if ($entry->getStatus() == EntryService::POST_STATUS_APPROVAL && OW::getUser()->isAuthorized('iisnews'))
        {
            $tb[] = array(
                'label' => OW::getLanguage()->text('base', 'approve'),
                'href' => OW::getRouter()->urlForRoute('entry-approve', array('id'=>$entry->getId())),
                'id' => 'news_entry_toolbar_approve',
                'class'=>'ow_mild_green'
            );
        }

        if ( OW::getUser()->isAuthenticated() && ( $entry->getAuthorId() != OW::getUser()->getId() ) )
        {
            $js = UTIL_JsGenerator::newInstance()
                ->jQueryEvent('#news_entry_toolbar_flag', 'click', UTIL_JsGenerator::composeJsString('OW.flagContent({$entityType}, {$entityId});',
                            array(
                        'entityType' => EntryService::FEED_ENTITY_TYPE,
                        'entityId' => $entry->getId()
            )));

            OW::getDocument()->addOnloadScript($js, 1001);

            $tb[] = array(
                'label' => OW::getLanguage()->text('base', 'flag'),
                'href' => 'javascript://',
                'id' => 'news_entry_toolbar_flag'
            );
        }
        if ( OW::getUser()->isAuthenticated() && ( OW::getUser()->isAdmin() || OW::getUser()->isAuthorized('iisnews') ) )
        {
            $tb[] = array(
                'href' => OW::getRouter()->urlForRoute('entry-save-edit', array('id' => $entry->getId())),
                'label' => OW::getLanguage()->text('iisnews', 'toolbar_edit')
            );
            $code='';
            $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$entry->getId(),'isPermanent'=>true,'activityType'=>'delete_news')));
            if(isset($iisSecuritymanagerEvent->getData()['code'])){
                $code = $iisSecuritymanagerEvent->getData()['code'];
            }
            $tb[] = array(
                'href' => OW::getRouter()->urlFor('IISNEWS_CTRL_Save', 'delete', array('id' => $entry->getId(), 'code' => $code)),
                'click' => "return confirm('" . OW::getLanguage()->text('iisnews', 'are_you_sure_delete_news') . "');",
                'label' => OW::getLanguage()->text('iisnews', 'toolbar_delete')
            );
        }

        $this->assign('tb', $tb);
        //~toolbar

        $paging = new BASE_CMP_Paging($page, $count, $count);

        //<ARCHIVE-NAVIGATOR>


        $this->assign('paging', $paging->render());
        if ( $isAuthorExists )
        {
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
        }

        //</ARCHIVE-NAVIGATOR>
        if ( $isAuthorExists )
        {
            $this->assign('author', $author);
        }

        $this->assign('isModerator', OW::getUser()->isAuthorized('iisnews'));
        if ( $isAuthorExists )
        {
            $this->assign('userNewsUrl', OW::getRouter()->urlForRoute('user-iisnews', array('user' => $author->getUsername())));
        }

        $rateInfo = new BASE_CMP_Rate('iisnews', 'news-entry', $entry->getId(), $entry->getAuthorId());

        /* Check comments privacy permissions */
        $allow_comments = true;
        if ($entry->getStatus() == EntryService::POST_STATUS_APPROVAL)
        {
            $allow_comments = false;
            $rateInfo->setVisible(false);
        }
        else
        {
            /*
            if ( $entry->authorId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('iisnews') )
            {
                $eventParams = array(
                    'action' => 'news_comment_news_entrys',
                    'ownerId' => $entry->authorId,
                    'viewerId' => OW::getUser()->getId()
                );

                try
                {
                    OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
                }
                catch ( RedirectException $ex )
                {
                    $allow_comments = false;
                }
            }
            */
        }
        /* */

        $this->addComponent('rate', $rateInfo);

        // additional components
        $cmpParams = new BASE_CommentsParams('iisnews', 'news-entry');
        $cmpParams->setEntityId($entry->getId())
            ->setOwnerId($entry->getAuthorId())
            ->setDisplayType(BASE_CommentsParams::DISPLAY_TYPE_BOTTOM_FORM_WITH_FULL_LIST)
            ->setAddComment($allow_comments);

        $this->addComponent('comments', new BASE_CMP_Comments($cmpParams));

        $this->assign('avatarUrl', '');

        $tagCloud = new BASE_CMP_EntityTagCloud('news-entry', OW::getRouter()->urlForRoute('iisnews.list', array('list'=>'browse-by-tag')));

        $tagCloud->setEntityId($entry->getId());

        $this->addComponent('tagCloud', $tagCloud);
        //~ additional components
    }

    public function approve($params)
    {
        if (!OW::getUser()->isAuthenticated())
        {
            throw new AuthenticateException();
        }

        if (!OW::getUser()->isAuthorized('iisnews'))
        {
            throw new Redirect403Exception();
        }

        //TODO trigger event for content moderation;
        $entryId = $params['id'];
        $entryDto = EntryService::getInstance()->findById($entryId);
        if (!isset($entryDto))
        {
            throw new Redirect404Exception();
        }

        $backUrl = OW::getRouter()->urlForRoute('entry', array('id'=>$entryId));

        $event = new OW_Event("moderation.approve", array(
            "entityType" => EntryService::FEED_ENTITY_TYPE,
            "entityId" => $entryId
        ));

        OW::getEventManager()->trigger($event);

        $data = $event->getData();
        if ( empty($data) )
        {
            $this->redirect($backUrl);
        }

        if ( $data["message"] )
        {
            OW::getFeedback()->info($data["message"]);
        }
        else
        {
            OW::getFeedback()->error($data["error"]);
        }

        $this->redirect($backUrl);
    }
}