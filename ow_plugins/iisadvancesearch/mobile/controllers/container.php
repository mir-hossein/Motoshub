<?php

/**
 * IIS Advance Search
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisadvancesearch
 * @since 1.0
 */

class IISADVANCESEARCH_MCTRL_Container extends OW_MobileActionController
{
    public function index($params)
    {
        OW::getNavigation()->activateMenuItem(OW_Navigation::MAIN, 'iisadvancesearch', 'search_users');

        OW::getDocument()->setHeading(OW::getLanguage()->text('iisadvancesearch','search_users'));
        $this->setPageHeading(OW::getLanguage()->text('iisadvancesearch', 'search_users'));
        $this->setPageHeadingIconClass('ow_ic_write');

        $place = 'iisadvancesearch';
        $componentPanel = $this->initDragAndDrop($place, OW::getUser()->getId());
    }

    private function initDragAndDrop( $place, $entityId = null, $componentTemplate = "widget_panel" )
    {
        $widgetService = BOL_MobileWidgetService::getInstance();

        $state = $widgetService->findCache($place);
        if ( empty($state) )
        {
            $state = array();
            $state['defaultComponents'] = $widgetService->findPlaceComponentList($place);
            $state['defaultPositions'] = $widgetService->findAllPositionList($place);
            $state['defaultSettings'] = $widgetService->findAllSettingList();

            $widgetService->saveCache($place, $state);
        }

        $defaultComponents = $state['defaultComponents'];
        $defaultPositions = $state['defaultPositions'];
        $defaultSettings = $state['defaultSettings'];

        $componentPanel = new BASE_MCMP_WidgetPanel($place, $entityId, $defaultComponents, $componentTemplate);
        $componentPanel->setPositionList($defaultPositions);
        $componentPanel->setSettingList($defaultSettings);

        $this->addComponent('dnd', $componentPanel);

        return $componentPanel;
    }

}