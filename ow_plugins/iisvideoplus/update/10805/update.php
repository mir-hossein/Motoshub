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
    $languageService->addOrUpdateValue($langFaId, 'iisvideoplus', 'latest_friends', 'آخرین ویدیوهای مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'iisvideoplus', 'meta_description_video_latest_friends', 'میتوانید آخرین ویدیوهای مخاطبان خود را در این صفحه مشاهده کنید.');
    $languageService->addOrUpdateValue($langFaId, 'iisvideoplus', 'meta_title_video_add_latest_friends', 'آخرین ویدیوهای مخاطبان');
}
