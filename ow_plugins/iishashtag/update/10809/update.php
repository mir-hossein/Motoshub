<?php
/**
 * User: Issa Annamoradnejad
 * Date: 8/19/2017
 */
$authorization = OW::getAuthorization();
$groupName = 'iishashtag';
$authorization->addGroup($groupName);
$authorization->addAction($groupName, 'view_newsfeed', true);

$languageService = Updater::getLanguageService();
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iishashtag', 'auth_action_label_view_newsfeed', 'مشاهده نتایج تازه‌ها');
$languageService->addOrUpdateValueByLanguageTag('en', 'iishashtag', 'auth_action_label_view_newsfeed', 'View Newsfeed Results');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iishashtag', 'auth_group_label', 'هشتگ');
$languageService->addOrUpdateValueByLanguageTag('en', 'iishashtag', 'auth_group_label', 'Hashtag');
