/**
 * Created by pars on 6/7/2017.
 */

function changeVisibilityOfGroupWidgets(){
    $speed = 500;
    if($('.owm_brief_info')[0].style.display == 'none' || $('.owm_brief_info')[0].style.display == ''){
        $('.owm_brief_info').show($speed);
        $('.owm_view_user_list').show($speed);
        $('.owm_view_file_list').show($speed);
        $('.owm_iisreport_widget').show($speed);
    }else{
        $('.owm_brief_info').hide($speed);
        $('.owm_view_user_list').hide($speed);
        $('.owm_view_file_list').hide($speed);
        $('.owm_iisreport_widget').hide($speed);
    }
}

function changeVisibilityOfGroupListWidgets(){
    $speed = 500;
    if($('.owm_list_search')[0].style.display == 'none' || $('.owm_list_search')[0].style.display == ''){
        $('.owm_list_search').show($speed);
    }else{
        $('.owm_list_search').hide($speed);
    }
}

function changeVisibilityOfNewsWidgets(){

    $speed = 500;
    if($('.owm_iisnews_widgets')[0].style.display == 'none' || $('.owm_iisnews_widgets')[0].style.display == ''){
        $('.owm_iisnews_widgets').show($speed);
    }else{
        $('.owm_iisnews_widgets').hide($speed);
    }
}

function changeVisibilityOfReportWidgets(){

    $speed = 500;
    if($('.owm_iisreport_widget')[0].style.display == 'none' || $('.owm_iisreport_widget')[0].style.display == ''){
        $('.owm_iisreport_widget').show($speed);
    }else{
        $('.owm_iisreport_widget').hide($speed);
    }
}

function addChangeVisibilityOfGroupWidgets($src) {
    var element = $('.owm_box_heading_btns');
    element.prepend("<img id='group_info' src='"+$src+"' />");
    // changeVisibilityOfGroupWidgets();
    $('#group_info').click(function () {
        changeVisibilityOfGroupWidgets();
    });
}

function addChangeVisibilityOfGroupListWidgets($src, $groupplus) {
    if($groupplus) {
        var element = $('.owm_box_heading_btns');
        element.prepend("<img id='group_info' src='" + $src + "' />");
        // changeVisibilityOfGroupListWidgets();
        $('#group_info').click(function () {
            changeVisibilityOfGroupListWidgets();
        });
    }
}

function addChangeVisibilityOfNewsWidgets($src) {
    var element = $('#testNewsButtons');
    element.prepend("<div class='owm_widget_info'><img id='news_info' src='"+$src+"' /></div>");
    $('#news_info').click(function () {
        changeVisibilityOfNewsWidgets();
    });
}
