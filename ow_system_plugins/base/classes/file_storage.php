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
 * File Storage class
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_system_plugins.base.classes
 * @since 1.0
 */

class BASE_CLASS_FileStorage implements OW_Storage
{

    public function copyDir( $sourcePath, $destPath, array $fileTypes = null, $level = -1 )
    {
        if ( !$this->fileExists($destPath) )
        {
            $this->mkdir($destPath);
        }

        UTIL_File::copyDir($sourcePath, $destPath, $fileTypes, $level);
    }

    // $destPath - must be a file path ( not directory path )

    public function copyFile( $sourcePath, $destPath, $direct = false )
    {
        if ( $this->fileExists($sourcePath)  )
        {
            $checkAnotherExtensionEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_PHOTO_TEMPORARY_PATH_RETURN, array('source' => $sourcePath, 'destination' => $destPath)));
            if(isset($checkAnotherExtensionEvent->getData()['destination'])){
                $destPath = $checkAnotherExtensionEvent->getData()['destination'];
            }
            if($direct){
                @copy($sourcePath, $destPath);
            }else{
                copy($sourcePath, $destPath);
            }
            $this->chmod($destPath, 0666);
            return true;
        }

        return false;
    }

    public function copyFileToLocalFS( $destPath, $toFilePath )
    {
        return $this->copyFile($destPath, $toFilePath);
    }

    public function fileGetContent( $destPath, $direct = false, $use_include_path = false, $context = null )
    {
        if($direct){
            return @file_get_contents($destPath, $use_include_path, $context);
        }else{
            return file_get_contents($destPath, $use_include_path, $context);
        }
    }

    public function fileSetContent( $destPath, $conent, $direct = false )
    {
        if($direct){
            @file_put_contents($destPath, $conent);
        }else{
            file_put_contents($destPath, $conent);
        }
    }

    public function removeDir( $dirPath )
    {
        UTIL_File::removeDir($dirPath);
    }

    public function removeFile( $destPath, $direct = false )
    {
//        OW::getLogger()->writeLog(OW_Log::NOTICE, 'remove_file', ['actionType'=>OW_Log::DELETE, 'enType'=>'file', 'destPath' => $destPath]);

        if($direct){
            return @unlink($destPath);
        }else{
            return unlink($destPath);
        }
    }

    public function getFileNameList( $dirPath, $prefix = null, array $fileTypes = null )
    {
        $dirPath = UTIL_File::removeLastDS($dirPath);

        $resultList = array();

        $handle = opendir($dirPath);

        while ( ($item = readdir($handle)) !== false )
        {
            if ( $item === '.' || $item === '..' )
            {
                continue;
            }

            if ( $prefix != null )
            {
                $prefixLength = strlen($prefix);

                if ( !( $prefixLength <= strlen($item) && substr($item, 0, $prefixLength) === $prefix ) )
                {
                    continue;
                }
            }

            $path = $dirPath . DS . $item;

            if ( $fileTypes === null || $this->isFile($path) && in_array(UTIL_File::getExtension($item), $fileTypes) )
            {
                $resultList[] = $path;
            }
        }

        closedir($handle);

        return $resultList;
    }

    public function getFileUrl( $path, $returnPath = false )
    {
        if ( $path === null )
        {
            return '';
        }

        $url = '';

        $prefixLength = strlen(OW_DIR_ROOT);
        $filePathLength = strlen($path);

        if ( $prefixLength <= $filePathLength && substr($path, 0, $prefixLength) === OW_DIR_ROOT )
        {
            $url = str_replace(OW_DIR_ROOT, OW_URL_HOME, $path);
            $url = str_replace(DS, '/', $url);
        }


        $event = OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_BEFORE_GET_FILE_URL, array('url' => $url, 'path' => $path, 'returnPath' => $returnPath)));
        if(isset($event->getData()['url'])){
            return $event->getData()['url'];
        }

        return $url;
    }

    public function fileExists( $path )
    {
        if(!file_exists($path)){
            return $this->checkUrlFilePathExist($path);
        }

        return true;
    }

    public function isFile( $path )
    {
        if(!is_file($path)){
            return $this->checkUrlFilePathExist($path);
        }

        return true;
    }

    public function checkUrlFilePathExist($path){
        if( strpos(strtolower($path), 'http') !==0){
            return false;
        }
        $file_headers = @get_headers($path);
        if(isset($file_headers) && isset($file_headers[0]) && $file_headers[0] == 'HTTP/1.1 200 OK'){
            return true;
        }else{
            return false;
        }
    }

    public function isDir( $path )
    {
        return is_dir($path);
    }

    public function mkdir( $path , $direct = false)
    {
        if($direct){
            return @mkdir($path, 0777, true);
        }else{
            return mkdir($path, 0777, true);
        }
    }

    public function isWritable( $path )
    {
        return is_writable($path);
    }

    public function renameFile( $oldDestPath, $newDestPath )
    {
        if ( $this->isFile($oldDestPath) )
        {
            return rename($oldDestPath, $newDestPath);
        }

        return false;
    }


    public function chmod( $path, $permissions, $direct = false )
    {
        if (function_exists("posix_getuid") && posix_getuid() !== fileowner($path)) {
            return;
        }

        if($direct){
            @chmod($path, $permissions);
        }else{
            chmod($path, $permissions);
        }
    }

    public function moveFile ( $oldPath, $newPath, $direct = false ){
        return move_uploaded_file($oldPath, $newPath);
    }
}
?>
