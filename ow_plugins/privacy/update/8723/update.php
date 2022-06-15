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
    $languageService->addOrUpdateValue($langFaId, 'privacy', 'privacy_description', 'شما در اینجا می‌توانید حق دسترسی به محتوای خود برای دیگر کاربران را تنظیم کنید');

    $languageService->addOrUpdateValue($langFaId, 'privacy', 'privacy_no_permission_message', '{$display_name} اجازه نداده است تا این محتوا به اشتراک گذاشته شود.');

    $languageService->addOrUpdateValue($langFaId, 'privacy', 'no_items', 'موردی وجود ندارد.');

    $languageService->addOrUpdateValue($langFaId, 'privacy', 'action_action_data_was_saved', 'سامانه در حال اعمال تنظیماتی است که شما تغییر داده‌اید. این مسئله می‌تواند مقداری زمان‌بر باشد.');
    $languageService->addOrUpdateValue($langFaId, 'privacy', 'no_permission_message', 'شما اجازه مشاهده این صفحه را ندارید');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'privacy', 'no_permission_message', 'You do not have permission to view this page');
}