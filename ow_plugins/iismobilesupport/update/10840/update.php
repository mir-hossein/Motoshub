<?php
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 */

if (!OW::getConfig()->configExists('iismobilesupport', 'last_firebase_send_notifications_time')){
    OW::getConfig()->addConfig('iismobilesupport', 'last_firebase_send_notifications_time', '0');
}