<?php

OW::getPluginManager()->addPluginSettingsRouteName('iisnewsfeedplus','iisnewsfeedplus.admin_config');

if(OW::getConfig()->configExists('iisnewsfeedplus', 'newsfeed_list_order')){
    OW::getConfig()->saveConfig('iisnewsfeedplus', 'newsfeed_list_order','activity');
}
else {
    OW::getConfig()->addConfig('iisnewsfeedplus', 'newsfeed_list_order','activity');
}

