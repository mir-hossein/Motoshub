<?php
/**
 * Created by PhpStorm.
 * User: seied
 * Date: 4/22/2017
 * Time: 9:44 AM
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
    $languageService->addOrUpdateValue($langFaId, 'video', 'latest_friends_list_label', 'مخاطبان');
}