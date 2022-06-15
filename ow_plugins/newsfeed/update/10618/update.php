<?php
$languageService = Updater::getLanguageService();

$languages = $languageService->getLanguages();
$langFaId = null;
foreach ($languages as $lang) {
    if ($lang->tag == 'fa-IR') {
        $langFaId = $lang->id;
    }
}

if ($langFaId != null) {
    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'empty_feed_message', 'موردی وجود ندارد');
}
