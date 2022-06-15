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
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'friends_online', 'مخاطبان حاضر');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'no_contacts_online', 'هیچ مخاطبی اکنون حاضر نیست');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'settings_friends_only_label', 'فقط مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'warning_user_privacy_friends_only', '{$displayname} فقط درخواست‌های گپ از مخاطبان خود را می‌پذیرد');
}
