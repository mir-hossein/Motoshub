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
 * Console section component
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.ow_plugins.notifications.mobile.components
 * @since 1.6.0
 */
class NOTIFICATIONS_MCMP_ConsoleSection extends OW_MobileComponent
{
    /**
     * @var NOTIFICATIONS_BOL_Service
     */
    private $service;

    const SECTION_ITEMS_LIMIT = 20;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->service = NOTIFICATIONS_BOL_Service::getInstance();
        $count = $this->service->findNotificationCount(OW::getUser()->getId());

        if ( !$count )
        {
            $this->setVisible(false);
        }
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $limit = self::SECTION_ITEMS_LIMIT;
        $this->addComponent('itemsCmp', new NOTIFICATIONS_MCMP_ConsoleItems($limit));
        $this->assign('loadMore', $this->service->findNotificationCount(OW::getUser()->getId()) > $limit);

        //Issa Annamoradnejad
        //added button to "view all"
        if (OW::getRequest()->isAjax()) {
            OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('notifications')->getStaticUrl() . 'notification.css');
            $plugin = BOL_PluginDao::getInstance()->findPluginByKey('iismainpage');
            if(isset($plugin) && $plugin->isActive() && !IISMAINPAGE_BOL_Service::getInstance()->isDisabled('notifications')) {
                $viewAll = array(
                    'label' => OW::getLanguage()->text('notifications', 'view_all'),
                    'url' => OW::getRouter()->urlForRoute('iismainpage.notifications')
                );
                $this->assign('viewAll', $viewAll);
            }else {
                $plugin = BOL_PluginDao::getInstance()->findPluginByKey('iismobilesupport');
                if(isset($plugin) && $plugin->isActive()) {
                    $viewAll = array(
                        'label' => OW::getLanguage()->text('notifications', 'view_all'),
                        'url' => OW::getRouter()->urlForRoute('iismobilesupport-notifications')
                    );
                    $this->assign('viewAll', $viewAll);
                }
            }
        }

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('notifications')->getStaticUrl() . 'mobile.js');

        $params = array('limit' => $limit);
        $script = 'var notificationsConsole = new OWM_NotificationsConsole(' . json_encode($params) . ');';

        OW::getDocument()->addOnloadScript($script);
    }
}