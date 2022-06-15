<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/25/18
 * Time: 2:50 PM
 */

$eventHandler = new IISQUESTIONS_MCLASS_EventHandler();
$eventHandler->init();

$urlMapping = new IISQUESTIONS_CLASS_UrlMapping();
$urlMapping->mobileInit();