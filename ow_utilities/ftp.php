<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_utilities
 * @since 1.0
 */
class UTIL_Ftp
{
    const ERROR_FTP_FUNCTION_IS_NOT_AVAILABLE = 'error_ftp_function_is_not_available';
    const ERROR_EMPTY_HOST_PROVIDED = 'error_empty_host_provided';
    const ERROR_CANT_CONNECT_TO_HOST = 'error_cant_connect_to_host';
    const ERROR_EMPTY_CREDENTIALS_PROVIDED = 'error_empty_credentials_provided';
    const ERROR_INVALID_CREDENTIALS_PROVIDED = 'error_invalid_credentials_provided';

    /**
     * @var connection stream
     */
    private $stream;

    /**
     * @var connection timeout
     */
    private $timeout = 30;

    /**
     * @var boolean
     */
    private $loggedIn = false;

    /**
     * FTP root dir.
     *
     * @var string
     */
    private $ftpRootDir;

    /**
     * Constructor.
     *
     * @param array $options
     */
    private function __construct()
    {
        
    }

    public function init()
    {
        $dirRoot = OW_DIR_ROOT;

        if ( substr($dirRoot, 0, 1) === DS )
        {
            $dirRoot = substr($dirRoot, 1);
        }

        if ( substr($dirRoot, -1) === DS )
        {
            $dirRoot = substr($dirRoot, 0, -1);
        }

        $pathList = array($dirRoot);

        while ( true )
        {
            $dirRoot = substr($dirRoot, ( strpos($dirRoot, DS) + 1));
            $pathList[] = $dirRoot;
            if ( !strstr($dirRoot, DS) )
            {
                break;
            }
        }


        foreach ( $pathList as $path )
        {
            if ( $this->isFtpRootDir($path) )
            {
                $this->ftpRootDir = substr(OW_DIR_ROOT, 0, strpos(OW_DIR_ROOT, $path));
                break;
            }
        }

        if ( $this->ftpRootDir === null )
        {
            $this->ftpRootDir = OW_DIR_ROOT;
        }

        $this->chdir('/');

        $dirname = "temp" . rand(1, 1000000);
        if ( OW::getStorage()->mkdir($dirname) )
        {
            // hotfix, doesn't work with win servers
            $rootPath = "";

            $dirRootPathArr = array_filter(explode(DS, OW_DIR_ROOT));
            array_unshift($dirRootPathArr, "");

            foreach ( $dirRootPathArr as $pathItem )
            {
                $rootPath .= $pathItem . DS;
                //Issa Annamoradnejad
                //fix issues in windows systems
                if(strpos($pathItem,':')!==false && strpos($rootPath, DS)===0){
                    $rootPath = substr($rootPath, 1);
                }

                if ( OW::getStorage()->fileExists($rootPath . $dirname) )
                {
                    $this->ftpRootDir = $rootPath;

                    OW::getStorage()->removeDir($dirname);
                    return;
                }
            }
        }
    }

    /**
     * @param array $params
     * @return UTIL_Ftp
     */
    public static function getConnection( array $params )
    {
        if ( !function_exists('ftp_connect') )
        {
            throw new LogicException(self::ERROR_FTP_FUNCTION_IS_NOT_AVAILABLE);
        }

        if ( empty($params['host']) )
        {
            throw new InvalidArgumentException(self::ERROR_EMPTY_HOST_PROVIDED);
        }

        if ( empty($params['login']) || empty($params['password']) )
        {
            throw new InvalidArgumentException(self::ERROR_EMPTY_CREDENTIALS_PROVIDED);
        }

        $connection = new self();

        if ( !empty($params['timeout']) )
        {
            $connection->setTimeout((int) $params['timeout']);
        }

        if ( !$connection->connect(trim($params['host']), (!empty($params['port']) ? (int) $params['port'] : 21)) )
        {
            throw new LogicException(self::ERROR_CANT_CONNECT_TO_HOST);
        }

        if ( !$connection->login(trim($params['login']), trim($params['password'])) )
        {
            throw new LogicException(self::ERROR_INVALID_CREDENTIALS_PROVIDED);
        }

        $connection->init();

        return $connection;
    }

