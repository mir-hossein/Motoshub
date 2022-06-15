<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>,
 * @package ow_plugins.base
 * @since 1.0
 */
class BASE_CMP_UserInfo extends OW_Component
{
    public function __construct( $username )
    {
        parent::__construct();

        $userService = BOL_UserService::getInstance();
        $user = $userService->findByUsername($username);
        $userData = array(
            'username'=>$username,
            'fullname'=>$userService->getDisplayName($user->id),
            'email'=>$user->email,
            'url'=>$userService->getUserUrl($user->id),
            'avatarUrl'=>BOL_AvatarService::getInstance()->getAvatarUrl($user->id)
        );

        $this->assign('userData', $userData);
    }
}