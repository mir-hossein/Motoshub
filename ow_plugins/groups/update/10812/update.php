<?php

if (OW::getConfig()->configExists('groups', 'check_all_private_groups')){
    OW::getConfig()->saveConfig('groups', 'check_all_private_groups', true);
}else{
    OW::getConfig()->addConfig('groups', 'check_all_private_groups', true);
}
