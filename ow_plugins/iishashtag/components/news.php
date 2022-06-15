<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_CMP_News extends OW_Component
{

    public function __construct( array $idList , $page = 1)
    {
        parent::__construct();
        $hashtagService = IISHASHTAG_BOL_Service::getInstance();
        $entryService = EntryService::getInstance();

        $ids = array();
        foreach ($idList as $element)
            $ids[] = $element['id'];
        $entries = $entryService->findEntryListByIds($ids);

        //delete removed ids
        $existingEntityIds = array();
        foreach($entries as $key=>$dto){
            if (!$dto->isDraft()) {
                $existingEntityIds[] = $dto->id;
            }
        }
        if(count($idList)>count($existingEntityIds)){
            $deletedEntityIds = array();
            foreach($idList as $key=>$element){
                if(!in_array($element['id'], $existingEntityIds)){
                    $deletedEntityIds[] = $key;
                }
            }
            $hashtagService->deleteEntitiesByListIds($deletedEntityIds);
        }

        //paging
        $rpp = (int) OW::getConfig()->getValue('iisnews', 'results_per_page');
        $itemsCount = count($existingEntityIds);
        if($page>0 && $page<=ceil($itemsCount / $rpp)) {
            $paging = new BASE_CMP_Paging($page, ceil($itemsCount / $rpp), 5);
            $this->addComponent('paging', $paging);
            $first = $itemsCount - (($page - 1) * $rpp) - $rpp;
            $count = $rpp;
            if($first<0){
                $count = $count + $first;
                $first = 0;
            }
            $entries = array_slice($entries, $first, $count);
        }else{
            $entries = array();
        }


        $entries = $hashtagService->checkNewsItemsForDisplay($entries);
        $this->assign('list', $entries);
    }
}