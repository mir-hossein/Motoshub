<?php

/**
 * iismainpage
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iismainpage
 * @since 1.0
 */
class IISMAINPAGE_MCMP_GroupList extends OW_MobileComponent
{
    protected $showOnline = true, $list = array();
    protected $listKey;

    public function __construct($listKey, $list, $showOnline)
    {
        parent::__construct();

        $this->list = $list;
        $this->showOnline = $showOnline;

        $this->setTemplate(OW::getPluginManager()->getPlugin('iismainpage')->getMobileCmpViewDir().'group_list.html');
    }


    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $this->process($this->list, $this->showOnline);
    }

    public function getContextMenu()
    {
        return null;
    }

    protected function process( $groupList, $showOnline )
    {
        $tplList = array();
        foreach ( $groupList as $group )
        {
            $id = null;
            if(isset($group['id'])){
                $id = $group['id'];
            }else{
                $id = $group;
            }
            $item = GROUPS_BOL_Service::getInstance()->findGroupById($id);
            if($item == null){
                continue;
            }
            $userCount = GROUPS_BOL_Service::getInstance()->findUserListCount($item->id);
            $title = strip_tags($item->title);

            $toolbar = array(
                array(
                    'label' => OW::getLanguage()->text('groups', 'listing_users_label', array(
                        'count' => $userCount
                    ))
                )
            );

            $tplList[] = array(
                'id' => $item->id,
                'url' => OW::getRouter()->urlForRoute('groups-view', array('groupId' => $item->id)),
                'title' => $title,
                'imageTitle' => $title,
                'content' => UTIL_String::truncate(strip_tags($item->description), 300, '...'),
                'time' => UTIL_DateTime::formatDate($item->timeStamp),
                'imageSrc' => GROUPS_BOL_Service::getInstance()->getGroupImageUrl($item),
                'users' => $userCount,
                'toolbar' => $toolbar
            );
        }
        $this->assign('list', $tplList);
    }
}