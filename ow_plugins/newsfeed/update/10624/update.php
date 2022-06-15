<?php
/**
 * @author Mohammad Reza Heidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languages = $languageService->getLanguages();
$langEnId = null;
$langFaId = null;
foreach ($languages as $lang) {
    if ($lang->tag == 'fa-IR') {
        $langFaId = $lang->id;
    }
    if ($lang->tag == 'en') {
        $langEnId = $lang->id;
    }
}

if ($langFaId != null) {
    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'email_notifications_setting_status_like', 'کسی وضعیت من را می‌پسندد');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'unfollow_button', 'دنبال نکردن');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'admin_features_expanded_label', 'حالت گسترده به صورت پیش‌فرض برای پسندیدن و نظرات');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'admin_save_btn', 'ذخیره');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'email_notifications_status_like', '<a href="{$userUrl}">{$userName}</a> <a href="{$url}"> نوشته </a> شما را پسندید');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'admin_settings_title', 'تنظیمات');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'feed_view_more_btn', 'نمایش بیشتر');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'settings_updated', 'تنظیمات به‌روزرسانی شد');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'widget_settings_count', 'گزینه‌ها برای نمایش');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'widget_settings_view_more', 'نمایش بیشتر');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'admin_index_status_label', 'فعال‌سازی فیلد «چه خبر» در صفحه اصلی');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'admin_comments_count_label', 'تعداد نظرات پیش‌فرض برای نمایش');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'admin_page_heading', 'تنظیمات افزونه تازه‌ها');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'admin_page_title', 'تنظیمات افزونه تازه‌ها');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'follow_complete_message', 'شما اکنون {$username} را دنبال می‌کنید');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'activity_string_self_status_like', 'نوشته وضعیت خودش را دوست دارد');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'admin_customization_legend', 'امکان کنترل این‌که چه نوع مطالبی در تازه‌ها نشان داده شود، لطفا توجه کنید که تغییرات تنها بر روی پست‌های جدید اثر خواهد گذاشت.');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'delete_feed_item_label', 'حذف نوشته');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'delete_feed_item_user_label', 'حذف کاربر');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'feed_delete_item_label', 'حذف نوشته');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'feed_likes_1_label', '{$user1} پسندید');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'feed_likes_2_label', '{$user1} و {$user2} پسندیدند');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'feed_likes_3_label', '{$user1}, {$user2} و {$user3} پسندیدند');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'feed_likes_list_label', '<a href="{$url}">{$count} کاربر</a> پسندیدند');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'follow_complete_message', 'شما اکنون فعالیت‌های {$username} را دنبال می‌کنید');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'unfollow_complete_message', 'شما دیگر فعالیت‌های {$username} را دنبال نمی‌کنید');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'item_deleted_feedback', 'گزینه حذف شد');

    $languageService->addOrUpdateValue($langFaId, 'newsfeed', 'email_notifications_setting_user_status', 'کسی بر روی نمایه من چیزی نوشت');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValueByLanguageTag($langEnId, 'newsfeed', 'email_notifications_status_like', '<a href="{$userUrl}">{$userName}</a> likes your "<a href="{$url}">status</a>"');

}