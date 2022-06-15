<?php

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisphotoplus', 'multiple_photo_liked_notification', '<a href="{$userUrl}">{$user}</a> تصاویر شما را در آلبوم: <a href="{$albumUrl}">{$album}</a> پسندید');

$languageService->addOrUpdateValueByLanguageTag('en', 'iisphotoplus', 'multiple_photo_liked_notification', '<a href="{$userUrl}">{$user}</a> liked album: <a href="{$albumUrl}">{$album}</a> photos');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisphotoplus', 'photo_liked_notification', '<a href="{$userUrl}">{$user}</a> تصویر شما را پسندید');

$languageService->addOrUpdateValueByLanguageTag('en', 'iisphotoplus', 'photo_liked_notification', '<a href="{$userUrl}">{$user}</a> likes your photo');