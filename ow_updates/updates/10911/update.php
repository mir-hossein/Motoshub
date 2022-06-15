<?php

try
{
    $logger = Updater::getLogger();
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'feed_activity_join_profile_string_like', 'می‌پسندد که {$user} عضو شبکه اجتماعی {$site_name} شد');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'feed_user_join', 'به شبکه اجتماعی {$site_name} پیوست');
    $languageService->addOrUpdateValueByLanguageTag('en', 'base', 'feed_activity_join_profile_string_like', 'liked that {$user} joined {$site_name} social network');
    $languageService->addOrUpdateValueByLanguageTag('en', 'base', 'feed_user_join', 'joined {$site_name} social network');
}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
