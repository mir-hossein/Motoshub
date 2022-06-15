var OWMobile = function(){
    var self = this;
    var langs = {};
    var events = {};        
    var $overlay = $('#owm_overlay'), leftSbClass = 'owm_sidebar_left_active', rightSbClass = 'owm_sidebar_right_active', $main = $('#main');
    var $leftHeaderBtn = $('#owm_header_left_btn'), $rightHeaderBtn = $('#owm_header_right_btn'), $heading = $('#owm_heading'), $title = $('title');
    var state = "content";

    $('.owm_content_header_count').on("click.nav", function(){
        if ( state === "content" ) {
            self.showRightSidebar();
        }
        else if ( state === "right" ) {
            self.showContent();
        }
    });

    var btnDefaultInit = function(){
        $leftHeaderBtn.off('click.nav').on("click.nav", function(){self.showLeftSidebar();});
        $rightHeaderBtn.off('click.nav').on("click.nav", function(){self.showRightSidebar();});
    };

    btnDefaultInit();

    this.showLeftSidebar = function(){
        last_windows_top = $(window).scrollTop();//console.log('set:'+last_windows_top);
        $('body').removeClass(leftSbClass).removeClass(rightSbClass).addClass(leftSbClass);
        $overlay.css({display:'block'}).off('click.nav').on("click.nav", function(){self.showContent();});
        $leftHeaderBtn.off('click.nav').on("click.nav", function(){self.showContent();});
        state = 'left';
        self.trigger('mobile.show_sidebar', {type: state});
    };

    this.showRightSidebar = function(options){

        options || (options = {});

        silent = options.silent;

        last_windows_top = $(window).scrollTop();//console.log('set:'+last_windows_top);
        $('body').removeClass(leftSbClass).removeClass(rightSbClass).addClass(rightSbClass);
        $overlay.css({display:'block'}).off('click.nav').on("click.nav", function(){self.showContent();});
        $rightHeaderBtn.unbind('click').click(function(){self.showContent();});

        if (!silent){
            state = 'right';
            self.trigger('mobile.show_sidebar', {type: "right"});
        }
    };

    this.showContent = function(){
        $('body').removeClass(leftSbClass).removeClass(rightSbClass);
        $overlay.css({display:'none'}).off('click.nav');
        btnDefaultInit();
        self.trigger('mobile.hide_sidebar', {type: state});
        state = "content";
    };

    this.Header = (function(){
//        var states = [];
//        var lBtnClass = 'owm_nav_menu';
//        var rBtnClass = 'owm_nav_profile';
//
//        var setData = function( data ){
//            if( data.heading ){
//               $heading.html(data.heading);
//               $title.html(data.heading);
//           }
//
//           if( data.lBtnClick ){
//               $leftHeaderBtn.unbind('click').attr('href', 'javascript://').click(data.lBtnClick);
//           }
//
//           if( data.lBtnHref ){
//               $leftHeaderBtn.unbind('click').attr('href', data.lBtnHref);
//           }
//
//           if( data.lBtnIconClass ){
//               if( data.lBtnIconClass.indexOf(lBtnClass) == -1 ){
//                    data.lBtnIconClass += lBtnClass;
//               }
//
//               $leftHeaderBtn.attr('class', data.lBtnIconClass);
//           }
//
//           if( data.rBtnClick ){
//               $rightHeaderBtn.unbind('click').attr('href', 'javascript://').click(data.rBtnClick);
//           }
//
//           if( data.rBtnHref ){
//               $rightHeaderBtn.unbind('click').attr('href', data.rBtnHref);
//           }
//
//           if( data.rBtnIconClass ){
//               if( data.rBtnIconClass.indexOf(rBtnClass) == -1 ){
//                    data.rBtnIconClass += rBtnClass;
//               }
//
//               $rightHeaderBtn.attr('class', data.rBtnIconClass);
//           }
//        };
//
//        return {
//           addState: function( data ){
//               if( states.length == 0 ){
//                   var initState = {};
//                   initState.heading = $heading.html();
//                   initState.lBtnIconClass = $leftHeaderBtn.attr('class');
//                   initState.rBtnIconClass = $rightHeaderBtn.attr('class');
//                   states.push(initState);
//               }
//
//               states.push(data);
//               setData(data);
//           },
//           removeState: function(){
//               states.pop();
//
//               if( states.length > 0 ){
//                   setData(states[states.length-1]);
//               }
//           }
//        };
    })();

    this.showUsers = function(userIds, title)
    {
        OWM.Users.showUsers(userIds, title);
    };
//    this.Header.addState(
//        {
//            lBtnClick: function(){self.showLeftSidebar();},
//            rBtnClick: function(){self.showRightSidebar();}
//        }
//    );

    this.escapeRegExp = function(s) {
        return s.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
    }

    this.error = function( message ){
        this.message(message, 'error');
    };

    this.warning = function( message ){
    	this.message(message, 'warning');
    };

    this.info = function( message ){
        this.message(message, 'info');
    };

     var $messageCont = $('<div class="owm_msg_wrap"></div>');
     var messageTime = 3000;
     $messageCont.appendTo($('body'));

    this.message = function( message, type, paramTime ){
        $( ".owm_msg_block" ).remove();
        var $messageNode = $( '<div class="owm_msg_block owm_msg_'+type+' clearfix" style="display:none;"><a href="javascript://" onclick="$(this).closest(\'.owm_msg_block\').slideUp(200, function(){$(this).remove();})" class="owm_close_btn"></a><span class="owm_msg_txt">'+message+'</span></div>').appendTo($messageCont);
        if( paramTime == undefined ){
            paramTime = messageTime;
        }

        $messageNode.fadeIn(1000,
            function(){
                window.setTimeout(
                    function(){
                        $messageNode.fadeOut(1000,
                            function() {
                                $messageNode.remove();
                            }
                    );
                    }, paramTime
                );
            }
        );
    };

    this.getLanguageText = function(prefix, key, assignedVars)
    {
        if ( langs[prefix] === undefined ) {
                return prefix + '+' + key;
        }

        if ( langs[prefix][key] === undefined ) {
                return prefix + '+' + key;
        }

        var langValue = langs[prefix][key];

        if ( assignedVars ) {
                for( varName in assignedVars ) {
                        langValue = langValue.replace('{$'+varName+'}', assignedVars[varName]);
                }
        }

        return langValue;
    };

    this.registerLanguageKey = function(prefix, key, value)
    {   
            if ( langs[prefix] === undefined ) {
                    langs[prefix] = {};
            }

            langs[prefix][key] = value;
    };

    this.inProgressNode = function(node)
    {
    	return $(node).inprogress();
    };

    this.activateNode = function(node)
    {
    	return $(node).activate();
    };

    this.bind = function(type, func)
    {
        if (events[type] == undefined)
        {
            events[type] = [];
        }

        events[type].push(func);

    };

    this.unbind = function( type )
    {
        if (events[type] == undefined) {
            return false;
        }

        events[type] = [];
    };

    this.trigger = function(type, params, applyObject)
    {
        if (events[type] == undefined) {
            return false;
        }

        applyObject = applyObject || this;
        params = params || [];

        if ( !$.isArray(params) )
        {
            params = [params];
        }

        for (var i = 0, func; func = events[type][i]; i++)
        {
            if (func.apply(applyObject, params) === false)
            {
                return false;
            }
        }

        return true;
    };
    
    this.flagContent = function( entityType, entityId )
    {
        OWM.ajaxFloatBox("BASE_MCMP_Flag", [entityType, entityId], {
            width: 315,
            title: OWM.getLanguageText('base', 'flag_as')
        });
    };

    this.authorizationLimitedFloatbox = function( message )
    {
        OWM.ajaxFloatBox("BASE_MCMP_AuthorizationLimited", [message],
            {title: OWM.getLanguageText('base', 'authorization_limited_permissions')}
        );
    };

    this.addCssFile = function( url )
    {
        $('head').append($('<link type="text/css" rel="stylesheet" href="'+$.trim(url)+'" />'));
    };

    this.addCss = function( css ){
        $('head').append($('<style type="text/css">'+css+'</style>'));
    };

    var loadedScriptFiles = {};
    this.loadScriptFiles = function( urlList, callback, options ){
        
        if ( $.isPlainObject(callback) ) {
            options = callback;
            callback = null;
        }
        
        var addScript = function(url) {
            return jQuery.ajax($.extend({
                dataType: "script",
                cache: true,
                url: url
            }, options || {})).done(function() {
                loadedScriptFiles[url] = true;
            });
        };
        
        if( urlList && urlList.length > 0 ) {
            var recursiveInclude = function(urlList, i) {
                if( (i+1) === urlList.length )
                {
                    addScript(urlList[i]).done(callback);
                    return;
                }

                addScript(urlList[i]).done(function() {
                    recursiveInclude(urlList, ++i);
                });
            };
            recursiveInclude(urlList, 0);
        } else {
            callback.apply(this);
        }
    };

    this.addScriptFiles = function( urlList, callback, once ) {
        if ( once === false ) {
            this.loadScriptFiles(urlList, callback);
            return;
        }
        
        $("script").each(function() {
            loadedScriptFiles[this.src] = true;
        });
        
        var requiredScripts = $.grep(urlList, function(url) {
            return !loadedScriptFiles[url];
        });

        this.loadScriptFiles(requiredScripts, callback);
    };

    this.initWidgetMenu = function( items ){
        var $toolbarCont = null;
        var $contex = null;
        var condIds = [];
        var linkIds = [];
        $.each( items, function(key, value){
                if( $toolbarCont === null ){
                    $contex = $('#'+value['contId']).closest('.ow_box, .ow_box_empty');
                    $toolbarCont = $('.ow_box_toolbar_cont', $contex);
                }
                condIds.push('#'+value['contId']);
                linkIds.push('#'+value['id']);
            }
        );

        var contIdSelector = $(condIds.join(','));
        var linkIdSelector = $(linkIds.join(','));

        $.each( items, function(key, value){
                $('#'+value['id']).bind('click', {value:value},
                    function(e){
                        contIdSelector.hide();
                        $('#'+e.data.value.contId).show();
                        linkIdSelector.removeClass('owm_box_menu_item_active');
                        $(this).addClass('owm_box_menu_item_active');
                        
                        if( e.data.value.toolbarId != undefined ){
                            if( e.data.value.toolbarId ){
                                if( $toolbarCont.length === 0 ){
                                    $toolbarCont = $('<div class="ow_box_toolbar_cont"></div>');
                                    $contex.append($toolbarCont);
                                }
                                $toolbarCont.html($('#'+e.data.value.toolbarId).html());
                            }
                            else{
                                if( $toolbarCont.length !== 0 ){
                                    $toolbarCont.remove();
                                    $toolbarCont = [];
                                }
                            }
                        }
                    }
                );
            }
        );
    };

    this.addScript = function( script, scope, context )
    {
        if (!script)
        {
            return;
        }

        context = context || window;
        scope = scope || window;

        Function('_scope', script).call(context, scope);
    };

    /**
     * Loads a component
     *
     * Examples:
     * 1:
     * OW.loadComponent(cmpClass, '.place_to_node');
     *
     * 2:
     * OW.loadComponent(cmpClass, function(){
     *     //onReady event
     *     //Add the html somewhere
     * });
     *
     * 3:
     * OW.loadComponent(cmpClass, [ p1, p2, ... ], '.place_to_node');
     *
     * 4:
     * OW.loadComponent(cmpClass, [ p1, p2, ... ], function(html){
     *     //onReady event
     *     //Add the html somewhere
     * });
     *
     * 5:
     * OW.loadComponent(cmpClass, [ p1, p2, ... ],
     * {
     *     onLoad: function(){},
     *     onError: function(){},
     *     onComplete: function(){},
     *     onReady: function( html )
     *     {
     *        //Add the html somewhere
     *     }
     * });
     *
     * @param string cmpClass
     *
     * Cmp class params or targetNode selector or targetNod HtmlElement or ready callback
     * @param array|string|HTMLElement|jQuery|function p1
     *
     * Options or targetNode selector or targetNod HtmlElement or ready callback
     * @param object|string|HTMLElement|jQuery|function p2
     */
    this.loadComponent = function( cmpClass, p1, p2 )
    {
        function isNode( node )
        {
            return typeof node === 'string' || node.jquery || node.nodeType
        }

        var params = [], options = {};

        if ( isNode(p2) )
        {
            options.place = $(p2);
        }
        else if ( $.isPlainObject(p2) )
        {
            options = p2;
        }
        else if ( $.isFunction(p2) )
        {
            options.onReady = p2;
        }

        if ( isNode(p1) )
        {
            options.place = $(p1);
        }
        else if ( $.isArray(p1) || $.isPlainObject(p1) )
        {
            params = p1;
        }
        else if ( $.isFunction(p1) )
        {
            options.onReady = p1;
        }

        options = $.extend({}, {
            place: false,
            scope: window,
            context: window,
            addClass: '',
            onLoad: function( r ){},
            onReady: function( r ){},
            onError: function( r ){},
            onComplete: function( r ){}
        }, options);

        var rsp = this.ajaxComponentLoaderRsp,
            jsonParams = JSON.stringify(params),
            $preloader = false;

        if ( options.place )
        {
            $preloader = $('<div class="owm_preloader ' + options.addClass + '"></div>');
            $(options.place).html($preloader);
        }

        var ajaxOptions = {
            url: rsp + '?cmpClass=' + cmpClass + '&r=' + Math.random(),
            dataType: 'json',
            type: 'POST',
            data: {params: jsonParams},
            error: function(r)
            {
                options.onError(r);
            },
            complete: function(r)
            {
                options.onComplete(r);
            },
            success: function(markup)
            {
                var contentHtml = markup.content, $contentHtml = $(contentHtml);

                if ( !$contentHtml.length )
                {
                    contentHtml = '<span>' + contentHtml + '</span>';
                    $contentHtml = $(contentHtml)
                }

                if ( $preloader )
                {
                    $preloader.replaceWith($contentHtml);
                }

                options.onReady($contentHtml);

                if (markup.styleSheets)
                {
                    $.each(markup.styleSheets, function(i, o)
                    {
                        OWM.addCssFile(o);
                    });
                }

                if (markup.styleDeclarations)
                {
                    OWM.addCss(markup.styleDeclarations);
                }

                if (markup.beforeIncludes)
                {
                    OWM.addScript(markup.beforeIncludes, options.scope, options.context);
                }

                if (markup.scriptFiles)
                {

                    OWM.addScriptFiles(markup.scriptFiles, function()
                    {
                        if (markup.onloadScript)
                        {
                            OWM.addScript(markup.onloadScript, options.scope, options.context);
                            options.onLoad();
                        }
                    });
                }
                else
                {
                    if (markup.onloadScript)
                    {
                        OWM.addScript(markup.onloadScript, options.scope, options.context);
                    }
                    options.onLoad();
                }
            }
        };

        $.ajax(ajaxOptions);
    };

    /* Prevent main scrolling */
    var last_windows_top = 0;
    this.bind("mobile.show_sidebar", function( data ){
        $('body>#main').css('position', 'fixed');
        $(window).scrollTop(0);
    });
    this.bind("mobile.hide_sidebar", function( data ){
        $('body>#main').css('position', '');
        $(window).scrollTop(last_windows_top);
    });
    /* Prevent main scrolling */
};

