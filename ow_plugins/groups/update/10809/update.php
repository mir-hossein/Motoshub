<?php
/**
 * Created by PhpStorm.
 * User: seied
 * Date: 4/19/2017
 * Time: 11:35 AM
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
    $languageService->addOrUpdateValue($langFaId, 'groups', 'post_reply_permission_error', 'برای درج پاسخ، شما باید عضو گروه باشید.');
}