<?php

class IISOGHAT_CTRL_Time extends OW_ActionController
{

    public function getTime()
    {
        if (!OW::getRequest()->isAjax()) {
            throw new Redirect404Exception();
        }
        date_default_timezone_set("Asia/Tehran");
        $azan = json_decode($_POST["azan"]);
        $time = new DateTime();
        $time->setTime($azan[0],$azan[1]);
        $azanTime = strtotime($time->format('Y/m/d H:i:s'));
        exit(json_encode(array('time' => time() , 'azanTime' => $azanTime )));
    }

}
