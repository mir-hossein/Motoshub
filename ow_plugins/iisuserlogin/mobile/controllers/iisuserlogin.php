<?php

class IISUSERLOGIN_MCTRL_Iisuserlogin extends OW_MobileActionController
{

    public function index($params)
    {
        if(OW::getConfig()->configExists('iisuserlogin','update_active_details') && OW::getConfig()->getValue('iisuserlogin','update_active_details')) {
            $this->redirect(OW::getRouter()->urlForRoute('iisuserlogin.active'));
        }
        $this->redirect(OW::getRouter()->urlForRoute('iisuserlogin.login'));
    }
    public function login($params)
    {
        if(!OW::getUser()->isAuthenticated()){
            throw new Redirect404Exception();
        }
        $service = IISUSERLOGIN_BOL_Service::getInstance();
        $items = array();
        $details = $service->getUserLoginDetails(OW::getUser()->getId());
        if($details != null) {
            foreach ($details as $detail) {
                $items[] = array(
                    'time' => UTIL_DateTime::formatSimpleDate($detail->time),
                    'browser' => $detail->browser,
                    'ip' => $detail->ip
                );
            }
        }
        $this->assign("items", $items);
        $menu = new BASE_MCMP_ContentMenu($service->getMenu(0));
        $this->addComponent('menu', $menu);
    }
    /**
     * @author Issa Annamoradnejad
     */
    public function active($params)
    {
        $service = IISUSERLOGIN_BOL_Service::getInstance();
        $uId = OW::getUser()->getId();
        if(!OW::getUser()->isAuthenticated()){
            throw new Redirect404Exception();
        }
        if(!OW::getConfig()->configExists('iisuserlogin','update_active_details') || !OW::getConfig()->getValue('iisuserlogin','update_active_details')) {
            throw new Redirect404Exception();
        }
        $js = '
        function terminateDevice(id){
            $.ajax({
                url: "'.OW::getRouter()->urlForRoute('iisuserlogin.terminate_device').'",
                type: "POST",
                data: {deviceId: id},
                dataType: "json",
                success: function (data) {
                    if(data.result){
                        $("#device_"+data.id).hide(500, function() {$(this).remove();});
                    }
                }
            });
        }
        function terminateAllDevices(){
            if(confirm("'.OW::getLanguage()->text('iisuserlogin','are_you_sure').'")){
                $.ajax({
                    url: "'.OW::getRouter()->urlForRoute('iisuserlogin.terminate_device').'",
                    type: "POST",
                    data: {deviceId: -1},
                    dataType: "json",
                    success: function (data) {
                        if(data.result){
                            location.reload(true);
                        }
                    }
                });
            }
        }
        ';
        OW::getDocument()->addScriptDeclarationBeforeIncludes($js);

        //paging
        $rpp = 10;
        $page = (!empty($_GET['page']) && intval($_GET['page']) > 0 ) ? $_GET['page'] : 1;
        $itemsCount = $service->getUserActiveDetailsCount($uId);
        if($itemsCount>$rpp){
            $paging = new BASE_CMP_PagingMobile($page, ceil($itemsCount / $rpp), 5);
            $this->addComponent('paging', $paging);
        }
        if(intval($page)<=0 || $page>ceil($itemsCount / $rpp)) {
            $page = 1;
        }

        $items = array();
        $details = $service->getUserActiveDetails($uId, $page, $rpp);
        if($details != null) {
            foreach ($details as $detail) {
                if(session_id() == $detail->sessionId){
                    $actions = OW::getLanguage()->text('iisuserlogin','current_device');
                }else{
                    $actions = '<a class="owm_btn_class_2 ow_right" href="javascript://" onclick="terminateDevice('.$detail->id.')">'. OW::getLanguage()->text('iisuserlogin','terminate_device').'</a>';
                }
                $items[] = array(
                    'time' => UTIL_DateTime::formatSimpleDate($detail->time),
                    'browser' => $detail->browser,
                    'ip' => $detail->ip,
                    'actions' => $actions,
                    'id' => $detail->id
                );
            }
        }
        $this->assign("items", $items);
        $menu = new BASE_MCMP_ContentMenu($service->getMenu(1));
        $this->addComponent('menu', $menu);
    }
}