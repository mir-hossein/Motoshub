<?php

try
{
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'manage_plugins_core_update_request_text_manually', 'کدهای نرم‌افزار شما از نسخه <b>{$oldVersion}</b> به <b>{$newVersion}</b> به‌روزرسانی شده‌اند. حال می‌بایست پایگاه داده نیز به‌روزرسانی شود. {$info}');

    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'manage_plugins_batch_update_success_message', 'Plugins have been successfully updated');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'manage_plugins_batch_update_success_message', 'افزونه‌ها با موفقیت به‌روزرسانی شدند');
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
