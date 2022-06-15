var editNewsfeedComponent;
var forwardNewsfeedComponent;

function showEditNewsfeedComponent($eid, $etype){
    if (typeof(OWM) != "undefined"){
        editNewsfeedComponent = OWM.ajaxFloatBox('IISNEWSFEEDPLUS_CMP_EditPostFloatBox', {iconClass: 'ow_ic_add',eId: $eid, eType: $etype})
    }else{
        editNewsfeedComponent = OW.ajaxFloatBox('IISNEWSFEEDPLUS_CMP_EditPostFloatBox', {iconClass: 'ow_ic_add',eId: $eid, eType: $etype})
    }
}

function closeEditNewsfeedComponent($text, $actionId){
    var innerHtml = '';
    if($('#action-feed1-'+$actionId + ' .ow_newsfeed_body .ow_newsfeed_content .ow_newsfeed_content').length > 0){
        innerHtml = $('#action-feed1-'+$actionId + ' .ow_newsfeed_body .ow_newsfeed_content .ow_newsfeed_content').html();
    }

    var statusBody = $('#action-feed1-'+$actionId + ' .ow_newsfeed_body .ow_newsfeed_content');
    if(statusBody.length == 0){
        var statusBody = $('#action-feed1-'+$actionId + ' .owm_newsfeed_body_status');
    }
    else {
        $text = '<div class="ow_newsfeed_content_status" >'+$text+'</div>';
    }

    var attachmentsHtml = null;
    var attachmentsPreviewHtml = null;

    for (var i = 0; i < statusBody[0].childNodes.length; i++) {
        if (statusBody[0].childNodes[i].className!=undefined && statusBody[0].childNodes[i].className.indexOf('ow_newsfeed_photo_grid')>-1) {
            attachmentsPreviewHtml=statusBody[0].childNodes[i].outerHTML;
        }
        else if (statusBody[0].childNodes[i].className!=undefined && statusBody[0].childNodes[i].className.indexOf('ow_iisnewsfeedplus_attachment')>-1) {
            if(attachmentsHtml==null)
            {
                attachmentsHtml = statusBody[0].childNodes[i].outerHTML;
            }
            else{
                attachmentsHtml = attachmentsHtml+statusBody[0].childNodes[i].outerHTML;
            }
        }
    }
    if(attachmentsPreviewHtml!=null)
    {
        innerHtml=innerHtml+attachmentsPreviewHtml;
    }

    if(attachmentsHtml!=null)
    {
        innerHtml=innerHtml+attachmentsHtml;
    }
    statusBody.html($text + innerHtml);
    if(editNewsfeedComponent){
        editNewsfeedComponent.close();
    }
}

function addAttachment(){
    $('#newsfeedplusAttachmentsBtn input')[0].click();
}

OW.bind('base.update_attachment', function(data) {
    if(!data || !data.pluginKey  || data.pluginKey !="iisnewsfeedplus")
    {
        return;
    }
    var attachmentIds = document.getElementById('attachment_feed_data').value;
    var dbId = false;
    if (data.pluginKey == 'iisnewsfeedplus') {
        for (var key in data.items) {
            dbId=true;
            if (attachmentIds != "") {
                attachmentIds = attachmentIds + '-' + key +':'+data.items[key]['dbId'];
            }else {
                attachmentIds = key + ':' + data.items[key]['dbId'];
            }
        }
        if(dbId) {
            document.getElementById('attachment_feed_data').value = attachmentIds;
        }
    }
});


OW.bind("base.attachment_deleted",
    function(data){
        var attachmentIds = document.getElementById('attachment_feed_data').value;
        var updatedIds="";
        var attachmentIdArr = attachmentIds.split("-");
        for(i=0;i<attachmentIdArr.length;i++){
            var index = attachmentIdArr[i].split(":");
            if(index[0]==data['id']){
                continue;
            }
            if(updatedIds==""){
                updatedIds=attachmentIdArr[i];
            }else{
                updatedIds=updatedIds+'-'+attachmentIdArr[i];
            }
        }
        document.getElementById('attachment_feed_data').value=updatedIds;
    });


function refreshAttachClass(){
    var items = $('.ow_file_attachment_info');
    var uid = "";
    items.each(function(key){
        if(items[key].closest('form[name="newsfeed_update_status"]') != null){
            if(items[key].closest('div[class="ow_file_attachment_preview clearfix"]') != null && items[key].closest('div[class="ow_file_attachment_preview clearfix"]').parentElement != null) {
                uid = items[key].closest('div[class="ow_file_attachment_preview clearfix"]').parentElement.id;
            }
        }
    });
    if(uid != "" && $('#'+uid)[0].childElementCount > 0){
        var childs = $('#'+uid).children()[0];
        $(childs).empty();
        $('#attachment_feed_data').val("");
    }
}

