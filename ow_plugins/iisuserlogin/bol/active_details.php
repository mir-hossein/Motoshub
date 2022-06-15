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
class IISUSERLOGIN_BOL_ActiveDetails extends OW_Entity
{

    public $ip;
    public $time;
    public $browser;
    public $userId;
    public $sessionId;
    public $loginCookie;
    public $delete;
    
    public function getIp()
    {
        return $this->ip;
    }
    
    public function setIp( $value )
    {
        $this->ip = $value;
        return $this;
    }
    
    public function getTime()
    {
        return (int)$this->time;
    }
    
    public function setTime( $value )
    {
        $this->time = (int)$value;
        
        return $this;
    }

    public function getBrowser()
    {
        return $this->browser;
    }

    public function setBrowser( $value )
    {
        $this->browser = $value;
        return $this;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId( $value )
    {
        $this->userId = $value;
        return $this;
    }

    public function getSessionId()
    {
        return $this->sessionId;
    }

    public function setSessionId( $value )
    {
        $this->sessionId = $value;
        return $this;
    }

    public function getDelete()
    {
        return $this->delete;
    }

    public function setDelete( $value )
    {
        $this->delete = $value;
        return $this;
    }

}
