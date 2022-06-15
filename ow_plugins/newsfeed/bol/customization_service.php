<?php

/**
 * 
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.newsfeed.bol
 * @since 1.0
 */

class NEWSFEED_BOL_CustomizationService
{
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return NEWSFEED_BOL_CustomizationService
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    private function __construct()
    {
    }
    
    public function getActionTypes()
    {
        $event = new BASE_CLASS_EventCollector('feed.collect_configurable_activity');
        OW::getEventManager()->trigger($event);
        $actions = array();
        $eventData = $event->getData();
        
        $configTypes = json_decode(OW::getConfig()->getValue('newsfeed', 'disabled_action_types'), true);
        
        foreach ( $eventData as $item ) {
            $item['activity'] = is_array($item['activity']) ? implode(',', $item['activity']) : $item['activity'];
            if (isset($configTypes[$item['activity']])) {
                if (is_array($configTypes[$item['activity']])){
                    $item['active1'] = $configTypes[$item['activity']][0];
                    $item['active2'] = $configTypes[$item['activity']][1];
                    $item['active3'] = $configTypes[$item['activity']][2];
                }else{
                    $item['active1'] = $item['active2'] = $item['active3'] = $configTypes[$item['activity']];
                }
            } else {
                $item['active1'] = $item['active2'] = $item['active3'] = empty($item['active']) || $item['active'];
            }
            $actions[] = $item;
        }
        
        return $actions; 
    }
    
    public function getDisabledEntityTypes( $page = 'all')
    {
        $allTypes = $this->getActionTypes();
        $out = array();
        foreach ( $allTypes as $type )
        {
            if ($page == 'dashboard')
            {
                if(!$type['active1'])
                    $out[] = $type['activity'];
            }
            elseif ($page == 'index')
            {
                if(!$type['active2'])
                    $out[] = $type['activity'];
            }
            elseif ($page == 'profile')
            {
                if(!$type['active3'])
                    $out[] = $type['activity'];
            }
            else
            {
                if ( !$type['active1'] && !$type['active2'] && !$type['active3'] )
                    $out[] = $type['activity'];
            }
        }
        
        return $out;
    }

    public function activityVisibility(OW_Event $event)
    {
        $params = $event->getParams();
        if (isset($params['activity'])) {
            $activity = $params['activity'];
            $dto = NEWSFEED_BOL_Service::getInstance()->findActionById($activity->actionId);
            if (!isset($activity->id)) {
                //$activityKey = "create.{$params['entityId']}:{$params['entityType']}.{$params['entityId']}:{$uid}";
                $activityKey = "create." . $dto->entityId . ":" . $dto->entityType . "." . $dto->entityId . ":" . $activity->userId;
            } else {
                $activityKey = $activity->activityType . "." . $activity->id . ":" . $dto->entityType . "." . $dto->entityId . ":" . $activity->userId;
            }

            # current visibility
            $visibility = decbin($activity->visibility);

            # check index
            $disabledActivity = $this->getDisabledEntityTypes('index');
            if (!empty($disabledActivity)) {
                $status = !$this->testHomePageActivityKey($activityKey, $disabledActivity);
                if (!$status) {
                    if (strlen($visibility) - 1 >= 0) {
                        $visibility[strlen($visibility) - 1] = 0;
                    }
                }
            }

            # check dashboard
            $disabledActivity = $this->getDisabledEntityTypes('dashboard');
            if (!empty($disabledActivity)) {
                $status = !$this->testHomePageActivityKey($activityKey, $disabledActivity);
                if (!$status) {
                    if (strlen($visibility) - 2 >= 0) {
                        $visibility[strlen($visibility) - 2] = 0;
                    }
                }
            }

            # check profile
            $disabledActivity = $this->getDisabledEntityTypes('profile');
            if (!empty($disabledActivity)) {
                $status = !$this->testHomePageActivityKey($activityKey, $disabledActivity);
                if (!$status) {
                    if (strlen($visibility) - 4 >= 0) {
                        $visibility[strlen($visibility) - 4] = 0;
                    }
                }
            }

            # new visibility
            $visibility = bindec($visibility);
            $event->setData(array('visibilityChanged' => $visibility));
        }
    }

    public function testHomePageActivityKey($key, $testKey, $all = false)
    {
        $key = NEWSFEED_BOL_Service::getInstance()->parseActivityKey($key);
        $testKey = NEWSFEED_BOL_Service::getInstance()->processActivityKey($testKey);

        $result = true;
        foreach ($testKey as $tk) {
            $result = true;
            foreach ($tk as $type => $f) {
                foreach ($f as $k => $v) {
                    $r = empty($key[$type][$k]) ? true : empty($v) || $key[$type][$k] == $v;
                    if (!$r) {
                        $result = false;

                        break 2;
                    }
                }
            }

            if ($result && !$all || !$result && $all) {
                break;
            }
        }

        return $result;
    }
}