<?php
/**
 * @author Mohammad Reza Heidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'photo', 'user_photo_albums_widget', 'آلبوم تصاویر {$username}');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'photo', 'page_title_user_albums', 'آلبوم تصاویر {$user}');

$languageService->addOrUpdateValueByLanguageTag('en', 'photo', 'user_photo_albums_widget', '{$username} Photo Albums');