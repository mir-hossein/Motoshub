<?php
/**
 * @author Mohammad Reza Heidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'meta_title_user_blog', 'بلاگ نوشته شده توسط {$user_name} ،{$user_age} | {$site_name}');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'user_blog_archive_description', 'نوشته‌های بلاگ {$display_name} ارسال شده در {$month_name} ،{$year}.');
