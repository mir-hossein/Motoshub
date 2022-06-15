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
    $languageService->addOrUpdateValue($langFaId,'iisgroupsplus','joined_notification_string','شما عضو گروه <a href="{$groupUrl}">{$groupTitle}</a> شده‌اید');
}
if ($langEn != null) {
    $languageService->addOrUpdateValue($langEn,'iisgroupsplus','joined_notification_string','you have been joined to a group named <a href="{$groupUrl}">{$groupTitle}</a>');
}