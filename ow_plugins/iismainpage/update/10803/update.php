<?php
OW::getPluginManager()->addPluginSettingsRouteName('iismainpage', 'iismainpage.admin');
$config = OW::getConfig();
if(!$config->configExists('iismainpage', 'orders'))
{
    $config->addConfig('iismainpage', 'orders', '');
}

$languageService = Updater::getLanguageService();

$languages = $languageService->getLanguages();
$langFaId = null;
$langEnId = null;
foreach ($languages as $lang) {
    if ($lang->tag == 'fa-IR') {
        $langFaId = $lang->id;
    }

    if ($lang->tag == 'en') {
        $langEnId = $lang->id;
    }
}

if ($langFaId != null) {
    $languageService->addOrUpdateValue($langFaId, 'iismainpage', 'orders', 'ترتیب منوها');
    $languageService->addOrUpdateValue($langFaId, 'iismainpage', 'empty_row_label', 'خالی');
}

if ($langEnId != null) {
    $languageService->addOrUpdateValue($langEnId, 'iismainpage', 'orders', 'Menu orders');
    $languageService->addOrUpdateValue($langEnId, 'iismainpage', 'empty_row_label', 'Empty');
}
