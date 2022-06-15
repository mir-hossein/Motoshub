<?php

class MAILBOX_MCMP_ChatConversation extends OW_MobileComponent
{
    public function __construct($data)
    {
        $script = UTIL_JsGenerator::composeJsString('
        OWM.conversation = new MAILBOX_Conversation({$params});
        OWM.conversationView = new MAILBOX_ConversationView({model: OWM.conversation});
        ', array('params' => $data));

        OW::getDocument()->addOnloadScript($script);

        OW::getLanguage()->addKeyForJs('mailbox', 'text_message_invitation');

        $form = new MAILBOX_MCLASS_NewMessageForm($data['conversationId'], $data['opponentId']);
        $this->addForm($form);
        $messages = MAILBOX_BOL_MessageDao::getInstance()->findUnreadMessagesForConversation($data['conversationId'],OW::getUser()->getId());
        foreach($messages as $message){
            $message->recipientRead = 1;
            MAILBOX_BOL_MessageDao::getInstance()->save($message);
        }

        $this->assign('data', $data);
        $this->assign('defaultAvatarUrl', BOL_AvatarService::getInstance()->getDefaultAvatarUrl());

        if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=null){
            $this->assign('backReffererUrl',$_SERVER['HTTP_REFERER']);
        }
        $plugin = BOL_PluginDao::getInstance()->findPluginByKey('iismainpage');
        if(isset($plugin) && $plugin->isActive() && !IISMAINPAGE_BOL_Service::getInstance()->isDisabled('mailbox')) {
            $this->assign('backReffererUrl', OW::getRouter()->urlForRoute('iismainpage.mailbox.type',array('type'=>'chat')));
        }

        $firstMessage = MAILBOX_BOL_ConversationService::getInstance()->getFirstMessage($data['conversationId']);

        if (empty($firstMessage))
        {
            $actionName = 'send_chat_message';
        }
        else
        {
            $actionName = 'reply_to_chat_message';
        }

        $isAuthorized = OW::getUser()->isAuthorized('mailbox', $actionName);
        
        if ( !$isAuthorized )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);

            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $this->assign('sendAuthMessage', $status['msg']);
            }
            else if ( $status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE )
            {
                $this->assign('sendAuthMessage', OW::getLanguage()->text('mailbox', $actionName.'_permission_denied'));
            }
        }

        $seenImgUrl = OW::getPluginManager()->getPlugin('mailbox')->getStaticUrl().'img/tic.svg';
        OW::getDocument()->addStyleDeclaration(".message_seen{background-image: url('".$seenImgUrl."');}");
        OW::getDocument()->addStyleDeclaration("#header{display:none}");
        OW::getDocument()->addStyleSheet( OW::getPluginManager()->getPlugin('mailbox')->getStaticCssUrl().'mailbox.css' );

        OW::getDocument()->addScriptDeclaration("window.mailbox_remove_url = '" . OW::getRouter()->urlForRoute('mailbox_ajax_remove_message') . "'");
        OW::getDocument()->addScriptDeclaration("window.replyToMessage = null;");

        $language = OW::getLanguage();
        $language->addKeyForJs('mailbox', 'find_contact');
        $language->addKeyForJs('base', 'user_block_message');
        $language->addKeyForJs('mailbox', 'send_message_failed');
        $language->addKeyForJs('mailbox', 'confirm_conversation_delete');
        $language->addKeyForJs('mailbox', 'silent_mode_off');
        $language->addKeyForJs('mailbox', 'silent_mode_on');
        $language->addKeyForJs('mailbox', 'show_all_users');
        $language->addKeyForJs('mailbox', 'show_all_users');
        $language->addKeyForJs('mailbox', 'show_online_only');
        $language->addKeyForJs('mailbox', 'new_message');
        $language->addKeyForJs('mailbox', 'mail_subject_prefix');
        $language->addKeyForJs('mailbox', 'chat_subject_prefix');
        $language->addKeyForJs('mailbox', 'new_message_count');
        $language->addKeyForJs('mailbox', 'chat_message_empty');
        $language->addKeyForJs('mailbox', 'text_message_invitation');
        $language->addKeyForJs('mailbox', 'delete_confirm');
        $language->addKeyForJs('mailbox', 'text');
        $language->addKeyForJs('mailbox', 'send');
        $language->addKeyForJs('mailbox', 'attachment');
        $language->addKeyForJs('base', 'cancel');
    }
}