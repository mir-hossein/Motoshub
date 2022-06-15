<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_MCMP_Photo extends OW_MobileComponent
{

    public function __construct($tag, $allCount)
    {
        parent::__construct();
        $service = IISHASHTAG_BOL_Service::getInstance();
        $idList = $service->findEntitiesByTag($tag,"photo_comments");

        //delete removed ids
        $existingEntityIds = array();
        foreach($idList as $item){
            $existingEntityIds[] = $item['id'];
        }
        if(count($idList)>count($existingEntityIds)){
            $deletedEntityIds = array();
            $photoService=PHOTO_BOL_PhotoService::getInstance();
            foreach($idList as $key=>$id){
                $id = $id['id'];
                if(!in_array($id, $existingEntityIds)){
                    if($photoService ->findPhotoById($id) == null ) {
                        $deletedEntityIds[] = $key;
                    }
                }
            }
            IISHASHTAG_BOL_Service::getInstance()->deleteEntitiesByListIds($deletedEntityIds);
        }
        $allCount = count($existingEntityIds);

        //detect viewable count
        $itemsCount = 0;
        if(is_array($idList) && sizeof($idList)>0) {
            $ids = array();
            foreach ($idList as $element)
                $ids[] = $element['id'];
            $photoObjects = PHOTO_BOL_PhotoDao::getInstance()->getPhotoList('latest', 0, 200, null, false, $ids);
            $itemsCount = count($photoObjects);
        }

        $countInfo = OW::getLanguage()->text('iishashtag', 'able_to_see_text', array('num'=>$itemsCount, 'all'=>$allCount));
        $this->assign('countInfo', $countInfo);

        //paging
        $count = 12;
        $this->assign('isEmpty', $itemsCount==0);

        $initialCmp = new IISHASHTAG_MCMP_PhotoList($tag, $count, array());
        $this->addComponent('photos', $initialCmp);

        $this->assign('loadMore', count($idList) > $count);

        $script = '
        OWM.bind("photo.hide_load_more", function(){
            $("#btn-photo-load-more").hide();
        });

        self.loadMore = true;
        $(document).bind(\'scroll\', function() {
            var diff = $(document).height() - ($(window).scrollTop() + $(window).height());
            if ( diff < 100 && self.loadMore )
            {
                loadMorePhotosInHashtagPage();
                self.loadMore = false;
            }
        });

        function loadMorePhotosInHashtagPage(){
            var exclude = $("div.owm_photo_list_item").map(function(){ return $(this).data("ref"); }).get();
            OWM.loadComponent(
                "IISHASHTAG_MCMP_PhotoList",
                {tag: "' . $tag . '", count:' . $count . ', exclude: exclude},
                {
                    onReady: function(html){
                        $("#photo-list-cont").append(html);
                        if (html[0].childElementCount === 0){
                            $("#btn-photo-load-more").hide();
                        }
                        else{
                            self.loadMore = true;
                        }
                    }
                }
            );
        }';

        OW::getDocument()->addOnloadScript($script);
    }
}