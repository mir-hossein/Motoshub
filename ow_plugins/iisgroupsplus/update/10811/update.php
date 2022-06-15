<?php
try {
    $authorization = OW::getAuthorization();
    $groupName = 'groups';
    $authorization->deleteAction($groupName, 'groups-add-file');
    $authorization->deleteAction($groupName, 'groups-update-status');
}catch (Exception $e){}

$languageService = Updater::getLanguageService();

$languages = $languageService->getLanguages();
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
    $languageService->addOrUpdateValue($langFaId, 'iisgroupsplus', 'view_category_label', 'دسته: <a href="{$categoryUrl}">{$categoryLabel}</a>');
}

if ($langEnId != null) {
    $languageService->addOrUpdateValue($langFaId, 'iisgroupsplus', 'view_category_label', 'Category: <a href="{$categoryUrl}">{$categoryLabel}</a>');
}