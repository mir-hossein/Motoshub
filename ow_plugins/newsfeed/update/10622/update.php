<?php

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'newsfeed', 'email_notifications_status_like', '<a href="{$userUrl}">{$userName}</a> <a href="{$url}"> نوشته </a> شما را پسندید');
$languageService->addOrUpdateValueByLanguageTag('en', 'newsfeed', 'email_notifications_status_like', '<a href="{$userUrl}">{$userName}</a> likes your "<a href="{$url}">status</a>"');

