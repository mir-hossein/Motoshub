<?php

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisnews', 'index_page_title', '{$title} | {$site_name}');
$languageService->addOrUpdateValueByLanguageTag('en', 'iisnews', 'index_page_title', '{$site_name} | {$title}');


