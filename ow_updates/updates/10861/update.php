<?php

try
{
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('en', 'base', 'captcha_value', 'Captcha value');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'captcha_value', 'عبارت امنیتی');

    $languageService->addOrUpdateValueByLanguageTag('en', 'base', 'comma', ',');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'comma', '،');
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
