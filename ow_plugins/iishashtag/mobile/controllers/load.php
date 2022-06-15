<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_MCTRL_Load extends OW_MobileActionController
{
    /***
     * @param $params
     * @throws Redirect404Exception
     */
    public function index($params){
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'iishashtag', 'mobile_main_menu_item');
        $service = IISHASHTAG_BOL_Service::getInstance();

        $tag = empty($params['tag'])? false:trim(htmlspecialchars(UTIL_HtmlTag::stripJs(urldecode($params['tag']))));
        $tag = preg_replace("/[#@! &^%$';+()]/", '', $tag);
        if($tag == '')
            $tag = false;
        $this->assign('tag',$tag);
        $contentMenu = new BASE_CMP_ContentMenu(null);
        $isEmptyList = false;

        //search form
        $this->addForm($service->getSearchForm());

        if(!$tag) {
            //no tag specified
            $page_title = OW::getLanguage()->text('iishashtag', 'list_page_title_default');
            $top_tags_tmp = $service->findTags("",50);
            $top_tags = array();
            if(count($top_tags_tmp)>0) {
                $count_max = $top_tags_tmp[0]['count'];
                foreach($top_tags_tmp as $item) {
                    $size = 12 + intval(intval($item['count']) * 8 / $count_max);
                    $top_tags[] = array(
                        'label' => $item['tag'],
                        'size' => $size,
                        'lineHeight' => $size + 5,
                        'url' => OW::getRouter()->urlForRoute('iishashtag.tag', array('tag' => $item['tag']))
                    );
                }
            }
            $this->assign('top_tags',$top_tags);
            $isEmptyList = (count($top_tags)==0);
            $this->assign('no_newsfeed',false);
        }else if(!BOL_PluginDao::getInstance()->findPluginByKey('newsfeed') || !BOL_PluginDao::getInstance()->findPluginByKey('newsfeed')->isActive()) {
            $page_title = OW::getLanguage()->text('iishashtag', 'list_page_title_default');
            $this->assign('no_newsfeed',true);
        }else{
            $page_title = OW::getLanguage()->text('iishashtag', 'list_page_title', array("tag"=>$tag));

            //menu
            $selectedTab = empty($params['tab'])?'':$params['tab'];
            $contentMenuArray = $service->getContentMenu($tag, $selectedTab);
            if($selectedTab != $contentMenuArray['default']){
                $this->redirect(OW::getRouter()->urlForRoute('iishashtag.tag.tab', array('tag' => $tag , 'tab' => $contentMenuArray['default'])));
            }

            $allCounts = $contentMenuArray['allCounts'];
            $selectedTab = $contentMenuArray['default'];
            $selectedPage = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;

            $this->assign('no_newsfeed',false);
            $this->assign('selected_tab',$selectedTab);

            if ($selectedTab == "newsfeed" && BOL_AuthorizationService::getInstance()->isActionAuthorized('iishashtag', 'view_newsfeed')) {
                $entityIds = $service->findEntitiesByTag($tag, "user-status");
                if (count($entityIds) > 0) {
                    $this->addComponent('newsfeedComponent', new IISHASHTAG_MCMP_Newsfeed($entityIds, $allCounts['newsfeed']));
                }
                $isEmptyList = (count($entityIds) == 0);
            } else if ($selectedTab == "news" && OW::getPluginManager()->isPluginActive('iisnews')) {
                $entityIds = $service->findEntitiesByTag($tag, "news-entry");
                if (count($entityIds) > 0) {
                    $this->addComponent('newsComponent', new IISHASHTAG_MCMP_News($entityIds, $selectedPage));
                }
                $isEmptyList = (count($entityIds) == 0);
            }
            else if ($selectedTab == "blogs" && OW::getPluginManager()->isPluginActive('blogs')) {
                $entityIds = $service->findEntitiesByTag($tag, "blog-post");
                if (count($entityIds) > 0) {
                    $this->addComponent('blogsComponent', new IISHASHTAG_MCMP_Blogs($entityIds, $selectedPage));
                }
                $isEmptyList = (count($entityIds) == 0);
            }
            else if ($selectedTab == "groups" && OW::getPluginManager()->isPluginActive('groups')) {
                $entityIds = $service->findGroupEntitiesByTag($tag);
                if (count($entityIds) > 0) {
                    $this->addComponent('groupsComponent', new IISHASHTAG_MCMP_Groups($entityIds, $allCounts['groups'], $selectedPage));
                }
                $isEmptyList = (count($entityIds) == 0);
            } else if ($selectedTab == "event" && OW::getPluginManager()->isPluginActive('event')) {
                $entityIds = $service->findEntitiesByTag($tag, "event");
                if (count($entityIds) > 0) {
                    $this->addComponent('eventComponent', new IISHASHTAG_MCMP_Event($entityIds, $allCounts['event'], $selectedPage));
                }
                $isEmptyList = (count($entityIds) == 0);
            } else if ($selectedTab == "video" && OW::getPluginManager()->isPluginActive('video')) {
                $entityIds = $service->findEntitiesByTag($tag, "video_comments");
                if (count($entityIds) > 0) {
                    $this->addComponent('videoComponent', new IISHASHTAG_MCMP_Video($entityIds, $allCounts['video'], $selectedPage));
                }
                $isEmptyList = (count($entityIds) == 0);
            } else if ($selectedTab == "photo" && OW::getPluginManager()->isPluginActive('photo')) {
                $this->addComponent('photoComponent', new IISHASHTAG_MCMP_Photo($tag,  $allCounts['photo']));
            } else if ($selectedTab == "forum" && OW::getPluginManager()->isPluginActive('forum')) {
                $entityIds = $service->findEntitiesByTag($tag, "forum-post");
                if (count($entityIds) > 0) {
                    $this->addComponent('forumComponent', new IISHASHTAG_MCMP_Forum($entityIds, $allCounts['forum'], $selectedPage));
                }
                $isEmptyList = (count($entityIds) == 0);
            } else {
                $notFound = true;
                foreach ($allCounts as $key=>$count){
                    if($count > 0){
                        $notFound = false;
                        break;
                    }
                }
                if ($notFound) {
                    throw new Redirect404Exception();
                }else{
                    $this->assign('selected_tab','no_access');
                    $this->addComponent('no_access', new IISHASHTAG_CMP_NoAccess($allCounts));
                }
            }
        }

        if(isset($selectedTab)) {
            $contentMenuArray = $service->getContentMenu($tag, $selectedTab);
            $contentMenu = $contentMenuArray['menu'];
            $contentMenu = new BASE_MCMP_ContentMenu($contentMenu);
            $selectedTab = $contentMenuArray['default'];
            $contentElement = $contentMenu->getElement($selectedTab);
            if (isset($contentElement)) {
                $contentElement->setActive(true);
            }
        }

        $this->addComponent('menu', $contentMenu);
        $this->assign('isEmpty',$isEmptyList);
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'iishashtag', 'mobile_main_menu_item');

        $this->setPageHeading($page_title);
        $this->setPageTitle($page_title);
        $this->setPageHeadingIconClass('ow_ic_write');
    }
}