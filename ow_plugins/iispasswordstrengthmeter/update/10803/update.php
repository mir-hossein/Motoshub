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
    $languageService->addOrUpdateValue($langFaId, 'iispasswordstrengthmeter', 'strength_password_validate_error', 'گذرواژه وارد شده، حداقل استحکام مورد نیاز را ندارد. حداقل استحکام قابل قبول، سطح {$value} است.');

    $languageService->addOrUpdateValue($langFaId, 'iispasswordstrengthmeter', 'minimum_requirement_password_strength_label', 'انتخاب حداقل معیار قبولی برای گذرواژه ');

    $languageService->addOrUpdateValue($langFaId, 'iispasswordstrengthmeter', 'admin_page_heading', 'تنظیمات افزونه نمایشگر میزان قدرت گذرواژه');
    $languageService->addOrUpdateValue($langFaId, 'iispasswordstrengthmeter', 'admin_page_title', 'تنظیمات افزونه نمایشگر میزان قدرت گذرواژه');
    $languageService->addOrUpdateValue($langFaId, 'iispasswordstrengthmeter', 'secure_password_information_minimum_strength_type', 'گذرواژه باید دارای حداقل سطح امنیتی {$value} باشد.');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'iispasswordstrengthmeter', 'admin_page_heading', 'Password strength meter plugin settings');
    $languageService->addOrUpdateValue($langEnId, 'iispasswordstrengthmeter', 'admin_page_title', 'Password strength meter plugin settings');
}