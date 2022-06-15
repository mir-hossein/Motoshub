<?php

/**
 * IIS Advance Search
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisadvancesearch
 * @since 1.0
 */

class IISADVANCESEARCH_MCTRL_AllUsersList extends OW_MobileActionController
{
    private $usersPerPage;

    public function __construct()
    {
        parent::__construct();
        $this->setPageHeading(OW::getLanguage()->text('base', 'users_browse_page_heading'));
        $this->setPageTitle(OW::getLanguage()->text('base', 'users_browse_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_user');
        $this->usersPerPage = (int)OW::getConfig()->getValue('base', 'users_count_on_page');
    }

    public function index( $params )
    {
        $type = isset($params['type'])?$params['type']:'all';
        if(!in_array($type, array('all', 'new', 'friends'))){
            throw new Redirect404Exception();
        }

        OW::getLanguage()->addKeyForJs('base', 'more');
        $jsDir = OW::getPluginManager()->getPlugin("iisadvancesearch")->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir . "iisadvancesearch-mobile.js");
        OW::getDocument()->addOnloadScript(';iisadvancesearch_search_users(\''.OW::getRouter()->urlForRoute('iisadvancesearch.search_users', array('type'=>$type, 'key' => '')).'\', "#iisadvancedsearch_search_all_users", 30, true);');


        //setting back url
        if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']!=null){
            $this->assign('backUrl',$_SERVER['HTTP_REFERER']);
        }
        else {
            $plugin = BOL_PluginService::getInstance()->findPluginByKey('iismainpage');
            if (isset($plugin) && $plugin->isActive() && !IISMAINPAGE_BOL_Service::getInstance()->isDisabled('friends')) {
                $backUrl = OW::getRouter()->urlForRoute('iismainpage.friends');
            } else {
                $backUrl = OW::getRouter()->urlForRoute('index');
            }
            $this->assign('backUrl',$backUrl);
        }

    }
}