<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iishashtag
 * @since 1.0
 */
class IISHASHTAG_MCMP_Newsfeed extends OW_MobileComponent
{

    public $driver = false;
    public function __construct( array $idList, $allCount )
    {
        parent::__construct();
        $hashtagService = IISHASHTAG_BOL_Service::getInstance();
        $itemsResult = $hashtagService->checkNewsfeedItemsForDisplay($idList);
        $existingEntityIds = $itemsResult['existingEntityIds'];
        $actionIdList = $itemsResult['actionIdList'];
        $allCount = count($existingEntityIds);

        $feedParams['displayCount'] = 20;

        $feedParams['displayCount'] = $feedParams['displayCount'] > 20
            ? 20
            : $feedParams['displayCount'];

        $feedParams['includeActionIdList'] = $actionIdList;
        $feedParams['viewMore'] = false;
        if(is_array($idList)){
            if(sizeof($idList)>20){
                $feedParams['viewMore'] = true;
            }
        }

        $feed = $this->createFeed('site', null);
        $feed->setDisplayType(NEWSFEED_CMP_Feed::DISPLAY_TYPE_ACTIVITY);

        //find viewable count
        $tmp = $feedParams['displayCount'];
        $feedParams['displayCount'] = 200;
        $feed->setup($feedParams);
        $this->driver->getActionList();
        $viewableCount = $this->driver->getActionCount();
        $countInfo = OW::getLanguage()->text('iishashtag', 'able_to_see_text', array('num'=>$viewableCount, 'all'=>$allCount));
        $this->assign('countInfo', $countInfo);
        $this->assign('isEmpty', $viewableCount==0);
        $feedParams['displayCount'] = $tmp;

        $feed->setup($feedParams);
        $this->addComponent('feed', $feed);
    }

    /**
     *
     * @param string $feedType
     * @param int $feedId
     * @return NEWSFEED_CMP_Feed
     */
    protected function createFeed( $feedType, $feedId )
    {
        $this->driver = OW::getClassInstance("IISHASHTAG_CLASS_NewsfeedDriver");

        return OW::getClassInstance("NEWSFEED_MCMP_Feed", $this->driver, $feedType, $feedId);
    }
}