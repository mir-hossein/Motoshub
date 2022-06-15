<?php
/**
 * Created by PhpStorm.
 * User: seied
 * Date: 4/19/2017
 * Time: 11:17 AM
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
    $languageService->addOrUpdateValue($langFaId, 'event', 'friends_invite_button_label', 'دعوت مخاطبانم');
}
