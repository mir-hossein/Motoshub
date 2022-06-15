<?php

class IISMAINPAGE_MCMP_PhotoList extends OW_MobileComponent
{

    protected $list = array();

    public function __construct( $listType, $photos )
    {
        parent::__construct();

        $this->list = $photos;
        $this->setTemplate(OW::getPluginManager()->getPlugin('iismainpage')->getMobileCmpViewDir().'photo_list.html');
        $this->assign('photos', $this->list);
        if(!empty($this->list)){
            $this->assign('no_content', false);
        }else{
            $this->assign('no_content', OW::getLanguage()->text('photo', 'no_items'));
        }
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('photo')->getStaticJsUrl() . 'masonry.pkgd.min.js');
        $js = "
var \$grid = $('.photo_section').masonry({
itemSelector: '.owm_photo_list_item',
originLeft: false,
transitionDuration: '0s'
});
\$grid.masonry()
setInterval(function(){ \$grid.masonry() }, 100);
";
        OW::getDocument()->addOnloadScript($js);
    }
}



