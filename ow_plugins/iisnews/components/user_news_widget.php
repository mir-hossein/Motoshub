<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisnews.components
 * @since 1.0
 */
class IISNEWS_CMP_UserNewsWidget extends BASE_CLASS_Widget
{

    public function __construct( BASE_CLASS_WidgetParameter $params )
    {
        parent::__construct();

        $service = EntryService::getInstance();

        if ( empty($params->additionalParamList['entityId']) )
        {
            
        }

        $userId = $params->additionalParamList['entityId'];

        
        if ( $userId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('iisnews', 'view') )
        {
            $this->setVisible(false);
            return;
        }
        
        /* Check privacy permissions */
        /*
        $eventParams = array(
            'action' => EntryService::PRIVACY_ACTION_VIEW_BLOG_POSTS,
            'ownerId' => $userId,
            'viewerId' => OW::getUser()->getId()
        );

        try
        {
            OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
        }
        catch ( RedirectException $ex )
        {
            $this->setVisible(false);
            return;
        }
        */

        if ( $service->countUserEntry($userId) == 0 && !$params->customizeMode )
        {
            $this->setVisible(false);
            return;
        }

        $this->assign('displayname', BOL_UserService::getInstance()->getDisplayName($userId));
        $this->assign('username', BOL_UserService::getInstance()->getUserName($userId));

        $list = array();

        $count = $params->customParamList['count'];

        $userEntryList = $service->findUserEntryList($userId, 0, $count);

        foreach ( $userEntryList as $id => $item )
        {
            /* Check privacy permissions */
            /*
            if ( $item->authorId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('iisnews') )
            {
                $eventParams = array(
                    'action' => EntryService::PRIVACY_ACTION_VIEW_BLOG_POSTS,
                    'ownerId' => $item->authorId,
                    'viewerId' => OW::getUser()->getId()
                );

                try
                {
                    OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
                }
                catch ( RedirectException $ex )
                {
                    continue;
                }
            }
            */

            $list[$id] = $item;
            $list[$id]->setEntry(strip_tags($item->getEntry()));

            $idList[] = $item->id;
        }

        $commentInfo = array();

        if ( !empty($idList) )
        {
            $commentInfo = BOL_CommentService::getInstance()->findCommentCountForEntityList('news-entry', $idList);
            $tb = array();
            foreach ( $list as $key => $item )
            {


                $sentenceCorrected = false;
                if ( mb_strlen($item->getEntry()) > 170 )
                {
                    $sentence = $item->getEntry();
                    $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_HALF_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 170)));
                    if(isset($event->getData()['correctedSentence'])){
                        $sentence = $event->getData()['correctedSentence'];
                        $sentenceCorrected = true;
                    }
                    $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 170)));
                    if(isset($event->getData()['correctedSentence'])){
                        $sentence = $event->getData()['correctedSentence'];
                        $sentenceCorrected = true;
                    }
                }
                if($sentenceCorrected){
                    $list[$key]->setEntry($sentence.'...');
                }
                else{
                    $list[$key]->setEntry(UTIL_String::truncate($item->getEntry(), 170, '...'));
                }
                if ( mb_strlen($item->getTitle()) > 350)
                {
                    $list[$key]->setTitle(UTIL_String::truncate($item->getTitle(), 350, '...'));
                }                
                if ( $commentInfo[$item->getId()] == 0 )
                {
                    $comments_tb_link = array('label' => '', 'href' => '');
                }
                else
                {
                    $comments_tb_link = array(
                        'label' => OW::getLanguage()->text('iisnews', 'toolbar_comments') . '<span class="ow_txt_value">' . $commentInfo[$item->getId()] . '</span> ',
                        'href' => OW::getRouter()->urlForRoute('entry', array('id' => $item->getId()))
                    );
                }

                $tb[$item->getId()] = array(
                    $comments_tb_link
                );
                $tb[$item->getId()] = array(
                    $comments_tb_link,
                    array(
                        'label' => UTIL_DateTime::formatDate($item->getTimestamp()),
                        'class' => 'ow_ic_date'
                    )
                );
            }

            $this->assign('tb', $tb);
        }

        $itemList = array();
        foreach($list as $entry)
        {
            $itemList[] = array(
                'dto' => $entry,
                'titleHref' => OW::getRouter()->urlForRoute('user-entry', array('id'=>$entry->getId()))
            );
        }

        $this->assign('list', $itemList);

        $user = BOL_UserService::getInstance()->findUserById($userId);

        $this->setSettingValue(
            self::SETTING_TOOLBAR, array(
                array('label' => OW::getLanguage()->text('iisnews', 'total_news', array('total' => $service->countUserEntry($userId) ))),
            array(
                'label' => OW::getLanguage()->text('iisnews', 'view_all'),
                'href' => OW::getRouter()->urlForRoute('user-iisnews', array('user' => $user->getUsername()))
            )
            )
        );
    }

    public static function getSettingList()
    {
        $settingList = array();

        $options = array();

        for ( $i = 3; $i <= 10; $i++ )
        {
            $options[$i] = $i;
        }

        $settingList['count'] = array(
            'presentation' => self::PRESENTATION_SELECT,
            'label' => OW::getLanguage()->text('iisnews', 'cmp_widget_entry_count'),
            'optionList' => $options,
            'value' => 3,
        );

        return $settingList;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_TITLE => OW::getLanguage()->text('iisnews', 'news'),
            self::SETTING_ICON => 'ow_ic_write',
            self::SETTING_WRAP_IN_BOX => true,
            self::SETTING_SHOW_TITLE => true
        );
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }
}