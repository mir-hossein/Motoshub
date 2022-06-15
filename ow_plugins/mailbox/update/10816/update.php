<?php
/**
 * @author Muhammad Reza Hidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'mailbox','notification_mail_template_text', 'سلام {$username}،
            
            شما پیام جدیدی دریافت کردید از {$sendername} در {$site_name}.
            
            برای پاسخ دادن به پیام به {$replyUrl} ببروید.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'mailbox','notification_mail_template_html', 'سلام {$username} ،<br/>
            <br/>
            شما پیام جدیدی دریافت کردید از {$sendername} در{$site_name}.<br/>
            <br/>
            برای پاسخ به پیام به <a href="{$replyUrl}">{$replyUrl}</a> بروید.');