var OWM_Console = function( params )
{
    params.pingInterval = params.pingInterval * 1000;

    var self = this;
    self.params = params;
    self.counter = 0;
    self.consoleState = 'closed';
    self.lastSelectedTab = 'notifications';
    self.targetSelectedTab = "";
    self.counters_by_tab = {notifs:0, convers:0};

    self.counter_is_gray = false;
    self.gray_counters_by_tab = {notifs:0, convers:0};


    var $counter = $("#console-counter");
    var $preloader = $("#console_preloader");
    var $body = $("#console_body");


    /* on right sidebar shown */
    OWM.bind("mobile.show_sidebar", function( data ){

        var beforeShowSidebarData = {openDefaultTab: true};
        OW.trigger("mobile.before_show_sidebar", beforeShowSidebarData);

        if ( data.type == "right" ) {

            if (beforeShowSidebarData.openDefaultTab){


                OWM.trigger("mobile.open_sidebar_tab", {key: self.lastSelectedTab});

//                self.loadPage("notifications", "BASE_MCMP_ConsoleNotificationsPage", function(){
//                    self.consoleState = 'open';
//                    self.counter = self.params.pages[0].counter;
//                });
            }
        }
    });

    /* on right sidebar hidden */
    OWM.bind("mobile.hide_sidebar", function( data ){
        if ( data.type == "right" )
        {
            self.consoleState = 'closed';
            self.counter = 0;
            self.setContent("");
            self.hideCounter(true, false);
        }
    });

    OWM.bind("mobile.console_item_removed", function( data ){
        self.hideCounter(true, false);
    });

    /* open right sidebar and specified tab */
    OWM.bind("mobile.open_sidebar_tab", function( data ){
        data.key = "notifications";
        OWM.showRightSidebar({silent: true});
        var pageData = self.getPageData(data.key);
        self.activateTab(data.key);
        self.loadPage(data.key, pageData.cmpClass);
    });

    OWM.bind('mobile.console_show_counter', function( data ){
        if((data.options) !== undefined && (data.options.tab) !== undefined) {
            self.gray_counters_by_tab[data.options.tab] = data.counter;
            if((data.new) !== undefined){
                self.counters_by_tab[data.options.tab] += data.new;
            }else if(data.options.tab == "notifs"){
                self.counters_by_tab[data.options.tab] = data.counter;
            }
        }
        self.showCounter(data.counter, data.tab, data.options);
    });

    /* console tab clicked */
    $("a.owm_sidebar_console_item_url").click(function() {
        var key = $(this).data('key');
        var data = self.getPageData(key);
        self.activateTab(key);
        self.loadPage(key, data.cmpClass);
    });

    /* loads console page */
    this.loadPage = function( key, cmpClass, onLoad ) {
        self.showPreloader();
        OWM.loadComponent(cmpClass, { },
            {
                onReady: function(html){
                    if(key != self.targetSelectedTab)
                        return;
                    self.setContent(html);
                    self.hidePreloader();
                    if ( $.isFunction(onLoad) )
                    {
                        onLoad();
                    }
                    OWM.trigger('mobile.console_page_loaded', {key : key});
                }
            }
        );
        self.lastSelectedTab = key;
    };

    this.showPreloader = function() {
        $preloader.fadeIn(300);
    };

    this.hidePreloader = function() {
        $preloader.fadeOut(300);
    };

    this.setContent = function( html ) {
        $body.html(html);
    };

    this.activateTab = function( key ) {
        $(".owm_sidebar_console_item").removeClass('owm_sidebar_console_item_active');
        $(".owm_sidebar_console_" + key).addClass("owm_sidebar_console_item_active");
        self.targetSelectedTab = key;

        if(key == "notifications"){
            self.counters_by_tab["notifs"] = 0;
            self.gray_counters_by_tab["notifs"] = 0;
        }else if(key == "convers"){
            self.counters_by_tab["convers"] = 0;
        }
    };

    this.showCounter = function(counter, tab, options){

        var options = options || {};

        //changed to fix notification count
        //self.counter = counter;
        self.counter = self.counters_by_tab["notifs"] + self.counters_by_tab["convers"];
        if(self.counter > 0) {
            self.counter_is_gray = false;
            $("span#console-counter").css({background: "#e20000"});
        }
        else{
            self.counter_is_gray = true;
            self.counter = self.gray_counters_by_tab["notifs"] + self.gray_counters_by_tab["convers"];
            $("span#console-counter").css({background: "gray"});
        }

        // counter is visible, set new value
        if ( $(".owm_content_header_count").is(":visible") ) {
            if(self.counter == 0){
                self.hideCounter(true, true);
            }else {
                $counter.html(self.counter);
            }
        }
        else {
            if(self.counter > 0){
                // show counter
                $(".owm_nav_profile").fadeOut("fast", function(){
                    if(self.counter > 0) {
                        $counter.html(self.counter);
                        $(".owm_content_header_count").css({display: "inline-block"}).animate({right: "5"}, {duration: 300});
                    }else{
                        self.hideCounter(true, true);
                    }
                });
            }
        }

        if ( !tab ) {
            self.lastSelectedTab = options.tab;
        }

        //update tab counters
        var notifs_counter = self.counters_by_tab["notifs"];
        var convers_counter = self.counters_by_tab["convers"];
        if(self.counter_is_gray){
            notifs_counter = self.gray_counters_by_tab["notifs"];
            convers_counter = self.gray_counters_by_tab["convers"];
        }
        self.updateTabCounter("#console-tab-notifications", notifs_counter);
        self.updateTabCounter("#console-tab-convers", convers_counter);
    };

    this.updateTabCounter = function( tabSelector, notif_counter) {
        var $tabCounter = $(".owm_sidebar_count_txt", tabSelector);
        if (notif_counter == 0){
            $(".owm_sidebar_count", tabSelector).hide();
            $tabCounter.html("");
        }
        else if ( $(".owm_sidebar_count", tabSelector).is(":visible") ) {
            $tabCounter.html(notif_counter);
        }
        else {
            // show counter
            $tabCounter.html(notif_counter);
            $(".owm_sidebar_count", tabSelector).fadeIn();
        }
    };

    this.hideCounter = function( tab, total){
        if(total) {
            $(".owm_content_header_count").hide();
            $counter.html("");
            $(".owm_nav_profile").show();
        }
        if(tab) {
            self.updateTabCounter("#console-tab-notifications", 0);
        }
    };

    this.getPageData = function( pageKey ) {
        var result = null;
        $.each(self.params.pages, function(key, value) {
            if ( value.key == pageKey )
            {
                result = value;
            }
        });

        return result;
    };

    /* called after each ping response */
    this.afterPing = function( data ) {
        if ( data.count ) {
            if ( self.consoleState == "open" ) {
                self.updateMarkup(data);
            }
            else {
               self.showCounter(data.count, true);
            }
        }
    };

    this.updateMarkup = function( data ) {
        if ( data.new_items )
        {
            self.showCounter(data.count + self.counter, true);

            // trigger event for each section to show new markup
            $.each(data.new_items, function( section, markup ){
                if ( markup != '' )
                {
                    OWM.trigger('mobile.console_load_new_items', {page: 'notifications', section: section, markup: markup});
                }
            });
        }
    };

    $("body")
        .on("click", "li.owm_sidebar_msg_disabled", function(){
            OWM.error(
                OWM.getLanguageText(
                    'base',
                    'mobile_disabled_item_message',
                    {url : $(this).data("disabled-url") != "" ? decodeURIComponent($(this).data("disabled-url")) : params.desktopUrl} )
            );
        });

    /* console ping initialization */
    this.init = function() {
        OWM.getPing().setRspUrl(params.rspUrl);
        var cmd = OWM.getPing().addCommand("mobileConsole", {
            before: function() {
                this.params.state = self.consoleState;
                this.params.timestamp = self.params.lastFetchTime;
            },
            after: function( data ) {
                self.params.lastFetchTime = data.timestamp;
                if(typeof(data.count) !== undefined) {
                    OWM.trigger('mobile.console_show_counter', {
                        counter: data.count,
                        tab: true,
                        options: {tab: 'notifs'}
                    });
                }
                self.afterPing(data);
            }
        });

        window.setTimeout(function() {
            cmd.start(self.params.pingInterval);
        }, 1000);
    };
};

