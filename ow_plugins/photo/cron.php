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
 * Photo cron job.
 *
 * @authors Egor Bulgakov <egor.bulgakov@gmail.com>, Kairat Bakitow <kainisoft@gmail.com>
 * @package ow.ow_plugins.photo
 * @since 1.0
 */
class PHOTO_Cron extends OW_Cron
{
    const ALBUMS_DELETE_LIMIT = 10;
    
    public function __construct()
    {
        parent::__construct();

        $this->addJob('albumsDeleteProcess');
        $this->addJob('contentIndexing');
        $this->addJob('cleareCache', 10);
        $this->addJob('deleteLimitedPhotos', 180);
    }

    public function run()
    {
        
    }

    public function albumsDeleteProcess()
    {
        $config = OW::getConfig();
        
        // check if uninstall is in progress
        if ( !$config->getValue('photo', 'uninstall_inprogress') )
        {
            return;
        }
        
        // check if cron queue is not busy
        if ( $config->getValue('photo', 'uninstall_cron_busy') )
        {
            return;
        }
        
        $config->saveConfig('photo', 'uninstall_cron_busy', 1);
        
        $albumService = PHOTO_BOL_PhotoAlbumService::getInstance();
        
        try
        {
            $albumService->deleteAlbums(self::ALBUMS_DELETE_LIMIT);
        }
        catch ( Exception $e )
        {
            OW::getLogger()->addEntry(json_encode($e));
        }

        $config->saveConfig('photo', 'uninstall_cron_busy', 0);
        
        if ( !$albumService->countAlbums() ) 
        {
            BOL_PluginService::getInstance()->uninstall('photo');
            $config->saveConfig('photo', 'uninstall_inprogress', 0);

            PHOTO_BOL_PhotoService::getInstance()->setMaintenanceMode(false);
        } else {
            OW::getEventManager()->trigger(new OW_Event(PHOTO_BOL_PhotoService::EVENT_UNINSTALL_IN_PROGRESS));
        }
    }
    
    public function cleareCache()
    {
        PHOTO_BOL_PhotoCacheDao::getInstance()->cleareCache();
    }
    
    public function deleteLimitedPhotos()
    {
        PHOTO_BOL_PhotoTemporaryService::getInstance()->deleteLimitedPhotos();
    }

    public function contentIndexing()
    {
        PHOTO_BOL_SearchService::getInstance()->contentIndexing();
    }
}
