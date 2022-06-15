<?php

try
{
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'user_list_chat_now', 'گپ‌زدن');
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}