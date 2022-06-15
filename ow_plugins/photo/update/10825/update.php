<?php
/**
 * @author Mohammad Reza Heidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'photo', 'meta_title_user_albums', 'آلبوم تصاویر {$user_name}');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'photo', 'meta_title_photo_useralbums', 'آلبوم تصاویر {$displayName}');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'photo', 'meta_title_photo_useralbum', '{$albumName} - آلبوم تصاویر {$displayName}');

$languageService->addOrUpdateValueByLanguageTag('en', 'photo', 'meta_title_user_albums', '{$displayName} photo album');
$languageService->addOrUpdateValueByLanguageTag('en', 'photo', 'user_photo_albums_widget', '{$displayName} photo album');
$languageService->addOrUpdateValueByLanguageTag('en', 'photo', 'meta_title_photo_useralbums', '{$displayName} photo album');
$languageService->addOrUpdateValueByLanguageTag('en', 'photo', 'meta_title_photo_useralbum', '{$albumName} - {$displayName} photo album');