    private function isFtpRootDir( $path )
    {
        $this->chdir('/');
        $segments = array_filter(explode(DS, $path));
        foreach ( $segments as $segment )
        {
            if ( !@$this->chdir($segment) )
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function setTimeout( $timeout )
    {
        $this->timeout = (int) $timeout;
    }

    public function connect( $host, $port = 21 )
    {
        if ( is_resource($this->stream) )
        {
            return true;
        }

        $this->stream = ftp_connect($host, $port, $this->timeout);
        return (!empty($this->stream) && is_resource($this->stream));
    }

    public function isConnected()
    {
        return is_resource($this->stream);
    }

    public function login( $username, $password )
    {
        if ( ftp_login($this->stream, $username, $password) )
        {
            $this->loggedIn = true;
        }

        return $this->loggedIn;
    }

    public function isLoggedIn()
    {
        return $this->loggedIn;
    }

    public function pwd()
    {
        return getcwd();
    }

    public function chdir( $path )
    {
        $path = $this->getPath($path);
        return chdir($path);
    }

    public function rename( $fromPath, $toPath )
    {
        $fromPath = $this->getPath($fromPath);
        $toPath = $this->getPath($toPath);

        return OW::getStorage()->renameFile($fromPath, $toPath);
    }

    public function delete( $filePath )
    {
        $filePath = $this->getPath($filePath);

        return OW::getStorage()->removeFile($filePath);
    }

    public function rmDir( $dirPath )
    {
        OW::getStorage()->removeDir($dirPath);
    }

    public function mkDir( $dirPath )
    {
        $dirPath = $this->getPath($dirPath);
        $result = OW::getStorage()->mkdir($dirPath);
        if ( $result === false )
        {
            trigger_error("Can't create dir by FTP `" . $dirPath . "`", E_USER_WARNING);
        }
    }

    public function readDir( $dirPath)
    {
        $dirPath = $this->getPath($dirPath);

        $result = [];
        if (is_dir($dirPath)){
            if ($dh = opendir($dirPath)){
                while (($file = readdir($dh)) !== false){
                    $result[] = $file;
                }
                closedir($dh);
            }
        }
        return $result;
    }

    public function chmod( $mode, $filePath )
    {
        $filePath = $this->getPath($filePath);
        OW::getStorage()->chmod($filePath, $mode);
    }

    /***
     * @author Issa Annamoradnejad
     * to ensure accessibility of new files by UserGroup for all files and sub directories
     * @param $path
     * @param $dirs_mode
     * @param $files_mode
     */
    public function chmod_r($path, $dirs_mode, $files_mode) {
        try {
            $this->chmod($dirs_mode, $path);
            $dir = new DirectoryIterator($path);
            foreach ($dir as $item) {
                try {
                    if (!$item->isDot()) {
                        if ($item->isDir()) {
                            $this->chmod_r($item->getPathname(), $dirs_mode, $files_mode);
                        } else {
                            $this->chmod($files_mode, $item->getPathname());
                        }
                    }
                } catch (Exception $ex) {
                }
            }
        }catch (Exception $ex) {
        }
    }

    public function upload( $localFile, $remoteFile )
    {
        $remoteFile = $this->getPath($remoteFile);
        OW::getStorage()->copyFile($localFile, $remoteFile);
    }

    /**
     * Uploads LOCAL DIR CONTENTS to remote dir.
     * @param string $localDir
     * @param string $remoteDir
     * @param $dirMod
     * @param $fileMod
     *
     * @author Issa Annamoradnejad
     * Added support to set permissions of uploaded dir
     */
    public function uploadDir( $localDir, $remoteDir, $dirMod = false, $fileMod = false )
    {
        $remoteDir = $this->getPath($remoteDir);

        if ( !OW::getStorage()->fileExists($localDir) )
        {
            trigger_error("Can't read dir `" . $localDir . "`!", E_USER_WARNING);
            return;
        }

        if ( !OW::getStorage()->fileExists($remoteDir) )
        {
            $this->mkDir($remoteDir);
        }

        $handle = opendir($localDir);

        while ( ($item = readdir($handle)) !== false )
        {
            if ( $item === '.' || $item === '..' )
            {
                continue;
            }

            $localPath = $localDir . DS . $item;
            $localPath = str_replace('//', '/', $localPath);
            $remotePath = $remoteDir . DS . $item;
            $remotePath = str_replace('//', '/', $remotePath);

            if ( OW::getStorage()->isFile($localPath) )
            {
                $this->upload($localPath, $remotePath);
                if($fileMod != false) {
                    $this->chmod($fileMod, $remotePath);
                }
            }
            else
            {
                $this->uploadDir($localPath, $remotePath, $dirMod, $fileMod);
                if($dirMod != false){
                    $this->chmod($dirMod, $remotePath);
                }
            }
        }

        closedir($handle);
    }

    public function download( $remoteFile, $localFile )
    {
        $localFile = $this->getPath($localFile);
        $remoteFile = $this->getPath($remoteFile);
        OW::getStorage()->copyFile($remoteFile, $localFile);
    }

    public function __destruct()
    {
        ftp_close($this->stream);
    }

    private function getPath( $path )
    {
        if ( $this->ftpRootDir === null )
        {
            return $path;
        }

        if ( strpos($path, $this->ftpRootDir) !== 0 )
        {
            return $path;
        }

        if ( strlen($path) != strlen($this->ftpRootDir) )
        {
            $path = substr($path, strlen($this->ftpRootDir));
        }

        return $path;
    }
}
