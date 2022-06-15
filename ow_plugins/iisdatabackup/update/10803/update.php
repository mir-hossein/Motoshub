<?php
/**
 * @author Mohammad Reza Heidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languages = $languageService->getLanguages();
$langEnId = null;
$langFaId = null;
foreach ($languages as $lang) {
    if ($lang->tag == 'fa-IR') {
        $langFaId = $lang->id;
    }
    if ($lang->tag == 'en') {
        $langEnId = $lang->id;
    }
}

if ($langFaId != null) {
    $languageService->addOrUpdateValue($langFaId, 'iisdatabackup', 'newsfeed_status', 'وضعیت‌های کاربران (به جر آخرین وضعیت)');
    $languageService->addOrUpdateValue($langFaId, 'iisdatabackup', 'newsfeed_action', 'اعمال انجام شده کاربران در تازه‌ها');
    $languageService->addOrUpdateValue($langFaId, 'iisdatabackup', 'admin_page_heading', 'تنظیمات افزونه پشتیبانی از داده ها');
    $languageService->addOrUpdateValue($langFaId, 'iisdatabackup', 'admin_page_title', 'تنظیمات افزونه پشتیبانی از داده ها');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'iisdatabackup', 'newsfeed_status', 'Newsfeed status(Except the last)');
    $languageService->addOrUpdateValue($langEnId, 'iisdatabackup', 'admin_page_heading', 'Data backup plugin settings');
    $languageService->addOrUpdateValue($langEnId, 'iisdatabackup', 'admin_page_title', 'Data backup plugin settings');
}