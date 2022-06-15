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
    $languageService->addOrUpdateValue($langFaId, 'iisgroupsplus', 'select_category', 'هر دسته');
    $languageService->addOrUpdateValue($langFaId, 'iisgroupsplus', 'choose_category', 'انتخاب دسته');
    $languageService->addOrUpdateValue($langFaId, 'iisgroupsplus', 'view_category_label', 'دسته: <a href="{$categoryUrl}">{$categoryLabel}</a>');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'iisgroupsplus', 'select_category', 'Any category');
    $languageService->addOrUpdateValue($langEnId, 'iisgroupsplus', 'choose_category', 'Select category');
    $languageService->addOrUpdateValue($langEnId, 'iisgroupsplus', 'choose_category', 'Category: <a href="{$$categoryUrl}">{$categoryLabel}</a>');
}