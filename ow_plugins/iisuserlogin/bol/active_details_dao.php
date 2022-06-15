<?php

/**
 * Copyright (c) 2018, Issa Annamoradnejad
 * All rights reserved.
 */

/**
 *
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisuserlogin.bol
 */
class IISUSERLOGIN_BOL_ActiveDetailsDao extends OW_BaseDao
{
    CONST IP = 'ip';
    
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
        return 'IISUSERLOGIN_BOL_ActiveDetails';
    }
    
    public function getTableName()
    {
        return OW_DB_PREFIX . 'iisuserlogin_active_details';
    }

    /***
     * @param $sessionId
     * @return bool
     */
    public function isSessionExpired($sessionId) {
        if(!OW::getConfig()->configExists('iisuserlogin','update_active_details')
            || !OW::getConfig()->getValue('iisuserlogin','update_active_details')) {
            return false;
        }

        $item = null;
        $owLogin = $this->getCurrentLoginCookie();
        if(!empty($owLogin)){
            $ex = new OW_Example();
            $ex->andFieldEqual('loginCookie', $owLogin);
            $ex->setLimitClause(0, 1);
            $item = $this->findObjectByExample($ex);
        }

        if(!isset($item) || $item == null){
            $ex = new OW_Example();
            $ex->andFieldEqual('sessionId', $sessionId);
            $ex->setLimitClause(0, 1);
            $item = $this->findObjectByExample($ex);
        }

        if ($item == null) {
            return true;
        }

        if($item->delete){
            return true;
        }
        return false;
    }

    /***
     * @param $userId
     * @param $newLogin
     * @return IISUSERLOGIN_BOL_ActiveDetails|null
     */
    public function updateActiveDetails($userId, $newLogin=false)
    {
        if(!OW::getConfig()->configExists('iisuserlogin','update_active_details') || !OW::getConfig()->getValue('iisuserlogin','update_active_details')) {
            return null;
        }
        if(!OW::getUser()->isAuthenticated()){
            return null;
        }
        if(!$userId){
            $userId = OW::getUser()->getId();
        }
        $sessionId = session_id();
        $item = null;
        $owLogin = $this->getCurrentLoginCookie();
        if(!empty($owLogin)){
            $ex = new OW_Example();
            $ex->andFieldEqual('loginCookie', $owLogin);
            $ex->setLimitClause(0, 1);
            $item = $this->findObjectByExample($ex);
        }

        if(!isset($item) || $item == null){
            $ex = new OW_Example();
            $ex->andFieldEqual('sessionId', $sessionId);
            $ex->setLimitClause(0, 1);
            $item = $this->findObjectByExample($ex);
        }

        if(!isset($item)){
            $item = new IISUSERLOGIN_BOL_ActiveDetails();
            $item->loginCookie = $owLogin;
        }
        else if (!$newLogin){
            if($item->delete){
                OW::getUser()->logout();
                if ( !empty($owLogin) )
                {
                    setcookie('ow_login', '', time() - 3600, '/');
                }
                OW::getSession()->set('no_autologin', true);
                return null;
            }
        }

        $changed = false;
        if($item->ip != IISUSERLOGIN_BOL_Service::getInstance()->getCurrentIP()){
            $item->setIp(IISUSERLOGIN_BOL_Service::getInstance()->getCurrentIP());
            $changed = true;
        }
        if($item->loginCookie != $owLogin){
            $item->loginCookie = $owLogin;
            $changed = true;
        }
        if($item->browser != IISUSERLOGIN_BOL_Service::getInstance()->getCurrentBrowserInformation()){
            $item->setBrowser(IISUSERLOGIN_BOL_Service::getInstance()->getCurrentBrowserInformation());
            $changed = true;
        }
        if($item->userId != $userId){
            $item->setUserId($userId);
            $changed = true;
        }
        if($item->sessionId != $sessionId){
            $item->setSessionId($sessionId);
            $changed = true;
        }
        if($item->delete !== "0" && $item->delete !== false){
            $item->setDelete(false);
            $changed = true;
        }
        if(!isset($item->time) || time() - $item->time > (3 * IISSecurityProvider::$updateActivityUserTimeThreshold)){
            $changed = true;
        }
        if($changed){
            $item->setTime(time());
            $this->save($item);
        }

        return $item;
    }

    public function getCurrentLoginCookie(){
        $loginCookie = isset($_COOKIE['ow_login'])?trim($_COOKIE['ow_login']):'';
        if($loginCookie == ''){
            $loginCookie = isset($_POST['access_token'])?trim($_POST['access_token']):'';
        }

        return $loginCookie;
    }

    /***
     * @param $loginCookie
     * @return IISUSERLOGIN_BOL_ActiveDetails
     */
    public function getItemByLoginCookie($loginCookie){
        $ex = new OW_Example();
        $ex->andFieldEqual('loginCookie', $loginCookie);
        $ex->setLimitClause(0, 1);
        return $this->findObjectByExample($ex);
    }

    /**
     * @param $userId
     * @param $page
     * @param int $countOfRow
     * @return array
     */
    public function getUserActiveDetails($userId, $page, $countOfRow){
        $ex = new OW_Example();
        $ex->andFieldEqual('userId', $userId);
        $ex->andFieldEqual('delete', false);
        $ex->setOrder('`time` DESC');
        if($countOfRow>0){
            $ex->setLimitClause(($page-1)*$countOfRow, $countOfRow);
        }
        return $this->findListByExample($ex);
    }

    /**
     * @param $userId
     * @param $page
     * @param int $countOfRow
     * @return array
     */
    public function getUserActiveDetailsWithoutEmptyLoginCookie($userId, $page, $countOfRow){
        $ex = new OW_Example();
        $ex->andFieldEqual('userId', $userId);
        $ex->andFieldEqual('delete', false);
        $ex->andFieldNotEqual('loginCookie', '');
        $ex->setOrder('`time` DESC');
        if($countOfRow>0){
            $ex->setLimitClause(($page-1)*$countOfRow, $countOfRow);
        }
        return $this->findListByExample($ex);
    }

    /**
     * @param $userId
     * @return array
     */
    public function getUserActiveDetailsCount($userId){
        $ex = new OW_Example();
        $ex->andFieldEqual('userId', $userId);
        $ex->andFieldEqual('delete', false);
        $ex->setOrder('`time` DESC');
        return $this->countByExample($ex);
    }

    /***
     * @param $id
     * @param $userId
     * @return bool|mixed
     */
    public function deleteDevice($id, $userId){
        $ex = new OW_Example();
        $ex->andFieldEqual('id', $id);
        $ex->andFieldEqual('userId', $userId);
        $item = $this->findObjectByExample($ex);
        $result = false;
        if($item){
            $item->setDelete(true);
            $this->save($item);
            $result = $item;
        }
        return $result;
    }

    /***
     * @param $userId
     * @param $currentSessionId
     * @return bool|mixed
     */
    public function deleteAllOtherDevices($userId, $currentSessionId){
        $ex = new OW_Example();
        $ex->andFieldNotEqual('sessionId', $currentSessionId);
        $ex->andFieldEqual('userId', $userId);
        $items = $this->findListByExample($ex);
        foreach($items as $item) {
            $item->setDelete(true);
            $this->save($item);
        }
        return true;
    }

    /***
     * @param $userId
     * @param $currentSessionId
     * @return bool|mixed
     */
    public function getAllOtherDevices($userId, $currentSessionId){
        $ex = new OW_Example();
        $ex->andFieldNotEqual('sessionId', $currentSessionId);
        $ex->andFieldEqual('userId', $userId);
        return $this->findListByExample($ex);
    }

    /***
     * @param $sessionId
     * @return bool|mixed
     */
    public function deleteDeviceBySessionId($sessionId){
        $ex = new OW_Example();
        $ex->andFieldEqual('sessionId', $sessionId);
        $item = $this->findObjectByExample($ex);
        if(!$item) {
            return '';
        }
        $item->setDelete(true);
        $this->save($item);
        return $item->loginCookie;
    }

    /***
     * deletes expired login information after 30 days
     * @return array<IISUSERLOGIN_BOL_ActiveDetails>
     */
    public function deleteExpiredDetails()
    {
        $expiredTime = time() - (int)OW::getConfig()->getValue('iisuserlogin', IISUSERLOGIN_BOL_Service::EXPIRE_TIME) * 30 * 24 * 60 * 60;

        $ex = new OW_Example();
        $ex->andFieldLessOrEqual('time',$expiredTime);
        $ex->andFieldEqual('loginCookie', '');
        $this->deleteByExample($ex);

        $ex = new OW_Example();
        $ex->andFieldLessOrEqual('time',$expiredTime);
        $ex->andFieldEqual('delete',true);
        $list = $this->findListByExample($ex);
        $this->deleteByExample($ex);

        return $list;
    }
}
