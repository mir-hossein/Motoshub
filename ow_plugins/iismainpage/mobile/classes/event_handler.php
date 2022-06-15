<?php

/**
 * iismainpage
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismainpage
 * @since 1.0
 */
class IISMAINPAGE_MCLASS_EventHandler
{
    public function init()
    {
        OW::getEventManager()->bind('mailbox.on.before.conversation.page.add', array($this, 'onConversationAdd'));
        OW::getEventManager()->bind('mailbox.on.before.profile.page.add', array($this, 'onProfileAdd'));
        OW::getEventManager()->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array($this, 'onBeforeDocumentRender'));
    }

    public function onConversationAdd( OW_Event $event )
    {
        $data = $event->getData();
        $data['add'] = true;
        $event->setData( $data );
    }

    public function onProfileAdd( OW_Event $event )
    {
        $data = $event->getData();
        $data['add'] = false;
        $event->setData( $data );
    }

    public function onBeforeDocumentRender(OW_Event $event)
    {
        $css = '.owm_sidebar_console_item {width:50%}';
        OW::getDocument()->addStyleDeclaration($css);

        if(OW::getUser()->isAuthenticated()) {
            $notificationSource = OW::getPluginManager()->getPlugin('iismainpage')->getStaticUrl() . '/img/notification.svg';
            $css = '.owm_nav_profile { background-image: url(' . $notificationSource . ') !important; background-size: 22px; background-repeat: no-repeat; }';
            OW::getDocument()->addStyleDeclaration($css);
        }
    }

}

