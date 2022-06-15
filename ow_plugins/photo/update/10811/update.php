<?php

try {
    $languageService = Updater::getLanguageService();

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'photo', 'total_score', 'میانگین امتیازات: ');
    $languageService->addOrUpdateValueByLanguageTag('en', 'photo', 'total_score', 'average score: ');

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'photo', 'total_count', 'تعداد امتیاز دهندگان: ');
    $languageService->addOrUpdateValueByLanguageTag('en', 'photo', 'total_count', 'total count: ');
}catch(Exception $e){

}