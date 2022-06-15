<?php
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
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'edited_message_tag', '(ویرایش شده)');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'mailbox', 'edited_message_tag', '(edited)');
}