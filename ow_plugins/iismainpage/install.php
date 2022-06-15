<?php
/**
 * iismainpage
 */
/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iismainpage
 * @since 1.0
 */

$config = OW::getConfig();
if(!$config->configExists('iismainpage', 'orders'))
{
    $config->addConfig('iismainpage', 'orders', '');
}
