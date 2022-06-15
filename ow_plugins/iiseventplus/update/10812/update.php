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
    $languageService->addOrUpdateValue($langFaId, 'iiseventplus', 'select_category', 'هر دسته');
    $languageService->addOrUpdateValue($langFaId, 'iiseventplus', 'choose_category', 'انتخاب دسته');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'iiseventplus', 'select_category', 'Any category');
    $languageService->addOrUpdateValue($langEnId, 'iiseventplus', 'choose_category', 'Select category');
}