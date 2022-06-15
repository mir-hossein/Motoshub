<?php

class NEWSFEED_FORMAT_Video extends NEWSFEED_CLASS_Format
{
    protected $uniqId;
    
    public function __construct($vars, $formatName = null) 
    {
        parent::__construct($vars, $formatName);
        
        $this->uniqId = IISSecurityProvider::generateUniqueId("vf-");
        $this->vars = $this->prepareVars($vars);
    }
    
    protected function prepareVars( $vars )
    {
        $defaults = array(
            "image" => null,
            "iconClass" => null,
            "title" => '',
            "description" => '',
            "status" => null,
            "url" => null,
            "embed" => ''
        );
        $videoId = $vars['url']['vars']['id'];
        $event = new OW_Event('videplus.on.video.list.view.render', array('forNewsFeed'=>true,'videoId'=>$videoId));
        OW::getEventManager()->trigger($event);
        if(isset($event->getData()['source']) && isset($event->getData()['height']) && isset($event->getData()['width'])){
            $vars['width']=$event->getData()['width'];
            $vars['height']=$event->getData()['height'];
            $vars['videoFile']=true;
            $vars['source']=$event->getData()['source'];
            $vars['image']="";
            $vars['embed']="";
            if(isset($event->getData()['thumbUrl'])) {
                $vars['thumbUrl'] = $event->getData()['thumbUrl'];
            }
        }
        $out = array_merge($defaults, $vars);
        $out["url"] = $this->getUrl($out["url"]);
        $out['blankImg'] = OW::getThemeManager()->getCurrentTheme()->getStaticUrl() . 'mobile/images/1px.png';
        
        return $out;
    }

    protected function initJs()
    {
        $js = UTIL_JsGenerator::newInstance();
        
        $code = BOL_TextFormatService::getInstance()->addVideoCodeParam($this->vars['embed'], "autoplay", 0);
        $code = BOL_TextFormatService::getInstance()->addVideoCodeParam($code, "play", 0);

        $js->addScript('$(".ow_oembed_video_cover", "#" + {$uniqId}).click(function() { '
                . '$("#" + {$uniqId}).addClass("ow_video_playing"); '
                . '$(".ow_newsfeed_item_picture", "#" + {$uniqId}).html({$embed});'
                . 'return false; });', array(
            "uniqId" => $this->uniqId,
            "embed" => $code
        ));
        OW::getDocument()->addOnloadScript($js);
        OW::getEventManager()->trigger(new OW_Event(IISEventManager::ON_AFTER_VIDEO_RENDERED, array('uniqId' =>$this->uniqId )));
    }
    
    public function onBeforeRender()
    {
        parent::onBeforeRender();
        
        $this->assign("uniqId", $this->uniqId);
        $this->assign('vars', $this->vars);
        
        if ( $this->vars['embed'] && (!isset($this->vars['videoFile'])))
        {
            $this->initJs();
        }
    }
}
