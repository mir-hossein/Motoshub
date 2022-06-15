<?php

try{
    OW::getPluginManager()->addPluginSettingsRouteName('iisemoji', 'iisemoji.admin');
}catch (Exception $ex){}