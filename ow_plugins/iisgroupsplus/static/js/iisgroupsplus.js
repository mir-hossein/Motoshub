var uploadFileIntoGroupFormComponent;

function showUploadFileIntoGroupForm($groupId){
    uploadFileIntoGroupFormComponent = OW.ajaxFloatBox('IISGROUPSPLUS_CMP_FileUploadFloatBox', {iconClass: 'ow_ic_add',groupId: $groupId})
}

function closeUploadFileIntoGroupForm(){
    if(uploadFileIntoGroupFormComponent){
        uploadFileIntoGroupFormComponent.close();
    }
}

function searchGroup(url) {
    var searchT = $('#searchTitle')[0].value;
    var categoryS = $('#categoryStatus')[0].value;
    url = url + "?searchTitle="+searchT+"&categoryStatus="+categoryS;
    window.location = url;
}

function searchGroupFiles() {
    var url = $('#original-search-group-files')[0].href;
    var searchT = $('#search-group-files')[0].value;
    url = url + "?searchTitle="+searchT;
    window.location = url;
}