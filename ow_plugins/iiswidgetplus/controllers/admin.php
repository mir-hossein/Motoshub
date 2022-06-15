<?php


class IISWIDGETPLUS_CTRL_Admin extends ADMIN_CTRL_Abstract
{

    public function index()
    {
        $this->setPageTitle(OW::getLanguage()->text('iiswidgetplus', 'index_page_title'));
        $this->setPageHeading(OW::getLanguage()->text('iiswidgetplus', 'index_page_heading'));
        $form = new Form('rateWidgetView');
        $language = OW::getLanguage();

        $whoCanView = new RadioField('rate_widget_display');
        $whoCanView->addOptions(array('1' => $language->text('iiswidgetplus', 'all_users'), '2' => $language->text('iiswidgetplus', 'only_login_users')));
        $whoCanView->setLabel($language->text('iiswidgetplus', 'display_rate_widget'));
        $whoCanView->setValue(OW::getConfig()->getValue('iiswidgetplus', 'displayRateWidget'));
        $form->addElement($whoCanView);
        $submit = new Submit('save');
        $submit->setValue($language->text('admin', 'save_btn_label'));
        $form->addElement($submit);
        $this->addForm($form);
        if (OW::getRequest()->isPost() && $form->isValid($_POST)) {
            $values = $form->getValues();
            OW::getConfig()->saveConfig('iiswidgetplus', 'displayRateWidget', $values["rate_widget_display"]);
            OW::getFeedback()->info($language->text('iiswidgetplus', 'settings_updated'));
            }
    }
}