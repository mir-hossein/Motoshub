<?php
/**
 * @author Mohammad Agha Abbasloo
 * Date: 5/31/2017
 * Time: 3:43 PM
 */

$updateDir = dirname(__FILE__) . DS;
Updater::getLanguageService()->importPrefixFromZip($updateDir . 'langs.zip', 'iiseventplus');
