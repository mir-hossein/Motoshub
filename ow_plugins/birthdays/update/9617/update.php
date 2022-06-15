<?php

$languageService = Updater::getLanguageService();
$languageService->addOrUpdateValueByLanguageTag('fa-IR','birthdays', 'console_notification_comment', '<a href="{$userUrl}">{$userName}</a> برای روز تولد شما نظر داد');
$languageService->addOrUpdateValueByLanguageTag('en', 'birthdays', 'console_notification_comment', '<a href="{$userUrl}">{$userName}</a> commented about your birthday');

$languageService->addOrUpdateValueByLanguageTag('fa-IR','birthdays', 'console_notification_like', '<a href="{$userUrl}">{$userName}</a>; روز تولد شما را می‌پسندد');
$languageService->addOrUpdateValueByLanguageTag('en', 'birthdays', 'console_notification_like', '<a href="{$userUrl}">{$userName}</a> likes your birthday');

$languageService->addOrUpdateValueByLanguageTag('fa-IR','birthdays', 'email_notification_comment', '{$user} در مورد روز تولد شما نظر داد');
$languageService->addOrUpdateValueByLanguageTag('en', 'birthdays', 'email_notification_comment', '{$user} commented about your birthday');

$languageService->addOrUpdateValueByLanguageTag('fa-IR','birthdays', 'feed_activity_self_birthday_string', 'بر روز تولد نظر داد');
$languageService->addOrUpdateValueByLanguageTag('en', 'birthdays', 'feed_activity_self_birthday_string', 'commented on their birthday');

$languageService->addOrUpdateValueByLanguageTag('fa-IR','birthdays', 'feed_item_line', 'در این تاریخ به دنیا آمده است');
$languageService->addOrUpdateValueByLanguageTag('en', 'birthdays', 'feed_item_line', 'It\'s {$user}\'s birthday');
