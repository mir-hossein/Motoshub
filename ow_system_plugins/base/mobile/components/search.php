<?php

/**
 * Search component.
 * 
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_system_plugins.base.components
 * @since 1.0
 */
class BASE_MCMP_Search extends OW_MobileComponent
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
     * @var string
     */
    private $placeholder;
    
    /**
     * BASE_CMP_TagSearch constructor.
     * @param null $url
     * @param string $label_lang_key
     */
    public function __construct( $url = null, $label_lang_key = 'base+tag_search', $param_name = 'tag', $placeholder = '')
    {
        parent::__construct();
        $this->url = $url;
        $this->box_label = $label_lang_key;
        $this->param_name = $param_name;
        if($placeholder == '') {
            $placeholder = OW::getLanguage()->text('admin', 'search');
        }
        $this->placeholder = $placeholder;
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
        $formId = 'search_form_' . $randId;
        $elId = 'search_input_' . $randId;

        $this->assign('form_id', $formId);
        $this->assign('el_id', $elId);
        $this->assign('lang_label', $this->box_label);
        $this->assign('placeholder', $this->placeholder);

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
    }
}