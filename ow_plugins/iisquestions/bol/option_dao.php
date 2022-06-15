<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 10:03 AM
 */
class IISQUESTIONS_BOL_OptionDao extends OW_BaseDao
{

    const QUESTION_ID = 'questionId';
    const USER_ID = 'userId';
    const TEXT = 'text';
    const TIME_STAMP = 'timeStamp';

    private static $INSTANCE;

    public static function getInstance(){
        if(!isset(self::$INSTANCE))
            self::$INSTANCE = new self();
        return self::$INSTANCE;
    }

    public function findOptionList($questionId){
        $example = new OW_Example();
        $example->andFieldEqual(self::QUESTION_ID,$questionId);
        return $this->findIdListByExample($example);
    }

    public function deleteByQuestion($questionId){
        $example = new OW_Example();
        $example->andFieldEqual(self::QUESTION_ID,$questionId);
        return $this->deleteByExample($example);
    }

    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisquestions_option';
    }

    public function getDtoClassName()
    {
        return 'IISQUESTIONS_BOL_Option';
    }
}