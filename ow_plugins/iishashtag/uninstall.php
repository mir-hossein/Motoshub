<?php

/**
 * iishashtag
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
try {
    $authorization = OW::getAuthorization();
    $groupName = 'iishashtag';
    $authorization->deleteAction($groupName, 'view_newsfeed');
}catch (Exception $e){}