function showUserGroupsComponent($aid, $fid,$vis,$pri,$fty,$sid,$title){
    if (typeof(OWM) != "undefined"){
        forwardNewsfeedComponent = OWM.ajaxFloatBox('IISNEWSFEEDPLUS_CMP_ForwardPostFloatBox', {iconClass: 'ow_ic_add',aId: $aid, fId: $fid,vis: $vis,pri: $pri,fty:$fty,sid:$sid},{title: $title})
    }else{
        forwardNewsfeedComponent = OW.ajaxFloatBox('IISNEWSFEEDPLUS_CMP_ForwardPostFloatBox', {iconClass: 'ow_ic_add',aId: $aid, fId: $fid,vis: $vis,pri: $pri,fty:$fty,sid:$sid},{title: $title})
    }
}

function closeForwardNewsfeedComponent(){
    if(forwardNewsfeedComponent){
        forwardNewsfeedComponent.close();
    }
}

function newsfeedImageViewerSliderNavigationLeft($sliderBar, $isMobile){
    if($isMobile){
        var leftPos = $('#items_bar_' + $sliderBar + '').scrollLeft();
        var leftTmp = leftPos;
        $('#items_bar_' + $sliderBar + '').animate({
            scrollLeft: leftPos + 200
        }, 800);
        $('.next.btn_'+$sliderBar+'').css('opacity','1');
        var leftPos = $('#items_bar_' + $sliderBar + '').scrollLeft();
        if (leftPos == leftTmp){
            $('.previous.btn_'+$sliderBar+'').css('opacity','0.5');
        }
    }else{
        var currentMargin = parseInt($('#items_bar_'+$sliderBar+'').css('margin-right'));
        var limiter = parseInt($('.ow_newsfeed_preview_grid_items_bar_limiter').css('width'));
        var barWidth = parseInt($('#items_bar_' + $sliderBar + '').css('width'));
        if(currentMargin<0){
            $('.next.btn_'+$sliderBar+'').css('opacity','1');
            if(currentMargin+100>0){
                var newMargin = currentMargin + Math.abs(currentMargin);
                $('.previous.btn_'+$sliderBar+'').css('opacity','0.5');
            }else{
                var newMargin = currentMargin + 100;
            }
            $('#items_bar_'+$sliderBar+'').css('margin-right', newMargin+'px');
        }else{
            $('.previous.btn_'+$sliderBar+'').css('opacity','0.5');
        }
    }

}


function newsfeedImageViewerSliderNavigationRight($sliderBar, $isMobile){
    if($isMobile){
        var leftPos = $('#items_bar_'+$sliderBar+'').scrollLeft();
        $('#items_bar_'+$sliderBar+'').animate({
            scrollLeft: leftPos - 200
        }, 800);
        $('.previous.btn_'+$sliderBar+'').css('opacity','1');
        setTimeout(function() {
            var leftPos = $('#items_bar_'+$sliderBar+'').scrollLeft();
            if (leftPos==0){
                $('.next.btn_'+$sliderBar+'').css('opacity','0.5');
            }
        }, 900);
    }else {
        var currentMargin = parseInt($('#items_bar_' + $sliderBar + '').css('margin-right'));
        var limiter = parseInt($('.ow_newsfeed_preview_grid_items_bar_limiter').css('width'));
        var barWidth = parseInt($('#items_bar_' + $sliderBar + '').css('width'));
        if (Math.abs(currentMargin) < Math.abs(barWidth - limiter)) {
            $('.previous.btn_'+$sliderBar+'').css('opacity','1');
            if((Math.abs(currentMargin)+Math.abs(limiter))<Math.abs(barWidth) &&
                (Math.abs(currentMargin)+Math.abs(limiter)+100)>Math.abs(barWidth)){
                var newMargin = currentMargin - (Math.abs(barWidth)-(Math.abs(currentMargin)+Math.abs(limiter)));
                $('.next.btn_'+$sliderBar+'').css('opacity','0.5');
            }else{
                var newMargin = currentMargin - 100;
            }
            $('#items_bar_' + $sliderBar + '').css('margin-right', newMargin + 'px');
        }else{
            $('.next.btn_'+$sliderBar+'').css('opacity','0.5');
        }
    }
}

