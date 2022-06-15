<?php

$componentDto = BOL_ComponentDao::getInstance()->findByClassName('IISNEWS_CMP_TagsWidget');
if ( $componentDto === null )
{
    $widget = BOL_ComponentAdminService::getInstance()->addWidget('IISNEWS_CMP_TagsWidget', false);
    $placeWidget = BOL_ComponentAdminService::getInstance()->addWidgetToPlace($widget, BOL_ComponentAdminService::PLACE_INDEX);
    //BOL_ComponentAdminService::getInstance()->addWidgetToPosition($placeWidget, BOL_ComponentAdminService::SECTION_RIGHT );
}

$languageService = Updater::getLanguageService();
$languageService->addOrUpdateValueByLanguageTag('en', 'iisnews', 'tag_widget_heading', 'Categorized news entries using tags');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisnews', 'tag_widget_heading', 'اخبار دسته‌بندی‌شده توسط برچسب‌ها');

