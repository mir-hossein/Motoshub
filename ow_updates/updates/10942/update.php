<?php

try
{
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'feed_activity_join_profile_string_like', 'می‌پسندد که {$user} عضو {$site_name} شد');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'base', 'feed_user_join', 'به {$site_name} پیوست');
    $languageService->addOrUpdateValueByLanguageTag('en', 'base', 'feed_activity_join_profile_string_like', 'liked that {$user} joined {$site_name}');
    $languageService->addOrUpdateValueByLanguageTag('en', 'base', 'feed_user_join', 'joined {$site_name}');
}
catch (Exception $e)
{
    Updater::getLogger()->addEntry(json_encode($e));
}
