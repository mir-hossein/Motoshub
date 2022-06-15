/**
 * IIS Advance Search
 */
/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @package ow_plugins.iisadvancesearch
 * @since 1.0
 */
function iisadvancesearch_search_users(base_url,selector,count,load_empty) {
    var input_selector = selector+" input";
    var results_selector = selector+" .iisadvancesearch_results";
    var empty_selector = selector + " .ow_nocontent";

    var iisadvancesearch_last_text = '';
    var iisadvancesearch_ListCache = {};
    var iisas_more_available_list = {};
    var iisas_next_start = {};
    if(count == null)
        count = 12;
    if (load_empty === undefined) {
        load_empty = false;
    }

    $(selector).append('<div id="load-more"></div>');

    var get_items = function (q) {
        $(selector + ' #load-more').html('<a href="javascript://" id="notifications-load-more" class="owm_sidebar_load_more owm_sidebar_load_more_with_text owm_sidebar_load_more_preloader">'+OW.getLanguageText('base', 'more')+'</a>');
        $.ajax({
            url: base_url + q,
            data: {"start": iisas_next_start[q], "count": count},
            success: function (response) {
                var result = jQuery.parseJSON(response);
                if(result.is_appending)
                    iisadvancesearch_ListCache[q] = iisadvancesearch_ListCache[q].concat(result.items);
                else
                    iisadvancesearch_ListCache[q] = result.items;
                iisas_more_available_list[q] = result.more_available;

                if (result.q == $(input_selector)[0].value) {
                    $(selector + ' #load-more').html('');
                    iisas_next_start[q] = result.next_start;
                    iisadvancesearch_load_data(result.items, results_selector, empty_selector, result.is_appending);
                    if (iisas_more_available_list[q]) {
                        $(selector + ' #load-more').html('<a href="javascript://" id="notifications-load-more" class="owm_sidebar_load_more owm_sidebar_load_more_with_text ">'+OW.getLanguageText('base', 'more')+'</a>');
                        $(selector + ' #notifications-load-more').on('click', function () {
                            q = $(input_selector)[0].value;
                            get_items(q);
                        });
                    }
                }
            },
            'error': function () {
                //OW.error(OW.getLanguageText('base', 'comment_add_post_error'));
            }
        });
    };

    if(load_empty){
        iisas_next_start[''] = 0;
        get_items('');
    }

    $(input_selector).on('change keydown paste input',function () {
        q = $(input_selector)[0].value;
        if (iisadvancesearch_last_text == q)
            return;
        if (q == "" && !load_empty) {
            $(results_selector).html('');
            $(selector + ' #load-more').html('');
            $(empty_selector).css('display', 'none');
            return;
        }
        next_start = 0;
        iisadvancesearch_last_text = q;
        if (q in iisadvancesearch_ListCache) {
            iisadvancesearch_load_data(iisadvancesearch_ListCache[q], results_selector, empty_selector, false);
            $(selector + ' #load-more').html('');
            if (iisas_more_available_list[q]) {
                $(selector + ' #load-more').html('<a href="javascript://" id="notifications-load-more" class="owm_sidebar_load_more owm_sidebar_load_more_with_text ">'+OW.getLanguageText('base', 'more')+'</a>');
                $(selector + ' #notifications-load-more').on('click', function () {
                    var q2 = $(input_selector)[0].value;
                    get_items(q2);
                });
            }
        }
        else {
            $(results_selector).html('');
            iisas_next_start[q] = 0;
            get_items(q);
        }
    });
}
function iisadvancesearch_load_data(items, results_selector,empty_selector,is_appending) {
    if(!is_appending) {
        $(results_selector).html('');
        $(empty_selector).css('display', 'block');
    }
    $.each(items, function( index, value ) {
        var html = '<div class="owm_avatar"><a href="'+value.url+'">' +
            '<div class="owm_align_center"><img alt="" title="'+value.title+'+" src="'+value.src+'" /></div>' +
            '<div class="owm_align_center"><span class="owm_avatar_title">'+value.title+'</span></div>' +
            '</a></div>';
        $(results_selector).append(html);
        $(empty_selector).css('display','none');
    });
}