<?php
/**
 * @author Seyed Ismail Mirvakili
 * Date: 6/19/2017
 * Time: 11:22 AM
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
    $languageService->addOrUpdateValue($langFaId, 'video', 'clip_updated', 'ویدیو به‌روزرسانی شد');
    $languageService->addOrUpdateValue($langFaId, 'video', 'cmp_widget_user_video_show_titles', 'نمایش عناوین ویدیو');
    $languageService->addOrUpdateValue($langFaId, 'video', 'my_video', 'ویدیو من');
}