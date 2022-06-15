<?php
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 */
try {
    if (!OW::getConfig()->configExists('iismobilesupport', 'web_config')) {
        OW::getConfig()->addConfig('iismobilesupport', 'web_config', '');
    }
    if (!OW::getConfig()->configExists('iismobilesupport', 'web_key')) {
        OW::getConfig()->addConfig('iismobilesupport', 'web_key', '');
    }
}catch (Exception $ex){}