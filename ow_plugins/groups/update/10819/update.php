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
    $languageService->addOrUpdateValue($langFaId, 'groups', 'email_notification_comment_setting', 'کسی بر روی دیوار گروهی که در آن شرکت دارم نظر داده است');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'confirm_delete_groups', 'آیا از حذف همه گروه‌ها اطمینان دارید؟');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'delete_confirm_msg', 'آیا از حذف این گروه اطمینان دارید؟ این کار همه محتوای آن‌را نیز پاک می‌کند.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'delete_content', 'حذف محتوا و افزونه');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'delete_feed_item_label', 'حذف پست');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'delete_group_label', 'حذف');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'delete_group_user_confirmation', 'آیا از حذف کاربر از این گروه اطمینان دارید؟');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'delete_group_user_label', 'حذف کاربر');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'page_title_uninstall', 'حذف افزونه گروه‌ها');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'plugin_set_for_uninstall', 'حذف افزونه انجمن آغاز شد.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'invite_list_page_title', 'دعوت‌نامه‌های  گروه - {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'feed_create_string', 'گروه جدید ایجاد کرد');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'auth_action_label_delete_comment_by_content_owner', 'نویسنده می‌تواند نظرهای روی دیوار را حذف کند');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'delete_complete_msg', 'گروه حذف شد');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'delete_confirm_msg', 'آیا از حذف این گروه اطمینان دارید؟ این کار همه محتوای آن‌را نیز حذف می‌کند.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'delete_user_success_message', 'کاربر حذف شده است');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'delete_content_desc', 'قبل از حذف افزونه گروه‌ها باید همه گروه‌های جدید حذف شوند. این کار ممکن است اندکی زمان‌بر باشد. بنابر همین دلیل وب‌گاه به "حالت نگهداری" خواهد رفت و به‌محض کامل شدن عملیات حذف، فعال می‌شود.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'errors_image_upload', 'بارگذاری فایل ناموفق بود.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'feed_follow_complete_msg', 'شما اکنون {$groupTitle} را دنبال می‌کنید');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'group_owner_delete_error', 'شما نمی‌توانید مالک گروه را حذف کنید');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'post_reply_permission_error', 'برای ایجاد پست، نیاز است تا شما عضوی از گروه باشید.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'widget_groups_count_setting', 'تعداد');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'widget_users_settings_count', 'تعداد');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'groups_sitemap', 'گروه‌ها');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'groups_sitemap_desc', 'گروه‌ها و فهرست‌ها');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'seo_meta_section', 'گروه‌ها');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'seo_meta_most_popular_label', 'صفحه مشهورترین گروه‌ها');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_title_most_popular', 'صفحه مشهورترین گروه‌ها | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_desc_most_popular', 'فهرست مشهورترین گروه‌ها در{$site_name}. عضو شوید.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_keywords_most_popular', '');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'seo_meta_latest_label', 'صفحه آخرین گروه‌ها');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_title_latest', 'آخرین گروه‌ها | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_desc_latest', 'لیست آخرین گروه‌های ایجاد شده در {$site_name}. عضو شوید.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_keywords_latest', '');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'seo_meta_user_groups_label', 'صفحه گروه');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_title_user_groups', 'فرد {$user_name}،{$user_age} عضو گروه شد | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_desc_user_groups', 'تمام گروه‌های {$user_name}، {$user_age} در {$site_name}.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_keywords_user_groups', '');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'seo_meta_groups_page_label', 'صفحه گروه');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_title_groups_page', '{$group_title} | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_desc_groups_page', '{$group_description}');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_keywords_groups_page', '');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'seo_meta_group_users_label', 'صفحه شرکت‌گنندگان گروه');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_title_group_users', 'تمامی اعضای {$group_title} | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_desc_group_users', 'فهرست اعضای "{$group_title}" در {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_keywords_group_users', '');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'auth_action_label_wall_post', 'اجازه برای ارسال نوشته‌های دیوار گروه');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'delete_feed_item_label', 'حذف نوشته');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'post_reply_permission_error', 'برای ایجاد نوشته، نیاز است تا شما عضوی از گروه باشید.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'seo_meta_most_popular_label', 'صفحه محبوب‌ترین گروه‌ها');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'meta_title_most_popular', 'صفحه محبوب‌ترین گروه‌ها | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'mobile_main_menu_list', 'گروه');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'users_invite_success_message', 'دعوت‌نامه به {$count} کاربر ارسال شد.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'widget_brief_info_create_date', 'زمان ایجاد: {$date}');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'additional_features','قابلیت‌های بیشتر');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'invitation_join_string_1', '{$user1} شما را به گروه {$group} دعوت کرد.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'invitation_join_string_2', '{$user1} و {$user2} شما را به گروه {$group} دعوت کردند.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'invitation_join_string_many', '{$user1} و {$user2} و {$otherUsers} شما را به گروه {$group} دعوت کردند.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'listing_no_items_msg', 'موردی وجود ندارد.');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'forum_btn_label', 'انجمن');
    $languageService->addOrUpdateValue($langFaId, 'groups', 'group_title','عنوان گروه: {$title}');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'groups', 'groups_sitemap', 'Groups');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'groups_sitemap_desc', 'Groups and lists');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'seo_meta_section', 'Groups');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'seo_meta_most_popular_label', 'Most Popular Groups Page');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_title_most_popular', 'Most Popular Groups | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_desc_most_popular', 'The list of most popular groups at {$site_name}. Join us!');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_keywords_most_popular', '');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'seo_meta_latest_label', 'Latest Groups Page');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_title_latest', 'Latest Groups | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_desc_latest', 'The list of all recently created groups at {$site_name}. Join us!');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_keywords_latest', '');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'seo_meta_user_groups_label', 'Individual Member\'s Groups Page');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_title_user_groups', 'Groups joined by {$user_name}, {$user_age} | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_desc_user_groups', 'All groups joined by {$user_name}, {$user_age} on {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_keywords_user_groups', '');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'seo_meta_groups_page_label', 'Separate Group Page');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_title_groups_page', '{$group_title} | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_desc_groups_page', '{$group_description}');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_keywords_groups_page', '');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'seo_meta_group_users_label', 'Separate Group Participants Page');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_title_group_users', 'All members of {$group_title} | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_desc_group_users', 'The list of all members of the "{$group_title}" at {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'meta_keywords_group_users', '');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'mobile_main_menu_list', 'Group');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'listing_no_items_msg', 'No item available.');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'forum_btn_label', 'Forum');
    $languageService->addOrUpdateValue($langEnId, 'groups', 'group_title', 'Group Title: {$title}');
}