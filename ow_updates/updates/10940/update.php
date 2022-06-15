<?php

$logger = Updater::getLogger();
try
{
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'input_image_compression_percentage_label', 'درصد فشرده‌سازی تصاویر');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'input_image_compression_percentage_label', 'Pictures Compression level');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'soft_version', 'نسخه نرم‌افزار شما :  {$build}');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'soft_version', 'Platform version build: {$build}');
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}

if( !Updater::getConfigService()->configExists("base", "image_compression_percentage") ){
    Updater::getConfigService()->addConfig("base", "image_compression_percentage", '90');
}
