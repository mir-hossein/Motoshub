<?php
/**
 * @author Mohammad Reza Heidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iiseventplus', 'notif_add_file_string', '<a href="{$userUrl}">{$userName}</a>  فایل «<a href="{$eventUrl}">{$fileName}</a> »  را در «<a href="{$eventUrl}">{$eventTitle}</a>» بارگذاری کرد');