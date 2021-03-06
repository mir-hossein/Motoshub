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
 * Photo list mobile component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.components
 * @since 1.6
 */
class PHOTO_MCMP_PhotoList extends OW_MobileComponent
{
    /**
     * @var PHOTO_BOL_PhotoService 
     */
    private $photoService;

    /**
     * @var PHOTO_BOL_PhotoAlbumService
     */
    private $photoAlbumService;

    public function __construct( $listType, $count, $exclude = null, $albumId = null )
    {
        parent::__construct();

        $this->photoService = PHOTO_BOL_PhotoService::getInstance();
        $this->photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();

        if ( $albumId )
        {
            $photos = $this->photoService->getAlbumPhotos($albumId, 1, $count, $exclude);
        }
        else
        {
            $photos = $this->photoService->findPhotoList($listType, 1, $count, $exclude, PHOTO_BOL_PhotoService::TYPE_PREVIEW);
        }

        $resultsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::GET_RESULT_FOR_LIST_ITEM_PHOTO, array('listtype' =>$listType,'page'=>1, 'photosPerPage'=>$count,'exclude'=>$exclude, PHOTO_BOL_PhotoService::TYPE_PREVIEW)));
        if(isset($resultsEvent->getData()['result'])){
            $photos= $resultsEvent->getData()['result'];
        }
        $this->assign('photos', $photos);

        foreach ( $photos as $photo )
        {
            array_push($exclude, $photo['id']);
        }

        if ( $albumId )
        {
            $loadMore = $this->photoAlbumService->countAlbumPhotos($albumId, $exclude);
        }
        else
        {
            $loadMore = $this->photoService->countPhotos($listType, FALSE, $exclude);
            $resultsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::GET_RESULT_FOR_LIST_ITEM_PHOTO, array('listtype' =>$listType,'page'=>1, 'photosPerPage'=>$count,'exclude'=>$exclude,PHOTO_BOL_PhotoService::TYPE_PREVIEW,'onlyCount'=>true)));
            if(isset($resultsEvent->getData()['count'])){
                $loadMore= $resultsEvent->getData()['count'];
            }
        }

        if ( !$loadMore )
        {
            $script = "OWM.trigger('photo.hide_load_more', {});";
            OW::getDocument()->addOnloadScript($script);
        }

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('photo')->getStaticJsUrl() . 'masonry.pkgd.min.js');
        $js = "
        var \$grid = $('.photo_section').masonry({
              itemSelector: '.owm_photo_list_item',
              originLeft: false,
              transitionDuration: '0s'
            });
        \$grid.masonry()
        setInterval(function(){ \$grid.masonry() }, 100);
        ";
        OW::getDocument()->addOnloadScript($js);
    }
}