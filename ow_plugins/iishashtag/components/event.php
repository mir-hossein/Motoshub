<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_CMP_Event extends OW_Component
{

    public function __construct( array $idList, $allCount, $page = 1)
    {
        parent::__construct();

        $eventService = EVENT_BOL_EventService::getInstance();
        $ids = array();
        foreach ($idList as $element)
            $ids[] = $element['id'];
        $events = $eventService->findEventsWithIds($ids, 1, 500, true);

        $existingEvent = $eventService->findByIdList($ids);
        //delete removed ids
        $existingEntityIds = array();
        foreach($existingEvent as $item){
            $existingEntityIds[] = $item->id;
        }
        if(count($idList)>count($existingEntityIds)){
            $deletedEntityIds = array();
            foreach($idList as $key=>$element){
                if(!in_array($element['id'], $existingEntityIds)){
                    if($eventService->findEvent($element['id']) == null ) {
                        $deletedEntityIds[] = $key;
                    }
                }
            }
            IISHASHTAG_BOL_Service::getInstance()->deleteEntitiesByListIds($deletedEntityIds);
        }
        $allCount = count($existingEntityIds);

        //paging
        $rpp = 10;
        foreach($events as $item){
            $visibleEntityIds[] = $item->id;
        }
        $itemsCount = count($visibleEntityIds);
        if($page>0 && $page<=ceil($itemsCount / $rpp)) {
            $paging = new BASE_CMP_Paging($page, ceil($itemsCount / $rpp), 5);
            $this->addComponent('paging', $paging);
            $first = ($page - 1) * $rpp;
            $count = $rpp;
            $events = array_slice($events, $first, $count);
        }else{
            $events = array();
        }

        $countInfo = OW::getLanguage()->text('iishashtag', 'able_to_see_text', array('num'=>$itemsCount, 'all'=>$allCount));
        $this->assign('countInfo', $countInfo);


        $events = $eventService->getListingDataWithToolbar($events);
        foreach($events as $key => $item){
            $stringRenderer = OW::getEventManager()->trigger(new OW_Event('hashtag.before_render_string', array('string' => $item['content'])));
            if (isset($stringRenderer->getData()['string'])) {
                $events[$key]['content'] = ($stringRenderer->getData()['string']);
                if($item['showMore']){
                    $events[$key]['content']=$events[$key]['content'].'<a class="ow_lbutton" href=" ' . $item['eventUrl'] . ' "> ' . OW::getLanguage()->text('base' ,'more' ) . '</a>';
                }
            }
        }
        $this->assign('events', $events);

        if ( sizeof($idList) > sizeof($events) )
        {
            $toolbarArray = array(array('href' => OW::getRouter()->urlForRoute('event.view_event_list', array('list' => 'latest')), 'label' => OW::getLanguage()->text('event', 'view_all_label')));
            $this->assign('toolbar', $toolbarArray);
        }
    }
}