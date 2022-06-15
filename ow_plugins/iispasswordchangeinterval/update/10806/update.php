<?php
/**
* @author Mohammad Reza Heidarian
* Date: 8/25/2017
* Time: 10:29 AM
*/

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iispasswordchangeinterval', 'description_change_password', 'به منظور بهبود سطح امنیتی، لازم است گذرواژه خود را تغییر دهید. برای این‌کار، با استفاده از گذرواژه قبلی خود وارد سامانه شده و از طریق بخش <a href="{$value}"> ویرایش نمایه </a> نسبت به تغییر رمزعبور حساب کاربری خود اقدام کنید.');
$languageService->addOrUpdateValueByLanguageTag('en', 'iispasswordchangeinterval', 'description_change_password', 'In order to improve your security, change your password. Log in using the old password and use <a href="{$value}"> Profile Edit </a> page to change your account password.');