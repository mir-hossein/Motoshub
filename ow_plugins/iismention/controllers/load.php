<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismention
 * @since 1.0
 */
class IISMENTION_CTRL_Load extends OW_ActionController
{

    public function __construct()
    {
    }

    /***
     * @param $params
     * @throws AuthenticateException
     */
    public function loadUsernames($params){
        if (!OW::getUser()->isAuthenticated()) {
            throw new AuthenticateException();
        }

        $username = false;
        if(isset($params['username']))
        {
            $username = urldecode($params['username']);
        }

        try {
            //sample
            $data = array();
            $data[] = array('username'=>'imoradnejad', 'fullname'=>'Issa Moradnejad');

            //actual
            $max_count = OW::getConfig()->getValue('iismention','max_count');

            $userPrioritizedIds = $this->findPrioritizedUsers($username, $max_count);
            //print_r($userFriendsIds);

            $data = $this->getUserInfoForUserIdList(array_unique($userPrioritizedIds));

            exit(json_encode($data));
        }catch(Exception $e){
            exit(json_encode(array('status'=>'error','error_msg'=>OW::getLanguage()->text('base','comment_add_post_error'))));
        }
    }

    /***
     * @param $kw
     * @param null $limit
     * @return array
     */
    public function findPrioritizedUsers( $kw, $limit = null)
    {
        if(!OW::getPluginManager()->isPluginActive('friends'))
            return array();

        $userId = OW::getUser()->getId();
        $limitStr = $limit === null ? '' : 'LIMIT 0, ' . intval($limit*2);

        //SELECT FROM FRIENDS
        $query = "SELECT DISTINCT ".OW_DB_PREFIX."base_user.id
            FROM ".OW_DB_PREFIX."base_user join ".OW_DB_PREFIX."base_question_data on ".OW_DB_PREFIX."base_user.id=".OW_DB_PREFIX."base_question_data.userId
            WHERE ".OW_DB_PREFIX."base_user.id IN (
                SELECT DISTINCT userId
                FROM ".OW_DB_PREFIX."friends_friendship
                WHERE friendId=".$userId." AND status='active'
                UNION
                SELECT DISTINCT friendId
                FROM ".OW_DB_PREFIX."friends_friendship
                WHERE userId=".$userId." AND status='active'
            )
            AND (username like :kw  or( questionName='realname' and  textValue like :kw )) ". $limitStr;;

        $all_users = OW::getDbo()->queryForColumnList($query, array( 'kw' =>  '%'.$kw . '%'  ));
        return $all_users;

        //checking privacy
        $privacy_values = PRIVACY_BOL_ActionService::getInstance()->getActionValueListByUserIdList(array('iismention_search_my_username'),$all_users);
        $result = array();
        foreach($privacy_values as $key => $value){
            $privacy_setting = $value['iismention_search_my_username'];
            if($userId == $key) {
                $result[] = $key;
            }
            if (in_array($privacy_setting, array('everybody', 'friends_only')))
                $result[] = $key;
        }
        return $result;
    }

    /***
     * @param $kw
     * @param null $limit
     * @return type
     */
    public function findUsers( $kw, $limit = null )
    {
        $limitStr = $limit === null ? '' : 'LIMIT 0, ' . intval($limit);
        $query = "SELECT DISTINCT id FROM ".OW_DB_PREFIX."base_user WHERE username like :kw ". $limitStr;
        $all_users = OW::getDbo()->queryForColumnList($query, array( 'kw' => '%'. $kw . '%'  ));
        return $all_users;
    }

    /***
     * @param null $limit
     * @return type
     */
    public function findFriends($limit = null )
    {
        $userId = OW::getUser()->getId();
        $limitStr = $limit === null ? '' : 'LIMIT 0, ' . intval($limit);

        //SELECT FROM FRIENDS
        $query = "SELECT DISTINCT id
            FROM ".OW_DB_PREFIX."base_user
            WHERE id IN (
                SELECT DISTINCT userId
                FROM ".OW_DB_PREFIX."friends_friendship
                WHERE friendId=".$userId."
                UNION
                SELECT DISTINCT friendId
                FROM ".OW_DB_PREFIX."friends_friendship
                WHERE userId=".$userId."
            )
            ". $limitStr;

        $all_users = OW::getDbo()->queryForColumnList($query, array());
        return $all_users;
    }

    /***
     * @param $userIdList
     * @return array
     */
    public function getUserInfoForUserIdList( $userIdList )
    {
        if (empty($userIdList))
        {
            return array();
        }

        $userInfoList = array();
        $userNameByUserIdList = BOL_UserService::getInstance()->getUserNamesForList($userIdList);
        $displayNameByUserIdList = BOL_UserService::getInstance()->getDisplayNamesForList($userIdList);
        foreach ($userIdList as $opponentId)
        {
            $info = array(
                'username' => $userNameByUserIdList[$opponentId],
                'fullname' => $displayNameByUserIdList[$opponentId]
            );
            $userInfoList[] = $info;
        }
        return $userInfoList;
    }
}

