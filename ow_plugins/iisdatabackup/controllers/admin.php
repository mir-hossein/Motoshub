<?php

/**
 * Copyright (c) 2016, Yaser Alimardany
 * All rights reserved.
 */

/**
 *
 * @author Yaser Alimardany <yaser.alimardany@gmail.com>
 * @package ow_plugins.iisdatabackup.controllers
 * @since 1.0
 */
class IISDATABACKUP_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    public function index($params)
    {
        $language = OW::getLanguage();
        $this->setPageHeading($language->text('iisdatabackup', 'admin_page_heading'));
        $this->setPageTitle($language->text('iisdatabackup', 'admin_page_title'));
        $config = OW::getConfig();
        $configs = $config->getValues('iisdatabackup');

        $formSettings = new Form('settings');
        $formSettings->setAjax();
        $formSettings->setAjaxResetOnSuccess(false);
        $formSettings->setAction(OW::getRouter()->urlForRoute('iisdatabackup.admin'));
        $formSettings->bindJsFunction(Form::BIND_SUCCESS, 'function(data){if(data.result){OW.info("Settings successfuly saved");}else{OW.error("Parser error");}}');

        $deadline = new Selectbox('deadline');
        $options = array();
        $options[1] = OW::getLanguage()->text("iisdatabackup", "deadline_for_saving_data_time", array('value' => 6));
        $options[2] = OW::getLanguage()->text("iisdatabackup", "deadline_for_saving_data_time", array('value' => 12));
        $options[3] = OW::getLanguage()->text("iisdatabackup", "deadline_for_saving_data_time", array('value' => 18));
        $options[4] = OW::getLanguage()->text("iisdatabackup", "deadline_for_saving_data_time", array('value' => 24));
        $options[5] = OW::getLanguage()->text("iisdatabackup", "deadline_for_saving_data_always");
        $deadline->setHasInvitation(false);
        $deadline->setOptions($options);
        $deadline->setRequired();
        if(isset($configs['deadline'])) {
            $deadline->setValue($configs['deadline']);
        }
        $formSettings->addElement($deadline);

        $submitSetting = new Submit('saveSettings');
        $formSettings->addElement($submitSetting);

        $this->addForm($formSettings);

        $formData = new Form('formData');
        $formData->setAction(OW::getRouter()->urlForRoute('iisdatabackup.admin.data'));

        $tablesToShow = array('newsfeed_status' , 'newsfeed_action', 'base_avatar' ,
            'base_comment', 'base_mail' , 'base_question_data', 'base_tag' ,
            'base_entity_tag', 'base_user_reset_password' , 'cover_photo',
            'event_item' , 'forum_post', 'forum_post_attachment' , 'forum_section',
            'forum_topic' , 'mailbox_attachment', 'mailbox_message' , 'photo',
            'photo_album' , 'video_clip');

        $tables = new Selectbox('tables');
        $optionsTable = array();
        foreach($tablesToShow as $table){
            $optionsTable[$table] = OW::getLanguage()->text("iisdatabackup", $table);
        }

        $tables->setHasInvitation(false);
        $tables->setOptions($optionsTable);
        $tables->setRequired();
        if(isset($configs['tables'])) {
            $tables->setValue($configs['tables']);
        }
        $formData->addElement($tables);

        $numberOfData = new Selectbox('numberOfData');
        $optionsNumberOfData = array();
        $optionsNumberOfData[10] = 10;
        $optionsNumberOfData[50] = 50;
        $optionsNumberOfData[100] = 100;
        $optionsNumberOfData[200] = 200;
        $numberOfData->setHasInvitation(false);
        $numberOfData->setOptions($optionsNumberOfData);
        $numberOfData->setRequired();
        if(isset($configs['numberOfData'])) {
            $numberOfData->setValue($configs['numberOfData']);
        }
        $formData->addElement($numberOfData);

        $submitFormData = new Submit('showFormData');
        $submitFormData->setValue(OW::getLanguage()->text("iisdatabackup", "showFormData"));
        $formData->addElement($submitFormData);

        $this->addForm($formData);

        if ( OW::getRequest()->isAjax() )
        {
            if ( $formSettings->isValid($_POST) )
            {
                $config->saveConfig('iisdatabackup', 'deadline', $formSettings->getElement('deadline')->getValue());
                exit(json_encode(array('result' => true)));
            }
        }
    }

    public function data($params)
    {
        if(!isset($_POST['tables']) || !isset($_POST['numberOfData'])){
            $this->redirect(OW::getRouter()->urlForRoute('iisdatabackup.admin'));
        }else {
            $tableName = $_POST['tables'];
            $numberOfData = $_POST['numberOfData'];
            $information = $this->getTableData($tableName, $numberOfData);
            $this->assign('tableColumns', $information['columns']);
            $this->assign('tableData', $information['data']);
            $this->assign('tableName', OW::getLanguage()->text("iisdatabackup", $tableName));
            $this->assign('returnToSetting', OW::getRouter()->urlForRoute('iisdatabackup.admin'));
        }
    }

    /**
     * @param $table_name
     * @param $numberOfData
     * @return array
     */
    public function getTableData($table_name, $numberOfData)
    {
        $data = array();

        $table_name = IISSecurityProvider::$prefixBackuplabel . OW_DB_PREFIX . $table_name;

        $hasTableExist = OW::getDbo()->queryForRow("show tables like '". $table_name ."'");
        if (empty($hasTableExist)){
            $data['error'] = true;
            return $data;
        }

        $getAllColumns = "select * from information_schema.columns where TABLE_SCHEMA = '" . OW_DB_NAME . "' and table_name = '". $table_name ."'";
        $data['columns'] = OW::getDbo()->queryForList($getAllColumns);
        for($i =0; $i< sizeof($data['columns']); $i++){
            if($data['columns'][$i]['COLUMN_NAME'] == 'userId'){
                $data['columns'][$i]['COLUMN_NAME'] = 'username - userId';
            }

            $translatedFormatColumnName = array('backup_timestamp' ,'backup_action' ,'backup_pk_id');
            foreach($translatedFormatColumnName as $columnName){
                if($data['columns'][$i]['COLUMN_NAME'] == $columnName){
                    $data['columns'][$i]['COLUMN_NAME'] = OW::getLanguage()->text("iisdatabackup", $columnName);
                }
            }
        }
        //Get all data
        $queryGetAllData = 'select * from '. $table_name . '  order by backup_timestamp desc'. ' limit '. $numberOfData ;
        $data['data'] = OW::getDbo()->queryForList($queryGetAllData);
        for($i =0; $i< sizeof($data['data']); $i++){
            $data['data'][$i]['backup_timestamp'] = UTIL_DateTime::formatDate($data['data'][$i]['backup_timestamp']);

            $timeFormatColumnName = array('timeStamp' ,'createStamp' ,'sentTime' ,'addDatetime' ,
                'createDatetime' ,'createStamp' ,'createTimeStamp' ,'startTimeStamp' ,
                'endTimeStamp' ,'addDateTime' ,'expirationTimeStamp', 'updateTimeStamp' ,'' ,'' );

            foreach($timeFormatColumnName as $columnName){
                if($data['data'][$i][$columnName]!=null){
                    $data['data'][$i][$columnName] = UTIL_DateTime::formatDate($data['data'][$i][$columnName]);
                }
            }

            if($data['data'][$i]['userId']!=null){
                $user = BOL_UserService::getInstance()->findUserById($data['data'][$i]['userId']);
                if($user!=null) {
                    $data['data'][$i]['userId'] = $data['data'][$i]['userId'] . ' - ' . BOL_UserService::getInstance()->findUserById($data['data'][$i]['userId'])->getUsername();
                }
            }
            if($data['data'][$i]['data']!=null){
                $stringData = $data['data'][$i]['data'];//preg_replace('/\\\\/', '', $data['data'][$i]['data']);
                $data['data'][$i]['data'] = preg_replace('/\\\\/', '',print_r(json_decode($stringData, true), true));
            }
            if($data['data'][$i]['backup_action'] == 'r'){
                $data['data'][$i]['backup_action'] = OW::getLanguage()->text("iisdatabackup", "action_remove");
            }else if($data['data'][$i]['backup_action'] == 'u'){
                $data['data'][$i]['backup_action'] = OW::getLanguage()->text("iisdatabackup", "action_update");
            }
        }

        return $data;
    }
}