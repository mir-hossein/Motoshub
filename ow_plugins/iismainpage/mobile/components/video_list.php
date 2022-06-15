<?php

class IISMAINPAGE_MCMP_VideoList extends OW_MobileComponent
{

    protected $list = array();

    public function __construct( $listType, $videos )
    {
        parent::__construct();

        $this->list = $videos;
        $this->setTemplate(OW::getPluginManager()->getPlugin('iismainpage')->getMobileCmpViewDir().'video_list.html');

        $event = new OW_Event('videplus.on.video.list.view.render', array('clips'=>$this->list));
        OW::getEventManager()->trigger($event);
        if(isset($event->getData()['clips'])){
            $this->list=$event->getData()['clips'];
        }

        if ( $this->list )
        {
            $tagsLabel = array();
            $this->assign('no_content', null);

            $this->assign('clips', $this->list);

            $userIds = array();
            if ( is_array($this->list) ) {
                foreach ($this->list as $clip) {
                    $clip = (array)$clip;
                    $tags = BOL_TagService::getInstance()->findEntityTags($clip['id'],'video');
                    if(sizeof($tags)>0){
                        $labels = " ";
                        $comma = OW::getLanguage()->text('base', 'comma').' ';
                        foreach($tags as $tag)
                        {
                            $labels .= '<a href="'.OW::getRouter()->urlForRoute('view_tagged_list', array('tag' => $tag->getLabel())).'">'.$tag->getLabel().'</a>'.$comma;
                        }
                        $labels = rtrim($labels, $comma);
                        $tagsLabel[$clip['id']]=$labels;
                    }
                    if (!in_array($clip['userId'], $userIds))
                        array_push($userIds, $clip['userId']);
                }
            }
            $this->assign('tags', $tagsLabel);

            $names = BOL_UserService::getInstance()->getDisplayNamesForList($userIds);
            $this->assign('displayNames', $names);
            $usernames = BOL_UserService::getInstance()->getUserNamesForList($userIds);
            $this->assign('usernames', $usernames);

        }
        else
        {
            $this->assign('no_content', OW::getLanguage()->text('video', 'no_video_found'));
        }
    }
}



