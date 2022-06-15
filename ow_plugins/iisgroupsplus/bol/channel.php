<?php

class IISGROUPSPLUS_BOL_Channel extends OW_Entity
{

    public $groupId;


    public function getGroupId()
    {
        return $this->groupId;
    }

    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
    }



}
