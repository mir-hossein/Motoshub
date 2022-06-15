<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_CMP_NoAccess extends OW_Component
{

    public function __construct($allCount)
    {
        parent::__construct();
        $sum = 0;
        foreach ($allCount as $key=>$value){
            $sum += $value;
        }
        $this->assign('msg', OW::getLanguage()->text('iishashtag','able_to_see_text',array('num'=>0,'all'=>$sum)));

    }
}