var OWM_InvitationsConsole = function( params )
{
    var self = this;
    self.params = params;

    this.consoleAcceptRequest = function( $node )
    {
        var invId = $node.data("ref");
        var cmd = $node.data("cmd");
        var $row = $node.closest(".owm_sidebar_msg_item");
        $.ajax({
            url: self.params.cmdUrl,
            type: "POST",
            data: {"invid": invId, "command" : cmd},
            dataType: "json",
            success : function(data) {
                if ( data ) {
                    $row.remove();
                    OWM.trigger('mobile.console_item_removed', { section : "invitations" });
                    if ( data.result == true ) {
                        OWM.info(data.msg);
                    }
                }
            }
        });
    };

    this.consoleIgnoreRequest = function( $node )
    {
        var invId = $node.data("ref");
        var cmd = $node.data("cmd");
        var $row = $node.closest(".owm_sidebar_msg_item");
        $.ajax({
            url: self.params.cmdUrl,
            type: "POST",
            data: {"invid": invId, "command" : cmd},
            dataType: "json",
            success : function(data) {
                if ( data ) {
                    $row.remove();
                    OWM.trigger('mobile.console_item_removed', { section : "invitations" });
                }
            }
        });
    };

    this.consoleLoadMore = function( $node )
    {
        $node.addClass("owm_sidebar_load_more_preloader");

        var exclude =
            $("li.owm_sidebar_msg_item", "#invitations-list")
                .map(function(){
                    return $(this).data("invid");
                })
                .get();

        OWM.loadComponent(
            "BASE_MCMP_ConsoleInvitations",
            {limit: self.params.limit, exclude: exclude},
            {
                onReady: function(html){
                    $("#invitations-list").append(html);
                    $node.removeClass("owm_sidebar_load_more_preloader");
                }
            }
        );
    };

    this.hideLoadMoreButton = function()
    {
        $("#invitations-load-more").closest(".owm_sidebar_msg_list").hide();
    };

    $("body")
        .on("click", "a.owm_invite_accept", function(){
            self.consoleAcceptRequest($(this));
        })
        .on("click", "a.owm_invite_ignore", function(){
            self.consoleIgnoreRequest($(this));
        })
        .on("click", "a#invitations-load-more", function(){
            self.consoleLoadMore($(this));
        });

    OWM.bind("mobile.console_hide_invitations_load_more", function(){
        self.hideLoadMoreButton();
    });

    OWM.bind("mobile.console_load_new_items", function(data){
        if ( data.page == 'notifications' && data.section == 'invitations' )
        {
            $("#invitations-cap").show();
            $("#invitations-list").prepend(data.markup);
        }
    });

    OWM.bind("mobile.console_item_removed", function( data ){
        if ( data.section == "invitations" )
        {
            if ( $("#invitations-list li").length == 0 )
            {
                $("#invitations-cap").hide();
            }
        }
    });

    // unbind all events
    OWM.bind("mobile.hide_sidebar", function(data){
        if ( data.type == "right" )
        {
            OWM.unbind("mobile.console_hide_invitations_load_more");
            OWM.unbind("mobile.console_load_new_items");
            $("body")
                .off("click", "a.owm_invite_accept")
                .off("click", "a.owm_invite_ignore")
                .off("click", "a#invitations-load-more");
        }
    });
};

jQuery.fn.extend({
	inprogress: function() {
		this.each(function()
		{
			var $this = jQuery(this).addClass('ow_inprogress');
			this.disabled = true;

			if ( this.tagName != 'INPUT' && this.tagName != 'TEXTAREA' && this.tagName != 'SELECT' )
			{
				this.jQuery_disabled_clone = $this.clone().removeAttr('id').removeAttr('onclick').get(0);

				$this.hide()
					.bind('unload', function(){
						$this.activate();
					})
					.after(this.jQuery_disabled_clone);
			}
		});

		return this;
	},

	activate: function() {
		this.each(function()
		{
			var $this = jQuery(this).removeClass('ow_inprogress');
			this.disabled = false;

			if ( this.jQuery_disabled_clone )
			{
				jQuery(this.jQuery_disabled_clone).remove();
				this.jQuery_disabled_clone = null;

				jQuery(this)
					.unbind('unload', function(){
						$this.activate();
					})
					.show();
			}
		});

		return this;
	}
});

window.OWM = new OWMobile();


/* OW Forms */

var OwFormElement = function( id, name ){
    this.id = id;
    this.name = name;
    this.input = document.getElementById(id);
    this.validators = [];
}

