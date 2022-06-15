<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iismobilesupport.bol
 * @since 1.0
 */
class IISMOBILESUPPORT_BOL_WebServiceSearch
{
    private static $classInstance;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
    }

    public function search(){
        if(!IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iisadvancesearch', true)){
            return array();
        }

        $guestAccess = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkGuestAccess();
        if(!$guestAccess){
            return array('valid' => false, 'message' => 'guest_cant_view');
        }

        $searchValue = '';
        if(isset($_GET['searchValue'])){
            $searchValue = $_GET['searchValue'];
        }

        $searchValue = trim($searchValue);
        $searchValue = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($searchValue, true, true);

        $first = 0;
        $count = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->getPageSize();
        if(isset($_GET['first'])){
            $first = (int) $_GET['first'];
        }

        $resultData = array();
        $event = OW::getEventManager()->trigger(new OW_Event('iisadvancesearch.on_collect_search_items',
            array('q' => $searchValue, 'first' => $first, 'count' => $count), $resultData));
        $resultData = $event->getData();

        $resultData['posts'] = array();

        if (IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('newsfeed', true)) {
            if (IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->checkPluginActive('iishashtag', true)
                && !empty($searchValue)
            ) {
                $userActionIds = array();
                $groupActionIds = array();
                $userEntityIdsInfo = IISHASHTAG_BOL_Service::getInstance()->findEntitiesByTag($searchValue, "user-status");
                $userActionIdsInfo = IISHASHTAG_BOL_Service::getInstance()->checkNewsfeedItemsForDisplay($userEntityIdsInfo);
                if (isset($userActionIdsInfo['actionIdList'])) {
                    $userActionIds = $userActionIdsInfo['actionIdList'];
                }
                $groupActionIdsInfo = IISHASHTAG_BOL_Service::getInstance()->findEntitiesByTag($searchValue, "groups-feed");
                $groupActionIdsInfo = IISHASHTAG_BOL_Service::getInstance()->checkGroupItemsForDisplay($groupActionIdsInfo);
                foreach ($groupActionIdsInfo as $groupActionIdInfo) {
                    $groupActionIds[] = (int)$groupActionIdInfo['id'];
                }
                $allActionIds = array_merge($groupActionIds, $userActionIds);
                sort($allActionIds);
                $allActionIds = array_reverse($allActionIds);
                $slicedActionIds = array_slice($allActionIds, $first, $count);
                $slicedActions = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->findOrderedListByIdList($slicedActionIds);
                $resultData['posts'] = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->preparedActionsData($slicedActions);
            } else {
                $resultData['posts'] = IISMOBILESUPPORT_BOL_WebServiceNewsfeed::getInstance()->getSiteActionData($first, $count);
            }
        }

        foreach($resultData as $key => $value){
            $newData = array();
            if (isset($value['data'])) {
                $data = $value['data'];
                foreach ($data as $singleData) {
                    if(isset($singleData['title'])) {
                        $singleData['title'] = IISMOBILESUPPORT_BOL_WebServiceGeneral::getInstance()->stripString($singleData['title']);
                    }
                    if(isset($singleData['userId'])) {
                        $singleData['userId'] = (int) $singleData['userId'];
                        $singleData['user'] = IISMOBILESUPPORT_BOL_WebServiceUser::getInstance()->getUserInformationById($singleData['userId']);
                    }
                    if(isset($singleData['id'])) {
                        $singleData['id'] = (int) $singleData['id'];
                    }
                    $newData[] = $singleData;
                }
            }
            if ($key != 'posts') {
                $resultData[$key] = $newData;
            }
            if(OW::getConfig()->configExists('iisadvancesearch','search_allowed_'.$key)){
                $isAllowed = OW::getConfig()->getValue('iisadvancesearch','search_allowed_'.$key);
                if(!$isAllowed){
                    unset($resultData[$key]);
                }
            }
        }

        return array('searchedValue' => $searchValue, 'data'=>$resultData);
    }
}