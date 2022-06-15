<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 10:03 AM
 */
class IISQUESTIONS_BOL_AnswerDao extends OW_BaseDao
{
    const USER_ID = 'userId';
    const QUESTION_ID = 'questionId';
    const OPTION_ID = 'optionId';
    const TIMESTAMP = 'timeStamp';

    private static $INSTANCE;

    public static function getInstance(){
        if(!isset(self::$INSTANCE))
            self::$INSTANCE = new self();
        return self::$INSTANCE;
    }

    public function deleteByUserAndOption($userId, $optionId)
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::USER_ID, $userId);
        $example->andFieldEqual(self::OPTION_ID, $optionId);
        $this->deleteByExample($example);
    }

    public function deleteByOption($optionId)
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::OPTION_ID, $optionId);
        $this->deleteByExample($example);
    }

    public function isAnsweredByUser($userId, $optionId)
    {
        $example = new OW_Example();
        $example->andFieldEqual(self::USER_ID, $userId);
        $example->andFieldEqual(self::OPTION_ID, $optionId);
        $result = $this->findIdListByExample($example);
        return isset($result) && !empty($result);
    }

    public function findAnswerCountByQuestion($questionId){
        $example = new OW_Example();
        $example->andFieldEqual(self::QUESTION_ID,$questionId);
        return $this->countByExample($example);
    }

    public function findAnswerCountByOption($optionId){
        $example = new OW_Example();
        $example->andFieldEqual(self::OPTION_ID,$optionId);
        return $this->countByExample($example);
    }

    public function findAnswerByOption($optionId){
        $example = new OW_Example();
        $example->andFieldEqual(self::OPTION_ID,$optionId);
        return $this->findListByExample($example);
    }

    public function findAnswerByOptionAndUser($userId,$optionId){
        $example = new OW_Example();
        $example->andFieldEqual(self::OPTION_ID,$optionId);
        $example->andFieldEqual(self::USER_ID, $userId);
        return $this->findListByExample($example);
    }

    public function findUserAnswerList( $userId, $optionIds )
    {
        $example = new OW_Example();
        $example->andFieldInArray('optionId', $optionIds);
        $example->andFieldEqual('userId', $userId);

        return $this->findListByExample($example);
    }

    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisquestions_answer';
    }

    public function getDtoClassName()
    {
        return 'IISQUESTIONS_BOL_Answer';
    }
}