OwFormElement.prototype = {

    validate: function(){
        
        try{
            for( var i = 0; i < this.validators.length; i++ ){
                this.validators[i].validate(this.getValue());
            }
        }catch (e) {
            this.showError(e);
            throw e;
        }
    },

    addValidator: function( validator ){
        this.validators.push(validator);
    },

    getValue: function(){
        return $(this.input).val();
    },

    setValue: function( value ){
        $(this.input).val(value);
    },

    resetValue: function(){
        $(this.input).val('');
    },

    showError: function( errorMessage ){
        $('#'+this.id+'_error').append(errorMessage).fadeIn(50);
    },

    removeErrors: function(){
        $('#'+this.id+'_error').empty().fadeOut(50);
    },

    /*
     * author: seyed ismail mirvakili
     * The goal is supporting persian numbers
     * */
    correctNumbers: function () {
        if($(this.input).attr('type') !== 'text')
            return;
        var val = $(this.input).val();
        val = val.replace(/۰/g, "0");
        val = val.replace(/۱/g, "1");
        val = val.replace(/۲/g, "2");
        val = val.replace(/۳/g, "3");
        val = val.replace(/۴/g, "4");
        val = val.replace(/۵/g, "5");
        val = val.replace(/۶/g, "6");
        val = val.replace(/۷/g, "7");
        val = val.replace(/۸/g, "8");
        val = val.replace(/۹/g, "9");
        $(this.input).val(val);
    }
}

var OwForm = function( params ){
    $.extend(this, params);
    this.form = document.getElementById(this.id);
    this.elements = {};
    var actionUrl = $(this.form).attr('action');
    this.actionUrl = ( !actionUrl ? location.href : actionUrl );
    this.showErrors = true;
    this.events = {
        submit:[],
        success:[]
    }
};

OwForm.prototype = {

    addElement: function( element ){
        this.elements[element.name] = element;
    },

    getElement: function( name ){
        if( this.elements[name] === undefined ){
            return null;
        }

        return this.elements[name];
    },

    validate: function(){

        var error = false;
        var element = null;
        var errorMessage;

        $.each( this.elements,
            function(index, data){
                try{
                    /*
                     * author: seyed ismail mirvakili
                     * The goal is supporting persian numbers
                     * */
                    if(typeof(data.correctNumbers) !== "undefined"){
                        data.correctNumbers();
                    }
                    data.validate();
                }catch (e){
                    error = true;

                    if( element == null ){
                        element = data;
                        errorMessage = e;
                    }
                }
            }
            );

        if( error ){
            if( element.input.style.visibility!='hidden' && element.input.style.display!='none') {
                element.input.focus();
            }

            if( this.validateErrorMessage ){
                throw this.validateErrorMessage;
            }else{
                throw errorMessage;
            }
        }
    },

    bind: function( event, fnc ){
        this.events[event].push(fnc);
    },

    sucess: function( fnc ){
        this.bind('success', fnc);
    },

    submit: function( fnc ){
        this.bind('submit', fnc);
    },

    trigger: function( event, data ){
        if( this.events[event] == undefined || this.events[event].length == 0 ){
            return;
        }
        
        var result = undefined, returnVal;

        for( var i = 0; i < this.events[event].length; i++ ){
            
            returnVal = this.events[event][i].apply(this.form, [data]);
            if(returnVal === false || returnVal === true ){
                result = returnVal;
            }
        }
        
        if( result !== undefined ){
            return result;
        }
    },

    getValues: function(){

        var values = {};

        $.each(this.elements,
            function( index, data ){
                values[data.name] = data.getValue();
            }
            );

        return values;
    },

    setValues: function( values ){

        var self = this;

        $.each( values,
            function( index, data ){
                if(self.elements[index]){
                    self.elements[index].setValue(data);
                }
            }
            );
    },

    resetForm: function(){
        $.each( this.elements,
            function( index, data ){
                data.resetValue();
            }
            );
    },

    removeErrors: function(){

        $.each( this.elements,
            function( index, data ){
                data.removeErrors();
            }
            );
    },

    submitForm: function(){

        var self = this;
        var formName;
        var formResult;
        this.removeErrors();

        try{
            this.validate();
        }catch(e){
            if( this.showErrors ){
                OWM.error(e);
            }
            return false;
        }

        var dataToSend = this.getValues();
        if( self.trigger('submit', dataToSend) === false ){
            return false;
        }

        var buttons = $('input[type=button], input[type=submit], button', '#' + this.id).addClass('ow_inprogress');

        if( this.ajax ){
            OWM.inProgressNode(buttons);
            var postString = '';

            $.each( dataToSend, function( index, data ){
                if ( $.isArray(data) || $.isPlainObject(data) ) {
                    $.each(data, function (key, value){
                        postString += index + '[' + key + ']=' + encodeURIComponent(value) + '&';
                    });
                }
                else{
                    postString += index + '=' + encodeURIComponent(data) + '&';
                }
            } );

            $.ajax({
                type: 'post',
                url: this.actionUrl,
                data: postString,
                dataType: self.ajaxDataType,
                success: function(data){
                    if(self.reset){
                        self.resetForm();
                    }
                    formName=self.form.name;
                    formResult=data.result;
                    self.trigger('success', data);
                },
                error: function( XMLHttpRequest, textStatus, errorThrown ){
                    OWM.error(textStatus);
                    throw textStatus;
                },
                complete: function(){
                    if(formName!="sign-in" || (formName=="sign-in" && formResult!=true)) {
                        OWM.activateNode(buttons);
                    }
                }
            });

            return false;
        }

        $.each(this.elements,
            function( i, o ){
                if( $(o.input).hasClass('invitation') ){
                    $(o.input).attr('disabled', 'disabled');
                }
            }
            );

        return true;
    }
}

owForms = {};

// custom fields
var addInvitationBeh = function( formElement, invitationString ){
    formElement.invitationString = invitationString;

    formElement.getValue = function(){
        var val = $(this.input).val();
        if( val != '' && val != this.invitationString ){
            $(this.input).removeClass('invitation');
            return val;
        }
        else{
            return '';
        }
    };

    var parentResetValue = formElement.resetValue;

    formElement.resetValue = function(){
        parentResetValue.call(this);

        $(this.input).addClass('invitation');
        $(this.input).val(invitationString);
    };

    $(formElement.input
        ).bind('focus.invitation', {formElement:formElement},
            function(e){
                el = $(this);
                el.removeClass('invitation');
                if( el.val() == '' || el.val() == e.data.formElement.invitationString){
                    el.val('');
                    //hotfix for media panel
                    if( 'htmlarea' in el.get(0) ){
                        el.unbind('focus.invitation').unbind('blur.invitation');
                        el.get(0).htmlarea();
                        el.get(0).htmlareaFocus();
                    }
                }
                else{
                    el.unbind('focus.invitation').unbind('blur.invitation');
                }
            }
        )/*.bind('blur.invitation', {formElement:formElement},
            function(e){
                el = $(this);
                if( el.val() == '' || el.val() == e.data.formElement.invitationString){
                    el.addClass('invitation');
                    el.val(e.data.formElement.invitationString);
                }
                else{
                    el.unbind('focus.invitation').unbind('blur.invitation');
                }
            }
    );*/
}

var OwTextField = function( id, name, invitationString ){
    var formElement = new OwFormElement(id, name);
    if( invitationString ){
        addInvitationBeh(formElement, invitationString);
    }
    return formElement;
}

var OwTextArea = function( id, name, invitationString ){
    var formElement = new OwFormElement(id, name);
    if( invitationString ){
        addInvitationBeh(formElement, invitationString);
    }
    return formElement;
}

var OwWysiwyg = function( id, name, invitationString ){
    var formElement = new OwFormElement(id, name);
    formElement.input.focus = function(){this.htmlareaFocus();};
    addInvitationBeh(formElement, invitationString);
    formElement.resetValue = function(){$(this.input).val('');$(this.input).keyup();};
    formElement.getValue = function(){
                var val = $(this.input).val();
                if( val != '' && val != '<br>' && val != '<div><br></div>' && val != this.invitationString ){
                    $(this.input).removeClass('invitation');
                    return val;
                }
                else{
                    return '';
                }
            };

    return formElement;
}

var OwRadioField = function( id, name ){
    var formElement = new OwFormElement(id, name);

    formElement.getValue = function(){
        var value = $("input[name='"+this.name +"']:checked", $(this.input.form)).val();
        return ( value == undefined ? '' : value );
    };

    formElement.resetValue = function(){
        $("input[name='"+this.name +"']:checked", $(this.input.form)).removeAttr('checked');
    };

    formElement.setValue = function(value){
        $("input[name='"+ this.name +"'][value='"+value+"']", $(this.input.form)).attr('checked', 'checked');
    };

    return formElement;
}

var OwCheckboxGroup = function( id, name ){
    var formElement = new OwFormElement(id, name);

    formElement.getValue = function(){
        var $inputs = $("input[name='"+ this.name +"[]']:checked", $(this.input.form));
        var values = [];

        $.each( $inputs, function(index, data){
                if( this.checked == true ){
                    values.push($(this).val());
                }
            }
        );

        return values;
    };

    formElement.resetValue = function(){
        var $inputs = $("input[name='"+ this.name +"[]']:checked", $(this.input.form));

        $.each( $inputs, function(index, data){
                $(this).removeAttr('checked');
            }
        );
    };

    formElement.setValue = function(value){
        for( var i = 0; i < value.length; i++ ){
            $("input[name='"+ this.name +"[]'][value='"+value[i]+"']", $(this.input.form)).attr('checked', 'checked');
        }
    };

    return formElement;
}

var OwCheckboxField = function( id, name ){
    var formElement = new OwFormElement(id, name);

    formElement.getValue = function(){
        var $input = $("input[name='"+this.name+"']:checked", $(this.input.form));
        if( $input.length == 0 ){
            return '';
        }
        return 'on';
    };

    formElement.setValue = function(value){
        var $input = $("input[name='"+this.name+"']:checked", $(this.input.form));
        if( value ){
            $input.attr('checked', 'checked');
        }
        else{
            $input.removeAttr('checked');
        }
    };
    formElement.resetValue = function(){
        var $input = $("input[name='"+this.name+"']:checked", $(this.input.form));
        $input.removeAttr('checked');
    };

    return formElement;
};

