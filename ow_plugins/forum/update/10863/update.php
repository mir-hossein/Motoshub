<?php
try {
    $authorization = OW::getAuthorization();
    $groupName = 'forum';
    $authorization->addAction($groupName, 'delete');
}catch (Exception $ex){}
