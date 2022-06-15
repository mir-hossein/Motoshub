<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_CLASS_EventHandler
{
    private static $classInstance;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }
        return self::$classInstance;
    }

    private function __construct()
    {
    }

    public function genericInit()
    {
        $service = IISHASHTAG_BOL_Service::getInstance();
        OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($this, 'onBeforeDocumentRender'));
        OW::getEventManager()->bind('admin.add_auth_labels', array($this, 'addAuthLabels'));
        OW::getEventManager()->bind('iisadvancesearch.on_collect_search_items', array($this, 'onCollectSearchItems'));

        //new content added
        OW::getEventManager()->bind('feed.after_comment_add', array($service, 'onAddComment'));
        OW::getEventManager()->bind('feed.action', array($service, 'onEntityUpdate') , 1500);
        OW::getEventManager()->bind('feed.delete_item', array($service, 'onEntityUpdate'));
        OW::getEventManager()->bind('hashtag.on_entity_change', array($service,'onEntityUpdate'));
        OW::getEventManager()->bind('hashtag.edit_newsfeed', array($service, 'onEntityUpdate'));
        OW::getEventManager()->bind('base_delete_comment', array($service, 'onCommentDelete'));
//        OW::getEventManager()->bind('feed.hashtag', array($service, 'feedHashtag'));

        //rendering content
        OW::getEventManager()->bind('base.comment_item_process', array($service, 'renderComments')); //comments, images
        //OW::getEventManager()->bind(IISEventManager::ON_FEED_ITEM_RENDERER, array($service,'renderNewsfeed') );
        //OW::getEventManager()->bind(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ, array($service,'renderString')); //newsfeed, iisnews
        OW::getEventManager()->bind('hashtag.before_render_string', array($service,'renderString')); //newsfeed, groups, event, video, forum, iisnews

    }

    public function onBeforeDocumentRender( OW_Event $event )
    {
        //  if (!startsWith(OW::getRouter()->getUri(), "forum/"))
        {
            OW::getDocument()->addStyleSheet( OW::getPluginManager()->getPlugin('iishashtag')->getStaticCssUrl() . 'iishashtag.css' );

            $js = ";var iishashtagLoadTagsUrl='". OW::getRouter()->urlForRoute('iishashtag.load_tags')."/';";
            $js = $js.";var iishashtagMaxCount=". OW::getConfig()->getValue('iishashtag', 'max_count').";";
            $friends = "var iishashtag_friends = [{tag: 'i.moradnejad', count: '5'}];";
            $js = $js.";".$friends.";";
            OW::getDocument()->addScriptDeclarationBeforeIncludes($js);
            OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('iishashtag')->getStaticJsUrl() . 'suggest.js' );
            OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('iishashtag')->getStaticJsUrl() . 'iishashtag.js' );
        }
    }

    /**
     * @param BASE_CLASS_EventCollector $event
     */
    public function addAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'iishashtag' => array(
                    'label' => $language->text('iishashtag', 'auth_group_label'),
                    'actions' => array(
                        'view_newsfeed' => $language->text('iishashtag', 'auth_action_label_view_newsfeed'),
                    )
                )
            )
        );
    }

    public function onCollectSearchItems(OW_Event $event){
        $params = $event->getParams();
        $searchValue = '';
        if ( !empty($params['q']) )
        {
            $searchValue = str_replace('#', '', $params['q']);
        }
        $maxCount = empty($params['maxCount'])?10:$params['maxCount'];
        $first= empty($params['first'])?0:$params['first'];
        $first=(int)$first;
        $count=empty($params['count'])?$first+$maxCount:$params['count'];
        $count=(int)$count;
        $result = array();
        $topics = IISHASHTAG_BOL_Service::getInstance()->findTagsInAdvanceSearchPlugin($searchValue,$first,$count);

        $count = 0;

        foreach($topics as $item){
            $itemInformation = array();
            $itemInformation['title'] = $item['tag'];
            $itemInformation['id'] = $item['tag'];
            $itemInformation['count'] = (int) $item['count'];
            $itemInformation['link'] = OW::getRouter()->urlForRoute('iishashtag.tag', array('tag' => $item['tag']));
            $itemInformation['label'] = OW::getLanguage()->text('iisadvancesearch', 'hashtag_label');
            $result[] = $itemInformation;
            $count++;
            if($count == $maxCount){
                break;
            }
        }

        $data = $event->getData();
        $data['hashtags']= array('label' => OW::getLanguage()->text('iisadvancesearch', 'hashtag_label'), 'data' => $result);
        $event->setData($data);
    }
}
