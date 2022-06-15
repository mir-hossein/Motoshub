<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_CMP_Forum extends OW_Component
{

    public function __construct( array $idList, $allCount, $page = 1)
    {
        parent::__construct();

        $forumService = FORUM_BOL_ForumService::getInstance();
        $hashtagService = IISHASHTAG_BOL_Service::getInstance();
        $itemsResult = $hashtagService->checkForumItemsForDisplay($idList);
        $existingEntityIds = $itemsResult['existingEntityIds'];
        $postList = $itemsResult['postList'];
        $allCount = count($existingEntityIds);

        //paging
        $rpp = 10;
        $itemsCount = count($postList);
        if($page>0 && $page<=ceil($itemsCount / $rpp)) {
            $postList = array_reverse($postList);
            $paging = new BASE_CMP_Paging($page, ceil($itemsCount / $rpp), 5);
            $this->addComponent('paging', $paging);
            $first = ($page - 1) * $rpp;
            $count = $rpp;
            $postList = array_slice($postList, $first, $count);
        }else{
            $postList = array();
        }

        $countInfo = OW::getLanguage()->text('iishashtag', 'able_to_see_text', array('num'=>$itemsCount, 'all'=>$allCount));
        $this->assign('countInfo', $countInfo);

        //------
        $iteration = 0;
        $toolbars = array();
        $userIds = array();
        $postIds = array();
        foreach ( $postList as &$post )
        {
            $content = UTIL_HtmlTag::linkify($post['text']);
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $content)));
            if (isset($stringRenderer->getData()['string'])) {
                $content = ($stringRenderer->getData()['string']);
            }
            $post['text'] = $content;
            $post['permalink'] = $post['postUrl'];
            $post['number'] = ($page - 1) * $forumService->getPostPerPageConfig() + $iteration + 1;

            // get list of users
            if ( !in_array($post['userId'], $userIds) )
                $userIds[$post['userId']] = $post['userId'];

            $toolbar = array();
            $label = $forumService->getTopicInfo($post['topicId'])['title'];
            if(mb_strlen($label)>100)
                $label = mb_substr($label,0, 100) . '...';
            $label .= '<br />' .
                OW::getLanguage()->text('forum', 'toolbar_post_number', array("num"=>
                    FORUM_BOL_PostDao::getInstance()->findPostNumber($post['topicId'], $post['id'])
                ));
            array_push($toolbar, array('class' => 'post_permalink', 'href' => $post['permalink'], 'label' => $label));

            $toolbars[$post['id']] = $toolbar;

            if ( count($post['edited']) && !in_array($post['edited']['userId'], $userIds) )
                $userIds[$post['edited']['userId']] = $post['edited']['userId'];

            $iteration++;

            array_push($postIds, $post['id']);
        }

        //----assign

        $this->assign('postList', $postList);
        $this->assign('toolbars', $toolbars);
        $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars($userIds);
        $this->assign('avatars', $avatars);
    }
}