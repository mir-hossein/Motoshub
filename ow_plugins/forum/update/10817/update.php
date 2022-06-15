<?php
/**
 * User: Issa Annamoradnejad
 * Date: 10/10/2017
 */

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'toolbar_post_number', 'پست شماره {$num}');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'toolbar_post_number', 'Post {$num}');
