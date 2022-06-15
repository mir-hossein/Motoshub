<?php

try
{
    $logger = Updater::getLogger();
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'core_is_already_up_to_date', 'Core is already up to date');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'core_is_already_up_to_date', 'نسخه نرم‌افزار در حال حاضر به‌روز است');
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}