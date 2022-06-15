<?php
/**
 * @author Mohammad Reza Heidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languages = $languageService->getLanguages();
$langEnId = null;
$langFaId = null;
foreach ($languages as $lang) {
    if ($lang->tag == 'fa-IR') {
        $langFaId = $lang->id;
    }
    if ($lang->tag == 'en') {
        $langEnId = $lang->id;
    }
}

if ($langFaId != null) {
    $languageService->addOrUpdateValue($langFaId, 'iismainpage', 'user_groups', 'گروه‌های من');
    $languageService->addOrUpdateValue($langFaId, 'iismainpage', 'find_friends', 'یافتن مخاطبان جدید');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'iismainpage', 'user_groups', 'My groups');
    $languageService->addOrUpdateValue($langEnId, 'iismainpage', 'find_friends', 'Find New Friends');
}