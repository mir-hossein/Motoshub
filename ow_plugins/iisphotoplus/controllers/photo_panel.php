<?php
class IISPHOTOPLUS_CTRL_PhotoPanel extends OW_ActionController
{
    public function __construct()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            $this->setVisible(false);
            return;
        }

        if ( !OW::getRequest()->isAjax() )
        {
            OW::getDocument()->getMasterPage()->setTemplate(OW::getThemeManager()->getMasterPageTemplate(OW_MasterPage::TEMPLATE_BLANK));
            OW::getDocument()->addStyleDeclaration(".ow_footer{display:none;}");
            OW::getEventManager()->trigger(new OW_Event('iismenu.hide.unwanted.element'));
        }

        if(isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'from-url')===false && strpos($_SERVER['REQUEST_URI'], 'fromurl')!==false){
            throw new Redirect404Exception();
        }
    }

    public function index($params)
    {
        $service = BOL_MediaPanelService::getInstance();
        $menu=$service->initMenu($params);

        $this->addComponent('menu', $menu);

        if ( !OW::getUser()->isAuthenticated() )
        {
            return;
        }
        $first = 0;
        $limit=10;

        $userId = OW::getUser()->getId();
        $userIdList = array($userId);

        $photoDao=PHOTO_BOL_PhotoDao::getInstance();
        $count=$photoDao->findPhotoListByUserIdListCount($userIdList);
        $photoList=$photoDao->findPhotoListByUserId($userId,$first,$limit);
        $list =array();
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

        $js = UTIL_JsGenerator::newInstance();
        $js->newFunction('window.location.href=url', array('url'), 'redirect');
        OW::getDocument()->addOnloadScript($js);

        $id = $params['id'];
        $elid = UTIL_HtmlTag::escapeHtml($id);
        $this->assign('list',$list);
        $this->assign('pageSimple', true);
        $this->assign('elid', $elid);

        //load more js
        $showMoreJS = "
        var count = " . $count . ";
        var offset = 10;
        var limit = 10;  
        var elid = '".$elid."';
        $('#show_more_btn').click(function(){
            $('#show_more_btn').hide();
            if(offset>=count){
                return;
            }
            OW.loadComponent(
            \"IISPHOTOPLUS_CMP_PhotoSelectionList\", 
            [{\"offset\" : offset,\"limit\" : limit,\"elid\" : elid}],
            \".photo_list_result_div\");
            offset = offset + limit;
        });        
        ";
        OW::getDocument()->addOnloadScript($showMoreJS);
    }
}