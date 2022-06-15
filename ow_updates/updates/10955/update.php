<?php
try {
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'theme_graphics_delete_success_message', 'تصویر حذف شد');


}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
