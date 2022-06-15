<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisadvancesearch
 * @since 1.0
 */
class IISADVANCESEARCH_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    public function __construct()
    {
        parent::__construct();

        $this->setPageHeading(OW::getLanguage()->text('iisadvancesearch', 'admin_settings_heading'));
        $this->setPageHeadingIconClass('ow_ic_gear_wheel');
    }

    /**
     * Default action
     */
    public function index()
    {
        OW::getDocument()->setTitle(OW::getLanguage()->text('iisadvancesearch', 'admin_settings_heading'));

        $form = new Form("form");

        $resultData = array();
        $event = OW::getEventManager()->trigger(new OW_Event('iisadvancesearch.on_collect_search_items',
            array('q' => 'collecting plugin names', 'maxCount' => 10), $resultData));
        $resultData = $event->getData();

        $fieldNames = array();
        foreach($resultData as $key => $value){
            $tmpFieldKey = 'search_allowed_'.$key;
            $fieldNames[] = $tmpFieldKey;
            $field = new CheckboxField($tmpFieldKey);
            $field->setLabel($value['label'])->setValue(true);
            if(OW::getConfig()->configExists('iisadvancesearch',$tmpFieldKey)){
                $isAllowed = OW::getConfig()->getValue('iisadvancesearch',$tmpFieldKey);
                if(!$isAllowed){
                    $field->setValue(false);
                    unset($resultData[$tmpFieldKey]);
                }
            }
            $form->addElement($field);
        }
        $this->assign('field_list', $fieldNames);

        $submit = new Submit('submit');
        $submit->setValue(OW::getLanguage()->text('iisadvancesearch', 'save_btn_label'));
        $form->addElement($submit);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $fieldValues = $form->getValues();
            foreach($fieldNames as $fieldKey){
                if(OW::getConfig()->configExists('iisadvancesearch', $fieldKey)){
                    OW::getConfig()->saveConfig('iisadvancesearch', $fieldKey, $fieldValues[$fieldKey]);
                }
                else {
                    OW::getConfig()->addConfig('iisadvancesearch', $fieldKey, $fieldValues[$fieldKey]);
                }
            }
            OW::getFeedback()->info(OW::getLanguage()->text('iisadvancesearch', 'admin_changed_success'));
        }

        $this->addForm($form);
    }

}