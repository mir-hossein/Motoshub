<?php
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
    $languageService->addOrUpdateValue($langFaId, 'iisnews', 'add_form_image_label', 'تصویر');
}

if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'iisnews', 'add_form_image_label', 'image');
}

$sql = "ALTER TABLE `".OW_DB_PREFIX."iisnews_entry` ADD `image` VARCHAR(32) default NULL;";

try {
    Updater::getDbo()->query($sql);
}
catch ( Exception $e ){ $exArr[] = $e; }
