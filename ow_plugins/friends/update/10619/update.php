<?php
/**
 * @author Mohammad Reza Heidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','activity_title', '<a href="{$user_url}">{$name}</a> و <a href="{$requester_url}">{$requester_name}</a> اکنون دوست یکدیگر هستند.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','comment_activity_string', 'نظر داد بر روی {$user1} و {$user2} که اکنون دوست یکدیگر شدند.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','confirmed_mail_subject', 'هم اکنون {$receiver} دوست شما است.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','email_notifications_setting_accept', 'کسی درخواست افزودن دوست من را تایید کرده است.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','email_notifications_setting_request', 'کسی برای من درخواست افزودن دوست ارسال کرده است.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','feedback_cancelled', 'کاربر مورد نظر از فهرست دوستان شما حذف شد.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','feedback_request_accepted', 'دوست جدید افزوده شد.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','feedback_request_ignored', 'درخواست رد شد.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','feedback_request_was_sent', 'درخواست افزودن دوست ارسال شد.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','friend_request_was_sent', 'درخواست افزودن دوست فرستاده شد.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','like_activity_string', 'می‌پسندد که {$user1} و {$user2} اکنون دوست یکدیگر هستند.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','newsfeed_action_string', 'و <a href="{$user_url}">{$name}</a> هم اکنون دوست یکدیگر هستند.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','new_friend_added', 'دوست جدید افزوده شد.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','notify_request', '{$sender} می خواهد دوست شما شود.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'friends','request_mail_subject', '{$sender} درخواست افزودن شما به دوستان خود را دارد.');

$languageService->addOrUpdateValueByLanguageTag('en', 'friends','newsfeed_action_string', 'and <a href="{$user_url}">{$name}</a> are now friends.');
$languageService->addOrUpdateValueByLanguageTag('en', 'friends','request_mail_subject', '{$sender} wants to be friends with you.');
