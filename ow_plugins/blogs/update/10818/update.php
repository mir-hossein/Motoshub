<?php

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'mobile_main_menu_list', 'بلاگ‌ها');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'mobile_main_menu_list', 'Blogs');
try {
    OW::getNavigation()->addMenuItem(OW_Navigation::MOBILE_TOP, 'blogs', 'blogs', 'mobile_main_menu_list', OW_Navigation::VISIBLE_FOR_MEMBER);
}catch(Exception $e) {}