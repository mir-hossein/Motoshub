var jsonUploadImageComponent;

function showUploadPhoto(title){
    deleteUploadedPhotos();
    csrfToken = $("form[name='newsfeed_update_status'] input[name='csrf_token']")[0].value;
    jsonUploadImageComponent = OW.ajaxFloatBox("IISPHOTOPLUS_CMP_AjaxUpload", {
        title: title,
        width: "746px"
    });
}

function isJsonStringified(data){
    try{
        JSON.parse(data);
        return true;
    }catch (e){
        return false;
    }
}

function closeJsonUploadImageComponent(data){
    if (data)
    {
        setUploadJsonData(data);
        var obj = getUploadJsonData();
        var imageList = obj.tmpList;
        var attachmentElement = document.getElementsByClassName("ow_photo_attachment_preview")[0];
        var attachmentPic = document.getElementsByClassName("ow_photo_attachment_pic")[0];
        attachmentPic.style.display='none';
        attachmentElement.style.display='block';
        var iDiv = document.createElement('div');
        iDiv.className = 'ow_photo_attachment_preview ow_smallmargin';
        for (var image in imageList)
        {
            var ia = document.createElement('a');
            ia.id = image;
            ia.className = 'ow_photo_attachment_pic ow_attachment_preload';
            ia.style="margin:5px"
            ia.style.backgroundImage='url('+imageList[image].imageSrc+')';

            var iDivLink = document.createElement('div');
            iDivLink.className = 'ow_attachment_delete ow_miniic_delete';
            iDivLink.style="cursor: pointer;"
            iDivLink.setAttribute('id',image);
            iDivLink.setAttribute('onclick','removeImageFromUploadJsonData('+image+')');

            ia.appendChild(iDivLink);


            iDiv.appendChild(ia);
        }
        document.getElementsByClassName('form_auto_click')[0].appendChild(iDiv);
    }

    if(jsonUploadImageComponent){
        jsonUploadImageComponent.close();
        OW.trigger("photo.onCloseUploaderFloatBox");
    }
}

function removeImageFromUploadJsonData(id){
    data = getUploadJsonData();
    var imageList = data.tmpList;
    var jsonStr = '';

    for (var image in imageList)
    {
        if(parseInt(image) != id) {
            jsonStr = jsonStr + '{"'+image+'":[' + JSON.stringify(imageList[image]) + ']}';
        }
    }
    if(jsonStr!='') {
        data.tmpList = JSON.parse(jsonStr);
    }else{
        data.tmpList = '';
    }
    $('.ow_photo_attachment_preview #'+id).remove()
    setUploadJsonData(data);
}

function getUploadJsonData(){
    if ($('#attachment_information_json').val().length>0) {
        return JSON.parse($('#attachment_information_json').val());
    }
}

function setUploadJsonData(data){
    if ($('#attachment_information_json').length) {
        if(isJsonStringified(data)){
            $('#attachment_information_json').val(data);
        }else{
            $('#attachment_information_json').val(JSON.stringify(data));
        }
    }
}

function formUploadSuccess( data )
{
    if ( data )
    {
        if ( !data.information )
        {
            if ( data.msg )
            {
                OW.error(data.msg);
            }
            else
            {
                OW.getLanguageText("photo", "photo_upload_error");
            }
        }
        else
        {
            closeJsonUploadImageComponent(data.information);
        }
    }
    else
    {
        OW.error("Server error");
    }

}

function deleteUploadedPhotos(){
    var obj = getUploadJsonData();
    if(!obj || !obj.tmpList){
        return;
    }
    var imageList = obj.tmpList;
    for (var image in imageList)
    {
        var elem = document.getElementById(image);
        if(elem) {
            elem.parentNode.removeChild(elem);
        }
    }
    var attachmentElement = document.getElementsByClassName("ow_photo_attachment_preview")[0];
    var attachmentPic = document.getElementsByClassName("ow_photo_attachment_pic")[0];
    if(attachmentPic) {
        attachmentPic.style.display = 'none';
    }
    if(attachmentElement) {
        attachmentElement.style.display = 'none';
    }
    $('#attachment_information_json').val('');
}