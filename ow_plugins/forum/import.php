<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Forum plugin data import
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.forum
 * @since 1.0
 */
class FORUM_Import extends DATAIMPORTER_CLASS_Import
{
    public function import( $params )
    {
        $importDir = $params['importDir'];

        $txtFile = $importDir . 'configs.txt';
        
        // import configs
        if ( OW::getStorage()->fileExists($txtFile) )
        {
            $string = OW::getStorage()->fileGetContent($txtFile);
            $configs = json_decode($string, true);    
        }
        
        if ( !$configs )
        {
            return;
        }
        
        $attachmentService = FORUM_BOL_PostAttachmentService::getInstance();
        $attDir = OW::getPluginManager()->getPlugin('forum')->getUserFilesDir();
        
        $attachments = $attachmentService->findAllAttachments();
        
        if ( !$attachments )
        {
            return;
        }
        
        foreach ( $attachments as $file )
        {
            OW::getDbo()->query("SELECT 1 ");
            $ext = UTIL_File::getExtension($file->fileName);
            $path = $attachmentService->getAttachmentFilePath($file->id, $file->hash, $ext);
            $fileName = str_replace($attDir, '', $path);
            $content = OW::getStorage()->fileGetContent($configs['url'] . '/' . $fileName);
            if ( mb_strlen($content) )
            {
                OW::getStorage()->fileSetContent($path, $content);
            }
        }
    }
}