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
    $languageService->addOrUpdateValue($langFaId, 'event', 'view_page_date_label', 'تاریخ و زمان شروع');

    $languageService->addOrUpdateValue($langFaId, 'event', 'add_form_end_date_label', 'تاریخ و زمان اتمام');

    $languageService->addOrUpdateValue($langFaId, 'event', 'no_events_label', 'موردی وجود ندارد');

    //issue 966

    $languageService->addOrUpdateValue($langFaId, 'event', 'add_form_success_message', 'رویداد اضافه شد.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'add_form_who_can_invite_option_creator', 'فقط سازنده رویداد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'add_new_button_label', 'افزودن رویداد جدید');

    $languageService->addOrUpdateValue($langFaId, 'event', 'add_new_link_label', 'رویداد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'add_page_heading', 'ساخت رویداد جدید');

    $languageService->addOrUpdateValue($langFaId, 'event', 'add_page_title', 'ساخت رویداد جدید - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'auth_action_label_add_comment', 'اظهار نظر رویدادها');

    $languageService->addOrUpdateValue($langFaId, 'event', 'auth_action_label_add_event', 'افزودن رویدادها');

    $languageService->addOrUpdateValue($langFaId, 'event', 'auth_action_label_view_event', 'مشاهده رویدادها');

    $languageService->addOrUpdateValue($langFaId, 'event', 'auth_group_label', 'رویدادها');

    $languageService->addOrUpdateValue($langFaId, 'event', 'cmp_widget_events_count', 'تعداد رویدادها');

    $languageService->addOrUpdateValue($langFaId, 'event', 'common_list_type_joined_label', 'رویدادهای من');

    $languageService->addOrUpdateValue($langFaId, 'event', 'console_notification_label', 'رویداد دعوت‌نامه‌ها');

    $languageService->addOrUpdateValue($langFaId, 'event', 'content_events_label', 'رویدادها');

    $languageService->addOrUpdateValue($langFaId, 'event', 'content_event_label', 'رویداد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'delete_confirm_message', 'آیا از حذف این رویداد اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'event', 'delete_success_message', 'رویداد حذف شد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'edit_form_end_date_error_message', 'لطفا یک رویداد پایانی تاریخ/زمان را انتخاب کنید.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'edit_form_success_message', 'رویداد ویرایش شد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'edit_page_heading', 'ویرایش رویداد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'edit_page_title', 'ویرایش رویداد – {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'email_notification_comment', '<a href="{$userUrl}">{$userName}</a> نظرخود را در رویداد <a href="{$url}">{$title}</a> بیان کرد.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'email_notification_comment_setting', 'یک نفر بر دیوارهای رویداد پست ارسال کرد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'email_notification_invite', '<a href="{$inviterUrl}">{$inviterName}</a> شما را دعوت کرده به رویداد "<a href="{$eventUrl}">{$eventName}</a>"');

    $languageService->addOrUpdateValue($langFaId, 'event', 'event_created_by_me_page_heading', 'رویدادهایی که ایجاد کرده‌ام');

    $languageService->addOrUpdateValue($langFaId, 'event', 'event_created_by_me_page_title', 'رویدادهایی که ایجاد کرده‌ام - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'event_create_string', 'ایجاد رویداد جدید');

    $languageService->addOrUpdateValue($langFaId, 'event', 'event_edited_string', 'ویرایش رویداد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'event_joined_by_me_page_heading', 'رویدادهایی که شرکت می کنم');

    $languageService->addOrUpdateValue($langFaId, 'event', 'event_joined_by_me_page_title', 'رویدادهایی که شرکت می‌کنم - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'feed_actiovity_attend_string', 'در رویداد {$user} شرکت می‌کند');

    $languageService->addOrUpdateValue($langFaId, 'event', 'feed_activity_comment_string', 'بر رویداد {$user} نظر داد.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'feed_activity_event_string_like', 'رویداد {$user} را می‌پسندد.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'feed_activity_event_string_like_own', 'رویداد آنها را می‌پسندد.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'feed_activity_own_comment_string', 'بر رویداد اظهارنظر کرد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'feed_add_item_label', 'رویداد جدید ایجاد کرد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'feed_content_label', 'رویدادها');

    $languageService->addOrUpdateValue($langFaId, 'event', 'feed_user_join_string', 'در رویداد شرکت می‌کند');

    $languageService->addOrUpdateValue($langFaId, 'event', 'invitation_join_string_1', '{$user1} شما را به رویداد {$event} دعوت کرد.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'invitation_join_string_2', '{$user1} و {$user2} شما را به رویداد {$event} دعوت کردند.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'invitation_join_string_many', '{$user1} و {$user2} و {$otherUsers} شما را به رویداد {$event} دعوت کردند.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'invited_events_page_heading', 'دعوت‌نامه‌های رویداد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'invited_events_page_title', 'دعوت‌نامه‌های رویداد - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'latest_events_page_desc', 'لیست رویدادهای پیش‌رو برای اعضا {$site_name}.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'latest_events_page_heading', 'رویداد‌ها');

    $languageService->addOrUpdateValue($langFaId, 'event', 'latest_events_page_title', 'رویدادها - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'main_menu_item', 'رویدادها');

    $languageService->addOrUpdateValue($langFaId, 'event', 'my_events_widget_block_cap_label', 'رویدادهای من');

    $languageService->addOrUpdateValue($langFaId, 'event', 'notifications_new_message', 'شخصی مرا به یک رویداد دعوت می‌کند');

    $languageService->addOrUpdateValue($langFaId, 'event', 'notifications_section_label', 'رویدادها');

    $languageService->addOrUpdateValue($langFaId, 'event', 'no_index_events_label', 'رویدادی نیست, <a href="{$url}">افزودن جدید</a>');

    $languageService->addOrUpdateValue($langFaId, 'event', 'past_events_page_desc', 'لیست رویدادهای سابق برای اعضای {$site_name}.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'past_events_page_heading', 'رویدادهای سابق');

    $languageService->addOrUpdateValue($langFaId, 'event', 'past_events_page_title', 'رویدادهای سابق - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'privacy_action_view_attend_events', 'مشاهده رویدادهای من');

    $languageService->addOrUpdateValue($langFaId, 'event', 'private_event_text', 'پوزش می‌خواهیم. این رویداد خصوصی است.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'private_page_heading', 'رویداد خصوصی');

    $languageService->addOrUpdateValue($langFaId, 'event', 'private_page_title', 'رویداد خصوصی');

    $languageService->addOrUpdateValue($langFaId, 'event', 'profile_events_widget_block_cap_label', 'رویدادهای من');

    $languageService->addOrUpdateValue($langFaId, 'event', 'up_events_widget_block_cap_label', 'رویدادهای پیش‌رو');

    $languageService->addOrUpdateValue($langFaId, 'event', 'usercredits_action_add_comment', 'نظر دادن رویداد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'usercredits_action_add_event', 'افزودن رویداد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'user_list_page_desc_1', 'لیست کاربران شرکت کننده در رویداد «{$eventTitle}».');

    $languageService->addOrUpdateValue($langFaId, 'event', 'user_list_page_desc_2', 'لیست کاربران شرکت کننده احتمالی در رویداد «{$eventTitle}».');

    $languageService->addOrUpdateValue($langFaId, 'event', 'user_list_page_desc_3', 'لیست کاربرانی که در رویداد «{$eventTitle}» شرکت نمی‌کنند.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'user_list_page_heading_1', '«{$eventTitle}» شرکت کنندگان رویداد - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'user_list_page_heading_2', 'رویداد «{$eventTitle}» – افرادای که ممکن است شرکت کنند - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'user_list_page_heading_3', 'رویداد «{$eventTitle}» – افرادی که شرکت نمی‌کنند - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'user_participated_events_page_desc', 'لیست رویدادهایی که {$display_name} در آن شرکت می‌کند.');

    $languageService->addOrUpdateValue($langFaId, 'event', 'user_participated_events_page_heading', 'رویدادهای {$display_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'user_participated_events_page_title', 'رویدادهای {$display_name} - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'event', 'user_status_author_cant_leave_error', 'با عرض پوزش، شما نمی‌توانید رویداد خود را ترک کنید');

    $languageService->addOrUpdateValue($langFaId, 'event', 'view_page_end_date_label', 'رویداد پایان می‌یابد');

    $languageService->addOrUpdateValue($langFaId, 'event', 'add_form_date_label', 'تاریخ و زمان شروع');

    $languageService->addOrUpdateValue($langFaId, 'event', 'feed_activity_event_string_like_own', 'رویداد را می‌پسندد.');

    // end of issue 966

    $languageService->addOrUpdateValue($langFaId, 'event', 'event_sitemap', 'رویدادها');
    $languageService->addOrUpdateValue($langFaId, 'event', 'seo_meta_section', 'رویدادها');
    $languageService->addOrUpdateValue($langFaId, 'event', 'seo_meta_events_list_label', 'صفحه فهرست رویدادها');
    $languageService->addOrUpdateValue($langFaId, 'event', 'meta_title_events_list', '{$event_list} رویدادها | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'event', 'meta_desc_events_list', 'تمامی رویدادهای {$event_list} را در {$site_name} مشاهده کرده، نظرات خود را درج کرده و با دیگر کاربران گفت‌وگو کنید.');
    $languageService->addOrUpdateValue($langFaId, 'event', 'meta_keywords_events_list', '');
    $languageService->addOrUpdateValue($langFaId, 'event', 'created_events_page_title', 'ایجاد شده');
    $languageService->addOrUpdateValue($langFaId, 'event', 'joined_events_page_title', 'اضافه شده');
    $languageService->addOrUpdateValue($langFaId, 'event', 'seo_meta_event_view_label', 'صفحه رویداد جداگانه');
    $languageService->addOrUpdateValue($langFaId, 'event', 'meta_title_event_view', '{$event_title} | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'event', 'meta_desc_event_view', '{$event_description}');
    $languageService->addOrUpdateValue($langFaId, 'event', 'meta_keywords_event_view', '');
    $languageService->addOrUpdateValue($langFaId, 'event', 'seo_meta_event_users_label', 'صفحه شرکت‌کنندگان رویداد');
    $languageService->addOrUpdateValue($langFaId, 'event', 'meta_title_event_users', 'همه کاربران "{$event_title}" | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'event', 'meta_desc_event_users', 'فهرست تمامی کاربران {$site_name}، که در رویداد "{$event_title}" مشارکت می‌کنند.');
    $languageService->addOrUpdateValue($langFaId, 'event', 'meta_keywords_event_users', '');
    $languageService->addOrUpdateValue($langFaId, 'event', 'event_mobile', 'رویداد');
    $languageService->addOrUpdateValue($langFaId, 'event', 'back‌', 'بازگشت');
    $languageService->addOrUpdateValue($langFaId, 'event', 'user_status_not_changed_error', 'در حال حاضر این وضعیت انتخاب شده است');
    $languageService->addOrUpdateValue($langFaId, 'event', 'view_page_users_block_cap_label', 'مشاهده شرکت کنندگان بر اساس وضعیت شرکت کردن');
    $languageService->addOrUpdateValue($langFaId, 'event', 'users_invite_success_message', 'دعوت‌نامه به {$count} کاربر ارسال شد.');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'event', 'event_sitemap', 'Events');
    $languageService->addOrUpdateValue($langEnId, 'event', 'seo_meta_section', 'Events');
    $languageService->addOrUpdateValue($langEnId, 'event', 'seo_meta_events_list_label', 'Events List Page');
    $languageService->addOrUpdateValue($langEnId, 'event', 'meta_title_events_list', '{$event_list} events | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'event', 'meta_desc_events_list', 'Find out more information about all {$event_list} events at {$site_name}, leave your comments, and discuss them with other site members.');
    $languageService->addOrUpdateValue($langEnId, 'event', 'meta_keywords_events_list', '');
    $languageService->addOrUpdateValue($langEnId, 'event', 'created_events_page_title', 'Created');
    $languageService->addOrUpdateValue($langEnId, 'event', 'joined_events_page_title', 'Joined');
    $languageService->addOrUpdateValue($langEnId, 'event', 'seo_meta_event_view_label', 'Separate Event Page');
    $languageService->addOrUpdateValue($langEnId, 'event', 'meta_title_event_view', '{$event_title} | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'event', 'meta_desc_event_view', '{$event_description}');
    $languageService->addOrUpdateValue($langEnId, 'event', 'meta_keywords_event_view', '');
    $languageService->addOrUpdateValue($langEnId, 'event', 'seo_meta_event_users_label', 'Separate Event Participants Page');
    $languageService->addOrUpdateValue($langEnId, 'event', 'meta_title_event_users', 'All users of "{$event_title}" | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'event', 'meta_desc_event_users', 'The list of all {$site_name} users who singed up for the "{$event_title}". Join in to not miss all the fun!');
    $languageService->addOrUpdateValue($langEnId, 'event', 'meta_keywords_event_users', '');
    $languageService->addOrUpdateValue($langEnId, 'event', 'event_mobile', 'Event');
    $languageService->addOrUpdateValue($langEnId, 'event', 'back‌', 'Back');
    $languageService->addOrUpdateValue($langEnId, 'event', 'view_page_users_block_cap_label', 'List of participants by participation status');
}