function newsfeedImageViewerSliderBarChanger( $object, $id, $downloadUrl, $previewUrl, $type){

    $('#items_bar_'+$id+' .ow_newsfeed_preview_grid_item a').css('opacity','0.7');
    $('#items_bar_'+$id+' .ow_newsfeed_preview_grid_item a').css('box-shadow','none');
    $object.css('opacity','1');
    $object.css('box-shadow','0 0 8px 0px #353535');

    var class_prefix = 'ow_newsfeed_item_';
    $('#Extended_link_'+$id).removeClass(function (index, className) {
        return (className.match (/(^|\s)ow_newsfeed_item_\S+/g) || []).join(' ');
    }).addClass(class_prefix + $type);
    if($type == 'image'){
        $('#Extended_link_'+$id).addClass('ow_newsfeed_image_no_size');
        $('#Extended_link_'+$id+'').css('display', ' block');
        $('#'+$id+'').css('background', ' url('+$previewUrl+'');
        $('#Extended_link_'+$id+'').attr('href',''+$downloadUrl+'');
        audioAndVideoStatusChanger(false, $id);
    }
    if($type == 'video' || $type == 'audio'){
        audioAndVideoStatusChanger($type, $id);
        audioAndVideoUrlChanger( $type , $id, $downloadUrl, $previewUrl )
    }
}

function audioAndVideoStatusChanger( $type, $id ){
    var videoStyle='none';
    var audioStyle='none';
    if($type == 'audio'){
        audioStyle='block';
        $('#Extended_link_' + $id + '').css('display', ' none');
        setTimeout(function() {
            $('#Extended_newsfeed_audio_' + $id + ' .mejs__button.mejs__playpause-button.mejs__pause').click();
        }, 100);
    }else{
        $('#Extended_newsfeed_audio_'+$id+' .mejs__button.mejs__playpause-button.mejs__pause button').click();
    }
    if($type == 'video'){
        videoStyle='block';
        $('#Extended_link_' + $id + '').css('display', ' none');
        if($('video#NewsfeedVideo_' + $id + '_html5').hasClass('NewsfeedVideo_html5_audio_background')){
            $('video#NewsfeedVideo_' + $id + '_html5').removeClass('NewsfeedVideo_html5_audio_background');
        }
        $('#Extended_newsfeed_video_' + $id + ' .mejs__button.mejs__playpause-button.mejs__pause').click();
        setTimeout(function() {
            $('#Extended_newsfeed_video_' + $id + ' .mejs__button.mejs__playpause-button.mejs__pause').click();
        }, 100);
    }else{
        $('#Extended_newsfeed_video_'+$id+' .mejs__button.mejs__playpause-button.mejs__pause button').click();
    }
    $('#Extended_newsfeed_video_'+$id+'').css('display', videoStyle);
    $('#Extended_newsfeed_audio_'+$id+'').css('display', audioStyle);

}

function audioAndVideoUrlChanger( $itemType , $id, $downloadUrl, $previewUrl ){
    var itemTypeString='';
    if($itemType=='video'){
        $itemTypeString='Video';
    }else{
        $itemTypeString='Audio';
    }

    $('#Newsfeed' + $itemTypeString + '_' + $id + '_html5')[0].src=$downloadUrl;
    $('#Newsfeed' + $itemTypeString + '_' + $id + '_html5 source')[0].src=$downloadUrl;
    $($itemTypeString + '#Newsfeed' + $itemTypeString + '_' + $id + '_html5').attr('poster',$previewUrl);
    $($itemTypeString + '#Newsfeed' + $itemTypeString + '_' + $id + '_html5').css('background','url("'+$previewUrl+'");');
}

function newsfeedVideoThumbnailCreator($id, $name, $thumbnailController){
    var delayInMilliseconds = 3000;
    setTimeout(function() {
        var videoObject = document.getElementById('NewsfeedVideo_'+$id+'_html5');
        var canvas = document.getElementById('canvas');
        canvas.width = videoObject.videoWidth;
        canvas.height = videoObject.videoHeight;
        canvas.getContext('2d').drawImage(videoObject, 0, 0, canvas.width, canvas.height);
        canvasData = canvas.toDataURL("image/png");
        if(canvasData.length > 7 ){
            var data = {'videoName': $name , 'canvasData': canvasData};
            $.ajax({type: 'POST', url: $thumbnailController , data: data, dataType: 'json'});
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        }else{
            setTimeout(newsfeedVideoThumbnailCreator($id, $name, $thumbnailController), 2000); //wait 2000ms, then try again
        }
    }, delayInMilliseconds);
}


