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
    $languageService->addOrUpdateValue($langFaId, 'forum', 'admin_forum_settings_heading', 'تنظیمات افزونه انجمن');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'feed_activity_topic_string', 'یک موضوع در انجمن ایجاد کرد.');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'advanced_search', 'جستجو پیشرفته');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'search_advanced_heading', 'جستجو پیشرفته');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'this_topic', 'تنظیمات موضوع');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'email_notification_post', '<a href="{$userUrl}">{$userName}</a> به یک موضوع در انجمن <a href="{$postUrl}">{$title}</a> پاسخ داد.');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'add_post_title', 'درج پاسخ');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'flag', 'گزارش تخلف');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'confirm_delete_attachment', 'آیا از حذف این سند اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'confirm_delete_forum', 'آیا از حذف همه موضوعات انجمن و بخش‌ها اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'delete', 'حذف');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'delete_content', 'حذف محتوا و افزونه');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'delete_content_desc', 'قبل از حذف افزونه انجمن تمام محتوای موجود در آن باید پاک شود. این کار ممکن است اندکی زمان‌بر باشد. به همین دلیل سایت را به "حالت نگهداری" برده و پس از پایان عملیات پاکسازی دوباره فعال‌سازی می‌کنیم.');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'delete_group', 'حذف');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'delete_group_confirm', 'آیا از حذف این انجمن گروه اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'delete_post_confirm', 'آیا از حذف این نوشته اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'delete_section', 'حذف');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'delete_section_confirm', 'آیا از حذف این بخش انجمن اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'delete_topic', 'حذف');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'delete_topic_confirm', 'آیا از حذف این موضوع اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'page_title_uninstall', 'حذف افزونه انجمن');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'plugin_set_for_uninstall', 'حذف افزونه انجمن آغاز شد.');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'unsticky_topic', 'حذف از بخش مهم‌ها');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'unsticky_topic_confirm', 'این موضوع مهم نخواهد بود، آیا از حذف این موضوع از بخش مهم‌ها اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'clear_all', 'حذف همه');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'confirm_delete_all_attachments', 'آیا از حذف این اسناد اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'delete_quote_confirm', 'آیا از حذف این نقل قول اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'clear_all', 'حذف همه');

    $languageService->addOrUpdateValue($langFaId, 'forum', 'forum_sitemap', 'انجمن');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'seo_meta_section', 'انجمن');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'seo_meta_home_label', 'صفحه اصلی انجمن');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_title_home', 'انجمن | {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_desc_home', 'به انجمن {$site_name} خوش آمدید. موضوع جدید ایجاد کنید، نوشته‌های دیگران را مطالعه کنید و با دیگر کاربران گفت‌وگو کنید.');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_keywords_home', '');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'seo_meta_adv_search_label', 'صفحه جستجوی پیشرفته انجمن');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_title_adv_search', 'جستجوی پیشرفته انجمن {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_desc_adv_search', 'از جستجوگر پیشرفته برای شناسایی اطلاعات مورد نیاز در انجمن {$site_name} استفاده کنید.');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_keywords_adv_searche', '');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'seo_meta_adv_search_result_label', 'صفحه پیشرفته نتایج جستجوی انجمن');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_title_adv_search_result', 'نتایج جستجوی برای انجمن {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_desc_adv_search_result', 'مشاهده نتایج جستجوی انجمن در {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_keywords_adv_searche_result', '');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'seo_meta_section_label', 'صفحه زیربخش‌ها');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_title_section', '{$section_name} در انجمن {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_desc_section', '{$section_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_keywords_section', '');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'seo_meta_group_label', 'صفحه دسته‌های زیربخش‌ها');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_title_group', '{$group_name} در انجمن {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_desc_group', '{$group_description}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_keywords_group', '');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'seo_meta_topic_label', 'صفحه ریسمان انجمن');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_title_topic', '{$topic_name} در انجمن {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_desc_topic', '{$topic_description}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_keywords_topic', '');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'seo_meta_section_search_label', 'صفحه جستجوی زیربخش‌ها');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_title_section_search', 'جستجوی {$section_name} در انجمن {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_desc_section_search', 'نتایج جستجو برای زیربخش {$section_name} در انجمن {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_keywords_section_search', '');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'seo_meta_group_search_label', 'صفحه جستحوی زیربخش‌های انجمن');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_title_group_search', 'جستجوی {$group_name} در انجمن {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_desc_group_search', 'نتیج جستجو برای ریسمان {$group_name} در انجمن {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_keywords_group_search', '');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'seo_meta_topic_search_label', 'جستجوی ریسمان انجمن');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_title_topic_search', 'جستجوی موضوع {$topic_name} در {$site_name}');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_desc_topic_search', 'نتایج جستجو برای موضوع {$topic_name} در انجمن {$site_name}.');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'meta_keywords_topic_search', '');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'forms_search_sort_direction_field_value_increase', 'افزایشی');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'forms_search_sort_direction_field_value_decrease', 'کاهشی');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'search_example_usage_text', '<b>سیب موز</b> - یافتن سطرهایی که شامل حداقل یکی از دو کلمه است.<br><b>سیب +آب+</b> - یافتن سطرهایی که شامل هر دو کلمه است <br><b>+کوه دماوند </b> - یافتن سطرهایی که شامل کلمه "کوه" هستند، اما سطرهایی را که شامل "دماوند" نیز هستند را در رتبه بالاتری قرار می‌دهد.<br><b>+کوه -دماوند</b> - یافتن سطرهایی که شامل کلمه "کوه" هستند اما شامل "دماوند" نیستند.<br><b>+کوه ~دماوند</b> - یافتن سطرهایی که شامل کلمه "کوه" هستند، اما اگر سطر همچنین شامل کلمه "دماوند" نیز باشد، آن‌را از سطری که ندارد امتیاز پایین‌تری می‌دهد.<br><b>+کوه+(<دامنه>قله)</b> - یافتن سطرهایی که شامل کلمه‌های "کوه" و "دامنه"، یا "کوه" و "قله" (با هر ترتیبی) است، اما رتبه "کوه دامنه" بالاتر از "کوه قله" است.<br><b>کوه*</b> - یافتن سطرهایی که شامل کلماتی مانند "کوه"، "کوهستان"، "کوهپایه"، یا "کوهسار" است.<br><b>"برخی کلمات"</b> - یافتن سطرهایی که دقیقا شامل عبارت "برخی کلمات" (برای مثال، سطرهایی که شامل "برخی کلمات آشنا" است اما شامل "برخی حروف کلمات" نیست). توجه شود که کاراکترهای “"” که عبارت را محصور می‌کنند، کاراکترهای عملیاتی هستند که عبارت را محدود می‌کنند. این کاراکترها گیومه‌هایی نیستند که خود رشته جستجو را محصور کنند.');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'messages_on_forum', 'پیام در انجمن');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'attached_files','سند پیوستی');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'additional','بیشتر');
    $languageService->addOrUpdateValue($langFaId, 'forum', 'no_topic','در حال حاضر موضوعی وجود ندارد.');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'forum', 'admin_forum_settings_heading', 'Forum plugin settings');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'forum_sitemap', 'Forum');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'seo_meta_section', 'Forum');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'seo_meta_home_label', 'Forum Homepage');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_title_home', 'Forum | {$site_name}');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_desc_home', 'Welcome to the discussion forum at {$site_name}. Create new posts, read what others have to say, and join the conversation.');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_keywords_home', '');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'seo_meta_adv_search_label', 'Forum Advanced Search Page');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_title_adv_search', 'Advanced search {$site_name} Forum');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_desc_adv_search', 'Use advanced search to find information you need within {$site_name} forum.');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_keywords_adv_searche', '');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'seo_meta_adv_search_result_label', 'Forum Advanced Search Results Page');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_title_adv_search_result', 'Search results for {$site_name} Forum');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_desc_adv_search_result', 'View the results of your forum search on {$site_name}.');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_keywords_adv_searche_result', '');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'seo_meta_section_label', 'Separate Sub-forum Page');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_title_section', '{$section_name} at {$site_name} Forum');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_desc_section', '{$section_name}');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_keywords_section', '');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'seo_meta_group_label', 'Separate Sub-forum Category Page');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_title_group', '{$group_name} at {$site_name} Forum');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_desc_group', '{$group_description}');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_keywords_group', '');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'seo_meta_topic_label', 'Separate Forum Thread Page');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_title_topic', '{$topic_name} at {$site_name} Forum');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_desc_topic', '{$topic_description}');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_keywords_topic', '');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'seo_meta_section_search_label', 'Separate Sub-forum Search Page');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_title_section_search', '{$section_name} search at {$site_name} Forum');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_desc_section_search', 'Search results for {$section_name} sub-forum on {$site_name} forum.');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_keywords_section_search', '');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'seo_meta_group_search_label', 'Sub-forum Category Search Page');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_title_group_search', '{$group_name} search at {$site_name} Forum');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_desc_group_search', 'Search results for {$group_name} threads on {$site_name} forum.');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_keywords_group_search', '');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'seo_meta_topic_search_label', 'Forum Thread Search');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_title_topic_search', '{$topic_name} search at {$site_name} Forum');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_desc_topic_search', 'Search results for {$topic_name} topic on {$site_name} forum.');
    $languageService->addOrUpdateValue($langEnId, 'forum', 'meta_keywords_topic_search', '');
}