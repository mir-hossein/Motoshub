$(function () {
    var lastText = '';
    var fullListLoadingStatus = 0;
    var input_selector = '#iismainpage_friends_search';

    var filterItems = function(q){
        if (q == ''){
            //clear search
            $('.owm_user_list > .owm_list_item_with_image').each(function(){
                $(this).css('display', 'block');
            });
        }
        else{
            //find items
            $('.owm_user_list > .owm_list_item_with_image').each(function(){
                if( $('.owm_user_list_name > a',this).text().toLowerCase().includes(q.toLowerCase()) ){
                    $(this).css('display', 'block');
                }
                else if( $('.owm_user_list_name > a[href]',this).attr('href').toLowerCase().includes(q.toLowerCase()) ){
                    $(this).css('display', 'block');
                }
                else {
                    $(this).css('display', 'none');
                }
            });
        }

        var listCount = $('.owm_content_list_item.owm_list_item_with_image:visible').length;
        if(listCount>0)
            $('.ow_nocontent').hide();
        else
            $('.ow_nocontent').show();
    };

    var fullListLoaded = function(){
        fullListLoadingStatus = 2;
        var number = 0;
        $('.owm_user_list > .owm_list_item_with_image').each(function(){
            $(this).attr('itemnum', number++);
        });
        //remove loading
        $('.owm_user_list_search_preloader.owm_preloader').hide();

        //refresh list
        q = $(input_selector)[0].value;
        filterItems(q);
        $('#friends_useritems_style')[0].innerHTML = '';
    };

    $(input_selector).on('change keydown paste input',function () {
        q = $(this)[0].value;
        if (lastText == q){
            return;
        }
        lastText = q;

        if (q == '')
        {
            filterItems(q);
        }
        else
        {
            if( fullListLoadingStatus == 2 ){
                filterItems(q);
            }
            else if( fullListLoadingStatus == 0 ){
                if(window.mobileUserList.process){
                    fullListLoaded();
                }
                else{
                    //show loading
                    var style = $('<style id="friends_useritems_style">.owm_user_list > .owm_list_item_with_image { display: none }</style>');
                    $('html > head').append(style);
                    $('.owm_user_list_search_preloader.owm_preloader')[0].style = 'visibility: visible; background-size: 40px; min-height: 100px; height: 100%;';
                    $('.owm_user_list_preloader.owm_preloader').hide();

                    window.mobileUserList.count = 1000;
                    window.mobileUserList.loadData();
                    fullListLoadingStatus = 1;

                    var timer = setInterval(function() {
                        if(window.mobileUserList.process)
                            return;
                        clearInterval(timer);
                        fullListLoaded();
                    }, 1000);
                }
            }
        }
    });
});

var last_mailbox_search_q = '';
function add_mailbox_search_events(ajax_url) {
    $('#iismainpage_messages_search').on('change keydown paste input', function () {
        q = $(this)[0].value;
        var listSelector = '#mailbox_page > .owm_list_page';
        if(q === last_mailbox_search_q){
            return;
        }
        if(last_mailbox_search_q ===''){
            $(listSelector + ' > .owm_list_item_with_image').hide();
            $(listSelector + ' > .ow_nocontent').hide();
            $(listSelector + ' > .owm_preloader').show();
        }
        last_mailbox_search_q = q;
        if (q === '') {
            $(listSelector + ' > .owm_list_item_with_image').show();
            $(listSelector + ' > .owm_list_item_search_item').remove();
            $(listSelector + ' > .ow_nocontent').hide();
            $(listSelector + ' > .owm_preloader').hide();
        } else {
            $(listSelector + ' > .owm_preloader').show();
            $.ajax({
                type: "POST",
                dataType: "json",
                data: {
                    'q': q
                },
                url: ajax_url,
                success:
                    function (response) {
                        var data = response;//jQuery.parseJSON(response);
                        if (data.result === 'ok') {
                            if (data.q !== last_mailbox_search_q)
                                return;
                            $(listSelector + ' > .owm_list_item_with_image').hide();
                            $(listSelector + ' > .owm_list_item_search_item').remove();
                            $(listSelector + ' > .owm_preloader').hide();
                            $(listSelector + ' > .ow_nocontent').hide();
                            if(data.results.length ===0){
                                $(listSelector + ' > .ow_nocontent').show();
                            }
                            $.each(data.results, function (index, value) {
                                var html = '<div class="owm_list_item_with_image owm_list_item_search_item" onclick="location.href=\''+value.conversationUrl+'\';">'+
                                    '<div class="owm_user_list_item"><div class="owm_avatar"><img src="'+value.avatarUrl+'"></div>'+
                                    '<div class="owm_user_list_name"><span id="mailboxSidebarConversationsItemDisplayName"><a href="'+value.conversationUrl+'">'+value.opponentName+'</a></span></div>'+
                                    '<div class="owm_sidebar_convers_mail_theme" id="mailboxSidebarConversationsItemSubject" onselectstart="return false" cb="set" style="user-select: none;"><a emoji="5">'+value.text+'</a></div>'+
                                    '<div class="owm_profile_online" id="mailboxSidebarConversationsItemOnlineStatus" style="display: none;"></div>'+
                                    '<div class="owm_newsfeed_date">'+value.timeString+'</div></div></div>';
                                $(listSelector).append(html);
                            });
                        }
                    }
            })
        }
    });
}