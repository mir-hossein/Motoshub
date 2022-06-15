<?php
/**
 * @author Seyed Ismail Mirvakili
 * Date: 5/31/2017
 * Time: 11:08 AM
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
    $languageService->addOrUpdateValue($langFaId, 'forum', 'feed_toolbar_discuss', 'مشاهده موضوع');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'forum', 'feed_toolbar_discuss', 'View Topic');
}