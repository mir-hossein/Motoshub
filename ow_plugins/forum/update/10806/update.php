<?php
/**
 * @author Seyed Ismail Mirvakili
 * Date: 5/22/2017
 * Time: 1:12 PM
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
    $languageService->addOrUpdateValue($langFaId, 'forum', 'feed_activity_topic_reply_string', 'در یک موضوع انجمن، پاسخی ارائه داد.');
}