var OwRange = function( id, name ){
    var formElement = new OwFormElement(id, name);

    formElement.getValue = function(){
        var $inputFrom = $("select[name='"+ this.name +"[from]']");
        var $inputTo = $("select[name='"+ this.name +"[to]']");
        var values = [];

        values.push($inputFrom.val());
        values.push($inputTo.val());

        return values;
    };

    formElement.setValue = function(value){
        var $inputFrom = $("select[name='"+ this.name +"[from]']");
        var $inputTo = $("select[name='"+ this.name +"[to]']");

        if( value[1] ){
            $("option[value='"+ value[1] +"']", $inputFrom).attr('selected', 'selected');
        }

        if( value[2] ){
            $("option[value='"+ value[2] +"']", $inputTo).attr('selected', 'selected');
        }

    };

    return formElement;
};

/* end of forms */

/* PING */
OWM_PingCommand = function( commandName, commandObject, stack )
{
    $.extend(this, commandObject);

    this.commandName = commandName;
    this.repeatTime = false;
    this.minRepeatTime = null;

    this.stack = stack;
    this.commandTimeout = null;
    this.stopped = true;
    this.skipped = false;
    this.inProcess = false;
    this.isRootCommand = false;

    this._lastRunTime = null;
};

OWM_PingCommand.PROTO = function()
{
    this._updateLastRunTime = function()
    {
        this._lastRunTime = $.now();
    };

    this._received = function( r )
    {
        this.after(r);
    };

    this._delayCommand = function()
    {
        var self = this;

        if ( this.commandTimeout )
        {
            window.clearTimeout(this.commandTimeout);
        }

        this.commandTimeout = window.setTimeout(function()
        {
            self._run();
            self.skipped = false;
        }, this.repeatTime);
    };

    this._completed = function()
    {
        this.inProcess = false;
        this._updateLastRunTime();

        if ( this.skipped || this.stopped || this.repeatTime === false )
        {
            return;
        }

        this._delayCommand();
    };

    this._getStackCommand = function()
    {
        return {
            "command": this.commandName,
            "params": this.params
        };
    };

    this._beforeStackSend = function()
    {
        if ( this.minRepeatTime === null || this.stopped || this.inProcess || this.isRootCommand )
        {
            return;
        }

        if ( $.now() - this._lastRunTime < this.minRepeatTime )
        {
            return;
        }

        this._run();
    };

    this._run = function()
    {
        if ( !this.stopped )
        {
            this.inProcess = true;
            this.stack.push(this);
        }

        if ( this.onRun )
        {
            this.onRun(this);
        }
    };

    this.params = {};
    this.before = function(){};
    this.after = function(){};

    this.start = function( repeatTime )
    {
        if ( $.isNumeric(repeatTime) )
        {
            this.repeatTime = repeatTime;
        }
        else if ( $.isPlainObject(repeatTime) )
        {
            if ( repeatTime.max )
            {
                this.repeatTime = repeatTime.max;
            }

            if ( repeatTime.min )
            {
                this.minRepeatTime = repeatTime.min == 'each' ? 0 : repeatTime.min;
            }
        }

        this.stop();
        this.stopped = false;

        if ( !this.inProcess )
        {
            this._run();
        }
    };

    this.skip = function()
    {
        this.skipped = true;
        this._delayCommand();
    };

    this.stop = function()
    {
        this.stopped = true;
    };
};

OWM_PingCommand.prototype = new OWM_PingCommand.PROTO();

OWM_Ping = function()
{
    var _stack = [], _commands = {};

    var rspUrl;
    var _rootCommand = null;

    var beforeStackSend, sendStack, refreshRootCommand, rootOnCommandRun, genericOnCommandRun, setRootCommand;

    rootOnCommandRun = function( command )
    {
        window.setTimeout(function(){
            sendStack();
        }, 10);
    };

    genericOnCommandRun = function( command )
    {
        if ( !_rootCommand )
        {
            setRootCommand(command);
            rootOnCommandRun(command);

            return;
        }

        if ( command.repeatTime === false )
        {
            return;
        }

        if ( _rootCommand.repeatTime === false || _rootCommand.repeatTime > command.repeatTime )
        {
            setRootCommand(command);
            rootOnCommandRun(command);
        }
    };

    refreshRootCommand = function()
    {
        var rootCommand = null;

        for ( var c in _commands )
        {
            if ( _commands[c].repeatTime === false || _commands[c].stopped  )
            {
                continue;
            }

            if ( !rootCommand || _commands[c].repeatTime < rootCommand.repeatTime )
            {
                rootCommand = _commands[c];
            }
        }

        if ( rootCommand )
        {
            setRootCommand(rootCommand);
        }
    };

    setRootCommand = function( command )
    {
        if ( _rootCommand )
        {
            _rootCommand.onRun = genericOnCommandRun;
            _rootCommand.isRootCommand = false;
        }

        command.isRootCommand = true;
        _rootCommand = command;
        _rootCommand.onRun = rootOnCommandRun;
    };

    beforeStackSend = function()
    {
        for ( var c in _commands )
        {
            _commands[c]._beforeStackSend();
        }
    };

    sendStack = function()
    {
        beforeStackSend();

        if ( !_stack.length )
        {
            return;
        }

        while ( _stack.length )
        {
            var stack = [], commands = [];

            while (_stack.length && stack.length < 3)
            {
                var c = _stack.pop();
                commands.push(c);

                if (c.before() === false) {
                    c.skip();
                    continue;
                }

                stack.push(c._getStackCommand());
            }

            if (!stack.length)
            {
                return;
            }

            var request = {
                "stack": stack
            };

            var jsonRequest = JSON.stringify(request);

            var ajaxOptions =
                {
                    url: rspUrl,
                    dataType: 'json',
                    type: 'POST',
                    data: {request: jsonRequest},
                    success: function (result) {
                        if (!result || !result.stack) {
                            return;
                        }

                        $.each(result.stack, function (i, command) {
                            if (_commands[command.command]) {
                                _commands[command.command]._received(command.result);
                            }

                            //to fix notifications count
                            if(command.command==="mailbox_ping") {
                                var new_count = 0, total_count = 0;
                                if(typeof(command.result.markedUnreadConversationList)!=="undefined"){
                                    if(typeof(command.result.markedUnreadConversationList.length)!=="undefined"){
                                        total_count = command.result.markedUnreadConversationList.length;
                                    }else if (typeof(Object.keys(command.result.markedUnreadConversationList).length)!=="undefined") {
                                        total_count = Object.keys(command.result.markedUnreadConversationList).length;
                                    }
                                }

                                if(typeof(command.result.newMessageCount)!=="undefined"){
                                    new_count = command.result.newMessageCount.new;
                                }
                                OWM.trigger('mobile.console_show_counter', {
                                    counter: total_count,
                                    new: new_count,
                                    tab: true,
                                    options: {tab: 'convers'}
                                });
                            }else if(command.command==="mobileConsole") {
                                OWM.trigger('mobile.console_show_counter', {
                                    counter: command.result.count,
                                    tab: true,
                                    options: {tab: 'notifs'}
                                });
                            }
                        });
                    },

                    complete: function () {
                        $(commands).each(function (i, command) {
                            command._completed();
                        });

                        refreshRootCommand();
                    }
                };

            $.ajax(ajaxOptions);
        }
    };

    this.addCommand = function( commandName, commandObject )
    {
        if ( _commands[commandName] )
        {
            return _commands[commandName];
        }

        commandObject = commandObject || {};

        _commands[commandName] = new OWM_PingCommand(commandName, commandObject, _stack);
        _commands[commandName].onRun = genericOnCommandRun;

        return _commands[commandName]
    };

    this.getCommand = function( commandName )
    {
        return _commands[commandName] || null;
    };

    this.setRspUrl = function( url )
    {
        rspUrl = url;
    };
};

OWM_Ping.getInstance = function()
{
    if ( !OWM_Ping.pingInstance )
    {
        OWM_Ping.pingInstance = new OWM_Ping();
    }

    return OWM_Ping.pingInstance;
};

OWM.getPing = function()
{
    return OWM_Ping.getInstance();
};


/* FloatBox */

OWM.getActiveFloatBox = function() {
    return window.OWActiveFloatBox || null;
};

