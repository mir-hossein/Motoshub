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
 * @author Zarif Safiullin <zaph.saph@gmail.com>
 * @package ow_plugins.friends.controllers
 * @since 1.0
 */
class FRIENDS_MCTRL_List extends OW_MobileActionController
{
    private $usersPerPage;

    public function __construct()
    {
        parent::__construct();
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'base', 'users_main_menu_item');

        $this->setPageHeading(OW::getLanguage()->text('base', 'users_browse_page_heading'));
        $this->setPageTitle(OW::getLanguage()->text('base', 'users_browse_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_user');
        $this->usersPerPage = (int)OW::getConfig()->getValue('base', 'users_count_on_page');
    }

    public function index( $params )
    {
        $username = $params['user'];
        $menu = new BASE_MCMP_ContentMenu();
        $this->addComponent('menu', $menu);
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl().'mobile_user_list.js');

        $data = $this->getData( $username, array(), true, $this->usersPerPage );
        $cmp = new BASE_MCMP_BaseUserList('latest', $data, true);
        $this->addComponent('list', $cmp);
        $this->assign('listType', 'latest');

        OW::getDocument()->addOnloadScript("
            window.mobileUserList = new OW_UserList(".  json_encode(array(
                'component' => 'BASE_MCMP_BaseUserList',
                'listType' => 'latest',
                'excludeList' => $data,
                'node' => '.owm_user_list',
                'showOnline' => true,
                'count' => $this->usersPerPage,
                'responderUrl' => OW::getRouter()->urlForRoute('friends_user_lists_responder',array('user'=>$username))
            )).");
        ", 50);
    }

    public function responder( $params )
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }
        $username = $params['user'];

        $excludeList = empty($_POST['excludeList']) ? array() : $_POST['excludeList'];
        $showOnline = empty($_POST['showOnline']) ? false : $_POST['showOnline'];
        $count = empty($_POST['count']) ? $this->usersPerPage : (int)$_POST['count'];

        $data = $this->getData( $username, $excludeList, $showOnline, $count );

        echo json_encode($data);
        exit;
    }

    protected function getData( $username, $excludeList, $showOnline, $count )
    {
        $service = FRIENDS_BOL_Service::getInstance();
        $start = count($excludeList);

        $userId = BOL_UserService::getInstance()->findByUsername($username)->getId();

        $data = $service->findUserFriendsInList($userId, $start, $count);
        return ($data);
    }
}