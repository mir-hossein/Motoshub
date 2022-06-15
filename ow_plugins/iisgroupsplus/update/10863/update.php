<?php

try {
    $authorization = OW::getAuthorization();
    $groupName = 'iisgroupsplus';
    $authorization->addAction($groupName, 'add-forced-groups');
}catch (Exception $e){}
