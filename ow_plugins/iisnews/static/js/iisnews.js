function initPublishDateField(className){
    var show = $(className)[0].classList.toggle("show");
    if(show){
        $(className)[0].style.display= "table";
    }else{
        $(className)[0].style.display= "none";
    }
}
