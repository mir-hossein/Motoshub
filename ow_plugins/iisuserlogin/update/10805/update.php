<?php
/**
 * @author Issa Annamoradnejad
 */
if(!OW::getConfig()->configExists('iisuserlogin','update_active_details')){
    OW::getConfig()->addConfig('iisuserlogin','update_active_details',true);
}

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisuserlogin', 'enableActiveDevices', 'فعال بودن زبانه «نشست‌های فعال»');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisuserlogin', 'login_details_header', 'جزئیات ورود');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisuserlogin', 'bottom_menu_item', 'جزئیات نشست');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisuserlogin', 'active_details_header', 'نشست‌های فعال');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisuserlogin', 'current_device', 'نشست فعلی');


$q = 'CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'iisuserlogin_active_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `browser` longtext NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `sessionId` longtext NOT NULL,
  `loginCookie` longtext NOT NULL,
  `delete` TINYINT(1) NOT NULL,
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8;
';
try {
    Updater::getDbo()->query($q);
}
catch (Exception $ex){}

