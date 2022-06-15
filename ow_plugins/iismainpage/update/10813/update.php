<?php
/**
 * @author Issa Annamoradnejad
 */

$languageService = Updater::getLanguageService();
$languageService->addOrUpdateValueByLanguageTag('en', 'iismainpage', 'admin_settings_heading', 'Mainpage Plugin Settings');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iismainpage', 'admin_settings_heading', 'تنظیمات افزونه صفحه اصلی موبایل');

try{
    OW::getNavigation()->addMenuItem(OW_Navigation::MOBILE_TOP, 'iismainpage.settings', 'iismainpage', 'settings', OW_Navigation::VISIBLE_FOR_MEMBER);
}catch(Exception $e){

}