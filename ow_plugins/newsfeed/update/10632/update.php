<?php

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'newsfeed', 'user_like', 'کاربر');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'newsfeed', 'email_notifications_status_comment', '<a href="{$userUrl}">{$userName}</a> به وضعیت شما نظر داد: «<a href="{$url}">{$status}</a>»');