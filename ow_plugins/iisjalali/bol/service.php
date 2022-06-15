<?php
/**
 * Created by Mohammad Aghaabbasloo
 * Date: 10/16/2016
 * Time: 4:12 PM
 */
class IISJALALI_BOL_Service
{
    private static $classInstance;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    /**
     * @param OW_Event $event
     */
    public function onRenderFormatDateField(OW_Event $event){
        $params = $event->getParams();
        if((isset($params['isPresentationDate']) && $params['isPresentationDate']==true) &&  OW::getConfig()->getValue('iisjalali', 'dateLocale')==1)
        {
            $timeStamp = $params['timeStamp'];
            $idt = new IISJALALI_CLASS_IntlDateTime();
            $tmpDateArray = explode('/', date('Y/m/d',$timeStamp));
            $fa_date = $idt->gregorian_to_jalali($tmpDateArray[0],$tmpDateArray[1],$tmpDateArray[2]);
            $event->setData(array('jalaliSimpleFormat' => $result =  $fa_date[0] .'/'.$fa_date[1] .'/'.$fa_date[2]));
        }
        else if((isset($params['isBirthday']) && $params['isBirthday']==true) &&  OW::getConfig()->getValue('iisjalali', 'dateLocale')==1)
        {
            $format = OW::getConfig()->getValue('base', 'date_field_format');
            $idt = new IISJALALI_CLASS_IntlDateTime();
            $fa_date = $idt->gregorian_to_jalali((int)$params['year'],(int)$params['month'],(int)$params['day']);
            $faDate_array = $idt->jdate_words( array('ss'=>$fa_date[0],'mm'=>$fa_date[1],'rr'=>$fa_date[2]) );
            $event->setData(array('jalaliSimpleFormat' =>$result = $fa_date[2] .' '.$faDate_array['mm'] . ' '. $fa_date[0]));
        }
        else if(isset($params['timeStamp'])  &&  OW::getConfig()->getValue('iisjalali', 'dateLocale')==1) {
            $timeStamp = $params['timeStamp'];
            $onlyDate = $params['onlyDate'] ? $params['onlyDate'] : false;
            $militaryTime = (bool) OW::getConfig()->getValue('base', 'military_time');

            $language = OW::getLanguage();

            if ( !$timeStamp || $timeStamp==""){
                $event->setData(array('jalaliSimpleFormat' => '_INVALID_TS_'));
                return;
            }
            $idt = new IISJALALI_CLASS_IntlDateTime();
            $tmpDateArray = explode('/', date('Y/m/d',$timeStamp));
            $fa_date = $idt->gregorian_to_jalali($tmpDateArray[0],$tmpDateArray[1],$tmpDateArray[2]);
            $faDate_array = $idt->jdate_words( array('ss'=>$fa_date[0],'mm'=>$fa_date[1],'rr'=>$fa_date[2]) );
            if ( $onlyDate ){
                $event->setData(array('jalaliSimpleFormat' =>
                    $fa_date[2] .' '.$faDate_array['mm'] . ' '. $fa_date[0]));
            }else{
                $event->setData(array('jalaliSimpleFormat' =>
                    $militaryTime ?   $fa_date[2] .' '.$faDate_array['mm'] . ' '.$fa_date[0] .' '.$language->text('iisjalali', 'date_time_month_short_fa_hour').' '.strftime("%H:%M", $timeStamp):$fa_date[2] .''.$faDate_array['mm'] . ' '.$fa_date[0] .' ساعت '.strftime(" %I:%M%p", $timeStamp)));

            }

        }
    }
    /**
     * @param OW_Event $event
     */
    public function onBeforeValidatingField(OW_Event $event){
        $params = $event->getParams();
        $data = array();
        if($event->getData() != null){
            $data = $event->getData();
        }
        $idt = new IISJALALI_CLASS_IntlDateTime();
        if(isset($params['field']) && $params['field'] instanceof DateField && OW::getConfig()->getValue('iisjalali', 'dateLocale')==1) {
            $dateField = $params['field'];
            if($dateField->getValue()!=null && $dateField->getValue()!="") {
                $tmpdateArray = explode('/', $dateField->getValue());
                $dateArray = $idt->jalali_to_gregorian($tmpdateArray[0], $tmpdateArray[1], $tmpdateArray[2]);
                $dateField->setValue((string)$dateArray[0] . '/' . $dateArray[1] . '/' . $dateArray[2]);
            }
        }else if(isset($params['data']) && isset($params['data']['year_range']) && isset($params['data']['year_range']['from']) && isset($params['field']) && $params['field'] instanceof YearRange && OW::getConfig()->getValue('iisjalali', 'dateLocale')==1) {
            $dateFromArray = $idt->jalali_to_gregorian((int)($params['data']['year_range']['from']), (int)('7'), (int)('7'));
            $dateToArray = $idt->jalali_to_gregorian((int)($params['data']['year_range']['to']), (int)('7'), (int)('7'));
            $data['from'] = $dateFromArray[0];
            $data['to'] = $dateToArray[0];
        }
        $event->setData($data);
    }

