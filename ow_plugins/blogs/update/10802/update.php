<?php

$languageService = Updater::getLanguageService();

$languages = $languageService->getLanguages();
$langFaId = null;
$langEnId = null;
foreach ($languages as $lang) {
    if ($lang->tag == 'fa-IR') {
        $langFaId = $lang->id;
    }
    if ($lang->tag == 'en') {
        $langEnId = $lang->id;
    }
}


if ($langFaId != null) {
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'show_all_blogs', '<a href="{$url}">نمایش تمامی بلاگ‌های {$user_name}</a>');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'view_page_heading', '{$postTitle}');
}

if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'show_all_blogs', '<a href="{$url}">show all {$user_name}\'s blogs</a>');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'view_page_heading', '{$postTitle}');

}