<?php
class IISADVANCEEDITOR_CTRL_Admin extends ADMIN_CTRL_Abstract{
    public function index(){
        if (!OW::getUser()->isAuthenticated()){
            throw new Redirect404Exception();
        }
        if(!OW::getUser()->isAdmin()){
            throw new Redirect404Exception();
        }

        $this->setPageHeading(OW::getLanguage()->text('iisadvanceeditor', 'config_page_title'));
        $this->setPageTitle(OW::getLanguage()->text('iisadvanceeditor', 'config_page_title'));
        $this->setPageHeadingIconClass('ow_ic_gear_wheel');

        $form = new Form('editor_config_form');

        $fieldMaxCount  = new TextField('max_symbols_count');
        $fieldMaxCount->setRequired();
        $fieldMaxCount->setLabel($this->text('iisadvanceeditor','max_symbols_count_title'));
        $validator = new IntValidator(1);
        $validator->setErrorMessage($this->text('iisadvanceeditor','max_symbols_count_error'));
        $fieldMaxCount->addValidator($validator);
        $form->addElement($fieldMaxCount);

        $submit = new Submit('add');
        $submit->setValue($this->text('iisadvanceeditor','set_max_symbols_count'));
        $form->addElement($submit);
        $this->addForm($form);


        if(OW::getRequest()->isPost()){
            if($form->isValid($_POST)){
                $data = $form->getValues();
                $maxSymbolsCount = $data['max_symbols_count'];
                if ( OW::getConfig()->configExists('iisadvanceeditor','MaxSymbolsCount') )
                {
                    OW::getConfig()->saveConfig('iisadvanceeditor','MaxSymbolsCount', $maxSymbolsCount);
                }
                else
                {
                    OW::getConfig()->addConfig('iisadvanceeditor','MaxSymbolsCount', $maxSymbolsCount);
                }
                $adminPlugin = OW::getPluginManager()->getPlugin('admin');
                if(isset($adminPlugin) && $adminPlugin->isActive()){
                    OW::getFeedback()->info(OW::getLanguage()->text($adminPlugin->getKey(), 'updated_msg'));
                }
                $this->redirect();
            }
        }else{
            if ( OW::getConfig()->configExists('iisadvanceeditor','MaxSymbolsCount') ){
                $maxSymbolsCount = OW::getConfig()->getValue('iisadvanceeditor','MaxSymbolsCount');
                $fieldMaxCount->setValue($maxSymbolsCount);
            }
        }
    }
    private function text( $prefix, $key, array $vars = null )
    {
        return OW::getLanguage()->text($prefix, $key, $vars);
    }


}