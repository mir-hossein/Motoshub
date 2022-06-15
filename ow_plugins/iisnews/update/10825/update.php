<?php
/**
 * @author Seyed Ismail Mirvakili
 * Date: 11/25/2017
 * Time: 5:46 AM
 */

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisnews', 'news_entry_title', '{$entry_title} در {$site_name}');
$languageService->addOrUpdateValueByLanguageTag('en', 'iisnews', 'news_entry_title', '{$entry_title} at {$site_name}');
