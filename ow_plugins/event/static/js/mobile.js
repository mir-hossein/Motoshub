var eventAddForm = function( $params )
{
    var self = this;

    var $startTime = $('select[name=\'start_time\']');
    var $startDateValue = $('input[name=\'start_date\']');
    //var $startDateSelectbox = $('select', $('#' + $params['start_date_id']));

    var $endTime = $('select[name=\'end_time\']');
    var $endDateValue = $('input[name=\'end_date\']');
    var $endDateSelectbox = $('select', $('#' + $params['tdId']));

    var end_date_id = $params['end_date_id'];

    if($('#' + $params['checkbox_id']).attr('checked'))
        $('#end_date_div').show();

    $('#' + $params['checkbox_id']).click(
        function(){
            if($(this)[0].checked )
            {
                var $date = $startDateValue.val();
                if(typeof(getCookie) == "function" && getCookie("iisjalali")==1)
                {
                    var res = $date.split("/");
                    var $tempDate;
                    $tempDate = jalali_to_gregorian(parseInt(res[0]), parseInt(res[1]), parseInt(res[2]));
                    $date = $tempDate[0]+"/" + $tempDate[1]+"/"+$tempDate[2];
                }
                var regexp = /^(\d+)\/(\d+)\/(\d+)$/;
                var matches = regexp.exec($date);

                if ( matches )
                {
                    var day = matches[3];
                    var month = matches[2];
                    var year = matches[1];

                    var date = new Date();
                    date.setHours(0, 0, 0, 0);

                    if ( $startTime.val() == 'all_day' )
                    {
                        date.setFullYear(parseInt(year), parseInt(month) -1 , parseInt(day)+1);
                        $endTime.val( 'all_day' );
                    }
                    else if ( $startTime.val() )
                    {
                        var time = $startTime.val();

                        var timeRegexp = /^(\d+)\:(\d+)$/;

                        var matches1 = timeRegexp.exec(time);

                        if ( matches1 )
                        {
                            date.setFullYear(parseInt(year), parseInt(month) -1 , parseInt(day));
                            date.setHours(parseInt(matches1[1]) + 1, parseInt(matches1[2]), 0, 0);

                            $endTime.val( date.getHours() + ":" + date.getMinutes() );
                        }
                    }

                    if(typeof(getCookie) == "function" && getCookie("iisjalali")==1) {
                        var jalaliDate = gregorian_to_jalali(date.getFullYear(),parseInt(date.getMonth()+1),parseInt(date.getDate()));
                        $('select[name=\'year_end_date\']').val(jalaliDate[0]);
                        $('select[name=\'month_end_date\']').val(jalaliDate[1]);
                        $('select[name=\'day_end_date\']').val(jalaliDate[2]);
                        window.date_field[end_date_id].updateValue();
                    }
                    else
                    {
                        $('select[name=\'year_end_date\']').val(date.getFullYear());
                        $('select[name=\'month_end_date\']').val(date.getMonth() + 1);
                        $('select[name=\'day_end_date\']').val(date.getDate());
                        window.date_field[end_date_id].updateValue();
                    }
                }



                $endDateSelectbox.removeAttr('disabled').show();
                $startTime.removeAttr('disabled');
                //$startTime.show();
                $('#end_date_div').show();
            }
            else
            {
                $endDateSelectbox.attr('disabled', 'disabled').hide();
                //$startTime.attr('disabled', 'disabled');
                //$startTime.hide();
                $('#end_date_div').hide();
            }
        }
        );
}


