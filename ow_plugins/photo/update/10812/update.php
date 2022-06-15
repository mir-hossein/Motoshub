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
    $languageService->addOrUpdateValue($langFaId, 'photo', 'of', 'از');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'feed_multiple_descriptions', '<span dir="auto" > {$number} تصویر جدید به آلبوم <a href="{$albumUrl}">{$albumName}</a> بارگذاری کرد. </span>');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'delete_content_desc', 'قبل از حذف افزونه تصویر، باید تمام تصاویر کاربران را پاک کنیم . این کار اندکی زمان‌بر است. در این زمان سایت را به حالت تعمیرات و نگهداری می‌بریم.');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'delete_selected', 'حذف انتخاب شده‌ها');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'plugin_set_for_uninstall', 'حذف افزونه تصویر راه‌اندازی شد.');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'confirm_delete', 'آیا از حذف این تصویر اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'confirm_delete_album', 'آیا از حذف این آلبوم تصویر اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'confirm_delete_photos', 'آیا از حذف تمام تصاویر کاربران اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'delete_album', 'حذف آلبوم');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'delete_content', 'حذف مطالب و افزونه');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'delete_fullsize_confirm', 'آیا از حذف تصاویر با اندازه کامل اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'delete_photo', 'حذف');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'remove_from_featured', 'حذف از حالت ویژه');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'album_delete_not_allowed', 'شما مجاز به حذف آلبوم‌های تصویر نیستید');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'album_updated', 'به‌روزرسانی');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'auth_action_label_add_comment', 'نظر تصویر');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'btn_edit', 'ذخیره');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'cmp_widget_photo_albums_count', 'تعداد آلبوم‌های تصاویر');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'cmp_widget_photo_albums_show_titles', 'نمایش عنوان‌های آلبوم تصویر');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'cmp_widget_photo_count', 'تعداد تصاویر برای نمایش');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'tb_edit_photo', 'ویرایش تصویر');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'upload_ini_value', '(محدودیت سرور : {$value})');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'view_fullsize', 'مشاهده سایز کامل');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'auth_action_label_view', 'مشاهده تصویر');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'admin_menu_view', 'تنظیمات نمایش');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'accepted_filesize_desc', 'بیشینه اندازه قابل قبول برای بارگذاری تصویر. کمینه مقدار قابل قبول <b><i>0.5</i></b> مگابایت.');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'admin_config', 'تنظیمات افزونه تصویر');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'rating_total', '(تعداد امتیاز دهندگان: {$count})');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'rating_your', '(تعداد امتیاز دهندگان: {$count} / امتیاز شما: {$score})');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'advanced_upload_desc', 'فعال‌سازی بارگذار پیشرفته نرم‌افزار فلش تا به کاربران اجازه داده شود تعداد اسناد و امکاناتی مانند تغییر اندازه، و چرخاندن تصاویر را قبل از بارگذاری انتخاب کنند.');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'album_deleted', 'آلبوم حذف شد');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'auth_action_label_delete_comment_by_content_owner', 'مالک محتوا می‌تواند نظرات را حذف کند');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'choose_existing_or_create', 'انتخاب آلبوم موجود یا ایجاد آلبوم جدید');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'delete_fullsize_photos', 'تصاویر اندازه کامل حذف شد ({$count})');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'dnd_support', 'تصاویر را بکشید و در این‌جا رها کنید یا برای مرور آن‌ها کلیک کنید');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'fullsize_resolution_desc', 'تمام تصاویر با اندازه‌های بزرگ‌تر از (X  یا Y) با این محدودیت‌های اندازه برای ذخیره تغییر اندازه پیدا خواهند کرد.');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'photos_deleted', 'تصاویر حذف شدند');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'photo_not_deleted', 'تصویر حذف نشده است');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'photo_uploaded_pending_approval', 'تصاویر در مدت کوتاهی در دسترس خواهد بود، در انتظار تایید.');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'plugin_set_for_uninstall', 'حذف افزونه تصویر آغاز شد.');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'view_fullsize', 'مشاهده اندازه کامل');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'user_quota_desc', 'این عدد باید به‌اندازه معقولی بزرگ نگه‌داشته شود.');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'photo_list', 'فهرست تصاویر');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'choose_type_of_photo_list', 'انتخاب نوع فهرست تصاویر');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'album_list', 'فهرست آلبوم');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'choose_type_of_album_list', 'انتخاب نوع فهرست آلبوم');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'photo_view', 'نمایش تصویر');

    $languageService->addOrUpdateValue($langFaId, 'photo', 'choose_type_of_photo_view', 'انتخاب نوع نمایش تصاویر');


    $languageService->addOrUpdateValue($langFaId, 'photo', 'photo_sitemap', 'تصاویر');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'seo_meta_section', 'تصاویر');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'seo_meta_tagged_list_label', 'صفحه تمامی تصاویر برچسب‌زده‌شده');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_title_tagged_list', '{$tag} photos | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_desc_tagged_list', 'فهرست تمام تصاویر برچسب زده شده "{$tag}" در {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_keywords_tagged_list', '');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'seo_meta_photo_list_label', 'صفحه انواع تصاویر  (آخرین‌ها، بیشترین امتیازدهی شده‌ها، بیشترین بحث‌شده‌ها)');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_title_photo_list', 'تصاویر {$list_type} | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_desc_photo_list', 'تمامی تصاویر {$list_type} در {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_keywords_photo_list', '');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'list_type_label_featured', 'ویژه');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'list_type_label_latest', 'آخرین‌ها');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'list_type_label_toprated', 'بیشترین امتیازدهی شده');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'list_type_label_tagged', 'برچسب‌زده‌شده');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'list_type_label_most_discussed', 'بیشتر بحث‌شده‌ها');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'seo_meta_user_albums_label', 'صفحه آلبوم‌های تصاویر فرد');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_title_user_albums', 'آلبوم تصاویر {$user_name}،{$user_age} | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_desc_user_albums', 'مشاهده تمامی تصاویر آلبوم که توسط {$user_name}،{$user_age} در {$site_name} بارگذاری شده است.');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_keywords_user_albums', '');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'seo_meta_user_album_label', 'صفحه آلبوم تصاویر فرد');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_title_user_album', 'مشاهده تمامی تصاویر در "{$album_name}" که توسط {$user_name}،{$user_age} در {$site_name} بارگذاری شده است.');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_desc_user_album', 'مشاهده تمامی تصاویر در "{$album_name}" که توسط {$user_name}،{$user_age} در {$site_name} بارگذاری شده است.');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_keywords_user_album', '');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'seo_meta_user_photos_label', 'صفحه آلبوم فرد');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_title_user_photos', 'تمامی تصاویر بارگذاری شده توسط {$user_name}،{$user_age} در {$site_name}.');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_desc_user_photos', 'مشاهده تمامی تصاویر بارگذاری شده توسط {$user_name}،{$user_age} در {$site_name}.');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_keywords_user_photos', '');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'seo_meta_photo_view_label', 'صفحه تصویر');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_title_photo_view', 'مشاهده تصویر "{$photo_id}" که توسط {$user_name} در {$site_name} بارگذاری شده است.');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_desc_photo_view', 'مشاهده تصویر "{$photo_id}" که توسط {$user_name} در {$site_name} بارگذاری شده است.');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_keywords_photo_view', '');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'search_invitation', 'توضیح تصویر، @نام ایجادکننده تصویر یا #برچسب تصویر');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'meta_title_photo_photo_friends', 'تصاویر مخاطبان - {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'mobile_back', 'بازگشت');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'page_title_user_albums', '{$user} آلبوم تصاویر');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'privacy_action_view_album_desc', 'با تغییر این فیلد، حریم خصوصی تمامی آلبوم‌های ساخته شده نیز تغییر خواهند کرد');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'photos_in_album', 'تعداد تصویر: {$total}');
    $languageService->addOrUpdateValue($langFaId, 'photo', 'feed_single_description', '<span dir="auto"> {$number} تصویر جدید به آلبوم <a href="{$albumUrl}">{$albumName}</a>            بارگذاری کرد. </span>');
}

