<?php

$filename= OW_DIR_ROOT . 'ow_includes/config.php';
$contents = file_get_contents($filename);
$contents=str_replace("OW_PASSWORD_SALT","OW_PASSWORD_PEPPER",$contents,$count);
if($count>0)
{
    file_put_contents($filename,$contents);
}
else
{
    $logger = Updater::getLogger();
    $logger->addEntry(json_encode("no salt found"));
}