<?php
/**
 * User: Issa Annamoradnejad
 * Date: 10/10/2017
 */

$languageService = Updater::getLanguageService();
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iishashtag', 'list_page_title', 'نتایج برای #{$tag}');
$languageService->addOrUpdateValueByLanguageTag('en', 'iishashtag', 'list_page_title', 'Results for #{$tag}');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'toolbar_post_number', 'پست شماره {$num}');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'toolbar_post_number', 'Post {$num}');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iishashtag', 'able_to_see_text', 'شما قادر به مشاهده {$num} مورد از {$all} مورد هستید.');
$languageService->addOrUpdateValueByLanguageTag('en', 'iishashtag', 'able_to_see_text', 'You are able to view {$num} of {$all} item(s).');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iishashtag', 'search_placeholder', 'هشتگ');
$languageService->addOrUpdateValueByLanguageTag('en', 'iishashtag', 'search_placeholder', 'Enter Hashtag');
