<?php

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'attachment_deleted', 'سند حذف گردیده است');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'chars_limit_exceeded', 'این متن طولانی است، لطفا تا اندازه {$limit} نشانه کمی فشرده‌سازی صورت گیرد');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'chars_limit_exceeded', 'Ouch, that\'s a long text! Try to be a little more compact, under {$limit} symbols');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'email_notification_post', '<a href="{$userUrl}">{$userName}</a> به یک موضوع در انجمن <a href="{$postUrl}">{$title}</a> پاسخ داد');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum','email_notification_post', '<a href="{$userUrl}">{$userName}</a> replied in <a href="{$postUrl}">{$title}</a> forum topic');


$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'feed_activity_topic_reply_string', 'در یک موضوع انجمن، پاسخی ارائه داد');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'feed_activity_topic_string', 'یک موضوع در انجمن ایجاد کرد');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'forum_already_connected', 'انجمن در حال حاضر وصل است');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'meta_description_add_topic', 'افزودن یک موضوع جدید در انجمن‌ها');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'meta_description_add_topic', 'Add a new topic at the forums');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'moved_to', '<a href="{$topicUrl}">منتقل شد</a>');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'moved_to', 'Topic was <a href="{$topicUrl}">moved</a>');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'no_topic', 'در حال حاضر موضوعی وجود ندارد');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'plugin_set_for_uninstall', 'حذف افزونه انجمن آغاز شد');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'plugin_set_for_uninstall', 'Forum plugin uninstall initiated');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'subscription-added', 'اشتراک موضوع افزوده شد');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'subscription-canceled', 'اشتراک موضوع لغو شد');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'uninstall_inprogress_desc', 'لطفا منتظر بمانید تا تمام محتوای انجمن حذف شود، این کار ممکن است با توجه به تعداد نوشته‌ها اندکی زمان‌بر
            باشد');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'uninstall_inprogress_desc', 'Please wait while all forum content is being removed, This make take awhile depending on the number of
            forum posts on your site');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'meta_desc_home', 'به انجمن {$site_name} خوش آمدید. موضوع جدید ایجاد کنید، نوشته‌های دیگران را مطالعه کنید و با دیگر کاربران گفت‌وگو کنید');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'meta_desc_home', 'Welcome to the discussion forum at {$site_name}. Create new posts, read what others have to say, and join
            the conversation');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'meta_desc_adv_search', 'از جستجوگر پیشرفته برای شناسایی اطلاعات مورد نیاز در انجمن {$site_name} استفاده کنید');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'meta_desc_adv_search', 'Use advanced search to find information you need within {$site_name} forum');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'meta_desc_topic_search', 'نتایج جستجو برای موضوع {$topic_name} در انجمن {$site_name}');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'meta_desc_topic_search', 'Search results for {$topic_name} topic on {$site_name} forum');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'no_forums', 'انجمنی وجود ندارد');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'no_forums', 'There are no forums');

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'forum', 'delete_content_desc', 'قبل از حذف افزونه انجمن تمام محتوای موجود در آن باید حذف شود. این کار ممکن است اندکی زمان‌بر باشد. به همین
            دلیل سایت به "حالت نگهداری" رفته و پس از پایان عملیات حذف دوباره فعال‌سازی می‌شود');
$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'delete_content_desc', 'Before uninstalling forum plugin we have to remove all existing forum content. This may take awhile. That
            is why we will put the site to "maintenance mode" and activate it back upon completing the uninstall');

$languageService->addOrUpdateValueByLanguageTag('en', 'forum', 'moved_from', 'Topic was moved from <a href="{$groupUrl}">{$groupName}</a>');
