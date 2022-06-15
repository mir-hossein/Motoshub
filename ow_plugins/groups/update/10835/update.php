<?php
$languageService = Updater::getLanguageService();

$languages = $languageService->getLanguages();
$langFaId = null;
$langEn = null;
foreach ($languages as $lang) {
    if ($lang->tag == 'fa-IR') {
        $langFaId = $lang->id;
    }
    if ($lang->tag == 'en') {
        $langEn = $lang->id;
    }
}

if ($langFaId != null) {
    $languageService->addOrUpdateValue($langFaId,'groups','leave_group_confirm_msg','آیا از ترک گروه اطمینان دارید؟');
}
if ($langEn != null) {
    $languageService->addOrUpdateValue($langEn,'groups','leave_group_confirm_msg','Are you sure you want to leave the group');
}