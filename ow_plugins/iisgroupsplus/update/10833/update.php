<?php
$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisgroupsplus', 'feed_add_file_string', 'فایل <a href="{$fileUrl}" target="_blank" >{$fileName}</a> را در <a href="{$groupUrl}">{$groupTitle}</a> بارگذاری کرد');
$languageService->addOrUpdateValueByLanguageTag('en', 'iisgroupsplus', 'feed_add_file_string', ' add <a href="{$fileUrl}" target="_blank" >{$file}</a> to <a href="{$groupUrl}">{$groupTitle}</a>.');
