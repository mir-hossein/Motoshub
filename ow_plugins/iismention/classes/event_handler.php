<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismention
 * @since 1.0
 */
class IISMENTION_CLASS_EventHandler
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
        $service = IISMENTION_BOL_Service::getInstance();
        OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($this, 'onBeforeDocumentRender'));
        //OW::getEventManager()->bind( OW_EventManager::ON_AFTER_ROUTE, array($this, 'onBeforeDocumentRender') );
        //OW::getEventManager()->bind('plugin.privacy.get_action_list', array($service, 'privacyAddAction'));

        //new content added
        OW::getEventManager()->bind('feed.after_comment_add', array($service, 'onAddComment'));
        OW::getEventManager()->bind('feed.action', array($service, 'onEntityUpdate') , 1500);
        OW::getEventManager()->bind('feed.delete_item', array($service, 'onEntityUpdate'));
        OW::getEventManager()->bind('hashtag.on_entity_change', array($service,'onEntityUpdate'));
        OW::getEventManager()->bind('hashtag.edit_newsfeed', array($service, 'onEntityUpdate'));
        OW::getEventManager()->bind('base_delete_comment', array($service, 'onCommentDelete'));

        //rendering content
        OW::getEventManager()->bind('base.comment_item_process', array($service, 'renderComments')); //comments, images
        //OW::getEventManager()->bind(IISEventManager::ON_FEED_ITEM_RENDERER, array($service,'renderNewsfeed') );
        //OW::getEventManager()->bind(IISEventManager::ON_AFTER_NEWSFEED_STATUS_STRING_READ, array($service,'renderString')); //newsfeed, iisnews
        OW::getEventManager()->bind('hashtag.before_render_string', array($service,'renderString')); //newsfeed, groups, event, video, forum

        //rendering notifications
        OW::getEventManager()->bind('notifications.collect_actions', array($service, 'onNotifyActions'));
        OW::getEventManager()->bind('notifications.on_item_render', array($service, 'onNotificationRender'));

    }

    public function onBeforeDocumentRender( OW_Event $event )
    {
        //  if (!startsWith(OW::getRouter()->getUri(), "forum/"))
        {
            OW::getDocument()->addStyleSheet( OW::getPluginManager()->getPlugin('iismention')->getStaticCssUrl() . 'iismention.css' );

            $js = ";var mentionLoadUsernamesUrl='". OW::getRouter()->urlForRoute('iismention.load_usernames')."/';";
            $js = $js.";var mentionMaxCount=". OW::getConfig()->getValue('iismention', 'max_count').";";
            $friends = "var mention_friends = [{username: 'i.moradnejad', fullname: 'Issa Moradnejad'}];";
            $js = $js.";".$friends.";";
            OW::getDocument()->addScriptDeclarationBeforeIncludes($js);
            OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('iismention')->getStaticJsUrl() . 'suggest.js' );
            OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('iismention')->getStaticJsUrl() . 'iismention.js' );
        }
    }
}