OWM.FloatBox = (function() {
    var _overlay = $(".owm_overlay");
    var _tpl = $("[data-tpl=wrap]", "#floatbox_prototype");
    var _stack = [];
    
    _overlay.on("click.fb", function() {
        while (_stack.length) {
            _stack[0].close();
        }
    });
    
    function FloatBox(params)
    {
        var self = this;
        params = params || {};
        
        if ( _stack.length ) {
            _stack[0]._disappear();
        }
        _stack.unshift(this);
        
        window.OWActiveFloatBox = this;
        //comment this line to prevent scrolling top
        //$(window).scrollTop(0);
        
        _overlay.show();
        
        this.container = _tpl.clone();
        $("#main > section").append(this.container);
        
        this.body = this.container.find("[data-tpl=body]");
        this.leftBtn = this.container.find("[data-tpl=left-btn]").hide();
        this.rightBtn = this.container.find("[data-tpl=right-btn]").hide();
        this.heading = this.container.find("[data-tpl=heading]");
        
        this.events = {};
        this._contentParent = null;
       
        if ( params.addClass ) {
            this.container.addClass(params.addClass);
        }
       
        if ( params.content ) {
            this.setContent(params.content);
        }
        
        if ( params.height ) {
            this.body.height(params.height);
        }
        
        this.title = params.title || "";
        
        if ( this.title ) {
            this.heading.text(this.title);
        }
        
        $('body').addClass('floatbox_opened');
        this._setupBtn(this.leftBtn, params.leftBtn || {
            "iconClass": "owm_nav_back",
            "click": function() {
                self.close();
            }
        });
        
        if ( params.rightBtn ) {
            this._setupBtn(this.leftBtn, params.rightBtn);
        }
        
        this.trigger("show");
    }
    
    var proto = {
        _appear: function() {
            this.container.show();
        },
        
        _disappear: function() {
            this.container.hide();
        },
        
        _setupBtn: function( btn, params ) {
            btn.show();
            
            if ( params.iconClass ) {
                btn.addClass(params.iconClass);
            }
            
            if ( params.click && $.isFunction(params.click) ) {
                btn.on("click.fb", params.click);
            }
            
            if ( params.url ) {
                btn.attr("href", params.url);
            }
        },
        
        setContent: function( content ) {
            if (typeof content === 'string')
            {
                if ( !$(content).length ) {
                    content = $('<span>' + content + '</span>');
                }
            }
            else
            {
                this._contentParent = content.parent();
            }
            
            this.body.html(content);
        },
        
        close: function() {
            $('body').removeClass('floatbox_opened');
            if (this.trigger('close') === false) {
                return false;
            }
            
            if ( this._contentParent )
            {
                this._contentParent.append(this.body.children());
            }
            
            this.container.remove();
            
            _stack.shift();
            
            if ( !_stack.length ) {
                _overlay.hide();
            }
            else {
                _stack[0]._appear();
            }
            
            return true;
        },

        bind: function(type, func) {
            this.events[type] = this.events[type] || [];
            this.events[type].push(func);
        },

        trigger: function(type, params) {
            params = params || [];
            var stack = this.events[type] || [];

            for ( var i = 0; stack[i]; i++ ) {
                if ( stack[i].apply(this, params) === false ) {
                    return false;
                }
            }

            return true;
        }
    };
        
    return function () {
        function F() {};
        F.prototype = proto;
        
        var o = new F();
        FloatBox.apply(o, arguments);

        return o;
    };
})();


OWM.ajaxFloatBox = function(cmpClass, params, options)
{
    params = params || [];

    options = options || {};
    options = $.extend({}, {
        title: '',
        height: false,
        iconClass: false,
        addClass: false,
        leftBtn: false,
        rightBtn: false,
        scope: {},
        context: null,
        onLoad: function(){},
        onReady: function(){},
        onError: function(){},
        onComplete: function(){}
    }, options);

    var floatBox = new OWM.FloatBox({
        title: options.title,
        height: options.height,
        iconClass: options.iconClass,
        addClass: options.addClass
    });

    options.scope = $.extend({floatBox: floatBox}, options.scope);
    options.context = options.context || floatBox;

    this.loadComponent(cmpClass, params,
    {
        scope: options.scope,
        context: options.context,
        onLoad: options.onLoad,
        onError: options.onError,
        onComplete: options.onComplete,
        onReady: function( r )
        {
            floatBox.setContent(r);

            if ( $.isFunction(options.onReady) )
            {
                options.onReady.call(this, r);
            }
        }
    });

    return floatBox;
};

window.OW = window.OWM;

/* Comments */
var OwMobileComments = function( contextId, formName, genId ){
	this.formName = formName;
	this.$cmpContext = $('#' + contextId);
    this.genId = genId;
    
};

OwMobileComments.prototype = {
    repaintCommentsList: function( data ){
        owForms[this.formName].getElement('commentText').resetValue();
        if(data.error){
            OW.error(data.error);
            return;
        }
        window.owCommentListCmps.items[this.genId].updateMarkup(data);
    },

    setCommentsCount: function( count ){
        $('input[name=commentCountOnPage]', this.$cmpContext).val(count);
    },

    getCommentsCount: function(){
        return parseInt($('input[name=commentCountOnPage]', this.$cmpContext).val());
    },
    
    initForm: function( textareaId, submitId ){
        var self = this;

        // init pseudo auto click
        var $textA = $('#'+textareaId, this.$cmpContext), $submitCont = $('#'+submitId, this.$cmpContext).closest('.comment_submit');
        if( !this.taMessage ){
            this.taMessage = $textA.val();
        }

        var taHandler = function(){
            $(this).removeClass('invitation').val('');
            $submitCont.show();
            //setTimeout(function(){$('body').animate({scrollTop: 1000})}, 2000);

        };

        var resetTa = function(){
            $textA.unbind('focus').one('focus', taHandler).val(self.taMessage).addClass('invitation');
            $submitCont.hide();
        };

        $textA.unbind('focus').one('focus', taHandler).bind('blur',
            function(){
                if( $(this).val() == '' ){
                    resetTa();
                }
            }
        );

/*        $textA.keydown(function(){
            var el = this;
            setTimeout(function(){
                el.style.height = "";
                el.style.cssText = 'height:'+ el.scrollHeight + "px !important;";
            },0);
        });*/

        //end
        owForms[this.formName].bind('success',
            function(data){                
                resetTa();
                self.repaintCommentsList(data);
                OW.trigger('base.comment_add', {entityType: data.entityType, entityId: data.entityId}, this);
                $textA.focus();

                var button_elem = $('.owm_newsfeed_comment_submit input');
                $(button_elem).removeClass('owm_preloader_circle');
                var bg=button_elem.css('color').replace('rgba','rgb');
                var a=bg.slice(4).split(',');
                var newBg='rgba('+a[0]+','+parseInt(a[1])+','+parseInt(a[2])+',255)';
                $(button_elem).css('color',newBg);
            }
        );

       owForms[this.formName].bind('submit',
            function(data){
                var cmpObj = owCommentCmps[self.genId], count = cmpObj.getCommentsCount()+1;
                data['commentCountOnPage'] = count;
                cmpObj.setCommentsCount(count);

                var button_elem = $('.owm_newsfeed_comment_submit input');
                $(button_elem).addClass('owm_preloader_circle');
                var bg=button_elem.css('color').replace('rgba','rgb');
                var a=bg.slice(4).split(',');
                var newBg='rgba('+a[0]+','+parseInt(a[1])+','+parseInt(a[2])+',0)';
                $(button_elem).css('color',newBg);
            }
        );

        owForms[this.formName].reset = false;
    }

};

var OwMobileCommentsList = function( params ){
	this.$context = $('#' + params.contextId);
	$.extend(this, params, owCommentListCmps.staticData);
};

OwMobileCommentsList.prototype = {
	init: function(){
		var self = this;
//
//        OW.bind('base.comments_list_update',
//            function(data){
//                if( data.entityType == self.entityType && data.entityId == self.entityId && data.id != self.cid ){
//                    self.reload();
//                }
//            }
//        );
        $.each(this.actionArray.comments,
            function(i,o){
                $('#'+i).click(
                    function(){
                        var ajax_settings ={
                            type: 'POST',
                            url: self.delUrl,
                            data: {
                                cid:self.cid,
                                commentCountOnPage:self.commentCountOnPage,
                                ownerId:self.ownerId,
                                pluginKey:self.pluginKey,
                                displayType:self.displayType,
                                entityType:self.entityType,
                                entityId:self.entityId,
                                initialCount:self.initialCount,
                                page:self.page,
                                commentId:o
                            },
                            dataType: 'json',
                            success : function(data){
                                if(data.error){
                                    OW.error(data.error);
                                    return;
                                }

                                self.$context.replaceWith(data.commentList);
                                OW.addScript(data.onloadScript);

                                var eventParams = {
                                    entityType: self.entityType,
                                    entityId: self.entityId,
                                    commentCount: data.commentCount
                                };

                                OW.trigger('base.comment_delete', eventParams, this);
                            }
                        } ;
                        var jc = $.confirm(self.delConfirmMsg);
                        var self2 = this;
                        jc.buttons.ok.action = function () {
                            $(self2).closest('div.ow_comments_item').slideUp(300, function(){$(self2).remove();});
                            $.ajax(ajax_settings);
                        }
                    }
                );
            }
        );

        $.each(this.actionArray.users,
            function(i,o){
                $('#'+i).click(
                    function(){
                        OW.Users.deleteUser(o);
                    }
                );
            }
        );

        for( i = 0; i < this.commentIds.length; i++ )
        {
            if( $('#att'+this.commentIds[i]).length > 0 )
            {
                $('.attachment_delete',$('#att'+this.commentIds[i])).bind( 'click', {i:i},
                    function(e){

                        $('#att'+self.commentIds[e.data.i]).slideUp(300, function(){$(this).remove();});

                        $.ajax({
                            type: 'POST',
                            url: self.delAtchUrl,
                            data: {
                                cid:self.cid,
                                commentCountOnPage:self.commentCountOnPage,
                                ownerId:self.ownerId,
                                pluginKey:self.pluginKey,
                                displayType:self.displayType,
                                entityType:self.entityType,
                                entityId:self.entityId,
                                page:self.page,
                                initialCount:self.initialCount,
                                loadMoreCount:self.loadMoreCount,
                                commentId:self.commentIds[e.data.i]
                            },
                            dataType: 'json'
                        });
                    }
                );
            }
        }
          $('.cmnt_load_more_cont', this.$context).click(
            function(){
                $(this).addClass('owm_load_more');
                self.commentCountOnPage += self.loadCount;
                self.commentsToLoad -= self.loadCount;
                window.owCommentCmps[self.cid].setCommentsCount(self.commentCountOnPage);
                self.reload();
            }
          );

         OW.trigger('base.comments_list_init', {entityType: this.entityType, entityId: this.entityId}, this);
	},

    updateMarkup: function( data ){
        this.$context.replaceWith(data.commentList);
        OW.addScript(data.onloadScript);
        OW.trigger('base.comments_list_update', {entityType: data.entityType, entityId: data.entityId, id:this.genId});
    },

	reload:function(){
		var self = this;
		$.ajax({
            type: 'POST',
            url: self.respondUrl,
            data: 'cid='+self.cid+'&commentCountOnPage='+self.commentCountOnPage+'&ownerId='+self.ownerId+'&pluginKey='+self.pluginKey+'&displayType='+self.displayType+'&entityType='+self.entityType+'&entityId='+self.entityId,
            dataType: 'json',
            success : function(data){
               if(data.error){
                        OW.error(data.error);
                        return;
                }
                self.updateMarkup(data);
                $(this).removeClass('owm_load_more');
            },
            error : function( XMLHttpRequest, textStatus, errorThrown ){
                $(this).removeClass('owm_load_more');
                OW.error('Ajax Error: '+textStatus+'!');
                throw textStatus;
            }
        });
	}
};

