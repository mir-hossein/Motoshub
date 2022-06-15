<?php

/**
 * Admin page
 * @author Mohammad Agha Abbasloo
 * @package ow_plugins.iiseventplus.controllers
 * @since 1.0
 */
class IISEVENTPLUS_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    public function eventCategory($params)
    {
        OW::getDocument()->setTitle(OW::getLanguage()->text('iiseventplus', 'admin_eventplus_settings_heading'));
        $service = $this->getService();
        $this->setPageTitle(OW::getLanguage()->text('iiseventplus', 'admin_category_title'));
        $this->setPageHeading(OW::getLanguage()->text('iiseventplus', 'admin_category_heading'));
        $deleteUrls = array();
        $eventListCategory = array();
        $eventCategories = IISEVENTPLUS_BOL_Service::getInstance()->getEventCategoryList();
        $editUrls = [];
        foreach ($eventCategories as $eventCategory) {
            $editUrls[$eventCategory->id] =  "OW.ajaxFloatBox('IISEVENTPLUS_CMP_EditItemFloatBox', {id: ".$eventCategory->id."} , {iconClass: 'ow_ic_edit', title: '".OW::getLanguage()->text('iiseventplus', 'edit_item_page_title')."'})";
            /* @var $contact IISEVENTPLUS_BOL_Category */
            $eventListCategory[$eventCategory->id]['name'] = $eventCategory->id;
            $eventListCategory[$eventCategory->id]['label'] = $eventCategory->label;
            $deleteUrls[$eventCategory->id] = OW::getRouter()->urlFor(__CLASS__, 'delete', array('id' => $eventCategory->id));
        }
        $this->assign('eventListCategory', $eventListCategory);
        $this->assign('deleteUrls', $deleteUrls);
        $this->assign('editUrls',$editUrls);
        $form = new Form('add_category');
        $this->addForm($form);

        $fieldLabel = new TextField('label');
        $fieldLabel->setRequired();
        $fieldLabel->setInvitation(OW::getLanguage()->text('iiseventplus', 'label_category_label'));
        $fieldLabel->setHasInvitation(true);
        $validator = new IISEVENTPLUS_CLASS_LabelValidator();
        $language = OW::getLanguage();
        $validator->setErrorMessage($language->text('iiseventplus', 'label_error_already_exist'));
        $fieldLabel->addValidator($validator);
        $form->addElement($fieldLabel);

        $submit = new Submit('add');
        $submit->setValue(OW::getLanguage()->text('iiseventplus', 'form_add_category_submit'));
        $form->addElement($submit);
        if (OW::getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                IISEVENTPLUS_BOL_Service::getInstance()->addEventCategory($data['label']);
                $this->redirect();
            }
        }
    }

    public function getService(){
        return IISEVENTPLUS_BOL_Service::getInstance();
    }


    public function delete( $params )
    {
        if ( isset($params['id']))
        {
            IISEVENTPLUS_BOL_Service::getInstance()->deleteEventCategory((int) $params['id']);
        }
        OW::getFeedback()->info(OW::getLanguage()->text('iiseventplus', 'database_record_edit'));
        $this->redirect(OW::getRouter()->urlForRoute('iiseventplus.admin'));
    }

    public function editItem()
    {
        $form = $this->getService()->getItemForm($_POST['id']);
        if ( $form->isValid($_POST) ) {
           $this->getService()->editItem($form->getElement('id')->getValue(), $form->getElement('label')->getValue());
            OW::getFeedback()->info(OW::getLanguage()->text('iiseventplus', 'database_record_edit'));
            $this->redirect(OW::getRouter()->urlForRoute('iiseventplus.admin'));
        }else{
            if($form->getErrors()['label'][0]!=null) {
                OW::getFeedback()->error($form->getErrors()['label'][0]);
            }
            $this->redirect(OW::getRouter()->urlForRoute('iiseventplus.admin'));
        }
    }
}
