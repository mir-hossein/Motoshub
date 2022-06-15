<?php
$languageService = Updater::getLanguageService();
$languageService->addOrUpdateValueByLanguageTag('en', 'iisnews', 'cmp_tags_widget_view_all_in_one', 'View all news in one list');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisnews', 'cmp_tags_widget_view_all_in_one', 'نمایش تمام اخبار در یک فهرست');
try {
    $widget = BOL_ComponentDao::getInstance()->findByClassName('IISNEWS_CMP_TagsWidget');
    $widget->clonable = true;
    BOL_ComponentDao::getInstance()->save($widget);
    BOL_ComponentAdminService::getInstance()->clearAllCache();
}catch(Exception $e){

}