owCommentCmps = {};
owCommentListCmps = {items:{},staticData:{}};



OWM.Utils = (function() {

    return {
        toggleText: function( node, value, alternateValue ) {
            var $node = $(node), text = $node.text();

            if ( !$node.data("toggle-text") )
                $node.data("toggle-text", text);

            alternateValue = alternateValue || $node.data("toggle-text");
            $node.text(text === alternateValue ? value : alternateValue);
        },

        toggleAttr: function( node, attributeName, value, alternateValue ) {
            var $node = $(node), attributeValue = $node.attr(attributeName);

            if ( !$node.data("toggle-" + attributeName) )
                $node.data("toggle-" + attributeName, attributeValue);

            alternateValue = alternateValue || $node.data("toggle-" + attributeName);
            $node.attr(attributeName, attributeValue === alternateValue ? value : alternateValue);
        }
    };
})();


OWM.Users = null;
OWM_UsersApi = function( _settings )
{
    var _usersApi = this;

    var _query = function(command, params, callback) {
        callback = callback || function( r ) {
            if ( !r ) return;
            if (r.info) OW.info(r.info);
            if (r.error) OW.error(r.error);
        };

        return $.getJSON(_settings.rsp, {"command": command, "params": JSON.stringify(params)}, callback);
    };

    OWM.Users = this;

    //Public methods

    this.deleteUser = function(userId,code, callBack)
    {
        var redirectUrl = null;

        if ( typeof callBack === "string" )
        {
            redirectUrl = callBack;
            
            callBack = function() {
                window.location.assign(redirectUrl);
            };
        }
        
        return _query("deleteUser", {"userId": userId,"code": code}, callBack);
    },

    this.showUsers = function(userIds, title)
    {
        title = title || OW.getLanguageText('base', 'ajax_floatbox_users_title');

        OW.ajaxFloatBox('BASE_CMP_FloatboxUserList', [userIds], {iconClass: "ow_ic_user", title: title, width: 470});
    },
    this.suspendUser = function( userId,suspendCode,unSuspendCode, callback ) {
        return _query("suspend", {"userId": userId,"code": suspendCode}, callback);
    };

    this.unSuspendUser = function( userId,suspendCode,unSuspendCode, callback ) {
        return _query("unsuspend", {"userId": userId,"code": unSuspendCode}, callback);
    };

    this.blockUser = function( userId,code, callback ) {
        return _query("block", {"userId": userId, "code": code}, callback);
    };

    this.unBlockUser = function( userId,code, callback ) {
        return _query("unblock", {"userId": userId, "code": code}, callback);
    };

    this.featureUser = function( userId,featureCode,unFeatureCode, callback ) {
        return _query("feature", {"userId": userId, "code": featureCode}, callback);
    };

    this.unFeatureUser = function( userId,featureCode,unFeatureCode, callback ) {
        return _query("unfeature", {"userId": userId, "code": unFeatureCode}, callback);
    };
};

/*      clipboard menu. by Issa Annamoradnejad*/
$(function() {
    var longpress=850;
    var start=0;
    var cb_text;
    var element;
    $selector = ".owm_newsfeed_item .owm_newsfeed_body_status," +
        ".owm_newsfeed_body_info_wrap .owm_newsfeed_body_title," +
        ".owm_newsfeed_body_info_wrap .owm_newsfeed_body_descr," +
        ".owm_newsfeed_body_activity_info .owm_newsfeed_body_activity_descr," +
        ".owm_newsfeed_comment_list .owm_newsfeed_comment_txt," +
        ".iismainpage #mailboxSidebarConversationsItemSubject," +
        // ".owm_chat_window .owm_chat_bubble," +
        ".owm_mail_window .owm_mail_txt," +
        ".owm_mail_window .owm_mail_subject," +
        ".owm_list_item_with_image .ow_more_text," +
        ".owm_list_item_with_image .ow_ipc_content," +
        ".owm_list_item_without_image .owm_list_title_name," +
        ".owm_list_item_without_image .owm_list_body_body," +
        ".owm_list_item_view_body .owm_box_body_cont," +
        ".owm_iisnews_view .owm_list_item_view_body," +
        ".owm_forum_page .owm_list_title_name," +
        ".owm_forum_page .owm_forum_description," +
        ".owm_forum_page .owm_topic_name," +
        ".owm_forum_page .owm_forum_topic .owm_post_content," +
        ".owm_forum_page .owm_forum_reply .owm_post_content";

    function selectElementText(element) {
        var doc = document
            , range, selection
        ;
        if (doc.body.createTextRange) {
            range = document.body.createTextRange();
            range.moveToElementText(element);
            range.select();
        } else if (window.getSelection) {
            selection = window.getSelection();
            range = document.createRange();
            range.selectNodeContents(element);
            selection.removeAllRanges();
            selection.addRange(range);
        }

        $(element).addClass('cb_selected');
    }
    function clearSelection(){
        $('#cb_menu_parent').remove();
        $('.cb_selected').removeClass('cb_selected');
        $('.cb_text_overlay').addClass('cb_text_overlay_off');
        selectElementText($('#owm_header_right_btn')[0]);

    }
    function makeElementUnselectable(element){
        element.onselectstart = function() {
            return false;
        };
        element.unselectable = "on";
        $(element).css('-moz-user-select', 'none');
        $(element).css('-webkit-user-select', 'none');
        $(element).attr('onselectstart','return false');
        $(element).bind("contextmenu", function(e) {
            e.preventDefault();
        });
    }
    function getTop(){
        return $(window).scrollTop();
    }

    var iOS = !!navigator.platform && /iPad|iPhone|iPod/.test(navigator.platform);
    if(iOS)
    {
        makeElementUnselectable($('body'));
    }
    
    setInterval(function() {
        $( $selector ).each(function() {
            if($(this).attr('cb')==='set'){
                return;
            }
            if($(this).text()==''){
                return;
            }

            //prevent selection
            makeElementUnselectable(this);

            //event handlers
            var timer;

            $(this).on( 'mousedown touchstart', function( e ) {
                start = 0;
                cb_text = this.innerText;
                if ( $('.ow_more_text span[data-text=full]',this).length > 0 ){
                    cb_text = $('.ow_more_text span[data-text=full]',this).text();
                }
                element = this;
                var y1 = getTop();

                timer = setTimeout(function(){

                    clearTimeout(timer);

                    //remove old menu
                    clearSelection();

                    //check if page scrolled
                    var y2 = getTop();
                    //console.log(y1+"-"+y2);
                    if( Math.abs(y2-y1)>29){
                        return;
                    }

                    //add new menu
                    //if (!navigator.userAgent.match(/ipad|ipod|iphone/i)) {}
                    selectElementText(element);
                    $('body').append('<div id="cb_menu_parent"><div class="cb_text_overlay"></div><div id="cb_menu" class="menu_closed"><div id="cb_close">‌ ‌ ‌X‌ ‌ ‌</div>' +
                        '<div id="cb_copy">'+OW.getLanguageText('base', 'copy')+'</div></div></div>');
                    setTimeout(function(){
                        $('#cb_menu').removeClass('menu_closed');
                    }, 50);
                    makeElementUnselectable($('#cb_menu'));
                    //cb_copy
                    var clipboard = new Clipboard('#cb_copy', {
                        text: function() {
                            return cb_text;
                        }
                    });
                    clipboard.on('success', function(e) {
                        OWM.info(OW.getLanguageText('base', 'text_copied_to_clipboard'));
                    });
                    //close menu
                    $("#cb_menu #cb_copy, .cb_text_overlay, #cb_close").on( 'click', function( e ) {
                        if ( $('#cb_menu_parent').length>0 ) {
                            $("#cb_menu").addClass('menu_closed');
                            $('.cb_selected').removeClass('cb_selected');
                            setTimeout(function(){
                                clearSelection();
                            }, 500);
                        }
                    });
                },longpress);
                start = new Date().getTime();
            });
            $('body').on("mouseup touchend",function(){
                if(typeof(timer)!='undefined')
                    clearTimeout(timer);

            });
            $(this).attr('cb', 'set');
        });
    }, 1000);
})


var OwRate = function( params ){
    this.cmpId = params.cmpId;
    this.userRate = params.userRate;
    this.entityId = params.entityId;
    this.entityType = params.entityType;
    this.itemsCount = params.itemsCount;
    this.respondUrl = params.respondUrl;
    this.ownerId = params.ownerId;
    this.$context = $('#rate_'+params.cmpId);
}

