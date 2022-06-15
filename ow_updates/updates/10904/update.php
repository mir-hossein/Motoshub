<?php

try
{
    $logger = Updater::getLogger();
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('en', 'base', 'meta_title_user_list', '{$user_list} | {$site_name}');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'meta_title_user_list', '{$site_name} | {$user_list}');
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
