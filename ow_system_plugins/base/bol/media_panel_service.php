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
 * @author Aybat Duyshokov <duyshokov@gmail.com>
 * @package ow_system_plugins.base.bol
 * @since 1.0
 */
class BOL_MediaPanelService
{
    /*
     * @var BOL_MediaPanelFileDao
     */
    private $dao;

    /**
     * Constructor.
     *
     */
    protected function __construct()
    {
        $this->dao = BOL_MediaPanelFileDao::getInstance();
    }
    /**
     * Singleton instance.
     *
     * @var BOL_MediaPanelService
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return BOL_MediaPanelService
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function add( $plugin, $type, $userId, $data, $stamp=null )
    {
        $o = new BOL_MediaPanelFile();

        $this->dao->save(
                $o->setPlugin($plugin)
                ->setType($type)
                ->setUserId($userId)
                ->setData($data)
                ->setStamp(empty($stamp) ? time() : $stamp)
        );

        return $o->getId();
    }

    public function findGalleryImages( $plugin, $userId=null, $first, $count )
    {
        return $this->dao->findImages($plugin, $userId, $first, $count);
    }

    public function findImage( $imageId )
    {
        return $this->dao->findImage($imageId);
    }

    public function countGalleryImages( $plugin, $userId=null )
    {
        return $this->dao->countGalleryImages($plugin, $userId);
    }

    public function deleteImages( $plugin, $count )
    {
        $this->dao->deleteImages($plugin, $count);
    }
    
    public function deleteById($id)
    {
    	$this->dao->deleteImageById($id);
    }
    
    public function findAll()
    {
         return $this->dao->findAll();       
    }

    public function deleteImagesByUserId($userId)
    {
        return $this->dao->deleteImagesByUserId($userId);
    }
    public function initMenu($params)
    {
        $language = OW::getLanguage();
        $router = OW::getRouter();

        $menu = new BASE_CMP_ContentMenu();

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('base', 'upload'));
        $item->setOrder(0);
        $item->setKey('upload');
        $item->setUrl($router->urlFor('BASE_CTRL_MediaPanel', 'index', $params));
        $menu->addElement($item);

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('base', 'tf_img_from_url'));
        $item->setOrder(1);
        $item->setKey('url');
        $item->setUrl($router->urlFor('BASE_CTRL_MediaPanel', 'fromUrl', $params));
        $menu->addElement($item);

        $count = BOL_MediaPanelService::getInstance()->countGalleryImages($params['pluginKey'], OW::getUser()->getId());

        $item = new BASE_MenuItem();
        $item->setLabel($language->text('base', 'tf_img_gal') . ($count == 0 ? '' : " ({$count})" ));
        $item->setOrder(1);
        $item->setKey('gallery');
        $item->setUrl($router->urlFor('BASE_CTRL_MediaPanel', 'gallery', $params));
        $menu->addElement($item);

        $event = new OW_Event('media.panel.init.menu',array('menu' => $menu,'id'=>$params['id'],'pluginKey'=>$params['pluginKey']));
        OW::getEventManager()->trigger($event);

        return $menu;
    }
}