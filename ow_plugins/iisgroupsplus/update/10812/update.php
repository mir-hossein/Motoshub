<?php
/**
 * @author Seyed Ismail Mirvakili
 * Date: 5/31/2017
 * Time: 3:43 PM
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
    $languageService->addOrUpdateValue($langFaId, 'iisgroupsplus', 'widget_files_settings_count', 'تعداد فایل');
    $languageService->addOrUpdateValue($langEnId, 'iisgroupsplus', 'widget_files_settings_count', 'Files number');
}