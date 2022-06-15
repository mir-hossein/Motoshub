<?php
/**
 * @author Mohammad Reza Heidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'activity_add_string', '<a href="{$userUrl}">{$user}</a> گروه جدید <a href="{$groupUrl}">{$groupTitle}</a> ساخت.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'activity_join_string', '<a href="{$userUrl}">{$user}</a> عضو گروه<a href="{$groupUrl}">{$groupTitle}</a> شد.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'feed_create_string', 'گروه جدید ایجاد کرد.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'feed_follow_complete_msg', 'شما اکنون {$groupTitle} را دنبال می‌کنید.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'feed_unfollow_complete_msg', 'شما دیگر {$groupTitle} را دنبال نمی‌کنید.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'group_edited_string', 'گروه‌ ویرایش شد.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'leave_complete_message', 'شما از این گروه خارج شده‌اید.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'notifications_new_message', 'کسی من را به گروهی دعوت می‌کند.');


$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'feed_create_string', 'created a new group.');