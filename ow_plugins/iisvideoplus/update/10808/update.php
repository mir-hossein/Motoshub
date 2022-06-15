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
    $languageService->addOrUpdateValue($langFaId, 'iisvideoplus', 'latest_myvideo', 'ویدیوهای من');

    $languageService->addOrUpdateValue($langFaId, 'iisvideoplus', 'meta_title_video_add_latest_myvideo', 'ویدیوهای من');

    $languageService->addOrUpdateValue($langFaId, 'iisvideoplus', 'meta_description_video_latest_myvideo', 'می‌توانید ویدیوهای خود را در این صفحه مشاهده کنید.');
}
if ($langEnId != null) {

}