<?php
/**
 * Created by PhpStorm.
 * User: seied
 * Date: 4/19/2017
 * Time: 3:04 PM
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
    $languageService->addOrUpdateValue($langFaId, 'iisphotoplus', 'photo_friends', 'تصاویر مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'iisphotoplus', 'meta_description_photo_friends', 'می توانید تصاویر مخاطبان خود را در این صفحه مشاهده کنید.');
    $languageService->addOrUpdateValue($langFaId, 'iisphotoplus', 'meta_title_photo_add_friends', 'تصاویر مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'iisphotoplus', 'meta_title_photo_photo_friends', 'تصاویر مخاطبان - {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'iisphotoplus', 'list_type_label_photo_friends', 'تصاویر مخاطبان ');
}