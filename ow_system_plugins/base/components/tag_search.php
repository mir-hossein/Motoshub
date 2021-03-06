<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * Tag search component. Works only for whole entity types.   
 * 
 * @author Sardar Madumarov <madumarov@gmail.com>
 * @package ow_system_plugins.base.components
 * @since 1.0
 */
class BASE_CMP_TagSearch extends OW_Component
{
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $routeName;
    /**
     * @var string
     */
    private $box_label;
    /**
     * @var string
     */
    private $param_name;
    
    /**
     * BASE_CMP_TagSearch constructor.
     * @param null $url
     * @param string $label_lang_key
     */
    public function __construct( $url = null, $label_lang_key = 'base+tag_search', $param_name = 'tag', $show_tag_cloud=false,$entityType=false, $tagUrl=false , $tagsCount=false )
    {
        parent::__construct();
        $this->url = $url;
        $this->box_label = $label_lang_key;
        $this->param_name = $param_name;
        $this->show_tag_cloud = $show_tag_cloud;
        if($show_tag_cloud){
            $this->service = BOL_TagService::getInstance();
            $this->entityType = trim($entityType);
            $this->tagUrl = trim($tagUrl);
            $this->tagsCount = $tagsCount;
            $this->entityId = 0;
        }
    }

    /**
     * Sets route name for url generation. 
     * Route should be added to router and contain var - `tag`.
     * 
     * @param $routeName
     * @return BASE_CMP_TagSearch
     */
    public function setRouteName( $routeName )
    {
        $this->routeName = trim($routeName);
    }

    /**
     * @see OW_Renderable::onBeforeRender 
     */
    public function onBeforeRender()
    {
        $randId = rand(1, 100000);
        $formId = 'tag_search_form_' . $randId;
        $elId = 'tag_search_input_' . $randId;

        $this->assign('form_id', $formId);
        $this->assign('el_id', $elId);
        $this->assign('lang_label', $this->box_label);

        $mobileEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::IS_MOBILE_VERSION,array('check' => true)));
        if(isset($mobileEvent->getData()['isMobileVersion'])&& $mobileEvent->getData()['isMobileVersion']==true) {
            $this->assign('isMobile', true);
        }

        $urlToRedirect = ($this->routeName === null) ? OW::getRequest()->buildUrlQueryString($this->url, array($this->param_name => '_tag_')) : OW::getRouter()->urlForRoute($this->routeName, array($this->param_name => '#tag#'));

        $script = "
			var tsVar" . $randId . " = '" . $urlToRedirect . "';
			
			$('#" . $formId . "').bind( 'submit', 
				function(){
					if( !$.trim( $('#" . $elId . "').val() ) )
					{
						OW.error(".  json_encode(OW::getLanguage()->text('base', 'tag_search_empty_value_error')).");
					}
					else
					{
						window.location = tsVar" . $randId . ".replace(/_tag_/, $('#" . $elId . "').val());
					}

					return false;  
				}
			);
		";

        OW::getDocument()->addOnloadScript($script);

    if($this->show_tag_cloud){

        $tagCloud = new BASE_CMP_EntityTagCloud($this->entityType, $this->tagUrl, $this->tagsCount);
        $tagCloud->setTemplate(OW::getPluginManager()->getPlugin('base')->getCmpViewDir() . 'big_tag_cloud.html');
        $this->assign('show_tag_cloud', $this->show_tag_cloud);
        $this->addComponent('tagCloud', $tagCloud);
    }

    }
}