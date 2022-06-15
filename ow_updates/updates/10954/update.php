<?php
try {
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'error_empty_credentials_provided', 'ویژگی‌های FTP نامعتبر است! نام کاربری یا گذرواژه خالی است');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'permission_global_privacy_pass_length_error_message', 'لطفا گذرواژه معتبر وارد کنید(کمینه 3 کاراکتر مختلف)');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'site_password_letter_template_html', 'گذرواژه جدید برای{$site_name}<br>تنظیم گذرواژه جدید شما <b>{$password}</b><br>');
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'site_password_letter_template_text', 'گذرواژه جدید برای{$site_name}تنظیم گذرواژه جدید شما {$password}');

}
catch (Exception $e)
{
    $logger->addEntry(json_encode($e));
}
