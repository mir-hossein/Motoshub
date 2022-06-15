<?php



class IISPROFILEMANAGEMENT_MCLASS_EventHandler
{
    private static $classInstance;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct()
    {
    }

    public function init()
    {
        $service = IISPROFILEMANAGEMENT_BOL_Service::getInstance();
        $eventManager = OW::getEventManager();
        $eventManager->bind(OW_EventManager::ON_BEFORE_DOCUMENT_RENDER, array( $service, 'onBeforeDocumentRender'));
        $eventManager->bind('base.preference_menu_items', array($this, 'onPreferenceMenuItem'));
        $eventManager->bind(BOL_PreferenceService::PREFERENCE_ADD_FORM_ELEMENT_EVENT, array($this, 'onPreferenceAddFormElement'));
        $eventManager->bind(BOL_PreferenceService::PREFERENCE_SECTION_LABEL_EVENT, array($this, 'onAddPreferenceSectionLabels'));
    }

    public function onMobileAddItem(BASE_CLASS_EventCollector $event){
        $label = OW::getLanguage()->text('base','edit_index');
        $url = OW::getRouter()->urlForRoute('iisprofilemanagement.edit');
        $event->add(array('label' => $label, 'url' => $url));

        $label = OW::getLanguage()->text('base','preference_index');
        $url = OW::getRouter()->urlForRoute('iisprofilemanagement.preference_index');
        $event->add(array('label' => $label, 'url' => $url));

        $label = OW::getLanguage()->text('base','change_password');
        $url = OW::getRouter()->urlForRoute('iisprofilemanagement.edit.changepassword');
        $event->add(array('label' => $label, 'url' => $url));
    }
    public function onPreferenceMenuItem( BASE_CLASS_EventCollector $event )
    {
        $router = OW_Router::getInstance();
        $language = OW::getLanguage();

        $menuItem = new BASE_MenuItem();

        $menuItem->setKey('preference');
        $menuItem->setLabel($language->text('base', 'preference_menu_item'));
        $menuItem->setIconClass('ow_ic_gear_wheel');
        $menuItem->setUrl($router->urlForRoute('iisprofilemanagement.preference_index'));
        $menuItem->setOrder(1);
        $event->add($menuItem);
    }

    public function onAddPreferenceSectionLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();

        $sectionLabels = array(
            'general' => array(
                'label' => $language->text('base', 'preference_section_general'),
                'iconClass' => 'ow_ic_script'
            )
        );

        $event->add($sectionLabels);
    }

    public function onPreferenceAddFormElement( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();

        $params = $event->getParams();
        $values = $params['values'];

        $fromElementList = array();

        $fromElement = new CheckboxField('mass_mailing_subscribe');
        $fromElement->setLabel($language->text('base', 'preference_mass_mailing_subscribe_label'));
        $fromElement->setDescription($language->text('base', 'preference_mass_mailing_subscribe_description'));

        if ( isset($values['mass_mailing_subscribe']) )
        {
            $fromElement->setValue($values['mass_mailing_subscribe']);
        }


        $timeZoneSelect = new Selectbox("timeZoneSelect");
        $timeZoneSelect->setLabel($language->text('admin', 'timezone'));
        $timeZoneSelect->addOptions(UTIL_DateTime::getTimezones());

        $timeZoneSelect->setValue($values['timeZoneSelect']);

        $fromElementList[] = $timeZoneSelect;
        $fromElementList[] = $fromElement;
        $event->add($fromElementList);

    }

}