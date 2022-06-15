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
    $languageService->addOrUpdateValue($langFaId,'iisgroupsplus', 'meta_title_group_files','  فایلهای گروه {$group_title}');
}
if ($langEn != null) {
    $languageService->addOrUpdateValue($langEn,'iisgroupsplus','meta_title_group_files','  {$group_title} group files');
}