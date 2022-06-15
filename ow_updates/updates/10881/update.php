<?php

try
{
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'feed_user_edit_profile', 'جزئیات نمایه خود را ویرایش کرد');
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