if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'photo', 'accepted_filesize_desc', 'Maximum acceptable size for uploading a photo. Minimum acceptable size is <b><i>0.5</i></b>  Mb.');

    $languageService->addOrUpdateValue($langEnId, 'photo', 'photo_list', 'Photo list');

    $languageService->addOrUpdateValue($langEnId, 'photo', 'choose_type_of_photo_list', 'Choose type of photo list');

    $languageService->addOrUpdateValue($langEnId, 'photo', 'album_list', 'Album list');

    $languageService->addOrUpdateValue($langEnId, 'photo', 'choose_type_of_album_list', 'Choose type of album cover');

    $languageService->addOrUpdateValue($langEnId, 'photo', 'photo_view', 'Photo view');

    $languageService->addOrUpdateValue($langEnId, 'photo', 'choose_type_of_photo_view', 'Choose type of photo view');

    $languageService->addOrUpdateValue($langEnId, 'photo', 'photo_sitemap', 'Photos');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'seo_meta_section', 'Photos');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'seo_meta_tagged_list_label', 'All Tagged Photos Page');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_title_tagged_list', '{$tag} photos | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_desc_tagged_list', 'The list of all photos tagged "{$tag}" at {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_keywords_tagged_list', '');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'seo_meta_photo_list_label', 'Photos by List Type (Latest / Top Rated / Most Discussed) Page');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_title_photo_list', '{$list_type} photos | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_desc_photo_list', 'All {$list_type} photos at {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_keywords_photo_list', '');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'list_type_label_featured', 'Featured');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'list_type_label_latest', 'Latest');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'list_type_label_toprated', 'Top rated');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'list_type_label_tagged', 'Tagged');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'list_type_label_most_discussed', 'Most discussed');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'seo_meta_user_albums_label', 'Individual Member\'s Photo Albums Page');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_title_user_albums', 'Photo albums by {$user_name}, {$user_age} | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_desc_user_albums', 'View all photo albums uploaded by {$user_name}, {$user_age} on {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_keywords_user_albums', '');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'seo_meta_user_album_label', 'Individual Member\'s Specific Photo Album Page');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_title_user_album', '"{$album_name}" photo album by {$user_name}, {$user_age} | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_desc_user_album', 'View all photos in "{$album_name}" uploaded by {$user_name}, {$user_age} on {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_keywords_user_album', '');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'seo_meta_user_photos_label', 'Individual Member\'s Specific Photo Album Page');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_title_user_photos', 'All photos by {$user_name}, {$user_age} | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_desc_user_photos', 'View all photos uploaded by {$user_name}, {$user_age} on {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_keywords_user_photos', '');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'seo_meta_photo_view_label', 'Specific Photo Page');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_title_photo_view', 'View "{$photo_id}" photo by {$user_name} | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_desc_photo_view', 'View photo "{$photo_id}" uploaded by {$user_name} on {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_keywords_photo_view', '');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'list_type_label_photo_friends', 'Friends');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'meta_title_photo_photo_friends', 'Photos of Friends – {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'mobile_back', 'Back');
    $languageService->addOrUpdateValue($langEnId, 'photo', 'privacy_action_view_album_desc', 'By changing this field, the privacy of all built albums will be changed as well');
}