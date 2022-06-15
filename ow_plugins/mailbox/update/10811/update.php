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
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'settings_desc_show_all_members', 'اندیشه‌ای واقعا بد برای وب‌گاه‌های بزرگ');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'attache_file_delete_button', 'حذف');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'bulk_delete_conversations_btn', 'حذف');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'confirm_delete_im', 'آیا از حذف پیام‌ها اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'delete_chat', 'حذف گپ');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'delete_chat_desc', 'قبل از حذف افزونه پیام‌ها باید کل اطلاعات گپ کاربران حذف شود. این کار ممکن است اندکی زمان‌بر باشد.');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'delete_confirm_message', 'آیا از حذف گفت‌وگو(ها)ی انتخاب شده اطمینان دارید؟');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'delete_conversation_button', 'حذف گفت‌وگو');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'delete_conversation_link', 'حذف');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'delete_conversation_title', 'حذف تاریخچه');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'page_title_uninstall', 'حذف افزونه صندوق پیام');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'plugin_set_for_uninstall', 'حذف افزونه پیام‌ها آغاز شد.');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'upload_file_delete_fail', 'حذف فایل ناموفق!');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'upload_file_delete_success', 'حذف فایل موفق!');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'create_conversation_fail_message', 'پیام ارسال نشد');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'create_conversation_message', 'پیام ارسال شد');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'email_notifications_section_label', 'صندوق پستی');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'new_conversation_link', 'جدید');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'upload_file_cant_write_file_error', 'نوشتن فایل روی دیسک ناموفق بود.');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'conversation_label', 'مکالمه');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'notification_mail_template_html', 'سلام {$username},<br/><br/>شما پیام جدیدی دریافت کردید از {$sendername} در{$site_name}.<br/><br/>برای پاسخ به پیام به <a href="{$replyUrl}">{$replyUrl}</a> بروید.<br/>');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'notification_mail_template_text', 'سلام {$username},



شما پیام جدیدی دریافت کردید از {$sendername} در {$site_name}.



برای پاسخ دادن به پیام به {$replyUrl} ببروید. ');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'conversations', 'گفت‌و‌گو');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'conversation_empty', 'شما تاکنون گفت‌وگویی نداشته‌اید');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'conversation_item_list_empty', 'فهرست گفت‌وگو شما خالی است');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'conversation_not_found', 'گفت‌وگو پیدا نشد');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'delete_conversation_message', 'مکالمه حذف شد.');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'delete_message', '{$count} مکالمه حذف شد');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'message_too_long_error', 'متن شما بیش از حد طولانی است. آن‌را تا بیشینه اندازه {$maxLength} کاراکتر فشرده کنید.');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'message_was_not_authorized', 'پیام نامعتبر بود');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'settings_desc_show_all_members', 'گزینه‌ای نامناسب برای وب‌گاه‌های بزرگ');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'status_invisible_label', 'پنهان از دید همه');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'uninstall_inprogress_desc', '<i>افزونه پیامها</i> در حال حذف شدن است. زمان تقریبی بستگی به تعداد کاربران شما دارد. <br /> حذف شده تا کنون: {$percents}%');

    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'upload_file_extension_is_not_allowed', 'این پسوند فایل مجاز نیست.');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'usercredits_action_read_chat_message', 'خواندن پیام‌های گپ‌');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'user_is_deleted', 'کاربری که می‌خواهید با او تماس بگیرید حذف شده است');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'user_is_not_approved', 'کاربری که می‌خواهید با او تماس بگیرید تایید نشده است');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'user_is_not_verified', 'کاربری که می‌خواهید با او تماس بگیرید تایید نشده است');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'user_is_suspended', 'کاربری که می‌خواهید با او تماس بگیرید تعلیق شده است');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'user_is_offline', '[username] برخط نیست');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'user_went_offline', '{$displayname} از دسترس خارج شده است.');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'settings_label_send_message_interval_seconds', 'ثانیه');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'reply_to_chat_message_permission_denied', 'شما اجازه ادامه گپ زدن را ندارید');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'read_chat_message_permission_denied', 'شما حق دسترسی خواندن پیام گپ را ندارید');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'create_conversation_button', 'پیام خصوصی');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'usercredits_action_send_message', 'پیام خصوصی');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'auth_action_label_send_message', 'پیام خصوصی');
    $languageService->addOrUpdateValue($langFaId, 'mailbox', 'admin_config', 'تنظیمات افزونه پیام');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'mailbox', 'upload_file_extension_is_not_allowed', 'The file extention is not allowed.');

    $languageService->addOrUpdateValue($langEnId, 'mailbox', 'send_message_promoted', 'Please subscribe or buy credits to send messages');

    $languageService->addOrUpdateValue($langEnId, 'mailbox', 'reply_to_message_promoted', 'Please subscribe or buy credits to reply to conversation');

    $languageService->addOrUpdateValue($langEnId, 'mailbox', 'send_chat_message_promoted', 'Please subscribe or buy credits to send chat message');

    $languageService->addOrUpdateValue($langEnId, 'mailbox', 'reply_to_chat_message_promoted', 'Please subscribe or buy credits to reply to chat message');
    $languageService->addOrUpdateValue($langEnId, 'mailbox', 'reply_to_chat_message_permission_denied', 'You are not authorized to reply chat');
    $languageService->addOrUpdateValue($langEnId, 'mailbox', 'read_chat_message_permission_denied', 'You do not have any permission to read chat messages');

}