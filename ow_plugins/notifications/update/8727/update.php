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
    $languageService->addOrUpdateValue($langFaId, 'notifications', 'email_html_head', 'کاربر گرامی ({$userName})،');

    $languageService->addOrUpdateValue($langFaId, 'notifications', 'config_schedule_title', 'تنظیم زمان‌بندی ارسال رایانامه');

    $languageService->addOrUpdateValue($langFaId, 'notifications', 'email_txt_bottom', '');
    $languageService->addOrUpdateValue($langFaId, 'notifications', 'email_html_bottom', '');

    $languageService->addOrUpdateValue($langFaId, 'notifications', 'email_txt_head', 'کاربر گرامی {$userName} سلام

آخرین فعالیت‌های شما در {$site_name} که قصد اطلاع از وضعیت آن‌ها را دارید:');

    $languageService->addOrUpdateValue($langFaId, 'notifications', 'settings_not_changed', 'تنظیمات تغییر نکرد');

    $languageService->addOrUpdateValue($langFaId, 'notifications', 'preferences_legend', 'امکان انتخاب فعالیت‌هایی که در صورت عدم مراجعه به وب‌گاه در مدت زمان بیشینه 2 روز، به رایانشانی شما اطلاع رایانامه‌ای ارسال شود.');
    $languageService->addOrUpdateValue($langFaId, 'notifications', 'email_html_description', 'آخرین فعالیت‌های مرتبط با شما در <a href="{$site_url}">{$site_name}</a>');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'notifications', 'email_txt_head', 'Dear user ({$userName}), Here shows activities related to you at {$site_name}. ');

    $languageService->addOrUpdateValue($langEnId, 'notifications', 'email_txt_bottom', '');
    $languageService->addOrUpdateValue($langEnId, 'notifications', 'email_html_bottom', '');
}