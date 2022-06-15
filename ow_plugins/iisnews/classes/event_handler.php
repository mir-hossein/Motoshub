<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow.ow_plugins.iisnews.classes
 * @since 1.6.0
 */
class IISNEWS_CLASS_EventHandler
{
    /**
     * Singleton instance.
     *
     * @var IISNEWS_CLASS_EventHandler
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return IISNEWS_CLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * Get sitemap urls
     *
     * @param OW_Event $event
     * @return void
     */
    public function onSitemapGetUrls( OW_Event $event )
    {
        $params = $event->getParams();

        if ( BOL_AuthorizationService::getInstance()->isActionAuthorizedForGuest('iisnews', 'view') )
        {
            $offset = (int) $params['offset'];
            $limit  = (int) $params['limit'];
            $urls   = array();
            switch ( $params['entity'] )
            {
                case 'news' :
                    $entrieIds = EntryService::getInstance()->findLatestPublicEntryIds($offset, $limit);
                    foreach ( $entrieIds as $entryId )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('entry', array(
                            'id' => $entryId->id
                        ));
                    }
                    break;
            }
            if ( $urls )
            {
                $event->setData($urls);
            }
        }
    }
    public function init()
    {
        $this->genericInit();
    }
    public function genericInit()
    {
        $service = EntryService::getInstance();
        OW::getEventManager()->bind(OW_EventManager::ON_USER_SUSPEND, array($service, 'onAuthorSuspend'));

        OW::getEventManager()->bind(OW_EventManager::ON_USER_UNREGISTER, array($service, 'onUnregisterUser'));
        OW::getEventManager()->bind('notifications.collect_actions', array($service, 'onCollectNotificationActions'));
        OW::getEventManager()->bind('base_add_comment', array($service, 'onAddNewsEntryComment'));
        OW::getEventManager()->bind('base_add_news', array($service, 'onAddNewsEnt'));
        //OW::getEventManager()->bind('base_delete_comment',                array($this, 'onDeleteComment'));
        OW::getEventManager()->bind('ads.enabled_plugins', array($service, 'onCollectEnabledAdsPages'));

        OW::getEventManager()->bind('admin.add_auth_labels', array($service, 'onCollectAuthLabels'));
        OW::getEventManager()->bind('feed.collect_configurable_activity', array($service, 'onCollectFeedConfigurableActivity'));
//        OW::getEventManager()->bind('feed.collect_privacy', array($this, 'onCollectFeedPrivacyActions'));
//        OW::getEventManager()->bind('plugin.privacy.get_action_list', array($this, 'onCollectPrivacyActionList'));
//        OW::getEventManager()->bind('plugin.privacy.on_change_action_privacy', array($this, 'onChangeActionPrivacy'));

        OW::getEventManager()->bind('feed.on_entity_add', array($service, 'onAddNewsEntry'));
        OW::getEventManager()->bind('feed.on_entity_update', array($service, 'onUpdateNewsEntry'));
        OW::getEventManager()->bind('feed.after_comment_add', array($service, 'onFeedAddComment'));
        OW::getEventManager()->bind('feed.after_like_added', array($service, 'onFeedAddLike'));

        OW::getEventManager()->bind('socialsharing.get_entity_info', array($service, 'sosialSharingGetNewsInfo'));
        OW::getEventManager()->bind("base.sitemap.get_urls", array($this, "onSitemapGetUrls"));
        $credits = new IISNEWS_CLASS_Credits();
        OW::getEventManager()->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));
        OW::getEventManager()->bind('usercredits.get_action_key', array($credits, 'getActionKey'));
        OW::getEventManager()->bind('base_delete_comment', array($service, 'deleteComment'));
        OW::getEventManager()->bind('feed.on_item_render', array($service, 'feedOnItemRenderActivity'));
    }


}