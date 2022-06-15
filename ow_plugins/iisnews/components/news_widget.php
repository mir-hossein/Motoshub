<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisnews.components
 * @since 1.0
 */
class IISNEWS_CMP_NewsWidget extends BASE_CLASS_Widget
{

    public function __construct( BASE_CLASS_WidgetParameter $params )
    {
        parent::__construct();

        $service = EntryService::getInstance();

        $count = $params->customParamList['count'];
        $previewLength = $params->customParamList['previewLength'];

        $list = $service->findList(0, $count);

        if ( (empty($list) || (false && !OW::getUser()->isAuthorized('iisnews', 'add') && !OW::getUser()->isAuthorized('iisnews', 'view'))) && !$params->customizeMode )
        {
            $this->setVisible(false);

            return;
        }

        $entrys = array();

        $userService = BOL_UserService::getInstance();

        $entryIdList = array();
        foreach ( $list as $dto )
        {
            /* @var $dto Entry */

            if ( mb_strlen($dto->getTitle()) > 350 )
            {
                $dto->setTitle(UTIL_String::truncate($dto->getTitle(), 350, '...'));
            }
            $text = $service->processEntryText($dto->getEntry());
            $sentenceCorrected = false;
            $truncated=false;
            if ( mb_strlen($text) > $previewLength )
            {
                $truncated=true;
                $sentence = strip_tags($text);
                $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_HALF_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
                if(isset($event->getData()['correctedSentence'])){
                    $sentence = $event->getData()['correctedSentence'];
                    $sentenceCorrected = true;
                }
                $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
                if(isset($event->getData()['correctedSentence'])){
                    $sentence = $event->getData()['correctedSentence'];
                    $sentenceCorrected = true;
                }
            }
            if($sentenceCorrected) {
                $text = $sentence . '...';
            }
            else{
                $text = UTIL_String::truncate(strip_tags($text), $previewLength, "...");
            }
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $text)));
            if (isset($stringRenderer->getData()['string'])) {
                $text = ($stringRenderer->getData()['string']);
            }
            if($dto->getImage()){
                $entrys[] = array(
                    'dto' => $dto,
                    'text' => $text,
                    'truncated' => $truncated,
                    'url' => OW::getRouter()->urlForRoute('user-entry', array('id'=>$dto->getId())),
                    'imageSrc' => $service->generateImageUrl($dto->getImage(), true),
                    'imageTitle' => $dto->getTitle()
                );
            }else {
                $entrys[] = array(
                    'dto' => $dto,
                    'text' => $text,
                    'truncated' => $truncated,
                    'url' => OW::getRouter()->urlForRoute('user-entry', array('id' => $dto->getId()))
                );
            }

            $idList[] = $dto->getAuthorId();
            $entryIdList[] = $dto->id;
        }

        $commentInfo = array();

        if ( !empty($idList) )
        {
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList, true, false);
            foreach ( $avatars as $avatar )
            {
                $userId = $avatar['userId'];
                $avatars[$userId]['url'] = BOL_UserService::getInstance()->getUserUrl($userId);
            }
            $this->assign('avatars', $avatars);

            $urls = BOL_UserService::getInstance()->getUserUrlsForList($idList);

            $commentInfo = BOL_CommentService::getInstance()->findCommentCountForEntityList('news-entry', $entryIdList);

            $toolbars = array();

            foreach ( $list as $dto )
            {
                $toolbars[$dto->getId()] = array(
                    array(
                        'class' => 'ow_remark ow_ipc_date',
                        'label' => UTIL_DateTime::formatDate($dto->getTimestamp())
                    )
                );
            }
            $this->assign('tbars', $toolbars);
        }

        $this->assign('commentInfo', $commentInfo);
        $this->assign('list', $entrys);


        if ( $service->countEntrys() > 0 )
        {
            $toolbar = array();

            if (   OW::getUser()->isAuthorized('iisnews') || OW::getUser()->isAuthorized('iisnews', 'add')|| OW::getUser()->isAdmin())
            {
                $toolbar[] = array(
                        'label' => OW::getLanguage()->text('iisnews', 'add_new'),
                        'href' => OW::getRouter()->urlForRoute('entry-save-new')
                    );
            }

            if ( OW::getUser()->isAuthorized('iisnews', 'view') || OW::getUser()->isAdmin() )
            {
                $toolbar[] = array(
                    'label' => OW::getLanguage()->text('iisnews', 'go_to_news'),
                    'href' => OW::getRouter()->urlForRoute('iisnews')
                    );
            }

            if (!empty($toolbar))
            {
                $this->setSettingValue(self::SETTING_TOOLBAR, $toolbar);
            }

        }
    }

    public static function getSettingList()
    {

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
        $settingList['previewLength'] = array(
            'presentation' => self::PRESENTATION_TEXT,
            'label' => OW::getLanguage()->text('iisnews', 'news_widget_preview_length_lbl'),
            'value' => 200,
        );

        return $settingList;
    }

    public static function getStandardSettingValueList()
    {
        $list = array(
            self::SETTING_TITLE => OW::getLanguage()->text('iisnews', 'main_menu_item'),
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_ICON => 'ow_ic_write'
        );

        return $list;
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }
}

