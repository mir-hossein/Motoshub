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
    $languageService->addOrUpdateValue($langFaId, 'friends', 'accept_friendship_request', 'تایید درخواست افزودن مخاطب');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'activity_title', '<a href="{$user_url}">{$name}</a> و <a href="{$requester_url}">{$requester_name}</a> اکنون مخاطب یکدیگر هستند');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'add_to_friends', 'افزودن به مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'auth_action_label_add_friend', 'ارسال درخواست افزودن مخاطب');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'auth_group_label', 'مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'comment_activity_string', 'نظر داد بر روی {$user1} و {$user2} که اکنون مخاطب یکدگر شدند');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'confirmed_mail_html', '<p>
    {$sender} گرامی
    </p>
    <p>
    خیلی خوشحال هستیم که به شما خبر میدهیم اکنون شما و <a href="{$receiver_url}">{$receiver}</a> در <a href="{$site_url}">{$site_name}</a> مخاطب یکدیگر هستید
    </p>
    ');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'confirmed_mail_subject', 'هم اکنون {$receiver} مخاطب شما می باشد');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'confirmed_mail_txt', '{$sender} گرامی خیلی خوشحال هستیم که به شما خبر میدهیم اکنون شما و {$receiver} در {$site_name} مخاطب یکدیگر هستید
    <br/>
    {$receiver} نمایه : {$receiver_url}
    <br/>
    {$site_name}: {$site_url}
    ');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'console_requests_title', 'درخواست‌های افزودن مخاطب');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'console_request_item', '<a target="_blank" href="{$userUrl}">{$displayName}</a> می‌خواهد مخاطب شما شود');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'email_notifications_setting_accept', 'کسی درخواست افزودن مخاطب من را تایید کرده است');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'email_notifications_setting_request', 'کسی برای من درخواست افزودن مخاطب ارسال کرده است');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'feedback_cancelled', 'کاربر مورد نظر از فهرست مخاطبان شما حذف شد');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'feedback_request_accepted', 'مخاطب جدید افزوده شد');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'feedback_request_was_sent', 'درخواست افزودن مخاطب ارسال شد');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'feed_content_label', 'مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'friendship_management_area', 'مدیریت مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'friends_tab', 'مخاطب {$count}');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'friend_requests', 'درخواست‌های افزودن مخاطب');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'friend_request_was_sent', 'درخواست افزودن مخاطب فرستاده شد');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'got_requests_tab', '{$count} درخواست افزودن مخاطب');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'like_activity_string', 'می‌پسندد که {$user1} و {$user2} اکنون مخاطب یکدیگر هستند');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'my_friends_page_heading', 'مخاطبان من');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'my_friends_page_title', 'مخاطبان من - {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'newsfeed_action_string', 'و <a href="{$user_url}">{$name}</a> هم اکنون مخاطب یکدیگر هستند!');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'new_friend_added', 'مخاطب جدید افزوده شد');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'notification_section_label', 'مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'notify_accept', '{$receiver} شما را به عنوان مخاطب تایید کرد.');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'notify_request', '{$sender} می خواهد مخاطب شما شود');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'notify_request_string', '<a href="{$userUrl}">{$displayName}</a> درخواست افزودن شما به مخاطبان خود را دارد.');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'privacy_action_view_friends', 'نمایش مخاطبان من');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'privacy_friends_only', 'مخاطبان من');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'remove_from_friends', 'حذف از مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'remove_from_friendship_button', 'حذف از مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'remove_friendship_request', 'حذف درخواست افزودن مخاطب');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'request_friendship_button', 'ارسال درخواست افزودن مخاطب');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'request_mail_subject', '{$sender} درخواست افزودن شما به مخاطبان خود را دارد');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'usercredits_action_add_friend', 'درخواست افزودن مخاطب');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'user_friends_page_heading', '{$user} مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'user_friends_page_title', '{$user} مخاطبان - {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'user_widget_empty', 'هنوز مخاطبی ندارد');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'user_widget_title', 'مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'widget_friends', 'مخاطبان');
    $languageService->addOrUpdateValue($langFaId, 'friends', 'widget_title', 'مخاطبان من');
}