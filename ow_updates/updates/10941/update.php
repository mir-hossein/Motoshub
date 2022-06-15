<?php

$logger = Updater::getLogger();
try
{
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'input_image_compression_percentage_desc', 'درصد میزان فشرده‌سازی (مقادیر بین 0 و 99 باشد)');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'input_image_compression_percentage_desc', 'Degree of Compression in % (value between 0 and 99)');
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
