<?php

try {
    $languageService = Updater::getLanguageService();

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'photo', 'create_new_album', 'ساختن آلبوم جدید');
    $languageService->addOrUpdateValueByLanguageTag('en', 'photo', 'create_new_album', 'Create new album');
}catch(Exception $e){

}