<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_core
 *
 * Logging functionality using monolog
 * this class replaced an old class with the same name
 *
 * Log Levels:
 * DEBUG (100): Detailed debug information.
 * INFO (200): Interesting events. Examples: User logs in, SQL logs.
 * NOTICE (250): Normal but significant events.
 * WARNING (300): Exceptional occurrences that are not errors. Examples: Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
 * ERROR (400): Runtime errors that do not require immediate action but should typically be logged and monitored.
 * CRITICAL (500): Critical conditions. Example: Application component unavailable, unexpected exception.
 * ALERT (550): Action must be taken immediately. Example: Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
 * EMERGENCY (600): Emergency: system is unusable.
 *
 */
class OW_Log
{
    // Log Levels
    const DEBUG     = 'debug';
    const INFO      = 'info';
    const NOTICE    = 'notice';
    const WARNING   = 'warning';
    const ERROR     = 'error';
    const CRITICAL  = 'critical';
    const ALERT     = 'alert';
    const EMERGENCY = 'emergency';

    //General Log Types
    const CREATE    = 'create';
    const READ      = 'read';
    const UPDATE    = 'update';
    const DELETE    = 'delete';

    private $logger;

    private $channelName;
    private static $classInstances;

    /**
     * Returns an instance of class (singleton pattern implementation).
     * @param string $name
     * @return OW_Log
     */
    public static function getInstance($name)
    {
        if ( self::$classInstances === null )
        {
            self::$classInstances = array();
        }

        if ( empty(self::$classInstances[$name]) )
        {
            self::$classInstances[$name] = new self($name);
        }

        return self::$classInstances[$name];
    }

    /**
     * OW_Log constructor.
     * @param string $name
     */
    private function __construct($name)
    {
        $this->channelName = $name;
        $this->logger = new Logger($this->channelName);
    }

    /**
     * @param $handler
     */
    public function addLogHandler( $handler )
    {
        $this->logger->pushHandler($handler);
    }

    /***
     * @return string
     */
    private function getCurrentIP(){
        $ip = OW::getRequest()->getRemoteAddress();
        if($ip == '::1' || empty($ip)){
            $ip = '127.0.0.1';
        }
        return $ip;
    }

    /***
     * @return string
     */
    private function getCurrentMode(){
        $res = 'desktop';
        $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
        if(isset($mobileEvent->getData()['isMobileVersion'])&& $mobileEvent->getData()['isMobileVersion']==true) {
            $res = 'mobile';
        }
        else if (isset($_COOKIE['UsingMobileApp'])){
            $res = $_COOKIE['UsingMobileApp'];
        }
        return $res;
    }

    /**
     * @param $logLevel
     * @param string $title
     * @param array $data
     * @param bool $addUserId
     * @param bool $addIP
     * @param bool $addVersion
     * @param bool $addPOST
     */
    public function writeLog( $logLevel, $title='', $data=array(), $addUserId=true, $addIP=true, $addVersion=true, $addPOST=true)
    {
        $prepend_data = [];
        $trace = debug_backtrace();
        for($i = 1; $i<count($trace);$i++){
            if(isset($trace[$i]['class']) && !in_array($trace[$i]['class'], array("OW_Log", "OW_ErrorManager"))){
                $prepend_data['class_name'] = $trace[$i]['class'];
                $prepend_data['function_name'] = $trace[$i]['function'];
                break;
            }
        }
        if($addIP){
            $prepend_data['ip'] = $this->getCurrentIP();
        }
        if($addPOST){
            $post_data = $_POST;
            unset($post_data['login_cookie'],$post_data['access_token'],$post_data['csrf_token'],$post_data['csrf_hash']);
            $prepend_data['POST'] = $post_data;
        }
        if (php_sapi_name() === 'cli') {
            if ($addUserId) {
                $prepend_data['user_id'] = '0';
            }
            if ($addVersion) {
                $prepend_data['mode'] = 'cli';
            }
        } else {
            $auth = OW_Auth::getInstance()->getAuthenticator();
            if (isset($auth)) {
                if (class_exists('OW_User') && $addUserId) {
                    try {
                        $user = OW::getUser();
                        $prepend_data['user_id'] = isset($user) ? $user->getId() : -1;
                    } catch (Exception $ex) {
                    }
                }
                if (class_exists('OW_EventManager') && $addVersion) {
                    try {
                        $prepend_data['mode'] = $this->getCurrentMode();
                    } catch (Exception $ex) {
                    }
                }
            }
        }

        $data = $prepend_data + $data;

        $this->logger->{$logLevel}($title, $data);
    }

    /***
     * @deprecated This is a legacy function. Use writeLog instead.
     * @param string $title
     */
    public function addEntry( $title='')
    {
        $this->writeLog(self::ERROR, $title);
    }

}