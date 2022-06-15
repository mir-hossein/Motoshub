<?php


/**
 * iisvideoplus admin action controller
 *
 */
class IISVIDEOPLUS_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    public function uninstall()
    {
        if ( isset($_POST['action']) && $_POST['action'] == 'delete_content' )
        {
            OW::getConfig()->saveConfig('iisvideoplus', 'uninstall_inprogress', 1);
            OW::getEventManager()->trigger(new OW_Event(IISVIDEOPLUS_BOL_Service::EVENT_UNINSTALL_IN_PROGRESS));

            IISVIDEOPLUS_BOL_Service::getInstance()->setMaintenanceMode(true);

            OW::getFeedback()->info(OW::getLanguage()->text('iisvideoplus', 'plugin_set_for_uninstall'));
            $this->redirect();
        }

        $this->setPageHeading(OW::getLanguage()->text('iisvideoplus', 'page_title_uninstall'));
        $this->setPageHeadingIconClass('ow_ic_delete');

        $this->assign('inprogress', (bool) OW::getConfig()->getValue('iisvideoplus', 'uninstall_inprogress'));

        $js = new UTIL_JsGenerator();
        $js->jQueryEvent('#btn-delete-content', 'click', 'if ( !confirm("'.OW::getLanguage()->text('iisvideoplus', 'confirm_delete_video_file').'") ) return false;');

        OW::getDocument()->addOnloadScript($js);
    }
}
