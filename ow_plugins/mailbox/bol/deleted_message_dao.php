<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 5/23/18
 * Time: 11:56 AM
 */
class MAILBOX_BOL_DeletedMessageDao extends OW_BaseDao
{
    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }

    private static $classInstance;


    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function getDtoClassName()
    {
        return 'MAILBOX_BOL_DeletedMessage';
    }

    public function getTableName()
    {
        return OW_DB_PREFIX . 'mailbox_deleted_message';
    }

    public function getDeletedMessages($convId){
        $example = new OW_Example();
        $example->andFieldEqual('conversationId', $convId);

        return $this->findListByExample($example);
    }
}