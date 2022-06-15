<?php
/**
 * @author Mohammad Reza Heidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'photo', 'delete_content_desc', 'قبل از حذف افزونه تصویر، باید تمامی تصاویر کاربران حذف گردد. این کار اندکی زمان‌بر است، به همین دلیل در این زمان سایت
            به حالت تعمیرات و نگهداری می‌رود.
');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'photo', 'photo_deleted', 'تصویر حذف گردید.');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'photo', 'uninstall_inprogress_desc', 'لطفا تا حذف شدن تمامی تصاویر منتظر بمانید؛ بسته به تعداد تصاویر این عملیات ممکن است کمی زمان‌بر باشد.');