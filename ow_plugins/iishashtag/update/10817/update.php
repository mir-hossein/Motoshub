<?php
/**
 * User: Issa Annamoradnejad
 */

$languageService = Updater::getLanguageService();
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iishashtag', 'author_label', 'نویسنده: <span class="ow_txt_value">{$name}</span>');
$languageService->addOrUpdateValueByLanguageTag('en', 'iishashtag', 'author_label', 'Author: <span class="ow_txt_value">{$name}</span>');
