<?php

try
{
    $languageService = Updater::getLanguageService();
    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'sidebar_menu_item_log', 'لاگ‌نویسی');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'sidebar_menu_item_log', 'Logging');

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'heading_log_settings', 'لاگ‌نویسی');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'heading_log_settings', 'Logging');

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'settings_log_level_label', 'سطح لاگ‌نویسی');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'settings_log_level_label', 'Log level');

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'settings_log_output_handler_label', 'محل خروجی');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'settings_log_output_handler_label', 'Output handler');

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'settings_log_output_handler_desc', 'تنظیمات پیشفرض و محل خروجی فایل در انتهای فایل ow_includes/init.php مشخص شده است.');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'settings_log_output_handler_desc', 'Default settings and file path are specified at the end of ow_includes/init.php.');

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'settings_log_output_format_label', 'نوع خروجی');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'settings_log_output_format_label', 'Output format');

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'heading_log_test', 'تولید ورودی نمونه در واقعه‌نگاری');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'heading_log_test', 'Send a log');

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'log_settings_updated', 'تغییرات با موفقیت ثبت شد');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'log_settings_updated', 'Settings updated successfully');

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'log_settings_failed_to_update', 'عدم امکان ذخیره تنظیمات');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'log_settings_failed_to_update', 'Failed to update settings');

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'log_sent_successfully', 'با موفقیت ارسال شد');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'log_sent_successfully', 'Log sent successfully');

    $languageService->addOrUpdateValueByLanguageTag('fa-IR', 'admin', 'default', 'پیشفرض');
    $languageService->addOrUpdateValueByLanguageTag('en', 'admin', 'default', 'Default');

    $dto = new BOL_MenuItem();
    $dto->setType(BOL_NavigationService::MENU_TYPE_SETTINGS)
        ->setPrefix('admin')
        ->setKey('sidebar_menu_item_log')
        ->setRoutePath('admin_settings_log')
        ->setOrder(5)
        ->setVisibleFor(3);
    BOL_NavigationService::getInstance()->saveMenuItem($dto);
}
catch (Exception $e)
{
    OW::getLogger()->writeLog(OW_Log::ERROR, json_encode($e));
}