OwRate.prototype = {
    init: function(){
        var self = this;
        this.setRate(this.userRate);
        for( var i = 1; i <= this.itemsCount; i++ ){
            $('#' + this.cmpId + '_rate_item_' + i).bind( 'mouseover', {i:i},
                function(e){
                    self.setRate(e.data.i);
                }
            ).bind( 'mouseout',
                function(){
                    self.setRate(self.userRate);
                }
            ).bind( 'click', {i:i},
                function(e){
                    self.updateRate(e.data.i);
                }
            );
        }
    },

    setRate: function( rate ){
        for( var i = 1; i <= this.itemsCount; i++ ){
            var $el = $('#' + this.cmpId + '_rate_item_' + i);
            $el.removeClass('active');
            if( !rate ){
                continue;
            }
            if( i <= rate ){
                $el.addClass('active');
            }
        }
    },

    updateRate: function( rate ){
        var self = this;
        if( rate == this.userRate ){
            return;
        }
        this.userRateBackup = this.userRate;
        this.userRate = rate;
        $.ajax({
            type: 'POST',
            url: self.respondUrl,
            data: 'entityType='+encodeURIComponent(self.entityType)+'&entityId='+encodeURIComponent(self.entityId)+'&rate='+encodeURIComponent(rate)+'&ownerId='+encodeURIComponent(self.ownerId),
            dataType: 'json',
            success : function(data){

                if( data.errorMessage ){
                    OW.error(data.errorMessage);
                    self.userRate = self.userRateBackup;
                    self.setRate(self.userRateBackup);
                    return;
                }

                if( data.message ){
                    OW.info(data.message);
                }

                $('.total_score', self.$context).empty().append(data.totalScoreCmp);
                OW.trigger('base.rate_update', {entityType: self.entityType, entityId: self.entityId, rate: rate, ownerId: self.ownerId}, this);
            },
            error : function( XMLHttpRequest, textStatus, errorThrown ){
                $.alert('Ajax Error: '+textStatus+'!');
                throw textStatus;
            }
        });
    }
}

if(document.querySelector('textarea') && document.getElementsByClassName("owm_chat_window").length ||
    document.querySelector('textarea') && document.getElementsByClassName("owm_newsfeed_comments").length){
    var textarea = document.querySelector('textarea');
    $(textarea).on("keydown", function (e) {
        var el = this;
        setTimeout(function(){
            el.style.cssText = 'height:35px;';
            if(el.scrollHeight <=50 || e.key === 'Enter'){
                el.style.cssText = 'height:35px;';
            } else{
                el.style.cssText = 'height:' + el.scrollHeight + 'px !important';
            }
        },0);
    });}

function autosize(){
    var el = this;
    setTimeout(function(){
        el.style.cssText = 'height:35px;';
        if(el.scrollHeight <=50){
            el.style.cssText = 'height:35px;';
        }else{
            el.style.cssText = 'height:' + el.scrollHeight + 'px !important';
        }
    },0);
}

function flagOptionChanged($this){
    var jc = $.confirm(OW.getLanguageText('base', 'are_you_sure'));
    jc.buttons.ok.action = function () {
        $this.closest('form').submit();
        $('.flags-input').css('visibility', 'hidden');
        $('.flags-container').addClass('owm_preloader');
    }
    $('.flags-container').removeClass('owm_preloader');
}

/*
 Adds a parameter with the specified name and value to the URL of the current page.
 If such a parameter already exists, updates its value only.
 */
function addOrUpdateUrlParam(name, value) {
    var href = window.location.href;
    var anchorIndex = href.indexOf("#");
    var postfix;
    if (anchorIndex != -1) {
        postfix = href.substring(anchorIndex, href.length)
        href = href.substring(0, anchorIndex);
    } else {
        postfix = "";
    }
    var newHref;
    var regex = new RegExp("[&\\?]" + name + "=");
    if (regex.test(href)) {
        regex = new RegExp("([&|?]" + name + "=)[^&]*");
        newHref = href.replace(regex, "$1" + value);
    } else {
        if (href.indexOf("?") > -1) {
            newHref = href + "&" + name + "=" + value;
        } else {
            newHref = href + "?" + name + "=" + value;
        }
    }
    window.location.href = newHref + postfix;
}


OW_AttachmentItemColletction = {};

OW_Attachment = function(uniqId, data)
{
    var self = this;

    this.data = data;
    this.uniqId = uniqId;
    this.node = document.getElementById(this.uniqId);
    this.onChange = function(){};

    //OW.resizeImg(this.$('.EQ_AttachmentImageC'),{width:'150'});

    this.$('.OW_AttachmentSelectPicture').click(function()
    {
        $('.OW_AttachmentImage').show();
        self.showImageSelector();
    });

    this.$('.OW_AttachmentDeletePicture').click(function()
    {
        var img = $('img', this);
        self.changeImage('');
        $('.OW_AttachmentImage').hide();
    });

    this.$('.OW_AttachmentDelete').click(function()
    {
        $(self.node).remove();

        if ( $.isFunction(self.onChange) )
        {
            self.data = [];
            // Mohammad Reza Heidarian
            // This line is added to resolve problem with prefetch #4037
            // in ow_plugins/newsfeed/components/update_status.php:68 a new if has been added to handle the case when a prefetched
            // link is removed by user clicking on the remove button. the line below activates the case and keeps link remove from
            // activating error notification and also removes the link information
            self.data.type = "remove_link";
            self.onChange.call(self, self.data);
        }

        return false;
    });
};

OW_AttachmentProto = function()
{
    this.$ = function (sel)
    {
        return $(sel, this.node);
    };

    this.showImageSelector = function()
    {
        var fb, $contents, self = this;

        $contents = this.$('.OW_AttachmentPicturesFbContent')

        fb = new OW_FloatBox({
            $title: this.$('.OW_AttachmentPicturesFbTitle'),
            $contents: $contents,
            width: 520
        });

        $contents.find('.OW_AttachmentPictureItem').unbind().click(function()
        {
            var img = $('img', this);
            self.changeImage(img.attr('src'));

            fb.close();
        });
    };

    this.changeImage = function( url )
    {
        var clone, original;

        original = this.$('.OW_AttachmentImage');
        clone = original.clone();
        clone.attr('src', url);
        original.replaceWith(clone);

        if ( $.isFunction(this.onChange) )
        {
            this.data["thumbnail_url"] = url;

            this.onChange.call(this, this.data);
        }
    };

};
OW_Attachment.prototype = new OW_AttachmentProto();

OWMLinkObserver =
    {
        observers: {},

        observeInput: function( inputId, callBack )
        {
            this.observers[inputId] = new OWMLinkObserver.handler(inputId, callBack);
        },

        getObserver: function( inputId )
        {
            return this.observers[inputId] || null;
        }
    };

OWMLinkObserver.handler = function( inputId, callBack )
{
    this.callback = callBack;
    this.input = $('#' + inputId);
    this.inputId = inputId;
    this.detectedUrl = null;

    this.onResult = function(){};

    this.startObserve();
};

OWMLinkObserver.handler.prototype =
    {
        startObserve: function()
        {
            var self = this;

            var detect = function()
            {
                var val = self.input.val();

                if ( $.trim(val) )
                {
                    self.detectLink();
                }
            };

            this.input.bind('paste', function(){
                setTimeout(function() {
                    detect();
                }, 100);
            });

            this.input.bind('blur', detect);

            this.input.keyup(function(e)
            {
                if (e.keyCode == 32 || e.keyCode == 13) {
                    detect();
                }
            });
        },

        detectLink: function( text )
        {
            var text, rgxp, result;

            text = this.input.val();
            rgxp = /(http(s)?:\/\/|www\.)((\d+\.\d+\.\d+\.\d+)|(([\w-]+\.)+([a-z,A-Z][\w-]*)))(:[1-9][0-9]*)?(\/?([?\w\-.\,\/:%+@&*=~]+[\w\-\,.\/?\':%+@&=*|]*)?)?/;
            result = text.match(rgxp);

            if ( !result )
            {
                return false;
            }

            if ( this.detectedUrl == result[0] )
            {
                return false;
            }

            this.detectedUrl = result[0];

            this.callback.call(this, this.detectedUrl);
        },

        requestResult: function( callback, link )
        {
            var self = this;

            link = link || this.detectedUrl;

            $.ajax({
                type: 'POST',
                url: OWM.ajaxAttachmentLinkRsp,
                data: {"url": link},
                dataType: 'json',
                success: function( r )
                {
                    if ( r.content )
                    {
                        if ( r.content.css )
                        {
                            OW.addCss(r.content.css);
                        }

                        if ( $.isFunction(callback) )
                        {
                            callback.call(self, r.content.html, r.result);
                        }

                        if ( $.isFunction(self.onResult) )
                        {
                            self.onResult(r.result);
                        }

                        if ( r.content.js )
                        {
                            OW.addScript(r.content.js);
                        }
                    }

                    if ( r.attachment && OW_AttachmentItemColletction[r.attachment] )
                    {
                        OW_AttachmentItemColletction[r.attachment].onChange = function(oembed){
                            self.onResult(oembed);
                        };
                    }
                }
            });
        },

        resetObserver: function()
        {
            this.detectedUrl = null;
        }
    };


$(document).on("ready", function () {
    $("span.owm_context_arr_c").on("click", function (e) {
        var target =  $(e.target);
        var dropdown = $(target).parent().siblings(".owm_context_action_wrap.owm_context_pos_right.ca-dropdown")
        if ($(dropdown).css('display') === "none")
            $(target).addClass("iismenu_active_opened_dropdown");
        else
            $(target).removeClass("iismenu_active_opened_dropdown");
    });

    $($(".mobile_back_button_title").prevAll('h4')[0]).hide();
});
