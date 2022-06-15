<?php

/**
 * @author Yaser Alimardany <zaph.saph@gmail.com>
 * @package ow_plugins.iisnews.controllers
 * @since 1.0
 */
class IISNEWS_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    public function __construct()
    {
        parent::__construct();

        $this->setPageHeading(OW::getLanguage()->text('iisnews', 'admin_news_settings_heading'));
        $this->setPageHeadingIconClass('ow_ic_gear_wheel');
    }

    /**
     * Default action
     */
    public function index()
    {
        $form = new SettingsForm($this);
        if ( !empty($_POST) && $form->isValid($_POST) )
        {
            $data = $form->getValues();

            OW::getConfig()->saveConfig('iisnews', 'results_per_page', $data['results_per_page']);
        }

        $this->addForm($form);
    }
    
    public function uninstall()
    {
        if ( isset($_POST['action']) && $_POST['action'] == 'delete_content' )
        {
            OW::getConfig()->saveConfig('iisnews', 'uninstall_inprogress', 1);
            OW::getEventManager()->trigger(new OW_Event(EntryService::EVENT_UNINSTALL_IN_PROGRESS));

            //maint-ce mode

            OW::getFeedback()->info(OW::getLanguage()->text('iisnews', 'plugin_set_for_uninstall'));
            $this->redirect();
        }

        $this->setPageHeading(OW::getLanguage()->text('iisnews', 'page_title_uninstall'));
        $this->setPageHeadingIconClass('ow_ic_delete');

        $this->assign('inprogress', (bool) OW::getConfig()->getValue('iisnews', 'uninstall_inprogress'));

        $js = new UTIL_JsGenerator();

        $js->jQueryEvent('#btn-delete-content', 'click', 'if ( !confirm("'.OW::getLanguage()->text('iisnews', 'confirm_delete_photos').'") ) return false;');

        OW::getDocument()->addOnloadScript($js);    	
    }    

}

class SettingsForm extends Form
{

    /***
     * SettingsForm constructor.
     * @param IISNEWS_CTRL_Admin $ctrl
     */
    public function __construct( $ctrl )
    {
        OW::getDocument()->setTitle(OW::getLanguage()->text('iisnews', 'admin_news_settings_heading'));
        parent::__construct('form');

        $configs = OW::getConfig()->getValues('iisnews');

        $ctrl->assign('configs', $configs);

        $l = OW::getLanguage();

        $textField['results_per_page'] = new TextField('results_per_page');

        $textField['results_per_page']->setLabel($l->text('iisnews', 'admin_settings_results_per_page'))
            ->setValue($configs['results_per_page'])
            ->addValidator(new IntValidator())
            ->setRequired(true);

        $this->addElement($textField['results_per_page']);

        $submit = new Submit('submit');

        $submit->setValue($l->text('iisnews', 'save_btn_label'));

        $this->addElement($submit);
    }
}