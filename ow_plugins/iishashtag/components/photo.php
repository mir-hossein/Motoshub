<?php

/**
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_CMP_Photo extends OW_Component
{

    public function __construct($tag, $allCount)
    {
        parent::__construct();
        $params = array('type' => 'latest');
        $plugin = OW::getPluginManager()->getPlugin('photo');

        $hasSideBar = OW::getThemeManager()->getCurrentTheme()->getDto()->getSidebarPosition() != 'none';
        $photoParams = array(
            'classicMode' => (bool)OW::getConfig()->getValue('photo', 'photo_list_view_classic')
        );
        $contParams = array(
            'isClassic' => $photoParams['classicMode'],
            'isModerator' => OW::getUser()->isAuthorized('photo')
        );

        $photoParams['level'] = ($photoParams['classicMode'] ? ($hasSideBar ? 4 : 5) : 4);

        $photoDefault = array(
            'getPhotoURL' => OW::getRouter()->urlFor('IISHASHTAG_CTRL_Load', 'ajaxResponder', array('tag' => $tag)),
            'listType' => $params['type'],
            'rateUserId' => OW::getUser()->getId(),
            'urlHome' => OW_URL_HOME,
            'tagUrl' => OW::getRouter()->urlForRoute('view_tagged_photo_list', array('tag' => '-tag-'))
        );

        $contDefault = array(
            'downloadAccept' => (bool)OW::getConfig()->getValue('photo', 'download_accept'),
            'downloadUrl' => OW_URL_HOME . 'photo/download-photo/:id',
            'actionUrl' => $photoDefault['getPhotoURL'],
            'listType' => $params['type']
        );

        $document = OW::getDocument();

        $document->addScriptDeclarationBeforeIncludes(
            UTIL_JsGenerator::composeJsString(';window.browsePhotoParams = Object.freeze({$params});', array(
                'params' => array_merge($photoDefault, $photoParams)
            ))
        );
        $document->addOnloadScript(';window.browsePhoto.init();');
        $document->addScriptDeclarationBeforeIncludes(
            UTIL_JsGenerator::composeJsString(';window.photoContextActionParams = Object.freeze({$params});', array(
                'params' => array_merge($contDefault, $contParams)
            ))
        );
        $document->addOnloadScript(';window.photoContextAction.init();');
        $this->assign('isClassicMode', $photoParams['classicMode']);
        $this->assign('hasSideBar', $hasSideBar);
        $this->assign('type', $params['type']);
        $document->addStyleSheet($plugin->getStaticCssUrl() . 'browse_photo.css');
        $document->addScript($plugin->getStaticJsUrl() . 'utils.js');
        $document->addScript($plugin->getStaticJsUrl() . 'browse_photo.js');

        $language = OW::getLanguage();
        if ( $params['type'] != 'albums' )
        {
            $event = new OW_Event(PHOTO_CLASS_EventHandler::EVENT_INIT_FLOATBOX, $photoParams);
            OW::getEventManager()->trigger($event);

            $language->addKeyForJs('photo', 'tb_edit_photo');
            $language->addKeyForJs('photo', 'confirm_delete');
            $language->addKeyForJs('photo', 'mark_featured');
            $language->addKeyForJs('photo', 'remove_from_featured');
            $language->addKeyForJs('photo', 'no_photo');
            $language->addKeyForJs('photo', 'rating_total');
            $language->addKeyForJs('photo', 'rating_your');
            $language->addKeyForJs('base', 'rate_cmp_owner_cant_rate_error_message');
            $language->addKeyForJs('photo', 'download_photo');
            $language->addKeyForJs('photo', 'delete_photo');
            $language->addKeyForJs('photo', 'save_as_avatar');
            $language->addKeyForJs('photo', 'save_as_cover');
            $language->addKeyForJs('photo', 'search_invitation');
            $language->addKeyForJs('photo', 'set_as_album_cover');
            $language->addKeyForJs('photo', 'search_result_empty');
        }
        else
        {
            $language->addKeyForJs('photo', 'edit_album');
            $language->addKeyForJs('photo', 'delete_album');
            $language->addKeyForJs('photo', 'album_delete_not_allowed');
            $language->addKeyForJs('photo', 'newsfeed_album');
            $language->addKeyForJs('photo', 'are_you_sure');
            $language->addKeyForJs('photo', 'album_description');
        }

        $language->addKeyForJs('photo', 'no_items');

        //detect viewable count
        $idList = IISHASHTAG_BOL_Service::getInstance()->findEntitiesByTag($tag,"photo_comments");
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

    }
}