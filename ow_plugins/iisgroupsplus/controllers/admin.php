<?php

/**
 * Admin page
 * @author Mohammad Agha Abbasloo
 * @package ow_plugins.iisgroupsplus.controllers
 * @since 1.0
 */
class IISGROUPSPLUS_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    public function groupCategory($params)
    {
        OW::getDocument()->setTitle(OW::getLanguage()->text('iisgroupsplus', 'admin_groupplus_settings_heading'));
        $this->setPageTitle(OW::getLanguage()->text('iisgroupsplus', 'admin_category_title'));
        $this->setPageHeading(OW::getLanguage()->text('iisgroupsplus', 'admin_category_heading'));
        $deleteUrls = array();
        $groupListCategory = array();
        $groupCategories = IISGROUPSPLUS_BOL_Service::getInstance()->getGroupCategoryList();
        $editUrls = [];
        foreach ($groupCategories as $groupCategory) {
            $editUrls[$groupCategory->id] =  "OW.ajaxFloatBox('IISGROUPSPLUS_CMP_EditItemFloatBox', {id: ".$groupCategory->id."} , {iconClass: 'ow_ic_edit', title: '".OW::getLanguage()->text('iisgroupsplus', 'edit_item_page_title')."'})";
            /* @var $contact IISGROUPSPLUS_BOL_Category */
            $groupListCategory[$groupCategory->id]['name'] = $groupCategory->id;
            $groupListCategory[$groupCategory->id]['label'] = $groupCategory->label;
            $deleteUrls[$groupCategory->id] = OW::getRouter()->urlFor(__CLASS__, 'delete', array('id' => $groupCategory->id));
        }
        $this->assign('groupListCategory', $groupListCategory);
        $this->assign('deleteUrls', $deleteUrls);
        $this->assign('editUrls',$editUrls);
        $form = new Form('add_category');
        $this->addForm($form);

        $fieldLabel = new TextField('label');
        $fieldLabel->setRequired();
        $fieldLabel->setInvitation(OW::getLanguage()->text('iisgroupsplus', 'label_category_label'));
        $fieldLabel->setHasInvitation(true);
        $validator = new IISGROUPSPLUS_CLASS_LabelValidator();
        $language = OW::getLanguage();
        $validator->setErrorMessage($language->text('iisgroupsplus', 'label_error_already_exist'));
        $fieldLabel->addValidator($validator);
        $form->addElement($fieldLabel);

        $submit = new Submit('add');
        $submit->setValue(OW::getLanguage()->text('iisgroupsplus', 'form_add_category_submit'));
        $form->addElement($submit);
        if (OW::getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                IISGROUPSPLUS_BOL_Service::getInstance()->addGroupCategory($data['label']);
                $this->redirect();
            }
        }

        $notificationForm = new Form('notification_settings');
        $this->addForm($notificationForm);
        $fileUploadFeed= new checkBoxGroup('fileUploadFeed');
        $fileUploadFeed->addOption('fileFeed',OW::getLanguage()->text('iisgroupsplus', 'file_feed_label'));
        $fileUploadFeed->addOption('joinFeed',OW::getLanguage()->text('iisgroupsplus', 'join_feed_label'));
        $fileUploadFeed->addOption('leaveFeed',OW::getLanguage()->text('iisgroupsplus', 'leave_feed_label'));
        $fileUploadFeedValue= OW::getConfig()->getValue('iisgroupsplus','groupFileAndJoinAndLeaveFeed');
        if(isset($fileUploadFeedValue) && $fileUploadFeedValue !=null ){
            $fileUploadFeed->setValue(json_decode($fileUploadFeedValue));
        }
        $fileUploadFeed->setLabel(OW::getLanguage()->text('iisgroupsplus', 'group_file_and_join_feed_label'));
        $config = OW::getConfig();
        $notificationForm->addElement($fileUploadFeed);
        $fileUploadInCreateGroup = new CheckboxField('fileUploadSettings');
        $fileUploadInCreateGroup->setLabel(OW::getLanguage()->text('iisgroupsplus', 'show_file_upload_settings'));
        $fileUploadInCreateGroup->setValue($config->getValue('iisgroupsplus', 'showFileUploadSettings'));
        $notificationForm->addElement($fileUploadInCreateGroup);

        $createTopicInCreateGroup = new CheckboxField('createTopicSettings');
        $createTopicInCreateGroup->setLabel(OW::getLanguage()->text('iisgroupsplus', 'show_add_topic_settings'));
        $createTopicInCreateGroup->setValue($config->getValue('iisgroupsplus', 'showAddTopic'));
        $notificationForm->addElement($createTopicInCreateGroup);

        $notificationFormSubmit = new Submit('notificationFormAdd');
        $notificationFormSubmit->setValue(OW::getLanguage()->text('iisgroupsplus', 'group_file_and_join_feed_submit'));
        $notificationForm->addElement($notificationFormSubmit);
        if (OW::getRequest()->isPost()) {
            if ($notificationForm->isValid($_POST)) {
                $data = $notificationForm->getValues();
                OW::getConfig()->saveConfig('iisgroupsplus','groupFileAndJoinAndLeaveFeed',json_encode($data['fileUploadFeed']));

                if(!isset($data["fileUploadSettings"]))
                   $config->saveConfig('iisgroupsplus','showFileUploadSettings',0);
                else
                    $config->saveConfig('iisgroupsplus','showFileUploadSettings',1);

                if(!isset($data["createTopicSettings"]))
                    $config->saveConfig('iisgroupsplus','showAddTopic',0);
                else
                    $config->saveConfig('iisgroupsplus','showAddTopic',1);

                $this->redirect();
            }
        }
    }

    public function getService(){
        return IISGROUPSPLUS_BOL_Service::getInstance();
    }


    public function delete( $params )
    {
        if ( isset($params['id']))
        {
            IISGROUPSPLUS_BOL_Service::getInstance()->deleteGroupCategory((int) $params['id']);
        }
        OW::getFeedback()->info(OW::getLanguage()->text('iisgroupsplus', 'database_record_edit'));
        $this->redirect(OW::getRouter()->urlForRoute('iisgroupsplus.admin'));
    }

    public function editItem()
    {
        $form = $this->getService()->getItemForm($_POST['id']);
        if ( $form->isValid($_POST) ) {
           $this->getService()->editItem($form->getElement('id')->getValue(), $form->getElement('label')->getValue());
            OW::getFeedback()->info(OW::getLanguage()->text('iisgroupsplus', 'database_record_edit'));
            $this->redirect(OW::getRouter()->urlForRoute('iisgroupsplus.admin'));
        }else{
            if($form->getErrors()['label'][0]!=null) {
                OW::getFeedback()->error($form->getErrors()['label'][0]);
            }
            $this->redirect(OW::getRouter()->urlForRoute('iisgroupsplus.admin'));
        }
    }
}
