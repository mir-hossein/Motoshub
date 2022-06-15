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
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'added', 'ایجاد شده در');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'latest_title', 'بلاگ‌های کاربر - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'list_page_heading', 'بلاگ‌های کاربر');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'activity_string', '<a href="{$userUrl}">{$user}</a> نوشته جدید بلاگ ارسال کرد <a href="{$url}">{$title}</a>');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'authorization_failed_view_blog', 'با عرض پوزش، شما مجاز به مشاهده این بلاگ نیستید .');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'auth_action_label_add', 'افزودن نوشته‌های بلاگ');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'auth_action_label_add_comment', 'نظرات بلاگ');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'auth_action_label_view', 'مشاهده نوشته‌های بلاگ');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'auth_group_label', 'بلاگ‌ها');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'blog', 'بلاگ');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'blog_index', 'خانه بلاگ');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'browse_by_tag_description', 'مرور نوشته‌های بلاگ‌ها بر اساس بر چسب‌ها : {$tags} و دیگران.');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'browse_by_tag_item_description', 'مرور برچسب‌های نوشته‌های بلاگ به عنوان {$tag}.');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'browse_by_tag_item_title', '{$tag}نوشته‌های مرتبط بلاگ {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'browse_by_tag_title', 'مرور نوشته‌های بلاگ به وسیله برچسب‌ها {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'feed_add_item_label', 'ایجاد یک نوشته جدید در بلاگ');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'feed_edit_item_label', 'ویرایش نوشته بلاگ خود');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'go_to_blog', 'برو به بلاگ‌ها');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'meta_title_new_blog_post', 'نوشته جدید بلاگ - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'most_discussed_description', 'بیشترین نوشته‌های بحث شده بلاگ کاربر در {$site_name}.');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'most_discussed_title', 'بیشترین بلاگ‌های بحث شده  - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'new_post', 'نوشته جدید بلاگ');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'save_page_heading', 'ایجاد نوشته جدید بلاگ');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'top_rated_title', 'برترین بلاگ‌ها - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'user_blog_page_heading', '{$name} بلاگ');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'user_blog_title', '{$display_name} بلاگ - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'admin_blogs_settings_heading', 'تنظیمات افزونه بلاگ‌ها');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'admin_settings_results_per_page', 'نوشته‌های این صفحه');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'authorization_failed_view_blog', 'با عرض پوزش، شما مجاز به مشاهده این بلاگ نیستید .');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'auth_action_label_delete_comment_by_content_owner', 'صاحب مطلب می تواند نظرات را پاک کند');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'blog_archive_lbl_archives', 'بایگانی');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'blog_post_title', '{$post_title} نوشته شده توسط: {$display_name} در {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'blog_widget_preview_length_lbl', 'طول پیش نمایش');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'browse_by_tag_item_description', 'مرور برچسب‌های نوشته‌های بلاگ به عنوان {$tag}.');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'browse_by_tag_item_title', '{$tag}نوشته‌های مرتبط بلاگ {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'browse_by_tag_title', 'مرور نوشته‌های بلاگ به وسیله برچسب‌ها {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'by', 'توسط');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'cmp_widget_post_count', 'تعداد نوشته‌ها برای نمایش');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'latest_post', 'آخرین نوشته‌ها');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'manage_page_last_updated', 'آخرین به‌روزرسانی');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'manage_page_menu_drafts', 'پیش نویس ها');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'manage_page_menu_published', 'پست‌های منتشر شده');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'manage_page_status', 'وضعیت');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'menuItemMostDiscussed', 'بیشترین بحث شده‌ها');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'more', 'بیشتر');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'most_discussed_description', 'بیشترین نوشته‌های بحث شده بلاگ کاربر در {$site_name}.');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'most_discussed_title', 'بیشترین بلاگ‌های بحث شده  - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'next_post', 'نوشته بعدی');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'on', 'روی');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'sava_draft', 'ذخیره به عنوان پیش نویس');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'save_btn_label', 'ذخیره');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'settings', 'تنظیمات');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'comments', 'تعداد نظرات:');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'browse_by_tag_item_title', 'نوشته‌های مرتبط بلاگ با برچسب «{$tag}» - {$site_name}');

    $languageService->addOrUpdateValue($langFaId, 'blogs', 'blogs_sitemap', 'بلاگ‌ها');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'blogs_sitemap_desc', 'بلاگ‌ها و فهرست‌ها');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'seo_meta_section', 'بلاگ‌ها');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'seo_meta_blogs_list_label', 'صفحه فهرست بلاگ‌ها');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'meta_title_blogs_list', '{$blog_list} بلاگ‌ها | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'meta_desc_blogs_list', 'تمامی نوشته‌های بلاگ {$blog_list} را در {$site_name} بخوانید، نظرات خود را وارد کرده و در مورد موضوعات، با دیگر کاربران گفت‌وگو کنید.');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'meta_keywords_blogs_list', '');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'seo_meta_user_blog_label', 'صفحه بلاگ فرد');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'meta_title_user_blog', 'بلاگ نوشته شده توسط {$user_name}, {$user_age} | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'meta_desc_user_blog', 'تمامی نوشته‌های کاربر {$user_name} را در {$site_name} بخوانید و نظرات خود را وارد کنید.');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'meta_keywords_user_blog', '');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'seo_meta_blog_post_label', 'صفحه نوشته‌های بلاگ فرد');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'meta_title_blog_post', 'بلاگ {$post_subject} | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'meta_desc_blog_post', '{$post_body}');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'meta_keywords_blog_post', '');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'comment_notification_string', '<a href="{$actorUrl}">{$actor}</a> بر روی نوشته شما نظر گذاشته: <a href="{$url}">"{$title}"</a>');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'results_by_tag', 'نتایج جستجو براساس برچسب: "<b>{$tag} </b>  "');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'blog_post_description', '{$post_body} برچسب : {$tags}.');
    $languageService->addOrUpdateValue($langFaId, 'blogs', 'feed_add_item_label','یک نوشته جدید در بلاگ ایجاد کرد');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'blogs_sitemap', 'Blogs');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'blogs_sitemap_desc', 'Blogs and lists');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'seo_meta_section', 'Blogs');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'seo_meta_blogs_list_label', 'Blogs List Page');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'meta_title_blogs_list', '{$blog_list} blogs | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'meta_desc_blogs_list', 'Read all {$blog_list} blog posts at {$site_name}, leave your own comments, and discuss the topics with other site members.');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'meta_keywords_blogs_list', '');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'seo_meta_user_blog_label', 'Individual Member Blog Page');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'meta_title_user_blog', 'Blog by {$user_name}, {$user_age} | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'meta_desc_user_blog', 'Read all {$user_name}\'s posts at {$site_name}, and leave your own comments.');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'meta_keywords_user_blog', '');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'seo_meta_blog_post_label', 'Individual Member Blog Post Page');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'meta_title_blog_post', '{$post_subject} | {$site_name} Blog');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'meta_desc_blog_post', '{$post_body}');
    $languageService->addOrUpdateValue($langEnId, 'blogs', 'meta_keywords_blog_post', '');
}