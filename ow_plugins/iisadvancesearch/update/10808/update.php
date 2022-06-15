<?php
/**
 * User: Issa Annamoradnejad <i.moradnejad@gmail.com>
 */

OW::getPluginManager()->addPluginSettingsRouteName('iisadvancesearch', 'iisadvancesearch.admin');

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValue( 'fa-IR', 'iisadvancesearch', 'admin_settings_heading', 'تنظیمات افزونه جستجوی پیشرفته');
$languageService->addOrUpdateValue( 'en', 'iisadvancesearch', 'admin_settings_heading', 'Advance Search Settings');

$languageService->addOrUpdateValue( 'fa-IR', 'iisadvancesearch', 'settings', 'تنظیمات');
$languageService->addOrUpdateValue( 'en', 'iisadvancesearch', 'settings', 'Settings');

$languageService->addOrUpdateValue( 'fa-IR', 'iisadvancesearch', 'save_btn_label', 'ذخیره');
$languageService->addOrUpdateValue( 'en', 'iisadvancesearch', 'save_btn_label', 'Save');

$languageService->addOrUpdateValue( 'fa-IR', 'iisadvancesearch', 'admin_changed_success', ' تغییرات با موفقیت ذخیره شد');
$languageService->addOrUpdateValue( 'en', 'iisadvancesearch', 'admin_changed_success', 'Settings successfully saved');
