<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisadvancesearch.classes
 * @since 1.0
 */
class IISADVANCESEARCH_CLASS_EventHandler
{
    private static $classInstance;

    public static function getInstance()
    {
        if (self::$classInstance === null) {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }


    private function __construct()
    {
    }

    public function init()
    {
        $eventManager = OW::getEventManager();
        $eventManager->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($this, 'onBeforeDocumentRender'));
        $eventManager->bind('console.collect_items', array($this, 'collectItems'));
        $eventManager->bind(OW_EventManager::ON_AFTER_PLUGIN_ACTIVATE, array($this, 'after_plugin_activate'));
        $eventManager->bind(OW_EventManager::ON_BEFORE_PLUGIN_DEACTIVATE, array($this, 'before_plugin_deactivate'));
        $eventManager->bind('iisadvancesearch.on_collect_search_items', array($this, 'onCollectSearchItems'));
    }

    public function collectItems(OW_Event $event)
    {
        if(!OW::getUser()->isAuthenticated()){
            return;
        }

        $baseConfigs = OW::getConfig()->getValues('base');
        //members only
        if ( !OW::getUser()->isAuthenticated() && (int) $baseConfigs['guests_can_view'] === BOL_UserService::PERMISSIONS_GUESTS_CANT_VIEW)
        {
            return;
        }

        $item = new IISADVANCESEARCH_CMP_ConsoleSearch();
        $event->addItem($item, 6);
    }

    public function onBeforeDocumentRender(OW_Event $event)
    {
//        if(!OW::getUser()->isAuthenticated()){
//            return;
//        }

        $jsFile = OW::getPluginManager()->getPlugin('iisadvancesearch')->getStaticJsUrl() . 'iisadvancesearch.js';
        OW::getDocument()->addScript($jsFile);

        $cssFile = OW::getPluginManager()->getPlugin('iisadvancesearch')->getStaticCssUrl() . 'iisadvancesearch.css';
        OW::getDocument()->addStyleSheet($cssFile);

        $css = '
    html body div .ow_ic_lens.ow_console_search {
        background-image: url("' . OW::getPluginManager()->getPlugin('iisadvancesearch')->getStaticCssUrl() . 'search.svg' . '") !important;
    }';
        OW::getDocument()->addStyleDeclaration($css);

        $lang = OW::getLanguage();
        $lang->addKeyForJs('iisadvancesearch', 'search_title');
        $lang->addKeyForJs('iisadvancesearch', 'no_data_found');
        $lang->addKeyForJs('iisadvancesearch', 'users');
        $lang->addKeyForJs('iisadvancesearch', 'minimum_two_character');
        $lang->addKeyForJs('iisadvancesearch', 'forum_posts_title');
        $lang->addKeyForJs('iisadvancesearch', 'forum_post_title');
        $lang->addKeyForJs('iisadvancesearch', 'forum_post_group_name');
        $lang->addKeyForJs('iisadvancesearch', 'forum_post_section_name');
    }



    public function after_plugin_activate(OW_Event $event)
    {
        $params = $event->getParams();
        if ( !isset($params['pluginKey']))
            return;
        if( $params['pluginKey'] == "friends" && false){
            $widgetService = BOL_ComponentAdminService::getInstance();
            $widget = $widgetService->addWidget('IISADVANCESEARCH_MCMP_FriendsSearchWidget', false);
            $placeWidget = $widgetService->addWidgetToPlace($widget, BOL_MobileWidgetService::PLACE_MOBILE_INDEX);
            $widgetService->addWidgetToPosition($placeWidget, BOL_MobileWidgetService::SECTION_MOBILE_MAIN);
        }
    }
    public function before_plugin_deactivate(OW_Event $event)
    {
        $params = $event->getParams();
        if ( !isset($params['pluginKey']))
            return;
        if( $params['pluginKey'] == "friends"){
            BOL_ComponentAdminService::getInstance()->deleteWidget('IISADVANCESEARCH_MCMP_FriendsSearchWidget');
        }
    }

    public function onCollectSearchItems(OW_Event $event){
        $params = $event->getParams();
        $searchValue = '';
        if ( !empty($params['q']) )
        {
            $searchValue = $params['q'];
        }
        $searchValue = strip_tags(UTIL_HtmlTag::stripTags($searchValue));
        $maxCount = empty($params['maxCount'])?10:$params['maxCount'];
        $first= empty($params['first'])?0:$params['first'];
        $first=(int)$first;
        $count=empty($params['count'])?$first+$maxCount:$params['count'];
        $count=(int)$count;
        $userId = OW::getUser()->getId();
        if( OW::getAuthorization()->isUserAuthorized($userId,'base','search_users') || OW::getUser()->isAdmin() ){
            $resultData = IISADVANCESEARCH_CTRL_Search::getInstance()->getUsersBySearchValue($searchValue,
                true, true, $first, $count);
        }else{
            $resultData = IISADVANCESEARCH_CTRL_Search::getInstance()->getUsersBySearchValue($searchValue,
                true, false, $first, $count);
        }

        $result = array();
        $count = 0;
        foreach($resultData as $item){
            $itemInformation = array();
            $itemInformation['username'] = substr($item['url'], strpos($item['url'], 'user/') + 5);
            $itemInformation['title'] = empty($item['title'])?$itemInformation['username']:$item['title'];
            $itemInformation['id'] = $item['id'];
            $itemInformation['link'] = $item['url'];
            $itemInformation['image'] = $item['src'];
            $itemInformation['label'] = OW::getLanguage()->text('iisadvancesearch', 'users_label');
            $result[] = $itemInformation;
            $count++;
            if($count == $maxCount){
                break;
            }
        }

        $data = $event->getData();
        $data['users'] = array('label' => OW::getLanguage()->text('iisadvancesearch', 'users_label'), 'data' => $result);
        $event->setData($data);
    }
}