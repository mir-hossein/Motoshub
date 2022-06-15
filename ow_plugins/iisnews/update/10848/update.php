<?php

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisnews', 'news_entry_title', '{$entry_title} | {$site_name}');
$languageService->addOrUpdateValueByLanguageTag('en', 'iisnews', 'news_entry_title', '{$site_name} | {$entry_title}');


