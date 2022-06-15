<?php

/**
 * iisgroupsplus
 *
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisgroupsplus.controllers
 * @since 1.0
 */
class IISGROUPSPLUS_CTRL_ForcedGroups extends OW_ActionController
{
    /**
     *
     * @var IISGROUPSPLUS_BOL_Service
     */
    private $service;

    public function __construct()
    {
        $this->service = IISGROUPSPLUS_BOL_Service::getInstance();
    }

    private static function getEnglishNumber($number){
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١','٠'];
        $num = range(0, 9);
        $convertedPersianNums = str_replace($persian, $num, $number);
        $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);

        return $englishNumbersOnly;
    }

    public function index( $params )
    {
        if (!OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('iisgroupsplus', 'add-forced-groups')) {
            throw new Redirect404Exception();
        }
        $this->setPageTitle(OW::getLanguage()->text('iisgroupsplus', 'forced_groups'));
        $this->setPageHeading(OW::getLanguage()->text('iisgroupsplus', 'forced_groups'));

        // Form to add a new group
        $form = new Form('mainForm');
        $form->setAjax(true);
        $form->setAction(OW::getRouter()->urlFor('IISGROUPSPLUS_CTRL_ForcedGroups', 'updateItem'));
        $groupIdField = new TextField('gId');
        $groupIdField->setLabel(OW::getLanguage()->text('iisgroupsplus','label_gId'));
        $groupIdField->setRequired()->addAttribute('type', 'number');
        $form->addElement($groupIdField);
        $removing = new CheckboxField('removing');
        $form->addElement($removing);
        $submitField = new Submit('submit');
        $form->addElement($submitField);
        $this->addForm($form);

        $addNewForcedGroupForm = new Form('addNewForcedGroup');
        $addNewForcedGroupForm->setAjax(true);
        $allSelectableQuestionElements = BOL_QuestionService::getInstance()->allSelectableQuestionElements();
        $profileQuestions = array();
        $forcedStay = new CheckboxField('forcedStay');
        $forcedStay->setLabel(OW::getLanguage()->text('iisgroupsplus', 'label_forcedStay'));
        $form->addElement($forcedStay);
        foreach ($allSelectableQuestionElements as $question_number => $question) {
            $question_label = OW::getLanguage()->text('base', 'questions_question_' . $question->getAttribute('name') . '_label');
            $profileQuestions[$question_number]['question_label'] = $question_label;
            $profileQuestions[$question_number]['custom_id'] = $question_number;
            foreach ($question->getOptions() as $question_option_number => $question_option) {
                $questionOption = new CheckboxField('profileQuestionFilter__' . $question_option->questionName . '__' . $question_option->value);
                $questionOption->setLabel(OW::getLanguage()->text('base', 'questions_question_' . $question_option->questionName . '_value_' . $question_option->value));
                $profileQuestions[$question_number]['options'][$question_option_number] = $questionOption;
                $addNewForcedGroupForm->addElement($questionOption);
            }
        }

        $addNewForcedGroupButton = new button('addNewForcedGroupButton');
        $addNewForcedGroupForm->addElement($addNewForcedGroupButton);

        $this->assign('profileQuestions', $profileQuestions);
        $selectedGroupId = new TextField('gId');
        $addNewForcedGroupForm->addElement($selectedGroupId);
        $this->addForm($addNewForcedGroupForm);

        $profileQuestionFilters = array();
        if (sizeof($_POST) != 0) {
            $allProfileQuestions = $_POST;
            $nonProfileQuestionFilters = array("form_name", "csrf_token", "csrf_hash", "gId", "forcedStay");

            foreach ($nonProfileQuestionFilters as $filed) {
                unset($allProfileQuestions[$filed]);
            }

            foreach ($allProfileQuestions as $filter_name => $filter_value) {
                if (isset($filter_value)) {
                    $filter_parts = explode("__", $filter_name);
                    $profileQuestionFilters[$filter_parts[1]][] = $filter_parts[2];
                }
            }
            $this->addAllUsersToGroup(null);
        }

        $config = OW::getConfig();
        if ( !$config->configExists('iisgroupsplus', 'forced_groups') )
        {
            $config->addConfig('iisgroupsplus', 'forced_groups', json_encode([]));
        }
        $list = $config->getValue('iisgroupsplus', 'forced_groups');
        $list = json_decode($list);

        $groups = [];
        if (isset($list)) {
            foreach ($list as $gId => $forced) {
                $group = GROUPS_BOL_Service::getInstance()->findGroupById($gId);
                $userCount = OW::getLanguage()->text('groups', 'feed_activity_users', array('usersCount' => GROUPS_BOL_Service::getInstance()->findUserCountForList([$gId])[$gId]));
                if (isset($group)) {
                    $groups[] = ['id' => $gId, 'name' => $group->title, 'forced' => ($forced == 'on') ? 'checked' : '',
                        'href' => GROUPS_BOL_Service::getInstance()->getGroupUrl($group), 'userCount' => $userCount, 'editURL' => OW::getRouter()->urlForRoute('iisgroupsplus.forced-group-edit', array('id' => $gId))
                    ];
                }
            }
        }

        $this->assign('groups', $groups);

        $js = IISGROUPSPLUS_BOL_Service::getForcedGroupSubmitFormJS() .  "
        $('a.f_insert, a.f_update, a.f_remove').click(function(){
            var tr = $(this).closest('tr');
            var gId = $('*[name=gId]', tr).val();
            $('form[name=mainForm] input[name=gId]').val(gId);
            var forcedStay = $('input[name=forcedStay]', tr).prop('checked');
            $('form[name=mainForm] input[name=forcedStay]').prop('checked', forcedStay);
            $('form[name=mainForm] input[name=removing]').prop('checked', $(this).hasClass('f_remove'));
            $('form[name=mainForm]').submit();
            if (gId != ''){
                $('#btn_loading').show();
                if($(this).hasClass('f_insert')){
                    OW.info('".OW::getLanguage()->text('iisgroupsplus','please_wait')."');
                }
            }
        });
        
        $('a.f_add_all_users').click(function(){
            var tr = $(this).closest('tr');
            var gId = $('*[name=gId]', tr).val();
            OW.info('" . OW::getLanguage()->text('iisgroupsplus', 'please_wait') . "');
            $.ajax( {
                url: '" . OW::getRouter()->urlFor('IISGROUPSPLUS_CTRL_ForcedGroups', 'addAllUsersToGroup') . "',
                type: 'POST',
                data: { gId: gId },
                dataType: 'json',
                success: function( result )
                {
                    OW.info(result['message']);
                }
            });
        });
        
        this.myForm = window.owForms['mainForm'];
		this.myForm.bind('success', function(result){
            $('#btn_loading').hide();
		    if ( result && result.result == 'success' )
            {
                OW.info(result['message']);
                if ( result.refresh === true){
                    window.location.reload();
                }
            }
            else if ( result['message'] )
            {
                OW.error(result['message']);
            }
		});
        ";
        OW::getDocument()->addOnloadScript($js);
    }

    public function updateItem( $params )
    {
        if (!OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('iisgroupsplus', 'add-forced-groups')) {
            exit(json_encode(array('result' => 'error', 'message' => '404')));
        }
        $gId = $_POST['gId'];

        $config = OW::getConfig();
        $list = $config->getValue('iisgroupsplus', 'forced_groups');
        $list = json_decode($list, true);
        $groupIds = array_keys($list);

        if($_POST['removing'] == 'on') {
            if (in_array($gId, $groupIds)) {
                unset($list[$gId]);
                $config->saveConfig('iisgroupsplus', 'forced_groups', json_encode($list));
            }
            exit(json_encode(array('result' => 'success', 'refresh'=>true, 'message' => OW::getLanguage()->text('iisgroupsplus', 'forced_group_removed'))));
        }
        else{
            $group = GROUPS_BOL_Service::getInstance()->findGroupById($gId);
            if(isset($group)) {
                $list[$gId] = ($_POST['forcedStay'] == 'on');
                $config->saveConfig('iisgroupsplus', 'forced_groups', json_encode($list));
                if (in_array($gId, $groupIds)) {
                    exit(json_encode(array('result' => 'success', 'message' => OW::getLanguage()->text('iisgroupsplus', 'forced_group_updated'))));
                }else{
                    $this->addAllUsersToGroup(['gId' => $gId]);
                }
            }else{
                exit(json_encode(array('result' => 'error', 'message' => OW::getLanguage()->text('iisgroupsplus', 'group_not_found'))));
            }
        }
    }

    public function addAllUsersToGroup( $params )
    {
        if (!OW::getUser()->isAuthenticated() || !OW::getUser()->isAuthorized('iisgroupsplus', 'add-forced-groups')) {
            exit(json_encode(array('result' => 'error', 'message' => '404')));
        }
        if (!isset($_POST['gId']) || $_POST['gId'] == "")
            exit(json_encode(array('result' => 'error', 'message' => OW::getLanguage()->text('iisgroupsplus', 'enter_group_id'))));

        $groupService = GROUPS_BOL_Service::getInstance();
        $gId = IISGROUPSPLUS_CTRL_ForcedGroups::getEnglishNumber($_POST['gId']);
        $group = $groupService->findGroupById($gId);
        if(isset($group)) {
            $list = OW::getConfig()->getValue('iisgroupsplus', 'forced_groups');
            $list = json_decode($list, true);
            $list[$gId]['canLeave'] = ($_POST['forcedStay'] == 'false');
            $list[$gId]['conditions'] = $_POST['profileQuestionFiltersList'];

            $config = OW::getConfig();
            if (!$config->configExists('iisgroupsplus', 'forced_groups')) {
                $config->addConfig('iisgroupsplus', 'forced_groups', json_encode([]));
            }
            $config->saveConfig('iisgroupsplus', 'forced_groups', json_encode($list));

            $registeredUsers = $groupService->findGroupUserIdList($gId);
            if (isset($_POST['profileQuestionFiltersList'])) {
                $filteredUsers = IISGROUPSPLUS_CTRL_ForcedGroups::findFilteredUserList($_POST['profileQuestionFiltersList']);
                $users = array();
                if (isset($filteredUsers) && sizeof($filteredUsers) != 0) {
                    $users = BOL_UserDao::getInstance()->findByIdList($filteredUsers);
                }
            } else{
                $numberOfUsers = BOL_UserService::getInstance()->count(true);
                $users = BOL_UserService::getInstance()->findList(0, $numberOfUsers, true);
            }
            $userIds = [];
            $_POST['no-join-feed'] = true;
            foreach($users as $user){
                if(! in_array($user->id, $registeredUsers)) {
                    $userIds[] = $user->id;
                    $groupService->addUser($gId, $user->id);
                }
            }
            $userIds = array_diff($userIds, $registeredUsers);

            $eventIisGroupsPlusAddAutomatically = new OW_Event('iisgroupsplus.add.users.automatically',array('groupId'=>$gId,'userIds'=>$userIds));
            OW::getEventManager()->trigger($eventIisGroupsPlusAddAutomatically);

            exit(json_encode(array('result' => 'success', 'refresh'=>true, 'forcedGroupsURL'=>OW::getRouter()->urlForRoute('iisgroupsplus.forced-groups'), 'message' => OW::getLanguage()->text('iisgroupsplus', 'all_users_added'))));
        }else{
            exit(json_encode(array('result' => 'error', 'message' => OW::getLanguage()->text('iisgroupsplus', 'group_not_found'))));
        }
    }

    private function findFilteredUserList($allProfileQuestions)
    {
        $profileQuestionFilters = null;
        foreach ($allProfileQuestions as $filter_name=>$filter_value){
            if (isset($filter_value)){
                $filter_parts = explode("__", $filter_name);
                $profileQuestionFilters[$filter_parts[1]][] = $filter_parts[2];
            }
        }

        if ($profileQuestionFilters != null) {
            $result = IISGROUPSPLUS_BOL_Service::getFilteredUsersList($profileQuestionFilters);
            if ($result != null) {
                $listOfFilteredUSerIds = array();
                foreach ($result as $index => $item) {
                    $listOfFilteredUSerIds[] = $result[$index]['userId'];
                }
            }
        }
        if (isset($listOfFilteredUSerIds))
            return $listOfFilteredUSerIds;
        return null;
    }

    public function edit($params)
    {
        $this->setPageTitle(OW::getLanguage()->text('iisgroupsplus', 'edit_forced_group'));
        $this->setPageHeading(OW::getLanguage()->text('iisgroupsplus', 'edit_forced_group'));

        $groupId = $params['id'];
        $config = OW::getConfig();
        $list = $config->getValue('iisgroupsplus', 'forced_groups');
        $forcedGroupData = get_object_vars(json_decode($list))[$groupId];

        if (isset($forcedGroupData)) {

            $forcedGroupConfigs = new Form('forcedGroupConfigs');
            $allSelectableQuestionElements = BOL_QuestionService::getInstance()->allSelectableQuestionElements();
            $forcedStay = new CheckboxField('forcedStay');
            $forcedStay->setLabel(OW::getLanguage()->text('iisgroupspluscustom_id', 'label_forcedStay'));

            $groupIdField = new TextField('gId');
            $groupIdField->setLabel(OW::getLanguage()->text('iisgroupsplus', 'label_gId'))->addAttribute('value', $groupId);
            $groupIdField->setRequired();
            $forcedGroupConfigs->addElement($groupIdField);

            $editNewForcedGroupButton = new button('editNewForcedGroupButton');
            $editNewForcedGroupButton->addAttribute('value', OW::getLanguage()->text('iisgroupsplus', 'edit_item'));
            $forcedGroupConfigs->addElement($editNewForcedGroupButton);

            if (!get_object_vars($forcedGroupData)['canLeave']) {
                $forcedStay->addAttribute('id', 'submitForcedGroupConfigs');
            }

            $profileQuestions = array();
            foreach ($allSelectableQuestionElements as $question_number => $question) {
                $question_label = OW::getLanguage()->text('base', 'questions_question_' . $question->getAttribute('name') . '_label');
                $profileQuestions[$question_number]['question_label'] = $question_label;
                $profileQuestions[$question_number]['custom_id'] = $question_number;
                foreach ($question->getOptions() as $question_option_number => $question_option) {
                    $questionOption = new CheckboxField('profileQuestionFilter__' . $question_option->questionName . '__' . $question_option->value);
                    $questionOption->setLabel(OW::getLanguage()->text('base', 'questions_question_' . $question_option->questionName . '_value_' . $question_option->value));
                    $profileQuestions[$question_number]['options'][$question_option_number] = $questionOption;
                    $forcedGroupConditions = get_object_vars($forcedGroupData)['conditions'];
                    if (isset($forcedGroupConditions) &&  isset(get_object_vars($forcedGroupConditions)['profileQuestionFilter__' . $question_option->questionName . '__' . $question_option->value]) == "1")
                        $questionOption->addAttribute('checked', true);
                    $forcedGroupConfigs->addElement($questionOption);
                }
            }
            $this->assign('profileQuestions', $profileQuestions);
            $this->assign('forceStay', !get_object_vars($forcedGroupData)['canLeave']);
            $this->addForm($forcedGroupConfigs);

            $js = IISGROUPSPLUS_BOL_Service::getForcedGroupSubmitFormJS();
            OW::getDocument()->addOnloadScript($js);
        }
        else{
            OW::getApplication()->redirect(OW::getRouter()->urlForRoute('base_page_404'));
            OW::getFeedback()->error(OW::getLanguage()->text('iisgroupsplus', 'forced_group_not_found'));
        }
    }
}