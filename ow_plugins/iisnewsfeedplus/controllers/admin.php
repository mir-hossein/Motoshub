<?php


/**
 * iisnewsfeedplus admin action controller
 *
 */
class IISNEWSFEEDPLUS_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    /**
     * @param array $params
     */
    public function index(array $params = array())
    {
        $this->setPageHeading(OW::getLanguage()->text('iisnewsfeedplus', 'admin_settings_heading'));
        $this->setPageTitle(OW::getLanguage()->text('iisnewsfeedplus', 'admin_settings_heading'));
        $this->setPageHeadingIconClass('ow_ic_gear_wheel');
        $config =  OW::getConfig();
        $language = OW::getLanguage();

        $form = new Form('form');


        $selectBox = new Selectbox('newsfeed_order_list');
        $options = array();
        $options[IISNEWSFEEDPLUS_BOL_Service::ORDER_BY_ACTIVITY] = $language->text('iisnewsfeedplus', 'sort_by_activity');
        $options[IISNEWSFEEDPLUS_BOL_Service::ORDER_BY_ACTION] = $language->text('iisnewsfeedplus', 'sort_by_action');
        $selectBox->setOptions($options);
        $selectBox->setRequired(true);
        $selectBox->setLabel($language->text('iisnewsfeedplus','newsfeed_list_display_order'));
        $form->addElement($selectBox);

        $allowSortField = new CheckboxField('allow_sort');
        $allowSortField->setLabel($language->text('iisnewsfeedplus', 'admin_allow_sort_label'));
        $form->addElement($allowSortField);

        $submit = new Submit('save');
        $form->addElement($submit);
        $this->addForm($form);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $data = $form->getValues();
            if ( $config->configExists('iisnewsfeedplus', 'newsfeed_list_order') )
            {
                $config->saveConfig('iisnewsfeedplus', 'newsfeed_list_order',$data['newsfeed_order_list']);
            }
            if ( $config->configExists('iisnewsfeedplus', 'allow_sort') )
            {
                $config->saveConfig('iisnewsfeedplus', 'allow_sort',$data['allow_sort']);
            }
            OW::getFeedback()->info($language->text('iisnewsfeedplus', 'modified_successfully'));
            $this->redirect();
        }
        if($config->configExists('iisnewsfeedplus', 'newsfeed_list_order')) {
            $selectBox->setValue($config->getValue('iisnewsfeedplus', 'newsfeed_list_order'));
            $allowSortField->setValue($config->getValue('iisnewsfeedplus','allow_sort'));
        }
    }

}