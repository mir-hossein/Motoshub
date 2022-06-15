<?php
/**
 * Created by PhpStorm.
 * User: seied
 * Date: 4/19/2017
 * Time: 11:06 AM
 */

$languageService = Updater::getLanguageService();

$languages = $languageService->getLanguages();
$langFaId = null;
foreach ($languages as $lang) {
    if ($lang->tag == 'fa-IR') {
        $langFaId = $lang->id;
    }
}

if ($langFaId != null) {
    $languageService->addOrUpdateValue($langFaId, 'photo', 'list_type_label_photo_friends', 'مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_title_photo_photo_friends', 'تصاویر مخاطبان - {$site_name}');
}
