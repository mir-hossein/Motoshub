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
    $languageService->addOrUpdateValue($langFaId, 'video', 'video_add_tip', 'ما اجازه داریم تا <b> ویدیوهای موجود </b>  را فقط از دیگر سایت‌های به اشتراک گذاری فیلم دیگر به اشتراک بگذاریم.');

    $languageService->addOrUpdateValue($langFaId, 'video', 'added', 'ایجاد شده در');

    $languageService->addOrUpdateValue($langFaId, 'video', 'confirm_delete', 'یا از حذف این ویدیو اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'video', 'remove_from_featured', 'حذف از حالت ویژه');

    $languageService->addOrUpdateValue($langFaId, 'video', 'tag_search', 'جستجوی برچسب');

    $languageService->addOrUpdateValue($langFaId, 'video', 'tag_search_result', 'نتایج جستجوی برچسب برای:');



    $languageService->addOrUpdateValue($langFaId, 'video', 'admin_config', 'تنظیمات افزونه ویدیو');

    $languageService->addOrUpdateValue($langFaId, 'video', 'admin_menu_general', 'عمومی');

    $languageService->addOrUpdateValue($langFaId, 'video', 'auth_action_label_delete_comment_by_content_owner', 'نویسنده مطلب می‌تواند نظرات را پاک کند.');

    $languageService->addOrUpdateValue($langFaId, 'video', 'auth_action_label_view', 'مشاهده ویدیو');

    $languageService->addOrUpdateValue($langFaId, 'video', 'auth_group_label', 'ویدیو');

    $languageService->addOrUpdateValue($langFaId, 'video', 'auth_view_permissions', 'شما مجاز به مشاهده ویدیو نیستید');

    $languageService->addOrUpdateValue($langFaId, 'video', 'description_desc', 'شرح کوتاه از این ویدیو');

    $languageService->addOrUpdateValue($langFaId, 'video', 'feed_activity_video_string', 'بر روی ویدیو {$user} نظر داد');

    $languageService->addOrUpdateValue($langFaId, 'video', 'flags', 'ویدیو');

    $languageService->addOrUpdateValue($langFaId, 'video', 'mark_featured', 'نشانه گذاری به عنوان ویژه');

    $languageService->addOrUpdateValue($langFaId, 'video', 'menu_featured', 'ویژه‌ها');

    $languageService->addOrUpdateValue($langFaId, 'video', 'menu_latest', 'آخرین‌های عمومی');

    $languageService->addOrUpdateValue($langFaId, 'video', 'menu_tagged', 'مرور به‌وسیله برچسب');

    $languageService->addOrUpdateValue($langFaId, 'video', 'menu_toprated', 'بیشترین امتیاز');

    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_description_user_video', 'ویدیوهای ارسال شده توسط کاربر {$displayName}');

    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_description_video_featured', 'بهترین ویدیوها در {$site_name} انتخاب کارکنان!');

    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_description_video_latest', 'ویدیوهای ارسال شده اخیر در {$site_name}.');

    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_description_video_tagged', 'فیلم‌ها توسط کلمات کلیدی : {$topTags}, و غیره.');

    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_description_video_tagged_as', 'مرور فیلم‌های برچسب گذاشته شده به عنوان {$tag}.');

    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_description_video_toprated', 'ویدیوهای ارسال شده توسط کاربران در{$site_name} با بالاترین امتیازدهی.');

    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_description_video_view', 'ویدیو نام‌گذاری شده «{$title}»، منطبق با برچسب {$tags}.');

    $languageService->addOrUpdateValue($langFaId, 'video', 'no_video_found', 'موردی موجود نیست');
    $languageService->addOrUpdateValue($langFaId, 'video', 'admin_page_heading', 'تنظیمات افزونه ویدیو');
    $languageService->addOrUpdateValue($langFaId, 'video', 'admin_page_title', 'تنظیمات افزونه ویدیو');
    $languageService->addOrUpdateValue($langFaId, 'video', 'video_add_tip', 'شما فقط مجاز به بارگذاری ویدیو از طریق وب‌گاه‌های اشتراک‌گذاری ویدیو هستید.');
    $languageService->addOrUpdateValue($langFaId, 'video', 'code_desc', 'از کد نمایش وب‌گاه‌های منبع فیلم مانند آپارات، یوتیوب، گوگل ویدیو و غیره استفاده کنید.');

    $languageService->addOrUpdateValue($langFaId, 'video', 'auth_action_label_add_comment', 'نظردادن برروی ویدیو');

    $languageService->addOrUpdateValue($langFaId, 'video', 'auth_add_permissions', 'شما به دلیل محدودیت‌های دسترسی، مجاز به افزودن ویدیو نیستید');

    $languageService->addOrUpdateValue($langFaId, 'video', 'clips_by', 'ویدیو توسط');

    $languageService->addOrUpdateValue($langFaId, 'video', 'clip_not_deleted', 'ویدیو حذف نشده');

    $languageService->addOrUpdateValue($langFaId, 'video', 'cmp_widget_video_count', 'تعداد ویدیوها برای نمایش');

    $languageService->addOrUpdateValue($langFaId, 'video', 'quota_desc', 'این عدد به یک اندازه منطقی بزرگ نگه‌داشته شود');

    $languageService->addOrUpdateValue($langFaId, 'video', 'video_sitemap', 'ویدیو');
    $languageService->addOrUpdateValue($langFaId, 'video', 'seo_meta_section', 'ویدیو');
    $languageService->addOrUpdateValue($langFaId, 'video', 'seo_meta_tagged_list_label', 'صفحه تمامی ویدیوها');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_title_tagged_list', 'برچسب ویدیو | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_desc_tagged_list', 'مشاهده تمامی ویدیوها در {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_keywords_tagged_list', '');
    $languageService->addOrUpdateValue($langFaId, 'video', 'seo_meta_view_list_label', 'صفحه ویدیوها بر اساس نوع (آخرین‌ها، بیشترین امتیازها، بیشترین بحث‌شده‌ها)');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_title_view_list', 'ویدیوهای {$video_list} | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_desc_view_list', 'مشاهده تمامی ویدیوهای {$video_list} در {$site_name}.');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_keywords_view_list', '');
    $languageService->addOrUpdateValue($langFaId, 'video', 'featured_list_label', 'ویژه');
    $languageService->addOrUpdateValue($langFaId, 'video', 'latest_list_label', 'آخرین‌ها');
    $languageService->addOrUpdateValue($langFaId, 'video', 'toprated_list_label', 'بیشترین امتیازدهی‌شده‌ها');
    $languageService->addOrUpdateValue($langFaId, 'video', 'tagged_list_label', 'برچسب زده شده');
    $languageService->addOrUpdateValue($langFaId, 'video', 'seo_meta_view_clip_label', 'مشاهده صفحه ویدیو');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_title_view_clip', 'ویدیو "{$video_title}"  توسط {$user_name} | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_desc_view_clip', 'مشاهده ویدیو "{$video_title}"  در {$site_name} توسط {$user_name}');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_keywords_view_clip', '');
    $languageService->addOrUpdateValue($langFaId, 'video', 'seo_meta_tag_list_label', 'صفحه ویدیوهای دارای برچسب');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_title_tag_list', 'ویدیوهای دارای برچسب "{$video_tag_name}" | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_desc_tag_list', 'مشاهده تمامی ویدیوهای دارای برچسب "{$video_tag_name}" در {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_keywords_tag_list', '');
    $languageService->addOrUpdateValue($langFaId, 'video', 'seo_meta_user_video_list_label', 'صفحه ویدیو فرد');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_title_user_video_list', 'ویدیوهای ایجاد شده توسط {$user_name}، {$user_age} | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_desc_user_video_list', 'مشاهده ویدیوهای بارگذاری شده توسط {$user_name}، {$user_age} در {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'video', 'meta_keywords_user_video_list', '');
    $languageService->addOrUpdateValue($langFaId, 'video', 'video_mobile', 'ویدیو');
    $languageService->addOrUpdateValue($langFaId, 'video', 'latest_myvideo_list_label', 'ویدیوهای من - {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'video', 'other_video', 'سایر ویدیوهای کاربر');
    $languageService->addOrUpdateValue($langFaId, 'video', 'privacy_action_view_video_desc', 'با تغییر این فیلد، حریم خصوصی تمامی ویدیوهای ساخته شده نیز تغییر خواهند کرد');
    $languageService->addOrUpdateValue($langFaId, 'video', 'menu_latest', 'آخرین‌ها');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'video', 'menu_latest', 'Latest Public');
    $languageService->addOrUpdateValue($langEnId, 'video', 'admin_page_heading', 'Video plugin settings');
    $languageService->addOrUpdateValue($langEnId, 'video', 'admin_page_title', 'Video plugin settings');
    $languageService->addOrUpdateValue($langEnId, 'video', 'video_sitemap', 'Video');
    $languageService->addOrUpdateValue($langEnId, 'video', 'seo_meta_section', 'Video');
    $languageService->addOrUpdateValue($langEnId, 'video', 'seo_meta_tagged_list_label', 'All Tagged Videos Page');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_title_tagged_list', 'Tagged Video | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_desc_tagged_list', 'Watch all tagged videos at {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_keywords_tagged_list', '');
    $languageService->addOrUpdateValue($langEnId, 'video', 'seo_meta_view_list_label', 'Videos by List Type (Latest / Top Rated / Most Discussed) Page');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_title_view_list', '{$video_list} videos | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_desc_view_list', 'Watch all {$video_list} videos at {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_keywords_view_list', '');
    $languageService->addOrUpdateValue($langEnId, 'video', 'featured_list_label', 'Featured');
    $languageService->addOrUpdateValue($langEnId, 'video', 'latest_list_label', 'Latest');
    $languageService->addOrUpdateValue($langEnId, 'video', 'toprated_list_label', 'Top rated');
    $languageService->addOrUpdateValue($langEnId, 'video', 'tagged_list_label', 'Tagged');
    $languageService->addOrUpdateValue($langEnId, 'video', 'seo_meta_view_clip_label', 'View Specific Video Page');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_title_view_clip', '"{$video_title}" video by {$user_name} | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_desc_view_clip', 'Watch "{$video_title}" video on {$site_name} uploaded by {$user_name}.');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_keywords_view_clip', '');
    $languageService->addOrUpdateValue($langEnId, 'video', 'seo_meta_tag_list_label', 'Videos Tagged by Specific Tag Page');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_title_tag_list', '"{$video_tag_name}" tagged videos | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_desc_tag_list', 'Watch all videos tagged "{$video_tag_name}" at {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_keywords_tag_list', '');
    $languageService->addOrUpdateValue($langEnId, 'video', 'seo_meta_user_video_list_label', 'Individual Member\'s Videos Page');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_title_user_video_list', 'Videos by {$user_name}, {$user_age} | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_desc_user_video_list', 'Watch all videos uploaded by {$user_name}, {$user_age} on {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'video', 'meta_keywords_user_video_list', '');
    $languageService->addOrUpdateValue($langEnId, 'video', 'latest_friends_list_label', 'Latest friends');
    $languageService->addOrUpdateValue($langEnId, 'video', 'video_mobile', 'Video');
    $languageService->addOrUpdateValue($langEnId, 'video', 'latest_myvideo_list_label', 'My Videos – {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'video', 'privacy_action_view_video_desc', 'By changing this field, the privacy of all built videos will be changed as well');
}