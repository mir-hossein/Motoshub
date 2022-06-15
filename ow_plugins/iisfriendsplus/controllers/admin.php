<?php


/**
 * iisfriendsplus admin action controller
 *
 */
class IISFRIENDSPLUS_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    /**
     * @param array $params
     */
    public function index(array $params = array())
    {
        $language = OW::getLanguage();
        $config =  OW::getConfig();
        $service = IISFRIENDSPLUS_BOL_Service::getInstance();

        $this->setPageHeading($language->text('iisfriendsplus', 'admin_settings_heading'));
        $this->setPageTitle($language->text('iisfriendsplus', 'admin_settings_heading'));
        $this->setPageHeadingIconClass('ow_ic_gear_wheel');

        $authService = BOL_AuthorizationService::getInstance();
        $list = array();
        $roles = $authService->getRoleList();

        foreach ( $roles as $role )
        {
            if($role->getName() != 'guest'){
                $list[$role->getId()] = array(
                    'dto' => $role,
                    'roleFieldId' => 'role_'.$role->getId()
                );
            }
        }

        $tplRoles = array();
        foreach ( $roles as $role )
        {
            if($role->getName() != 'guest') {
                $tplRoles[$role->sortOrder] = $role;
            }
        }

        ksort($tplRoles);

        $this->assign( 'set', $list );
        $this->assign('roles', $tplRoles);

        $selectedRoles = $config->getValue('iisfriendsplus', 'selected_roles');
        if($selectedRoles != null){
            $selectedRoles = json_decode($selectedRoles);
        }

        $form = new Form('form_roles');
        foreach ($tplRoles as $role){
            $field = new CheckboxField($list[$role->getId()]['roleFieldId']);
            $value = $service->isRoleInSelected($role->getId(), $selectedRoles);
            $field->setValue($value);
            $field->setLabel($language->text('base', 'authorization_role_'.$role->getName()));
            $form->addElement($field);
        }
        $submit = new Submit('submit');
        $form->addElement($submit);
        $this->addForm($form);

        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $data = $form->getValues();
            $selectedRoles = array();
            foreach ($tplRoles as $role){
                if($data[$list[$role->getId()]['roleFieldId']] == true){
                    $selectedRoles[] = $role->getId();
                }
            }
            if ( $config->configExists('iisfriendsplus', 'selected_roles') )
            {
                $config->saveConfig('iisfriendsplus', 'selected_roles', json_encode($selectedRoles));
            }
            OW::getFeedback()->info($language->text('iisfriendsplus', 'saved_successfully'));
            $this->redirect();
        }

        $this->addForm($service->getAllUsersForm());
    }

    /**
     * @param array $params
     */
    public function allUsers(array $params = array())
    {
        $service = IISFRIENDSPLUS_BOL_Service::getInstance();
        $form = $service->getAllUsersForm();
        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $service->manageAllUsers();
            OW::getFeedback()->info(OW::getLanguage()->text('iisfriendsplus', 'all_users_friends_successfully'));
        }
        $this->redirect(OW::getRouter()->urlForRoute('iisfriendsplus_admin_config'));
    }
}