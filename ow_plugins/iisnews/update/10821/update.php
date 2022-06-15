<?php
/**
 * @author Mohammad Reza Heidarian
 * Date: 8/25/2017
 * Time: 10:29 AM
 */

$languageService = Updater::getLanguageService();

$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisnews','search_by_entry_placeholder', 'محتوای مورد نظر خود را وارد کنید');
$languageService->addOrUpdateValueByLanguageTag('fa-IR', 'iisnews','search_by_tag_placeholder', 'برچسب مورد نظر خود را وارد کنید');

$languageService->addOrUpdateValueByLanguageTag('en', 'iisnews','search_by_entry_placeholder', 'Enter text to search');
$languageService->addOrUpdateValueByLanguageTag('en', 'iisnews','search_by_tag_placeholder', 'Enter tag to search');
