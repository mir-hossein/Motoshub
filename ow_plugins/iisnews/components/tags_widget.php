<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisnews.components
 * @since 1.0
 */
class IISNEWS_CMP_TagsWidget extends BASE_CLASS_Widget
{

    public function __construct( BASE_CLASS_WidgetParameter $params )
    {
        parent::__construct();

        $service = EntryService::getInstance();
        $allInOne = isset($params->customParamList['all_in_one']) ? $params->customParamList['all_in_one'] : false;
        $widgetLanguage = $params->customParamList['widget_language'];
        $displayWidgetAllowed=true;
        $currentLanguageTag = BOL_LanguageService::getInstance()->getCurrent()->getTag();
        if (!$params->customizeMode ){
            if($widgetLanguage!==$currentLanguageTag)
            {
                $params->standartParamList->showTitle=false;
                $params->standartParamList->wrapInBox=false;
                $displayWidgetAllowed=false;
            }
        }
        if($displayWidgetAllowed)
        {
            $enabledTagIdList = array();
            foreach ($params->customParamList as $tagKey => $tagParam) {
                if (strpos($tagKey, 'tag_') === 0) {
                    if ($tagParam == '1') {
                        $enabledTagIdList[] = (int)(substr($tagKey, 4));
                    }
                }
            }

            $maxCount = isset($params->customParamList['max_count']) ? $params->customParamList['max_count'] : 5;
            $maxCount = (int)($maxCount);

            $enabledTagList = BOL_TagDao::getInstance()->findByIdList($enabledTagIdList);
            $list = array();
            if (!$allInOne) {
                foreach ($enabledTagList as $tag) {
                    $latestNews = $service->findListByTag($tag->label, 0, $maxCount);
                    $eventForEnglishFieldSupport = new OW_Event('iismultilingualsupport.show.data.in.multilingual', array('list' => $latestNews, 'entityType' => 'news', 'display' => 'list', 'forWidget' => true));
                    OW::getEventManager()->trigger($eventForEnglishFieldSupport);
                    if (isset($eventForEnglishFieldSupport->getData()['multiData'])) {
                        $latestNews = $eventForEnglishFieldSupport->getData()['multiData'];
                    }
                    $entryItems = array();
                    foreach ($latestNews as $entry) {
                        $entryItems[] = array(
                            'title' => $entry->title,
                            'link' => OW::getRouter()->urlForRoute('user-entry', array('id' => $entry->id))
                        );
                    }
                    $item = array(
                        'label' => $tag->label,
                        'link' => OW::getRouter()->urlForRoute('iisnews.list', array('list' => 'browse-by-tag')) . "?tag=" . $tag->label,
                        'list' => $entryItems
                    );
                    $list[] = $item;
                }
                $this->assign('list', $list);
            } else {
                //$tagLabel = array();
                $tagNewsItems = array();
                $entrySourceImages = array();
                $tagLinks = array();
                foreach ($enabledTagList as $tag) {
                    //$tagLabel[] = $tag->label;
                    $latestNews = $service->findListByTag($tag->label, 0, $maxCount);
                    //$latestNews = $service->findListByTags($tagLabel, 0, $maxCount);
                    $eventForEnglishFieldSupport = new OW_Event('iismultilingualsupport.show.data.in.multilingual', array('list' => $latestNews, 'entityType' => 'news', 'display' => 'list', 'forWidget' => true));
                    OW::getEventManager()->trigger($eventForEnglishFieldSupport);
                    if (isset($eventForEnglishFieldSupport->getData()['multiData'])) {
                        $latestNews = $eventForEnglishFieldSupport->getData()['multiData'];
                    }
                    foreach ($latestNews as $entry) {
                        $description = nl2br(UTIL_String::truncate(strip_tags($entry->entry), 300, '...'));
                        if (mb_strlen($entry->entry) > 300) {
                            $sentence = $entry->entry;
                            $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_HALF_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
                            if (isset($event->getData()['correctedSentence'])) {
                                $sentence = $event->getData()['correctedSentence'];
                                $sentenceCorrected = true;
                            }
                            $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::PARTIAL_SPACE_CODE_DISPLAY_CORRECTION, array('sentence' => $sentence, 'trimLength' => 300)));
                            if (isset($event->getData()['correctedSentence'])) {
                                $sentence = $event->getData()['correctedSentence'];
                                $sentenceCorrected = true;
                            }
                        }
                        if (isset($sentenceCorrected) && $sentenceCorrected) {
                            $description = nl2br($sentence . '...');
                        }

                        $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $description)));
                        if (isset($stringRenderer->getData()['string'])) {
                            $description = ($stringRenderer->getData()['string']);
                        }

                        $entryItems = array(
                            'title' => $entry->title,
                            'description' => $description,
                            'link' => OW::getRouter()->urlForRoute('user-entry', array('id' => $entry->id)),
                            'date' => UTIL_DateTime::formatDate($entry->timestamp, true),
                            'id' => $entry->id
                        );
                        if ($entry->getImage()) {
                            $entrySourceImages[$entry->id] = $service->generateImageUrl($entry->getImage(), true);
                        }
                        $tagNewsItems[$tag->id]['entries'][]= $entryItems;
                    }
                    $tagNewsItems[$tag->id]['link']=OW::getRouter()->urlForRoute('iisnews.list', array('list' => 'browse-by-tag')) . "?tag=" . $tag->label;
                }
                $this->assign('tagLinks', $tagLinks);
                $this->assign('entrySourceImages', $entrySourceImages);
                $this->assign('tagNewsItems', $tagNewsItems);
            }
            $this->assign('allInOne', $allInOne);
            OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('iisnews')->getStaticCssUrl() . 'news.css');
        }
    }

    public static function getSettingList()
    {
        $settingList = array();
        $currentLanguageTag = BOL_LanguageService::getInstance()->getCurrent()->getTag();
        $settingList['max_count'] = array(
            'presentation' => self::PRESENTATION_NUMBER,
            'label' => OW::getLanguage()->text('iisnews', 'cmp_widget_entry_count'),
            'value' => 5
        );

        $topTags = BOL_TagService::getInstance()->findMostPopularTags('news-entry', 50);
        foreach($topTags as $tag) {
            if(empty($tag['label'])){
                continue;
            }
            $settingList['tag_' . $tag['id']] = array(
                'presentation' => self::PRESENTATION_CHECKBOX,
                'label' => $tag['label'].' ',
                'value' => false
            );
        }

        $settingList['all_in_one'] = array(
            'presentation' => self::PRESENTATION_CHECKBOX,
            'label' => OW::getLanguage()->text('iisnews', 'cmp_tags_widget_view_all_in_one'),
            'value' => false
        );

        $settingList['widget_language'] = array(
            'presentation' => self::PRESENTATION_SELECT,
            'label' => OW::getLanguage()->text('iisnews', 'select_language_tag_widget'),
            'optionList' => array('en' => 'en', 'fa-IR' => 'fa-IR'),
            'value' => $currentLanguageTag
        );

        return $settingList;
    }

    public static function getStandardSettingValueList()
    {
        $list = array(
            self::SETTING_TITLE => OW::getLanguage()->text('iisnews', 'tag_widget_heading'),
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