OW.bind('iisnewsfeedplus.add.to.previewlist', function(data) {
    if(!data)
    {
        return;
    }
    var dbId = false;
    var attachmentPreviewIds;
    if ($(data.data.dbId).size() == 1) {
        attachmentPreviewIds = document.getElementById('attachment_preview_data').value;
        dbId = true;
        if (attachmentPreviewIds == "") {
            attachmentPreviewIds = data.data.dbId;
        }
        else {
            attachmentPreviewIds = attachmentPreviewIds + '-' + data.data.dbId;
        }
    }
    if ($(data.data.dbId).size() > 1) {
        attachmentPreviewIds = document.getElementById('attachment_preview_data').value;
        dbId = true;
        for (i = 0; i < $(data.data.dbId).size(); i++) {
            attachmentPreviewIds = attachmentPreviewIds + '-' + data.data.dbId[i];
        }
    }
    if(dbId) {
        document.getElementById('attachment_preview_data').value = attachmentPreviewIds;
    }
});

OW.bind('iisnewsfeedplus.remove.from.previewlist', function(data) {
    if(!data)
    {
        return;
    }
    var updatedIds="";
    var attachmentPreviewIds = document.getElementById('attachment_preview_data').value;
    var updatedPreviewIds="";
    var previewIdArr = attachmentPreviewIds.split("-");
    for(i=0;i<previewIdArr.length;i++){
        if(data.data.dbId==previewIdArr[i]){
            continue;
        }
        if(updatedIds==""){
            updatedIds=previewIdArr[i];
        }else{
            updatedIds=updatedIds+'-'+previewIdArr[i];
        }
    }
    document.getElementById('attachment_preview_data').value=updatedIds;
});

$(window).on('load', function () {
    $('.mejs__button.mejs__playpause-button').click(function () {
        var id = $(this).parent().parent().parent().parent().attr('id').replace('Extended_newsfeed_video_', '');
        var postername=$('#NewsfeedVideo_' + id + '_html5').attr('postername');
        var postercontroller=$('#NewsfeedVideo_' + id + '_html5').attr('postercontroller');
        if (postername && postercontroller && ($('#items_bar_' + id + ' .ow_newsfeed_preview_grid_item').length == 0 || $('#items_bar_' + id + ' .ow_newsfeed_preview_grid_item a:first-child').css('opacity') ==1) ) {
            newsfeedVideoThumbnailCreator( id, postername, postercontroller );
        }
    });
});

$("#updatestatus_submit_button").on("click", function(event){
    var result;
    result = OW.trigger('check.attachment.upload.status');
    if(!result)
    {
        if(!confirm(OW.getLanguageText('base', 'attachment_is_inprogress'))) {
            event.preventDefault();
        }
    }
});

function changeNewsfeedOrders()
{
    selectboxValue=  $('#newsfeed_select_order')[0].value;
    if(selectboxValue!=undefined && selectboxValue!=$.cookie("name"))
    {
        $.cookie("newsfeed_order", selectboxValue);
        location.reload();
    }
}

$(document).ready(function () {
    $(document).on("input property change", "#newsfeed_add_status_url_input",  function()  {
        var obj = JSON.parse(document.getElementsByName("attachment")[0].value);
        obj.description=document.getElementById("newsfeed_add_status_url_input").value;
        document.getElementsByName("attachment")[0].value=JSON.stringify(obj);
        if (!is_text_persian(obj.description))
            $("input#newsfeed_add_status_url_input").css("direction", "ltr");
        else
            $("input#newsfeed_add_status_url_input").css("direction", "rtl");
    });

    $(document).on("click", "#newsfeed_add_status_url_edit_button" ,function()  {
        var prefetch_text = $("#newsfeed_add_status_url_input").val();
        $("#newsfeed_add_status_url_input").removeAttr("disabled").removeClass("disabled_input_border").focus().val("").val(prefetch_text);
    });
});

function thumbnailCreator() {
    $('.mejs__button.mejs__playpause-button').click(function () {
        var id = $(this).parent().parent().parent().parent().attr('id').replace('Extended_newsfeed_video_', '');
        var newsfeedVideo = $('#NewsfeedVideo_' + id + '_html5');
        var postername = newsfeedVideo.attr('postername');
        var postercontroller= newsfeedVideo.attr('postercontroller');
        var gridItemString = '#items_bar_' + id + ' .ow_newsfeed_preview_grid_item';
        var gridItem = $(gridItemString);
        var gridItemFirstChild = $(gridItemString +' a:first-child');
        if (!newsfeedVideo.attr('poster') && postername && postercontroller &&
            (gridItem.length == 0 || gridItemFirstChild.css('opacity') == 1) ) {
            newsfeedVideoThumbnailCreator( id, postername, postercontroller );
        }
    });
}