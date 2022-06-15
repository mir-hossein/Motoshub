<?php
/**
 * Created by PhpStorm.
 * User: Mohammadi
 * Date: 08/05/2018
 * Time: 19:43
 */
try {
    OW::getNavigation()->deleteMenuItem('iismainpage', 'settings');
    OW::getNavigation()->addMenuItem(OW_Navigation::MOBILE_BOTTOM, 'iismainpage.settings', 'iismainpage', 'settings', OW_Navigation::VISIBLE_FOR_MEMBER);
}
catch(Exception $e){

}