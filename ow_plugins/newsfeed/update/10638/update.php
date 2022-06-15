<?php
/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 11:49 AM
 */

$languageService = Updater::getLanguageService();
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'newsfeed', 'email_notifications_status_comment', '<a href="{$userUrl}">{$userName}</a> به مطلب شما نظر داد: «<a href="{$url}">{$status}</a>»');