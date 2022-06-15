<?php

class IISGROUPSPLUS_CLASS_LabelValidator extends OW_Validator
{
    public function isValid( $label )
    {
        if ( $label === null )
        {
            return false;
        }

        $alreadyExist = IISGROUPSPLUS_BOL_CategoryDao::getInstance()->findIsExistLabel($label);

        if ( !isset($alreadyExist) )
        {
            return true;
        }
        return false;
    }
}
