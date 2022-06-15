<?php

class BASE_CMP_ConsoleItem extends OW_Renderable
{

    protected $content = null, $control = null, $hidden = false;
    private $uniqId, $class = array();

    public function __construct()
    {
        parent::__construct();

        $this->uniqId = IISSecurityProvider::generateUniqueId('console_item_');

        $template = OW::getPluginManager()->getPlugin('base')->getCmpViewDir() . 'console_item.html';
        $this->setTemplate($template);
    }

    public function setIsHidden( $hidden = true )
    {
        $this->hidden = $hidden;
    }

    public function getIsHidden()
    {
        return $this->hidden;
    }

    public function setContent( $content )
    {
        $this->content = $content;
    }

    public function setControl( $control )
    {
        $this->control = $control;
    }

    public function getUniqId()
    {
        return $this->uniqId;
    }

    public function getContentUniqId()
    {
        return $this->uniqId . '_content';
    }

    public function addClass( $class )
    {
        $this->class[$class] = $class;
    }

    public function getClass()
    {
        return implode(' ', $this->class);
    }

    public function render()
    {
        $content = array();

        if ( !empty($this->content) )
        {
            $content['html'] = $this->content;
            $content['uniqId'] = $this->getContentUniqId();
        }
        $item = array
        (
            'uniqId' => $this->getUniqId(),
            'class' => $this->getClass(),
            'content' => $content,
            'html' => $this->control,
            'hidden' => $this->getIsHidden()
        );
        $event = new OW_Event(IISEventManager::ON_BEFORE_CONSOLE_ITEM_RENDER, array('item'=>$item));
        OW::getEventManager()->trigger($event);
        $data = $event->getData();
        if ( isset($data['item']) ){
            $item = $data['item'];
        }
        $this->assign('item',$item);
        return parent::render();
    }
}