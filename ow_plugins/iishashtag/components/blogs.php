<?php

/**
 * @author Hamed Salimian <hamed.salimian94@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_CMP_Blogs extends OW_Component
{
    public function __construct( array $idList , $page = 1)
    {
        parent::__construct();
        $hashtagService = IISHASHTAG_BOL_Service::getInstance();
        $postService = PostService::getInstance();

        $ids = array();
        foreach ($idList as $element)
            $ids[] = $element['id'];
        $posts = $postService->findPostListByIds($ids);

        //delete removed ids
        $existingPostIds = array();
        foreach($posts as $key=>$dto){
            if (!$dto->isDraft()) {
                $existingPostIds[] = $dto->id;
            }
        }
        if(count($idList)>count($existingPostIds)){
            $deletedPostIds = array();
            foreach($idList as $key=>$element){
                if(!in_array($element['id'], $existingPostIds)){
                    $deletedPostIds[] = $key;
                }
            }
            $hashtagService->deleteEntitiesByListIds($deletedPostIds);
        }

        //paging
        $rpp = (int) OW::getConfig()->getValue('blogs', 'results_per_page');
        $itemsCount = count($existingPostIds);
        if($page>0 && $page<=ceil($itemsCount / $rpp)) {
            $paging = new BASE_CMP_Paging($page, ceil($itemsCount / $rpp), 5);
            $this->addComponent('paging', $paging);
            $first = $itemsCount - (($page - 1) * $rpp) - $rpp;
            $count = $rpp;
            if($first<0){
                $count = $count + $first;
                $first = 0;
            }
            $posts = array_slice($posts, $first, $count);
        }else{
            $posts = array();
        }


        $posts = $hashtagService->checkBlogPostsForDisplay($posts);
        $this->assign('list', $posts);
    }
}