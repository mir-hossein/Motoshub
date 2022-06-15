<?php

$languageService = Updater::getLanguageService();
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'blog_post_description', '{$post_body} برچسب: {$tags}');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'blog_post_description', '{$post_body} Tags: {$tags}');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'browse_by_tag_description', 'مرور نوشته‌های بلاگ‌ها بر اساس بر چسب‌ها : {$tags} و دیگران');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'browse_by_tag_description', 'Browse blog posts by tags: {$tags}and others');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'browse_by_tag_item_description', 'مرور برچسب‌های نوشته‌های بلاگ به عنوان {$tag}');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'browse_by_tag_item_description', 'Browse blog posts tagged as `{$tag}`');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'feed_activity_owner_post_string_like', 'نوشته بلاگش را پسندید');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'feed_activity_owner_post_string_like', 'liked their blog post');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'feed_add_item_label', 'یک نوشته جدید در بلاگ ایجاد کرد');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'feed_add_item_label', 'created a new blog post');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'manage_page_comment_deleted_msg', 'نظر حذف گردید');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'manage_page_comment_deleted_msg', 'Comment deleted');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'most_discussed_description', 'بیشترین نوشته‌های بحث شده بلاگ کاربر در {$site_name}');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'most_discussed_description', 'Most discussed user blog posts at {$site_name}');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'rate', 'رتبه:');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'results_by_tag', 'نتایج جستجو براساس برچسب: «<b>{$tag}</b>»');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'results_by_tag', 'Tag search results for: "<b>{$tag}</b>"');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'uninstall_inprogress_desc', 'لطفا تا وقتی که تصویرهای محتوا حذف شوند منتظر بمانید، این عمل ممکن است بنا به تعداد تصاویر موجود در بلاگ
            اندکی زمان‌بر باشد');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'uninstall_inprogress_desc', 'Please wait while all photo content is being removed. This make take awhile depending on the number of
            photos on your blogs');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'user_blog_archive_description', 'نوشته‌های بلاگ {$display_name} ارسال شده در {$month_name} ،{$year}');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'user_blog_archive_description', '{$display_name}\'s blog posts submitted in {$month_name}, {$year}');


$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'meta_desc_blogs_list', 'تمامی نوشته‌های بلاگ {$blog_list} را در {$site_name} بخوانید، نظرات خود را وارد کرده و در مورد موضوعات، با دیگر کاربران گفت‌وگو کنید');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'meta_desc_blogs_list', 'Read all {$blog_list} blog posts at {$site_name}, leave your own comments, and discuss the topics with
            other site members');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'meta_desc_user_blog', 'تمامی نوشته‌های کاربر {$user_name} را در {$site_name} بخوانید و نظرات خود را وارد کنید');
$languageService->addOrUpdateValueByLanguageTag('en', 'blogs', 'meta_desc_user_blog', 'Read all {$user_name}\'s posts at {$site_name}, and leave your own comments');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'authorization_failed_view_blog', 'با عرض پوزش، شما مجاز به مشاهده این بلاگ نیستید');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'blogs', 'auth_action_label_delete_comment_by_content_owner', 'صاحب مطلب می تواند نظرات را حذف کند');

