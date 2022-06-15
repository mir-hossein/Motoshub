<?php

/**
 *
 * @package ow_plugins.iisnews.controllers
 * @since 1.0
 */
class IISNEWS_MCTRL_View extends OW_MobileActionController
{


    public function __construct()
    {
        parent::__construct();
    }
    public function index( $params )
    {

        $this->setPageTitle(OW::getLanguage()->text('iisnews', 'index_page_title'));
        $this->setPageHeading(OW::getLanguage()->text('iisnews', 'index_page_heading'));

        $username = !empty($params['user']) ? $params['user'] : '';

        $id = $params['id'];

        $plugin = OW::getPluginManager()->getPlugin('iisnews');

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
        if ( $entry->getImage() )
        {
            $this->assign('imgsrc', $service->generateImageUrl($entry->getImage(), true));
        }
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
        $Title = strip_tags($entry->getTitle());
        $this->setPageTitle(OW::getLanguage()->text('iisnews', 'index_page_title',array('title'=>$Title)));
        $this->setPageHeading(UTIL_String::truncate(strip_tags($entry->getTitle()), 350, '...' ));

        $this->assign('newsTitle',UTIL_String::truncate(strip_tags($entry->getTitle()), 350, '...' ));

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


        $tags = BOL_TagService::getInstance()->findEntityTags($entry->getId(),'news-entry');
        if(sizeof($tags)>0){
            $labels = " ";
            $comma = OW::getLanguage()->text('base', 'comma').' ';
            foreach($tags as $tag)
            {
                $labels .= '<a href="'.OW::getRouter()->urlForRoute('iisnews.list', array('list'=>'browse-by-tag')) . "?tag=".$tag->getLabel().'">'.$tag->getLabel().'</a>'.$comma;
            }
            $labels = rtrim($labels, $comma);
            $this->assign('tags', $labels);
        }


        $info = array(
            'dto' => $entry,
            'text' => $parts[$page - 1]
        );

        $this->assign('info', $info);

        $paging = new BASE_CMP_Paging($page, $count, $count);
        $this->assign('paging', $paging->render());
        if ( $isAuthorExists )
        {
            $this->assign('author', $author);
        }

        $this->assign('isModerator', OW::getUser()->isAuthorized('iisnews'));

        $allow_comments = true;
        /* */

        // additional components
        $cmpParams = new BASE_CommentsParams('iisnews', 'news-entry');
        $cmpParams->setEntityId($entry->getId())
            ->setOwnerId($entry->getAuthorId())
            ->setDisplayType(BASE_CommentsParams::DISPLAY_TYPE_BOTTOM_FORM_WITH_FULL_LIST)
            ->setAddComment($allow_comments);
        $cmpCo=  new BASE_MCMP_Comments($cmpParams);
        $this->addComponent('comments', $cmpCo);

        $plugin = BOL_PluginService::getInstance()->findPluginByKey("iismenu");
        if (isset($plugin) && $plugin->isActive())
            $this->assign("iismenu_active", true);

        $this->assign('backUrl', (OW::getRouter()->urlForRoute('iisnews-default')));

        $this->assign('avatarUrl', '');
        //~ additional components
        if ( OW::getUser()->isAuthenticated() && ( OW::getUser()->isAdmin() || OW::getUser()->isAuthorized('iisnews') ) )
        {
            $this->assign('canEdit', true);
            $editNews = array(
                'href' => OW::getRouter()->urlFor('IISNEWS_MCTRL_Save', 'index', array('id' => $entry->getId())),
                'label' => OW::getLanguage()->text('iisnews', 'toolbar_edit')
            );

            $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
                array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$entry->getId(),'isPermanent'=>true,'activityType'=>'delete_news')));
            if(isset($iisSecuritymanagerEvent->getData()['code'])){
                $code = $iisSecuritymanagerEvent->getData()['code'];
            }
            $deleteNews = array(
                'href' => OW::getRouter()->urlFor('IISNEWS_MCTRL_Save', 'delete', array('id' => $entry->getId(),'code'=>$code)),
                'click' => "return confirm('" . OW::getLanguage()->text('iisnews', 'are_you_sure_delete_news') . "');",
                'label' => OW::getLanguage()->text('iisnews', 'toolbar_delete')
            );
            $this->assign('editNews', $editNews);
            $this->assign('deleteNews', $deleteNews);
        }

        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_NEWS_VIEW_RENDER, array('newsView' => $this)));

        $this->assign("urlForBack",OW::getRouter()->urlForRoute("iisnews"));
    }

}