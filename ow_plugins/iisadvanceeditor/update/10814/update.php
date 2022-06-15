<?php
try {
    BOL_LanguageService::getInstance()->addPrefix('iisadvanceeditor','IISAdvanceEditor');
    Updater::getLanguageService()->updatePrefixForPlugin('iisadvanceeditor');

    OW::getPluginManager()->addPluginSettingsRouteName('iisadvanceeditor', 'iisadvanceeditor.admin_config');
}catch(Exception $e){}

$langEnId = null;
$langFaId = null;

$languageService = Updater::getLanguageService();
$languages = $languageService->getLanguages();

foreach ($languages as $lang) {
    if ($lang->tag == 'fa-IR') {
        $langFaId = $lang->id;
    }
    if ($lang->tag == 'en') {
        $langEnId = $lang->id;
    }
}

if ($langFaId != null) {
    $languageService->addOrUpdateValue($langFaId, 'iisadvanceeditor', 'config_page_title', 'تنظیمات ویرایشگر پیشرفته');
    $languageService->addOrUpdateValue($langFaId, 'iisadvanceeditor', 'max_symbols_count_title', 'حداکثر تعداد کاراکترها');
    $languageService->addOrUpdateValue($langFaId, 'iisadvanceeditor', 'max_symbols_count_error', 'یک عدد بزرگتر از صفر وارد کنید');
    $languageService->addOrUpdateValue($langFaId, 'iisadvanceeditor', 'set_max_symbols_count', 'ثبت تعداد کاراکتر مجاز');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'iisadvanceeditor', 'config_page_title', 'Wysiwyg Settings');
    $languageService->addOrUpdateValue($langEnId, 'iisadvanceeditor', 'max_symbols_count_title', 'Max symbols count');
    $languageService->addOrUpdateValue($langEnId, 'iisadvanceeditor', 'max_symbols_count_error', 'enter a number greater than 0');
    $languageService->addOrUpdateValue($langEnId, 'iisadvanceeditor', 'set_max_symbols_count', 'Set max symbols count');
}

