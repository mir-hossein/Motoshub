<?php

class INSTALL_CMP_Steps extends INSTALL_Component
{
    private $steps = array();

    /**
     * Constructor
     *
     * @param array $optionalPlugins
     */
    public function __construct(array $optionalPlugins = array())
    {
        parent::__construct();
        $this->add('rules', 'قوانین');
        $this->add('site', 'سایت');
        $this->add('db', 'پایگاه داده');
        $this->add('install', 'نصب');

        // allow to admin select additional plugins
        if ( $optionalPlugins )
        {
            $this->add('plugins', 'افزونه‌ها');
        }
    }
    
    public function add($key, $label, $active = false)
    {
        $this->steps[$key] = array( 
            'label' => $label,
            'active' => $active
        );
    }
    
    public function activate($key)
    {
        foreach ( $this->steps as & $step )
        {
            $step['active'] = false;
        }
        
        $this->steps[$key]['active'] = true;
    }
    
    public function onBeforeRender()
    {
        parent::onBeforeRender();
        
        $this->assign('steps', $this->steps);
    }
}