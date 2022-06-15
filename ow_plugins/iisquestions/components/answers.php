<?php

/**
 * Created by PhpStorm.
 * User: Seyed Ismail Mirvakili
 * Date: 2/26/18
 * Time: 1:10 PM
 */
class IISQUESTIONS_CMP_Answers extends OW_Component
{
    const AVATARS_THRESHOLD = 3;
    /**
     * Constructor.
     *
     * @param array $idList
     * @param $totalCount
     */
    public function __construct( array $idList, $totalCount )
    {
        parent::__construct();

        $userId = OW::getUser()->getId();
        $hiddenUser = false;
        if ( $userId && !in_array($userId, $idList) )
        {
            $hiddenUser = $userId;
            $idList[] = $userId;
        }

        $users = BOL_AvatarService::getInstance()->getDataForUserAvatars($idList, true, true, true, false);

        if ($hiddenUser)
        {
            $users[$hiddenUser]['id'] = $hiddenUser;
            $this->assign('hiddenUser', $users[$hiddenUser]);
            unset($users[$hiddenUser]);
        }

        $count = count($users);
        $otherCount = $totalCount - ($count > self::AVATARS_THRESHOLD ? self::AVATARS_THRESHOLD : $count);
        $otherCount = $otherCount < 0 ? 0 : $otherCount;

        $title = OW::getLanguage()->text('iisquestions','more_users_title',array('count'=>$otherCount));
        $this->assign('title', $title);

        $this->assign('otherCount', $otherCount);

        $this->assign('users', $users);
        $userIds = array();
        foreach ($idList as $item)
            $userIds[] = (int) $item;
        $showUsers = 'javascript: OW.showUsers('.json_encode($userIds).')';
        $this->assign('userIds', $showUsers);

        $staticUrl = OW::getPluginManager()->getPlugin('iisquestions')->getStaticUrl();
        $this->assign('staticUrl', $staticUrl);
    }
}