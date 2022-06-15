<?php
try {
    $authorization = OW::getAuthorization();
    $groupName = 'iismobilesupport';
    $authorization->deleteAction($groupName, 'show-desktop-version');
}catch (Exception $e){}