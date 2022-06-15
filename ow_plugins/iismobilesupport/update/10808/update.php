<?php
/**
 * @author Milad Heshmati
 * Date: 6/26/2017
 */

if (!OW::getConfig()->configExists('iismobilesupport', 'custom_download_link_code')){
    OW::getConfig()->addConfig('iismobilesupport', 'custom_download_link_code', '<a class="app_download_link android" href="" target="_blank"></a>');
}

if (!OW::getConfig()->configExists('iismobilesupport', 'custom_download_link_activation')){
    OW::getConfig()->addConfig('iismobilesupport', 'custom_download_link_activation', false);
}

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
    $languageService->addOrUpdateValue($langFaId, 'iismobilesupport', 'download_show', ' نمایش دریافت');
    $languageService->addOrUpdateValue($langFaId, 'iismobilesupport', 'custom_download_link_label', ' کد HTML لینک‌های مرتبط');
    $languageService->addOrUpdateValue($langFaId, 'iismobilesupport', 'custom_download_link_desc', '    سایر لینک‌های دریافت نرم‌افزار از طریق لینک‌های خارجی مانند بازار را به صورت کد HTML در این قسمت وارد کنید.');
    $languageService->addOrUpdateValue($langFaId, 'iismobilesupport', 'download_activation', 'فعال‌سازی دکمه‌های دریافت در بخش فوتر');
}
if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'iismobilesupport', 'download_show', 'Download show');
    $languageService->addOrUpdateValue($langEnId, 'iismobilesupport', 'custom_download_link_label', 'custom Links\' HTML');
    $languageService->addOrUpdateValue($langEnId, 'iismobilesupport', 'custom_download_link_desc', 'Enter other related download links such as app store as HTML code');
    $languageService->addOrUpdateValue($langEnId, 'iismobilesupport', 'download_activation', 'Activate download buttons in footer');
}