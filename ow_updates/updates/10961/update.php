<?php
try {
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'auth_success_message_not_ajax', 'احراز هویت با موفقیت انجام شد');
}
catch (Exception $e)
{
    $logger->writeLog(OW_Log::ERROR, 'Core update_failed', array('exception'=>$e));
}
