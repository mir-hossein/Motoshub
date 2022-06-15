<?php

$languageService = Updater::getLanguageService();
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'activity_add_string', '<a href="{$userUrl}">{$user}</a> گروه جدید <a href="{$groupUrl}">{$groupTitle}</a> ساخت');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'activity_join_string', '<a href="{$userUrl}">{$user}</a> عضو گروه<a href="{$groupUrl}">{$groupTitle}</a> شد');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'create_page_description', 'ایجاد یک گروه جدید برای هر عنوان موضوع در {$site_name}');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'create_page_description', 'Create a new group for any topic within {$site_name}');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'delete_confirm_msg', 'آیا از حذف این گروه اطمینان دارید؟ این کار همه محتوای آن‌را نیز حذف می‌کند');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'delete_confirm_msg', 'Are you sure you want to delete this group? This will remove all its content, too');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'delete_content_desc', 'قبل از حذف افزونه گروه‌ها باید همه گروه‌های جدید حذف شوند. این کار ممکن است اندکی زمان‌بر باشد. بنابر همین دلیل وب‌گاه به "حالت نگهداری" خواهد رفت و به‌محض کامل شدن عملیات حذف، فعال می‌شود');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'delete_content_desc', 'Before uninstalling groups plugin we have to remove all existing groups. This may take awhile. That is why
            we will put the site to "maintenance mode" and activate it back upon completing the uninstall');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'email_notification_comment', '&lt;a href="{$userUrl}"&gt;{$userName}&lt;/a&gt; یک نظر در گروه &lt;a href="{$url}"&gt;{$title}&lt;/a&gt;
            اضافه کرد');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'email_notification_comment', ' &lt;a href="{$userUrl}"&gt;{$userName}&lt;/a&gt; added a comment in &lt;a href="{$url}"&gt;{$title}&lt;/a&gt;
            group');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'email_notification_comment_setting', 'کسی بر روی دیوار گروهی که در آن شرکت دارم نظر داده است');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'email_notification_comment_setting', 'Someone posts a comment in a group I participate in');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'errors_image_invalid', 'نوع تصویر نامعتبر است. فقط jpg/jpeg/png/gif مجازند');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'errors_image_invalid', 'Invalid image type. Only jpg/jpeg/png/gif allowed');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'errors_image_upload', 'بارگذاری فایل ناموفق بود');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'error_forum_disconnected', 'توجه: برای گروه‌ها انجمن نیاز است. برای فعال‌سازی آن به <a href="{$url}">لیست افزونه</a>بروید');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'feed_create_string', 'گروه جدید ایجاد کرد');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'feed_create_string', 'created a new group');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'feed_follow_complete_msg', 'شما اکنون این گروه را دنبال می‌کنید');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'feed_follow_complete_msg', 'You are now following this group');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'feed_unfollow_complete_msg', 'شما دیگر این گروه را دنبال نمی‌کنید');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'feed_unfollow_complete_msg', 'You are no longer following this group');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'group_edited_string', 'گروه‌ ویرایش شد');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'invitation_join_string_1', '{$user1} شما را به گروه {$group} دعوت کرد');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'invitation_join_string_1', '{$user1} invites you to {$group} group');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'invitation_join_string_2', '{$user1} و {$user2} شما را به گروه {$group} دعوت کردند');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'invitation_join_string_many', '{$user1} و {$user2} و {$otherUsers} شما را به گروه {$group} دعوت کردند');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'latest_list_page_description', 'گروه‌های کاربری که اخیرا در {$site_name} ایجاد شده است');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'latest_list_page_description', 'Most recently created user groups at {$site_name}');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'leave_complete_message', 'شما از این گروه خارج شده‌اید');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'listing_no_items_msg', 'موردی وجود ندارد');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'listing_no_items_msg', 'No item available');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'notifications_new_message', 'حذف افزونه گروهها آغاز شد');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'plugin_set_for_uninstall', 'حذف افزونه  گروه‌ها آغاز شد');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'plugin_set_for_uninstall', 'Groups plugin uninstall initiated');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'popular_list_page_description', 'محبوب‌ترین گروه‌های کاربر ساخته شده در {$site_name}');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'popular_list_page_description', 'Most popular created user groups at {$site_name}');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'post_reply_permission_error', 'برای درج پاسخ، شما باید عضو گروه باشید');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'post_reply_permission_error', 'You need to be a group member to post');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'private_group_text', 'با عرض پوزش، این گروه خصوصی است');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'private_group_text', 'We are sorry. This group is private');


$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'uninstall_inprogress_desc', 'لطفا منتظر بمانید، همه محتوای انجمن در حال حذف شدن است. این کار بسته به تعداد گروه‌ها بر تارنمای شما، اندکی
            زمان‌بر است');
$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'uninstall_inprogress_desc', 'Please wait while all forum content is being removed. This make take awhile depending on the number of
            groups on your site');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'users_invite_success_message', 'دعوت‌نامه به {$count} کاربر ارسال شد');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'user_groups_page_description', 'کاربران گروه در {$site_name}');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'user_groups_page_description', 'User groups at {$site_name}');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'meta_desc_most_popular', 'فهرست مشهورترین گروه‌ها در{$site_name}. عضو شوید!');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'meta_desc_latest', 'لیست آخرین گروه‌های ایجاد شده در {$site_name}. عضو شوید!');

$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'meta_desc_user_groups', 'All groups joined by {$user_name}, {$user_age} on {$site_name}');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'groups', 'meta_desc_user_groups', 'تمام گروه‌های {$user_name}، {$user_age} در {$site_name}}');

$languageService->addOrUpdateValueByLanguageTag('en', 'groups', 'meta_desc_group_users', 'The list of all members of the "{$group_title}" at {$site_name}');
