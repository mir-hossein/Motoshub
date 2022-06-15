<?php
$languageService = Updater::getLanguageService();

$languages = $languageService->getLanguages();
$langFaId = null;
$langEn = null;
foreach ($languages as $lang) {
    if ($lang->tag == 'fa-IR') {
        $langFaId = $lang->id;
    }
    if ($lang->tag == 'en') {
        $langEn = $lang->id;
    }
}

if ($langFaId != null) {
    $languageService->addOrUpdateValue($langFaId,'photo','cmp_widget_photo_count_msg','یک عدد کوچکتر یا مساوی ۳۰ وارد کنید');
}
if ($langEn != null) {
    $languageService->addOrUpdateValue($langEn,'photo','cmp_widget_photo_count_msg','Enter a number less than or equal to 30');
}