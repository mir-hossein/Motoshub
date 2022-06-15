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
    $languageService->addOrUpdateValue($langFaId,'iiseventplus','event_user_invitation_notification','<a href="{$userUrl}">{$userName}</a> شما را به رویداد <a href="{$eventUrl}">{$eventTitle}</a> دعوت کرده است');
    $languageService->addOrUpdateValue($langFaId,'iiseventplus','accept_event_invitation_notification','شما دعوتنامه شرکت در رویداد <a href="{$eventUrl}">{$eventTitle}</a> را پذیرفتید');
    $languageService->addOrUpdateValue($langFaId,'iiseventplus','ignore_event_invitation_notification','شما از پذیرش دعوتنامه رویداد <a href="{$eventUrl}">{$eventTitle}</a> چشم پوشی کردید');
}
if ($langEn != null) {
    $languageService->addOrUpdateValue($langEn,'iiseventplus','event_user_invitation_notification','<a href="{$userUrl}">{$userName}</a> invite you to <a href="{$eventUrl}">{$eventTitle}</a> event');
    $languageService->addOrUpdateValue($langEn,'iiseventplus','accept_event_invitation_notification','you accept to join <a href="{$eventUrl}">{$eventTitle}</a> event');
    $languageService->addOrUpdateValue($langEn,'iiseventplus','ignore_event_invitation_notification','you decline to join <a href="{$eventUrl}">{$eventTitle}</a> event');
}