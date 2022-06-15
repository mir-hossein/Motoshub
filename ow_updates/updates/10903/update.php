<?php

try
{
    $logger = Updater::getLogger();
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('en', 'base', 'are_you_sure', 'Are you sure?');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'are_you_sure', ' آیا از انجام این اقدام مطمئن هستید؟');
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}