<?php
class IISPHOTOPLUS_CMP_PhotoSelectionList extends OW_Component{

    protected $offset;
    protected $elid;

    public function __construct( $params )
    {
        parent::__construct();

        $this->offset = !empty($params['offset'])
            ? $params['offset']
            : 0;
        $this->elid = !empty($params['elid'])
            ? $params['elid']
            : 0;

    }

    /**
     * On before render
     *
     * @return void
     */
    public function onBeforeRender()
    {
        $limit=10;

        $userId = OW::getUser()->getId();
        $userIdList = array($userId);

        $list = array();
        $photoDao=PHOTO_BOL_PhotoDao::getInstance();
        $photoList=$photoDao->findPhotoListByUserId($userId,$this->offset,$limit);
        $count=$photoDao->findPhotoListByUserIdListCount($userIdList);
        if($count>$this->offset+$limit){
            $hasMorePage = true;
        }else{
            $hasMorePage = false;
        }
        foreach($photoList as $photo)
        {
            $photoId = $photo['id'];
            $hash = $photo['hash'];
            $photoUrl = $photoDao->getPhotoUrlByType($photoId,PHOTO_BOL_PhotoService::TYPE_MAIN,$hash);
            $list[] = array(
                'id' => $photoId,
                'bigUrl' => $photoUrl
            );
        }
        $this->assign('list',$list);
        $this->assign('hasMorePage',$hasMorePage);
        $this->assign('pageSimple', true);
        $this->assign('elid', $this->elid);

    }
}
