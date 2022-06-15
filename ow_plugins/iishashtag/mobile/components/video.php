<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_MCMP_Video extends OW_MobileComponent
{

    public function __construct( array $idList, $allCount, $page = 1)
    {
        parent::__construct();

        $ids = array();
        foreach ($idList as $element)
            $ids[] = $element['id'];
        $clipObject = VIDEO_BOL_ClipDao::getInstance()->getClipsList('latest', 1, 500, $ids);

        //delete removed ids
        $existingEntityIds = array();
        $existingClip = VIDEO_BOL_ClipDao::getInstance()->findByIdList($ids);
        foreach($existingClip as $item){
            $existingEntityIds[] = $item->id;
        }
        if(count($idList)>count($existingEntityIds)){
            $deletedEntityIds = array();
            $videoService = VIDEO_BOL_ClipService::getInstance();
            foreach($idList as $key=>$item){
                if(!in_array($item['id'], $existingEntityIds)) {
                    if ($videoService -> findClipById($item['id']) == null) {
                        $deletedEntityIds[] = $key;
                    }
                }
            }
            IISHASHTAG_BOL_Service::getInstance()->deleteEntitiesByListIds($deletedEntityIds);
        }
        $visibleEntityIds = array();
        foreach($clipObject as $item){
            $visibleEntityIds[] = $item->id;
        }
        //paging
        $itemsCount = count($visibleEntityIds);
        $rpp = VIDEO_BOL_ClipService::getInstance()->getClipPerPageConfig();
        if($page>0 && $page<=ceil($itemsCount / $rpp)) {
            $paging = new BASE_CMP_PagingMobile($page, ceil($itemsCount / $rpp), 5);
            $this->addComponent('paging', $paging);
            $first = $itemsCount - (($page - 1) * $rpp) - $rpp;
            $count = $rpp;
            if($first<0){
                $count = $count + $first;
                $first = 0;
            }
            $clipObject = array_slice($clipObject, $first, $count);
        }else{
            $clipObject = array();
        }

        $countInfo = OW::getLanguage()->text('iishashtag', 'able_to_see_text', array('num'=>$itemsCount, 'all'=>$allCount));
        $this->assign('countInfo', $countInfo);

        $clips = array();
        if ( is_array($clipObject) )
        {
            foreach ( $clipObject as $key => $clip )
            {
                $clip = (array) $clip;
                $clips[$key] = $clip;
                $clips[$key]['thumb'] = VIDEO_BOL_ClipService::getInstance()->getClipThumbUrl($clip['id'], $clip['code'], $clip['thumbUrl']);
                $clips[$key]['username'] = BOL_UserService::getInstance()->getUserName($clip['userId']);
                if(isset( $clips[$key]['description'])) {
                    $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $clips[$key]['description'])));
                    if(isset($stringRenderer))
                        $clips[$key]['description']=$stringRenderer->getData()['string'];
                }
            }
        }
        $event = new OW_Event('videplus.on.video.list.view.render', array('clips'=>$clips));
        OW::getEventManager()->trigger($event);
        if(isset($event->getData()['clips'])){
            $clips=$event->getData()['clips'];
        }
        $this->assign('clips', $clips);
        $this->assign('count', count($clips));
    }
}