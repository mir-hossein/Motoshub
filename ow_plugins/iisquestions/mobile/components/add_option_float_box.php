<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 1:10 PM
 */
class IISQUESTIONS_MCMP_AddOptionFloatBox extends OW_MobileComponent
{
    /**
     * Constructor.
     *
     * @param $questionId
     *  @param $newQuestion
     */
    public function __construct( $questionId, $newQuestion)
    {
        parent::__construct();
        $form = new IISQUESTIONS_CLASS_AddOptionForm($questionId, $newQuestion);
        $this->addForm($form);
    }
}