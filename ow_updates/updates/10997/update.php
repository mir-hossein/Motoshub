<?php
try {
    //to fix favicon for pictures and other cases
    $faviconPath = OW::getPluginManager()->getPlugin('base')->getUserFilesDir() . 'favicon.ico';
    $defaultFaviconPath = OW_DIR_ROOT . 'favicon.ico';

    if(OW::getStorage()->fileExists($faviconPath)) {
        OW::getStorage()->copyFile($faviconPath, $defaultFaviconPath, true);
    }
}
catch (Exception $ex) {}