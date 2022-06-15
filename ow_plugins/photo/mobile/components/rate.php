<?php


class PHOTO_MCMP_Rate extends OW_MobileComponent
{

    public function __construct( $pluginKey, $entityType, $entityId, $ownerId )
    {
        parent::__construct();
        if(OW::getPluginManager()->isPluginActive('iiswidgetplus') && OW::getConfig()->getValue('iiswidgetplus', 'displayRateWidget')==2 && !OW::getUser()->isAuthenticated()) {
            $this->assign('display', false);
            return;
        }
        else
            $this->assign('display', true);

        $service = BOL_RateService::getInstance();

        $maxRate = $service->getConfig(BOL_RateService::CONFIG_MAX_RATE);

        $cmpId = IISSecurityProvider::generateUniqueId();

        $entityId = (int) $entityId;
        $entityType = trim($entityType);
        $ownerId = (int) $ownerId;

        if ( OW::getUser()->isAuthenticated() )
        {
            $userRateItem = $service->findRate($entityId, $entityType, OW::getUser()->getId());

            if ( $userRateItem !== null )
            {
                $userRate = $userRateItem->getScore();
            }
            else
            {
                $userRate = null;
            }
        }
        else
        {
            $userRate = null;
        }

        $this->assign('maxRate', $maxRate);
        $totalRate = $service->findRateInfoForEntityItem($entityId,'photo_rates');
        $avgScore = !isset($totalRate['avg_score']) ? '' : round($totalRate['avg_score'], 2);
        $countScore = !isset($totalRate['rates_count']) ? 0 : (int) $totalRate['rates_count'];
        //$this->assign('totalScore',$avgScore);
        //$this->assign('countScore',$countScore);
        $this->addComponent('totalScore', new PHOTO_MCMP_TotalScore($entityId, $entityType, $maxRate));
        $this->assign('cmpId', $cmpId);
        $this->assign('ownerId',$ownerId);
        $this->assign('userId',OW::getUser()->getId());

        $jsParamsArray = array(
            'cmpId' => $cmpId,
            'userRate' => $userRate,
            'entityId' => $entityId,
            'entityType' => $entityType,
            'itemsCount' => $maxRate,
            'respondUrl' => OW::getRouter()->urlFor('BASE_CTRL_Rate', 'updateRate'),
            'ownerId' => $ownerId
        );
        $code='';
        $iisSecuritymanagerEvent= OW::getEventManager()->trigger(new OW_Event('iissecurityessentials.on.generate.request.manager',
            array('senderId'=>Ow::getUser()->getId(),'receiverId'=>$entityId,'isPermanent'=>true,'activityType'=>'update_rate')));
        if(isset($iisSecuritymanagerEvent->getData()['code'])){
            $code = $iisSecuritymanagerEvent->getData()['code'];
            $jsParamsArray['respondUrl'] = OW::getRequest()->buildUrlQueryString(OW::getRouter()->urlFor('BASE_CTRL_Rate', 'updateRate'),array('code' =>$code));
        }
        OW::getDocument()->addOnloadScript("var rate$cmpId = new OwRate(" . json_encode($jsParamsArray) . "); rate$cmpId.init();");
    }
}