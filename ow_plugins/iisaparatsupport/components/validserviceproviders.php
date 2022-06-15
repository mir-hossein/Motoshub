<?php

class IISAPARATSUPPORT_CMP_Validserviceproviders extends UrlValidator
{

    /***
     * Constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /***
     * @param mixed $value
     * @return bool
     */
    public function isValid( $value )
    {
        if (isset($_POST['input_type']) && $_POST['input_type']!="aparat"){
            return true;
        }

        if(!parent::isValid($value))
            return false;

        if(strpos($value, 'http') === false)
            $value = 'https://' . $value;

        $value = trim($value);
        if(strpos($value, 'aparat.com/') === false )
            return false;

        $parts = explode('/', $value);
        if(count($parts) != 5)
            return false;

        return true;
    }

}
