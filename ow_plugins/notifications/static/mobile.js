var OWM_NotificationsConsole = function( params )
{
    var self = this;
    self.params = params;
    self.loadMore = true;

    this.consoleLoadMore = function()
    {
        var exclude =
            $("li.owm_sidebar_msg_item", "#notifications-list")
                .map(function(){
                    return $(this).data("nid");
                })
                .get();

        OWM.loadComponent(
            "NOTIFICATIONS_MCMP_ConsoleItems",
            {limit: self.params.limit, exclude: exclude},
            {
                onReady: function (html) {
                    $("#notifications-list").append(html);
                    if (html.length === 1){
                        $("#notifications-load-more").hide();
                    }
                    else{
                        self.loadMore = true;
                    }
                }
            }
        );
    };

    this.hideLoadMoreButton = function()
    {
        $("#notifications-load-more").closest(".owm_sidebar_msg_list").hide();
    };

    if ($("#notifications-load-more").length) {
        var elementTop = $("#notifications-load-more").offset().top;
        var viewportBottom = $(window).scrollTop() + $(window).height();
        var load_more_visible = elementTop < viewportBottom;
        if (load_more_visible)
            self.consoleLoadMore();
    }

    $(document).bind('scroll', function() {
        var diff = $(document).height() - ($(window).scrollTop() + $(window).height());
        if ( diff < 100 && self.loadMore )
        {
            self.consoleLoadMore();
            self.loadMore = false;
        }
    });

    $("body")
        .on("click", "#notifications-list li.owm_sidebar_msg_item", function(){
            var url = $(this).data("url");
            if ( url != undefined && url.length )
            {
                document.location.href = url;
            }
        });

    OWM.bind("mobile.console_hide_notifications_load_more", function(){
        self.hideLoadMoreButton();
    });

    OWM.bind("mobile.console_load_new_items", function(data){
        if ( data.page == 'notifications' && data.section == 'notifications' )
        {
            $("#notifications-list").prepend(data.markup);
        }
    });

    OWM.bind("mobile.hide_sidebar", function(data){
        if ( data.type == "right" )
        {
            OWM.unbind("mobile.console_hide_notifications_load_more");
            OWM.unbind("mobile.console_load_new_items");
            $("body")
                .off("click", "a#notifications-load-more");
        }
    });
};