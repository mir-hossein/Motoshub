<?php
/**
 * Created by PhpStorm.
 * User: seied
 * Date: 4/20/2017
 * Time: 12:22 PM
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
    $languageService->addOrUpdateValue($langFaId, 'iisadvancesearch', 'search_friends', 'جستجوی مخاطبان');
}