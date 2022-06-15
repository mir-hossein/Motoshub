<?php

function smarty_function_online_now( $params, $smarty )
{
    $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
    $onlineStatus = BOL_UserService::getInstance()->findOnlineUserById($params['userId']);
    $chatNowMarkup = '';
    if (OW::getUser()->isAuthenticated() && isset($params['userId']) && OW::getUser()->getId() != $params['userId']) {
        $allowChat = OW::getEventManager()->call('base.online_now_click', array('userId' => OW::getUser()->getId(), 'onlineUserId' => $params['userId']));

        if ($allowChat) {
            if(!isset($event->getData()['isMobileVersion']) || $event->getData()['isMobileVersion']==false) {
                $chatNowMarkup = '<span id="ow_chat_now_' . $params['userId'] . '" class="ow_lbutton ow_green" onclick="OW.trigger(\'base.online_now_click\', [ \'' . $params['userId'] . '\' ] );" >' . OW::getLanguage()->text('base', 'user_list_chat_now') . '</span><span id="ow_preloader_content_' . $params['userId'] . '" class="ow_preloader_content ow_hidden"></span>';
            }else{
                $chatNowMarkup = '<span id="ow_chat_now_' . $params['userId'] . '" class="owm_btn_class_2 ow_lbutton ow_green" onclick="window.location=\''.OW::getRouter()->urlForRoute('mailbox_chat_conversation',array('userId' => $params['userId'])).'\';" >' . OW::getLanguage()->text('base', 'user_list_chat_now') . '</span><span id="ow_preloader_content_' . $params['userId'] . '" class="ow_preloader_content ow_hidden"></span>';
            }
        }
    }
    $spanOnline = ' <span class="ow_live_on"></span> ';
    if (!$onlineStatus || $onlineStatus==false)
    {
        $spanOnline='';
    }
    $buttonMarkup = '<div class="ow_miniic_live">'.   $chatNowMarkup . $spanOnline . '</div>';

    return $buttonMarkup;
}
?>
