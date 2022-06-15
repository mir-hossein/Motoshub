<?php

class IISNEWSFEEDPLUS_CTRL_Edit extends OW_ActionController
{

    public function edit()
    {
        $result['error'] = true;
        if (!isset($_POST['eid']) || !isset($_POST['etype']) || !isset($_POST['status']) || empty($_POST['status'])) {
            exit(json_encode($result));
        }
        if(!IISSecurityProvider::checkPluginActive('newsfeed', true)){
            exit(json_encode($result));
        }
        $service = IISNEWSFEEDPLUS_BOL_Service::getInstance();

        if($service->canEditPost($_POST['eid'], $_POST['etype'])) {
            $resultEdit = $service->editPost($_POST['status'], $_POST['eid'], $_POST['etype']);
            $result['error'] = false;
            $result['status'] = $resultEdit['status'];
            $result['actionId'] = $resultEdit['actionId'];
        }
        exit(json_encode($result));
    }
}
