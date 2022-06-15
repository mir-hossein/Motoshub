<?php


/**
 * Forum admin action controller
 *
 */
class IISJALALI_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    /**
     * @param array $params
     */
	public function index(array $params = array())
	{
        OW::getDocument()->setTitle(OW::getLanguage()->text('iisjalali', 'admin_settings_title'));
        OW::getDocument()->setHeading(OW::getLanguage()->text('iisjalali', 'admin_settings_title'));
        $config =  OW::getConfig();
        $language = OW::getLanguage();

        $form = new Form('form');
        $form->setAjax();
        $form->setAjaxResetOnSuccess(false);
        $form->setAction(OW::getRouter()->urlForRoute('iisjalali_admin_config'));
        $form->bindJsFunction(Form::BIND_SUCCESS,'function( data ){ if(data && data.result){OW.info(\''.$language->text('iisjalali', 'settings_updated').'\')  }  }');

        $dateLocale = new Selectbox('dateLocale');
        $option = array();
        $option[1] = OW::getLanguage()->text('iisjalali', 'date_locale_jalali_format');
        $option[2] = OW::getLanguage()->text('iisjalali', 'date_locale_gregorian_format');
        $dateLocale->setValue(OW::getConfig()->getValue('iisjalali', 'dateLocale'));
        $dateLocale->setHasInvitation(false);
        $dateLocale->setRequired();
        $dateLocale->setOptions($option);
        $form->addElement($dateLocale);

        $submit = new Submit('save');
        $form->addElement($submit);
        $this->addForm($form);

        if ( OW::getRequest()->isAjax() &&  OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $config->saveConfig('iisjalali', 'dateLocale', $form->getElement('dateLocale')->getValue());
            setcookie("iisjalali", "", time() - 3600);
            setcookie("iisjalali", $form->getElement('dateLocale')->getValue());
            exit(json_encode(array('result' => true)));
        }
	}
	
}