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
    $languageService->addOrUpdateValue($langFaId, 'iisuserlogin', 'email_login_details', 'سلام {$username}، یک ورود از حساب کاربری شما با رایانامه {$email} در شبکه ثبت شد. اطلاعات مرورگر، آی‌پی و زمان ورود عبارتند از: <br />اطلاعات مرورگر: {$browser}  <br /> آی‌پی: {$ip} <br /> زمان: {$time} <br />');
    $languageService->addOrUpdateValue($langFaId, 'iisuserlogin', 'preference_login_detail_subscribe_description', 'در هنگام ورود به شبکه اجتماعی، یک رایانامه به صورت خودکار، حاوی اطلاعات ورود برای شما ارسال خواهد شد');
    $languageService->addOrUpdateValue($langFaId, 'iisuserlogin', 'admin_page_heading', 'تنظیمات افزونه نمایش جزییات ورود کاربران');
    $languageService->addOrUpdateValue($langFaId, 'iisuserlogin', 'admin_page_title', 'تنظیمات افزونه نمایش جزییات ورود کاربران');
    $languageService->addOrUpdateValue($langFaId, 'iisuserlogin', 'mobile_bottom_menu_item', 'اطلاعات ورود');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'iisuserlogin', 'admin_page_heading', 'User login plugin settings');
    $languageService->addOrUpdateValue($langEnId, 'iisuserlogin', 'admin_page_title', 'User login plugin settings');
    $languageService->addOrUpdateValue($langEnId, 'iisuserlogin', 'mobile_bottom_menu_item', 'Login Information');
}