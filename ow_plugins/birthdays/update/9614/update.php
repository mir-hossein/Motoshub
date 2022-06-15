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
    $languageService->addOrUpdateValue($langFaId, 'birthdays', 'my_widget_title', 'تاریخ تولد من');
    $languageService->addOrUpdateValue($langFaId, 'birthdays', 'feed_item_line', 'در این تاریخ به دنیا آمده است.');
    $languageService->addOrUpdateValue($langFaId, 'birthdays', 'birthday', 'روز تولد');
}