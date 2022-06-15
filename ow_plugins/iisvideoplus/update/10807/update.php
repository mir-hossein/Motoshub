<?php

try {
    $languageService = Updater::getLanguageService();

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisvideoplus', 'download_file', 'دریافت ویدیو');
    $languageService->addOrUpdateValueByLanguageTag('en', 'iisvideoplus', 'download_file', 'download video');
}catch(Exception $e){

}