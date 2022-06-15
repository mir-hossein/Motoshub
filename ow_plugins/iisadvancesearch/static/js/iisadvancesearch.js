var iissearch_items_selector = '#div_result_search_items';
var iissearch_last_q = '';
function iissearch_doSearch(url, id){
    var searchValue = document.getElementById(id).value;
    if(iissearch_last_q==searchValue)
        return;
    iissearch_last_q = searchValue;
    if(searchValue.length>1) {
        iissearch_loadingForResults();
        var data = {"searchValue": searchValue};
        $.ajax({
            url: url,
            type: 'post',
            dataType: "json",
            data: data,
            success: function (results) {
                var searchedValue = results['searchedValue'];
                if(searchedValue === document.getElementById(id).value) {
                    $(iissearch_items_selector).fadeOut(400, function() {
                        $(iissearch_items_selector).empty();
                        var all_count = 0;
                        $.each( results.data, function( index, value ){
                            //console.log(index);
                            for (var i = 0; i < value.length; i++) {
                                var resultItem = '<div id="search_item_'+all_count+'" class="result_search_item result_search_item_' + index + '">' +
                                    '<a class="avatar" href="' + value[i]['link'] + '">';
                                if(typeof(value[i]['image']) !== "undefined"){
                                    resultItem = resultItem + '<img src="' + value[i]['image'] + '" />';
                                }
                                resultItem = resultItem + '</a>' +
                                    '<a class="title" href="' + value[i]['link'] + '">' + value[i]['title'] + '</a>' +
                                    '<span class="label">' + value[i]['label'] + '</span></div>';
                                $(resultItem).appendTo($(iissearch_items_selector));
                                all_count++;
                            }
                        });
                        if(all_count==0){
                            var resultItem = '<div class="result_search_item">' + OW.getLanguageText('iisadvancesearch', 'no_data_found') + '</div>';
                            $(resultItem).appendTo($(iissearch_items_selector));
                        }
                        $(iissearch_items_selector).fadeIn(400);
                    });
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                $(iissearch_items_selector).empty();
                var resultItem = '<div class="result_search_item">ERROR: ' + xhr.responseText + '</div>';
                $(resultItem).appendTo($(iissearch_items_selector));
                $(iissearch_items_selector).fadeIn(400);
            }
        });
    }else{
        $(iissearch_items_selector).fadeOut(10, function() {
            $(iissearch_items_selector).empty();
            $(iissearch_items_selector).fadeIn(10);

            resultItem = '<div class="result_search_item">' + OW.getLanguageText('iisadvancesearch', 'minimum_two_character') + '</div>';
            $(resultItem).appendTo($(iissearch_items_selector));
        });
    }
}

function iissearch_createSearchElements(){
    OW.ajaxFloatBox('IISADVANCESEARCH_CMP_Search', {} , {width:700, iconClass: 'ow_ic_add'});
}

function iissearch_loadingForResults(){
    if($('#div_result_search_spinner').length == 0) {
        $(iissearch_items_selector).empty();
        $('<div>').attr({
            class: 'spinner',
            id: 'div_result_search_spinner'
        }).append('<div class="double-bounce1"></div><div class="double-bounce2"></div>').prependTo($(iissearch_items_selector));
    }
}