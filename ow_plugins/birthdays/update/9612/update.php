<?php
/**
 * Created by PhpStorm.
 * User: seied
 * Date: 4/19/2017
 * Time: 3:17 PM
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
    $languageService->addOrUpdateValue($langFaId, 'birthdays', 'dashboard_widget_title', 'روزهای تولد مخاطبان من');
}