    /**
     * @param OW_Event $event
     */
    public function onAfterDefaultDateValueSet(OW_Event $event){
        $params = $event->getParams();
        $idt = new IISJALALI_CLASS_IntlDateTime();

        $birthdayEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::SET_BIRTHDAY_RANGE_TO_JALALI, $params));
        if($birthdayEvent->getData()!=null && sizeof($birthdayEvent->getData())>0){
            $event->setData($birthdayEvent->getData());
        }

        // change year rate to jalali in admin config

        $dateRangeEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::CHANGE_DATE_RANGE_TO_JALALI, $params));
        if($dateRangeEvent->getData()!=null && sizeof($dateRangeEvent->getData())>0){
            $event->setData($dateRangeEvent->getData());
        }

        // check if jalali date is valid
        if(isset($params['validatePersianDate']) && $params['validatePersianDate']==true)
        {
            $chYear=$chMonth=$chDay=null;
            if(isset($params['chYear']))
            {
                $chYear = (int)$params['chYear'];
            }
            if(isset($params['chMonth']))
            {
                $chMonth = (int)$params['chMonth'];
            }
            if(isset($params['chDay']))
            {
                $chDay = (int)$params['chDay'];
            }
            $isValid= $this->checkPersianDate($chYear,$chMonth,$chDay);
            $event->setData(array('isValid' => $isValid));
        }


        // change date format to jalali for blog and news

        $dateBlogAndNewsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::CHANGE_DATE_FORMAT_TO_JALALI_FOR_BLOG_AND_NEWS, $params));
        if($dateBlogAndNewsEvent->getData()!=null && sizeof($dateBlogAndNewsEvent->getData())>0){
            $event->setData($dateBlogAndNewsEvent->getData());
        }
        //change jalali month's format  to word format
        if(isset($params['changeJalaliMonthToWord']) && $params['changeJalaliMonthToWord']==true ) {
            if (OW::getConfig()->getValue('iisjalali', 'dateLocale') == 1) {
                $faYear=$faMonth=$faDay=null;
                if(isset($params['faYear']))
                {
                    $faYear = (int)$params['faYear'];
                }
                if(isset($params['faMonth']))
                {
                    $faMonth = (int)$params['faMonth'];
                }
                if(isset($params['faDay']))
                {
                    $faDay = (int)$params['faDay'];
                }
                $jalaliWordMonth = $this->changeJalaliMonthToWord($faYear,$faMonth,$faDay);
                $event->setData(array('jalaliWordMonth' => $jalaliWordMonth));
            }
        }
        // change jalali to gregorian

        $dateBlogAndNewsEvent = OW::getEventManager()->trigger(new OW_Event(IISEventManager::CHANGE_DATE_FORMAT_TO_GREGORIAN, $params));
        if($dateBlogAndNewsEvent->getData()!=null && sizeof($dateBlogAndNewsEvent->getData())>0){
            $event->setData($dateBlogAndNewsEvent->getData());
        }
        //calculate jalali max day of a month
        $maxJalaliLastDayEvent= OW::getEventManager()->trigger(new OW_Event(IISEventManager::CALCULATE_JALALI_MONTH_LAST_DAY, $params));
        if($maxJalaliLastDayEvent->getData()!=null && sizeof($maxJalaliLastDayEvent->getData())>0){
            $event->setData($maxJalaliLastDayEvent->getData());
        }
    }


    public function calculateJalaliMonthLastDay(OW_Event $event){
        $params = $event->getParams();
        $idt = new IISJALALI_CLASS_IntlDateTime();
        if(isset($params['jalaliMaxMonthDay']) && $params['jalaliMaxMonthDay']==true  && OW::getConfig()->getValue('iisjalali', 'dateLocale')==1){
            if(isset($params['jalaliYear'])){
                $jalaliYear = (int)$params['jalaliYear'];
            }
            if(isset($params['jalaliMonth'])){
                $jalaliMonth = (int)$params['jalaliMonth'];
            }
            $event->setData(array('lastDay' => $idt->maxDay($jalaliYear, $jalaliMonth)));
        }
    }

    public function setBirthdayRangeToJalali(OW_Event $event){
        $params = $event->getParams();
        $idt = new IISJALALI_CLASS_IntlDateTime();
        if(isset($params['DefaultBirthRange']) && $params['DefaultBirthRange']==true  && OW::getConfig()->getValue('iisjalali', 'dateLocale')==1){
            $defaultYearFrom ='';
            $persian_defaultYearFrom = '';
            $defaultYearTo =  '';
            $persian_defaultYearTo = '';
            if(isset($params['defaultYearFrom'])){
                $date = $idt->gregorian_to_jalali((int)$params['defaultYearFrom'], (int)date('9'), (int)date('28'));
                $defaultYearFrom =  $date[0];
                $persian_defaultYearFrom = $date[0];
            }
            if(isset($params['defaultYearTo'])){
                $date = $idt->gregorian_to_jalali((int)$params['defaultYearTo'], (int)(date('9')), (int)(date('28')));
                $defaultYearTo =  $date[0];
                $persian_defaultYearTo = $date[0];
            }
            $event->setData(array('defaultYearFrom' => $defaultYearFrom, 'persian_defaultYearFrom' => $persian_defaultYearFrom,'defaultYearTo' => $defaultYearTo, 'persian_defaultYearTo' => $persian_defaultYearTo));
        }
    }

    public function changeDateRangeToJalali(OW_Event $event)
    {
        $params = $event->getParams();
        $idt = new IISJALALI_CLASS_IntlDateTime();
        if(isset($params['dateField']) || (isset($params['yearRange']) && $params['yearRange'] ==true )){
            if(isset($params['dateField'])) {
                $dateField = $params['dateField'];
            }
            if(OW::getConfig()->getValue('iisjalali', 'dateLocale')==1)
            {
                if(isset($params['setDefault']) && !empty( $dateField->getDefaultDate() )) {
                    $this->setPersianDefaultDate($dateField);
                }
                if(isset($params['year'])){
                    $date = $idt->gregorian_to_jalali((int)$params['year'], (int)('9'), (int)('28'));
                    $event->setData(array('year' => $date[0], 'persian_year' => $date[0]));
                }else if(isset($params['month'])){
                    $date = $idt->gregorian_to_jalali(date('Y', time()), (int)$params['month'], (int)date('d', time()));
                    $event->setData(array('month' => $date[1], 'persian_month' => $date[1] ));
                }else if(isset($params['day'])){
                    $date = $idt->gregorian_to_jalali(date('Y', time()), (int)date('m', time()), (int)$params['day']);
                    $event->setData(array('day' => $date[2], 'persian_day' => $date[2] ));
                }else if(isset($params['lastDay'])){
                    if(empty( $dateField->getDefaultDate()))
                    {
                        $event->setData(array('lastDay' => 31));
                    }
                    else {
                        $event->setData(array('lastDay' => $idt->maxDay($dateField->getDefaultDate()['year'], $dateField->getDefaultDate()['month'])));
                    }
                }
            }
        }
    }


    public function changeDateFormatToJalaliForBlogAndNews(OW_Event $event){
        $params = $event->getParams();
        $idt = new IISJALALI_CLASS_IntlDateTime();
        if(isset($params['changeTojalali']) && $params['changeTojalali']==true ) {
            if (OW::getConfig()->getValue('iisjalali', 'dateLocale') == 1) {
                $yearTochange=date('Y', time());
                $monthTochange = (int)date('m', time());
                $dayTochange = (int)date('d', time());
                if(isset($params['yearTochange']))
                {
                    $yearTochange = (int)$params['yearTochange'];
                }
                if(isset($params['monthTochange']))
                {
                    $monthTochange = (int)$params['monthTochange'];
                }
                if(isset($params['dayTochange']))
                {
                    $dayTochange = (int)$params['dayTochange'];
                }
                $fa_date = $idt->gregorian_to_jalali($yearTochange,$monthTochange,$dayTochange);
                $changedYear = $fa_date[0];
                $changedMonth = $fa_date[1];
                $changedDay = $fa_date[2];
                if(isset($params['monthWordFormat']) && $params['monthWordFormat']==true){
                    $faDate_array = $idt->jdate_words( array('ss'=>$fa_date[0],'mm'=>$fa_date[1],'rr'=>$fa_date[2]) );
                    $changedMonth =$faDate_array['mm'];
                }
                $event->setData(array('changedYear' => $changedYear, 'changedMonth' => $changedMonth, 'changedDay' => $changedDay ));
            }
        }
    }


    public function changeDateFormatToGregorian(OW_Event $event){
        $params = $event->getParams();
        $idt = new IISJALALI_CLASS_IntlDateTime();
        if(isset($params['changeNewsJalaliToGregorian']) && $params['changeNewsJalaliToGregorian']==true ) {
            if (OW::getConfig()->getValue('iisjalali', 'dateLocale') == 1) {
                if(isset($params['faYear']))
                {
                    $faYear = (int)$params['faYear'];
                }
                if(isset($params['faMonth']))
                {
                    $faMonth = (int)$params['faMonth'];
                }
                if(isset($params['faDay']))
                {
                    $faDay = (int)$params['faDay'];
                }
                $dateArray = $idt->jalali_to_gregorian($faYear, $faMonth, $faDay);
                $event->setData(array('gregorianYearNews' => $dateArray[0],'gregorianMonthNews' => $dateArray[1] ,'gregorianDayNews' => $dateArray[2] ));
            }
        }
    }
    public function changeJalaliMonthToWord($faYear,$faMonth,$faDay){
        $idt = new IISJALALI_CLASS_IntlDateTime();
        $faDate_array = $idt->jdate_words( array('ss'=>$faYear,'mm'=>$faMonth,'rr'=>$faDay) );
        $changedMonth =$faDate_array['mm'];
        return $changedMonth;
    }
    /**
     * @param $dateField
     */
    public function setPersianDefaultDate($dateField)
    {
        $idt = new IISJALALI_CLASS_IntlDateTime();
        $date = $idt->gregorian_to_jalali((int)$dateField->getDefaultDate()['year'],$dateField->getDefaultDate()['month'],(int)$dateField->getDefaultDate()['day']);
        $dateField->setDefaultDate((int) $date[0],(int) $date[1],(int) $date[2]);
    }

    public function checkPersianDate($year,$month,$day)
    {
        $idt = new IISJALALI_CLASS_IntlDateTime();
        return $idt->jcheckdate((int)$month,(int)$day,(int)$year);
    }

    public function onBeforeDocumentRender()
    {
        if(OW::getConfig()->getValue('iisjalali', 'dateLocale')==1)
        {
            if (!isset($_COOKIE['iisjalali']) )
            {
                setcookie('iisjalali',1);
            }
        }
        $jsDir = OW::getPluginManager()->getPlugin('iisjalali')->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir.'iisjalali.js');
    }
}