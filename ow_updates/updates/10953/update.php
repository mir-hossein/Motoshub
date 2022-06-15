<?php
try {
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'ow_mail_information', 'این رایانامه از طریق وبگاه «{$site_name}» برای شما ارسال شده است');
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
