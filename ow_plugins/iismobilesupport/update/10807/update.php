<?php
/**
 * @author Seyed Ismail Mirvakili
 * Date: 6/11/2017
 * Time: 11:35 AM
 */

$config = OW::getConfig();
if (!$config->configExists('iismobilesupport', 'disable_notification_content')) {
    $config->addConfig('iismobilesupport', 'disable_notification_content', false);
}