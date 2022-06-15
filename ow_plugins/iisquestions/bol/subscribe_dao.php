<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 10:03 AM
 */
class IISQUESTIONS_BOL_SubscribeDao extends OW_BaseDao
{
    const USER_ID = 'userId';
    const QUESTION_ID = 'questionId';
    const TIMESTAMP = 'timeStamp';

    private static $INSTANCE;

    public static function getInstance(){
        if(!isset(self::$INSTANCE))
            self::$INSTANCE = new self();
        return self::$INSTANCE;
    }

    public function deleteByUserAndQuestion($userId,$questionId){
        $example = new OW_Example();
        $example->andFieldEqual(self::USER_ID,$userId);
        $example->andFieldEqual(self::QUESTION_ID,$questionId);
        $this->deleteByExample($example);
    }

    public function findSubscribeCountByQuestion($questionId){
        $example = new OW_Example();
        $example->andFieldEqual(self::QUESTION_ID,$questionId);
        return $this->countByExample($example);
    }

    public function findSubscribeByQuestion($questionId){
        $example = new OW_Example();
        $example->andFieldEqual(self::QUESTION_ID,$questionId);
        return $this->findListByExample($example);
    }

    public function findSubscribeByQuestionAndUser($userId,$questionId){
        $example = new OW_Example();
        $example->andFieldEqual(self::USER_ID,$userId);
        $example->andFieldEqual(self::QUESTION_ID,$questionId);
        return $this->findObjectByExample($example);
    }

    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisquestions_subscribe';
    }

    public function getDtoClassName()
    {
        return 'IISQUESTIONS_BOL_Subscribe';
    }
}