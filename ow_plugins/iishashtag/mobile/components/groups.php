<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_MCMP_Groups extends OW_MobileComponent
{

    public function __construct( array $idList, $allCount, $page = 1)
    {
        parent::__construct();
        $hashtagService = IISHASHTAG_BOL_Service::getInstance();
        $idList = $hashtagService->checkGroupItemsForDisplay($idList);

        //paging
        $rpp = 10;
        $itemsCount = count($idList);
        if($page>0 && $page<=ceil($itemsCount / $rpp)) {
            $paging = new BASE_CMP_PagingMobile($page, ceil($itemsCount / $rpp), 5);
            $this->addComponent('paging', $paging);
            $first = ($page - 1) * $rpp;
            $count = $rpp;
            $idList = array_slice($idList, $first, $count);
        }else{
            $idList = array();
        }

        $countInfo = OW::getLanguage()->text('iishashtag', 'able_to_see_text', array('num'=>$itemsCount, 'all'=>$allCount));
        $this->assign('countInfo', $countInfo);

        $out = array();

        foreach ( $idList as $key=>$fullItem )
        {
            $item = $fullItem['obj'];
            /* @var $item GROUPS_BOL_Group */
            $url = OW::getRouter()->urlForRoute('groups-view', array('groupId' => $item->id));
            $userCount = GROUPS_BOL_Service::getInstance()->findUserListCount($item->id);

            $feedId = null;
            if($fullItem['type']=='groups-feed') {
                $feedId = $fullItem['id'];
            }
            if(isset($fullItem['context']) && ($fullItem['type']=='photo_comments' || $fullItem['type']=='multiple_photo_upload')) {
                $feedId = $fullItem['feed']->id;
            }
            if(isset($feedId)){
                $userId = $hashtagService->findUserIdByActionId($feedId);
                $toolbar = array(
                    array('label' => OW::getLanguage()->text('newsfeed','newsfeed_feed'), 'href' => OW::getRouter()->urlForRoute('newsfeed_view_item', array('actionId' => $feedId))),
                    array('label' => '-'),
                    array('label' => strip_tags($item->title), 'href' => $url)
                );
                $item->description = $hashtagService->findActionStatusByActionId($feedId);
                $title = BOL_UserService::getInstance()->getDisplayName($userId);
                $url = BOL_UserService::getInstance()->getUserUrl($userId);
                $imgSrc = BOL_AvatarService::getInstance()->getAvatarUrl($userId);
            }else{
                $title = strip_tags($item->title);
                $imgSrc = GROUPS_BOL_Service::getInstance()->getGroupImageUrl($item);
                $toolbar = array(
                    array(
                        'label' => OW::getLanguage()->text('groups', 'listing_users_label', array(
                            'count' => $userCount
                        ))
                    )
                );
            }

            $content = UTIL_String::truncate(strip_tags($item->description), 300, '...');
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $content)));
            if (isset($stringRenderer->getData()['string'])) {
                $content = ($stringRenderer->getData()['string']);
            }

            $out[$key] = array(
                'isFeed' => ($fullItem['type']=='groups-feed'),
                'feedId' => $fullItem['id'],
                'id' => $item->id,
                'url' => $url,
                'title' => $title,
                'imageTitle' => $title,
                'content' => $content,
                'time' => UTIL_DateTime::formatDate($item->timeStamp),
                'imageSrc' => $imgSrc,
                'users' => $userCount,
                'toolbar' => $toolbar
            );
        }

        $this->assign('list', $out);
    }
}