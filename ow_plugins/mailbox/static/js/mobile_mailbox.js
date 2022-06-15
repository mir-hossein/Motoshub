OWM.bind('mailbox.after_ping', function(data){
    if(data['changes']){
        for(var i = 0;i<data['changes'].length;i++){
            var messageContainer = $('#messageItem'+data['changes'][i]['id']);
            var span = $('p.message span',messageContainer);
            if(messageContainer != undefined) {
                $('p.message', messageContainer).html(data['changes'][i]['text']);
                $('p.message', messageContainer).append(span);
                $(messageContainer).find("p.message .owm_edited_message_label").remove();
                $(messageContainer).find("p.message").append(create_edited_tag());
            }
        }
    }
    if(data['deleted']){
        for(i = 0;i<data['deleted'].length;i++){
            var messageContainer = $('#messageItem'+data['deleted'][i]['id']);
            if(messageContainer != undefined)
                messageContainer.remove();
        }
    }
});

function htmlspecialchars(string, quote_style, charset, double_encode) {
    // Convert special characters to HTML entities
    //
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/htmlspecialchars    // +   original by: Mirek Slugen
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Nathan
    // +   bugfixed by: Arno
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // +      input by: Mailfaker (http://www.weedem.fr/)
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +      input by: felix    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // %        note 1: charset argument not supported
    // *     example 1: htmlspecialchars("<a href='test'>Test</a>", 'ENT_QUOTES');
    // *     returns 1: '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;'
    // *     example 2: htmlspecialchars("ab\"c'd", ['ENT_NOQUOTES', 'ENT_QUOTES']);    // *     returns 2: 'ab"c&#039;d'
    // *     example 3: htmlspecialchars("my "&entity;" is still here", null, null, false);
    // *     returns 3: 'my &quot;&entity;&quot; is still here'
    var optTemp = 0,
        i = 0,        noquotes = false;
    if (typeof quote_style === 'undefined' || quote_style === null) {
        quote_style = 2;
    }
    string = string.toString();
    if (double_encode !== false) { // Put this first to avoid double-encoding
        string = string.replace(/&/g, '&amp;');
    }
    string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    var OPTS = {
        'ENT_NOQUOTES': 0,
        'ENT_HTML_QUOTE_SINGLE': 1,
        'ENT_HTML_QUOTE_DOUBLE': 2,
        'ENT_COMPAT': 2,
        'ENT_QUOTES': 3,
        'ENT_IGNORE': 4
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
        quote_style = [].concat(quote_style);
        for (i = 0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'ENT_IGNORE' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            }
            else
            if (OPTS[quote_style[i]])
            {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }

    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE)
    {
        string = string.replace(/'/g, '&#039;');
    }
    if (!noquotes)
    {
        string = string.replace(/"/g, '&quot;');
    }
    string = string.replace(/\n/g, '<br />');
    return string;
}

MAILBOX_Message = Backbone.Model.extend({

    idAttribute: 'id',

    readMessage: function(actionParams, callback){

        var that = this;
        $.ajax({
            'type': 'POST',
            'url': OWM.Mailbox.get('authorizationResponderUrl'),
            'data': {
                'actionParams': actionParams
            },
            'success': function(data){
                if (typeof data.error != 'undefined')
                {
                    OWM.error(data.error);
                }
                else
                {
                    if (typeof data.authorizationActionText != 'undefined')
                    {
                        OWM.info(data.authorizationActionText);
                    }

                    that.set(data);

                    if (typeof callback != 'undefined') {
                        callback.call(data);
                    }
                }
            },
            'dataType': 'json'
        });
    }

});

var create_edited_tag = function () {
    return '<span class="owm_edited_message_label">' +  OW.getLanguageText('mailbox', 'edited_message_tag') + '</span>';
};
MAILBOX_MessageView = Backbone.View.extend({
    initialize: function(){
        var template = _.template($('#dialogChatMessagePrototypeBlock').html());
        this.setElement(template(this.model.attributes));
        this.text = $('.owm_chat_bubble', this.$el);
    },

    render: function(){
        var self = this;
        this.$el.attr('id', 'messageItem'+this.model.get('id'));

        var readMessageAuthorized = this.model.get('readMessageAuthorized');
        var byCreditsMessage = this.model.get('byCreditsMessage');
        var promotedMessage = this.model.get('promotedMessage');
        var authErrorMessages = this.model.get('authErrorMessages');

        if(this.model.get('replyId')){
            $('#replyMessage', this.$el).show();
            var replySender = $('#replyMessage a.sender', this.$el);
            replySender.html(this.model.get('reply_sender')+':');
            var text = this.model.get('replyMessage');
            if (text.length > 40) {
                text = text.substring(0, 40);
                text = text + '...';
            }
            var replyMessage = $('#replyMessage p.msg', this.$el);
            if(this.model.get('replyAttachments') && this.model.get('replyAttachments').length != 0) {
                var replyAttachmentLink = '';
                if (this.model.get('replyAttachments')[0]['type'] == 'image') {
                    replyAttachmentLink = '<a href="'+this.model.get('replyAttachments')[0]['downloadUrl']+'" target="_blank"><img style="max-height: 50px;width: auto" src="'+this.model.get('replyAttachments')[0]['downloadUrl']+'" /></a>';
                }else {
                    replyAttachmentLink = '<a href="'+this.model.get('replyAttachments')[0]['downloadUrl']+'" target="_blank">'+OWM.Mailbox.formatAttachmentFileName(this.model.get('replyAttachments')[0]['fileName'])+'</a>';
                }
                $('#replyMessage', self.$el).append(replyAttachmentLink);
                $('#replyMessage', self.$el).prepend('<p class="sender">'+this.model.get('reply_sender')+':'+'</p>');
                $('#replyMessage a.sender', self.$el).remove();
                $('#replyMessage a.msg', self.$el).remove();
                $('#replyMessage p.msg', self.$el).remove();
            }
            else{
                replyMessage.html(text);
                $('#replyMessage', self.$el).click(function (data) {
                    var text = self.model.get('replyMessage');
                    if ($(this).hasClass('wide')) {
                        if (text && text.length > 40) {
                            text = text.substring(0, 40);
                            text = text + '...';
                        }
                        $(this).removeClass('wide')
                    } else {
                        $(this).addClass('wide')
                    }
                    $('#replyMessage a.sender', self.$el).html(self.model.get('reply_sender') + ':');
                    $('#replyMessage p.msg', self.$el).html(text);
                });
            }
        } else {
            $('#replyMessage', self.$el).remove();
        }

        var avatar = $('div.owm_avatar a', self.$el);
        avatar.attr('href',self.model.get('senderUrl'));
        $('img',avatar).attr('src',self.model.get('senderAvatar'));
        if (self.model.get('senderId') != self.model.get('opponentId')) {
            $('div.owm_avatar', self.$el).addClass('ow_left');
        }else {
            $('div.owm_avatar', self.$el).addClass('ow_right');
        }

        if ( readMessageAuthorized == false )
        {
            if( byCreditsMessage )
            {
                $('.owm_chat_bubble', this.$el).addClass('owm_lbutton owm_bg_color_3');
            }
            else if ( promotedMessage )
            {
                $('.owm_chat_bubble', this.$el).addClass('owm_remark');
            }
            else if ( authErrorMessages )
            {
                $('.owm_chat_bubble', this.$el).addClass('owm_remark');
            }
        }

        if (!this.model.get('isSystem')){
            var el_time = document.createElement('span');
            el_time.className = 'owm_chat_time';
            el_time.innerHTML = (this.model.get('timeLabel')+" ");

            var attachments = this.model.get('attachments');
            if (attachments.length != 0){
                var i = 0;
                caption = '';
                if (this.model.get('text') != OW.getLanguageText('mailbox', 'attachment')){
                    caption = '<div class="owm_padding ow_attachment_caption">' + this.model.get('text') + '</div>';
                }
                if (attachments[i]['type'] == 'image'){
                    this.$el.addClass('ow_dialog_picture_item');
                    replyAttachmentLink = '<a href="'+attachments[i]['downloadUrl']+'" target="_blank"><img src="'+attachments[i]['downloadUrl']+'" /></a>' + caption + el_time.outerHTML;
                }
                else{
                    this.$el.addClass('fileattach');
                    replyAttachmentLink ='<a href="'+attachments[i]['downloadUrl']+'" target="_blank">'+OWM.Mailbox.formatAttachmentFileName(attachments[i]['fileName'])+'</a>' + caption + el_time.outerHTML;
                }
                $('.owm_chat_bubble p.message', this.$el).html( replyAttachmentLink );
            }
            else{
//                html = htmlspecialchars(this.model.get('text'), 'ENT_QUOTES');

                html = this.model.get('text') + el_time.outerHTML;
                $('.owm_chat_bubble p.message', this.$el).html( html );
                $('.owm_chat_bubble p.message', this.$el).autolink();
                if (this.model.get('changed') == 1 || this.model.get('changed') == 2)
                    $('.owm_chat_bubble p.message', this.$el).append(create_edited_tag())
            }
        }
        else
        {
            /*
             commented to set all messages same style (even System Messages)
             by Mohammad Agha Abbasloo
             TODO delete and edit of this type of messages must be handled
             */
            var el_time = document.createElement('span');
            el_time.className = 'owm_chat_time';
            el_time.innerHTML = (this.model.get('timeLabel')+" ");
            //html =  el_time.outerHTML;
            html = this.model.get('text') + el_time.outerHTML;
            $('.owm_chat_bubble .ow_dialog_item', this.$el).html( html );
            $('.owm_chat_bubble .ow_dialog_item', this.$el).autolink();
            //this.text.removeClass('owm_chat_bubble');
        }

        if (this.model.get('isAuthor')){
            this.$el.addClass('owm_chat_bubble_mine_wrap');
            this.text.addClass('owm_chat_bubble_mine');
            if (this.model.get('recipientRead') == 1){
                this.$el.addClass('message_seen');
            }
        }

        if (!this.model.get('readMessageAuthorized')){
            var that = this;
            this.$el.on('click', '.callReadMessage', function(e){
                that.model.readMessage($(this).attr('id'), function(){
                    location.reload();
                });
            });
        }

        //-----related to the new chat menu+long press
        window.press_interval = 850;
        window.press_start=0;
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
        //one time actions
        if($('body').attr('cb')!=='set'){
            $('body').attr('cb','set');
            var iOS = !!navigator.platform && /iPad|iPhone|iPod/.test(navigator.platform);
            if(iOS)
                makeElementUnselectable($('body'));
            $('body').on("mouseup touchend", function(ev){
                if(typeof(window.press_timer)!='undefined') {
                    clearTimeout(window.press_timer);
                    var x1 = window.press_start + window.press_interval;
                    var x2 = new Date().getTime();
                    if ( window.press_start>0 && ( x2 >=  x1 )  ) {
                        //revert href after 50ms
                        setTimeout(function() {
                            $('a[href2]').each(function (el) {
                                var last_href = $(this).attr('href2');
                                $(this).attr('href2', null);
                                $(this).attr('href', last_href);
                            });
                        },50);
                    }else{
                        $('a[href2]').each(function (el) {
                            var last_href = $(this).attr('href2');
                            $(this).attr('href2', null);
                            $(this).attr('href', last_href);
                        });
                    }
                }
                window.press_start = 0;
            });
        }
        //check for long press
        makeElementUnselectable($('.owm_chat_bubble', this.$el));

        var query = 'div.owm_chat_bubble';
        $(query, this.$el).on( 'mousedown touchstart', function( e ) {
            if(window.press_start>0) {
                return;
            }
            window.press_start = new Date().getTime();
            var last_href = $('a', this).attr('href');
            $('a', this).each(function(el) {
                $(this).attr('href', null).attr('href2', last_href);
            });
            var y1 = getTop();
            window.press_timer = setTimeout(function(){
                clearTimeout(window.press_timer);
                //check if page scrolled
                var y2 = getTop();
                //console.log(y1+"-"+y2);
                if( Math.abs(y2-y1)>29){
                    return;
                }
                //add new menu
                displayMenu();

            }, window.press_interval);
        });

        function displayMenu(){
            $('#dialogReplyTo').remove();
            window.replyToMessage = null;
            if (window.selectedMessage) {
                $('#toolbox').remove();
                $('#mailboxBackToConversations div.owm_chat_name_block').show();
                $('#messageItem' + window.selectedMessage.get('id')).removeClass('ow_mailbox_selected');
                if (window.selectedMessage.get('id') == self.model.get('id')) {
                    window.selectedMessage = null;
                    return;
                }
            }
            var toolboxContainer = $('#toolBoxPrototypeBlock').clone();
            toolboxContainer.attr('id', 'toolbox');
            window.selectedMessage = self.model;
            $('#close', toolboxContainer).click(function () {
                $('#toolbox').remove();
                $('#mailboxBackToConversations div.owm_chat_name_block').show();
                window.selectedMessage = null;
                $('#messageItem' + self.model.get('id')).removeClass('ow_mailbox_selected');
            });
            if (self.model.get('senderId') != self.model.get('opponentId')) {
                if (self.model.get('attachments').length <= 0) {
                    $('.ow_mailbox_edit', toolboxContainer).show();
                    $('.ow_mailbox_edit', toolboxContainer).click(function () {
                        window.editFloatBox = OW.ajaxFloatBox('MAILBOX_CMP_EditMessage', [window.selectedMessage.get('id')], {width: 700});
                        window.messageEdited = function (data) {
                            window.editFloatBox.close();
                            if (data.error) {
                                OW.error(data.error);
                            } else {
                                var messageItem = $('#messageItem' + data.id);
                                var span = $('p.message span', messageItem);
                                $('p.message', messageItem).html(data.text);
                                if(span)
                                    $('p.message', messageItem).append(span);
                                OW.info(data.msg);

                            }
                        };
                        $('#toolbox').remove();
                        $('#mailboxBackToConversations div.owm_chat_name_block').show();
                        window.selectedMessage = null;
                        $('#messageItem' + self.model.get('id')).removeClass('ow_mailbox_selected');
                    });

                }
                $('#delete', toolboxContainer).show();
                $('#delete', toolboxContainer).click(function () {
                    if (confirm(OW.getLanguageText('mailbox', 'delete_confirm'))) {
                        $.ajax({
                            type: "POST",
                            url: window.mailbox_remove_url,
                            data: {id: window.selectedMessage.get('id')},
                            success: function (result) {
                                if (result.error) {
                                    OW.error(result.error);
                                } else {
                                    var pre = $('#messageItem' + result.id).prev();
                                    var next = $('#messageItem' + result.id).next();
                                    if (pre.attr('id').startsWith('timeBlock') && (!next.attr('id') || next.attr('id').startsWith('timeBlock')))
                                        $('#messageItem' + result.id).prev().remove();
                                    $('#messageItem' + result.id).remove();
                                    OW.info(result.msg);
                                }
                            },
                            dataType: "json"
                        });
                        $('#toolbox').remove();
                        $('#mailboxBackToConversations div.owm_chat_name_block').show();
                        window.selectedMessage = null;
                        $('#messageItem' + self.model.get('id')).removeClass('ow_mailbox_selected');
                    }
                });
            }
            $('a.ow_mailbox_reply', toolboxContainer).click(function () {
                var replyContainer = $('#dialogReplyToPrototypeBlock').clone();
                replyContainer.attr('id', 'dialogReplyTo');
                var text = window.selectedMessage.get('text');
                if (text && text.length > 40) {
                    text = text.substring(0, 40);
                    text = text + '...';
                }
                $('#dialogMessageText', replyContainer).html(text);
                window.replyToMessage = window.selectedMessage.get('id');
                $('#close', replyContainer).click(function () {
                    $('#dialogReplyTo').remove();
                    window.replyToMessage = null;
                });
                $('#mailboxConversationFooter').prepend(replyContainer);
                $('#toolbox').remove();
                $('#mailboxBackToConversations div.owm_chat_name_block').show();
                window.selectedMessage = null;
                $('#messageItem' + self.model.get('id')).removeClass('ow_mailbox_selected');
            });
            $('.ow_mailbox_copy', toolboxContainer).hide();
            if (self.model.get('attachments').length <= 0) {
                $('.ow_mailbox_copy', toolboxContainer).show();
                $('.ow_mailbox_copy', toolboxContainer).click(function () {
                    var msg_text = self.model.get('text');
                    var clipboard = new Clipboard('.ow_mailbox_copy', {
                        text: function() {
                            return msg_text;
                        }
                    });
                    clipboard.on('success', function(e) {
                        OWM.info(OW.getLanguageText('base', 'text_copied_to_clipboard'));
                    });
                });
            }
            $('#messageItem' + self.model.get('id')).addClass('ow_mailbox_selected');
            $('#mailboxBackToConversations div.owm_chat_name_block').after(toolboxContainer);
            $('#mailboxBackToConversations div.owm_chat_name_block').hide();
            $('#mailboxBackToConversations').prop('onclick',null).off('click');
        }

        return this;
    }

});

MAILBOX_MailMessageView = Backbone.View.extend({
    initialize: function(){
        if (this.model.get('isAuthor')){
            this.model.set('profileUrl', OWM.Mailbox.get('user').profileUrl);
            this.model.set('avatarUrl', OWM.Mailbox.get('user').avatarUrl);
            this.model.set('displayName', OWM.Mailbox.get('user').displayName);
        }
        else{
            var user = OWM.Mailbox.userListCollection.findWhere({opponentId: this.model.get('senderId')});
            this.model.set('profileUrl', user.get('profileUrl'));
            this.model.set('avatarUrl', user.get('avatarUrl'));
            this.model.set('displayName', user.get('displayName'));
        }
        var template = _.template($('#dialogMailMessagePrototypeBlock').html());
        this.setElement(template(this.model.attributes));
    },

    render: function(){

        this.$el.attr('id', 'messageItem'+this.model.get('id'));

        if (!this.model.get('isSystem')){

//            html = htmlspecialchars(this.model.get('text'), 'ENT_QUOTES');
            html = this.model.get('text');
            $('.owm_mail_txt', this.$el).html( html );
            $('.owm_mail_txt', this.$el).autolink();

            var attachments = this.model.get('attachments');
            if (attachments.length != 0){

                for (var i=0; i<attachments.length; i++){

                    var attachment = $('#mailboxMailMessageAttachmentPrototypeBlock').clone();
                    attachment.removeAttr('id');

                    $('a', attachment).prepend( OWM.Mailbox.formatAttachmentFileName(attachments[i]['fileName']) );
                    $('a', attachment).attr('href', attachments[i]['downloadUrl']);
                    $('.owm_mail_attach_size', attachment).html( OWM.Mailbox.formatAttachmentFileSize(attachments[i]['fileSize']) );


                    $('.owm_mail_msg_cont', this.$el).append( attachment );
                }
            }
        }

        var readMessageAuthorized = this.model.get('readMessageAuthorized');
        var byCreditsMessage = this.model.get('byCreditsMessage');
        var promotedMessage = this.model.get('promotedMessage');
        var authErrorMessages = this.model.get('authErrorMessages');

        if ( readMessageAuthorized == false )
        {
            if( byCreditsMessage )
            {
                $('.owm_mail_txt', this.$el).addClass('owm_lbutton owm_bg_color_3');
            }
            else if ( promotedMessage )
            {
                $('.owm_mail_txt', this.$el).addClass('owm_remark');
            }
            else if ( authErrorMessages )
            {
                $('.owm_mail_txt', this.$el).addClass('owm_remark');
            }
        }

        if (this.model.get('isAuthor')){
            this.$el.addClass('owm_mail_msg_mine_wrap');
        }

        if (!this.model.get('readMessageAuthorized')){
            var that = this;
            this.$el.on('click', '.callReadMessage', function(e){
                that.model.readMessage($(this).attr('id'), function(){
                    location.reload();
                });
            });
        }

        return this;
    }
});

MAILBOX_MessageList = Backbone.Collection.extend({
    model: MAILBOX_Message,
    comparator: function(model){
        return model.get('timeStamp');
    }
});

MAILBOX_UnreadMessageList = Backbone.Collection.extend({
    model: MAILBOX_Message
});

MAILBOX_SidebarMenuItem = Backbone.Model.extend({
    title: 'Default',
    selected: false,
    mode: 'default'
});

MAILBOX_SidebarMenuItemView = Backbone.View.extend({

    events: {
        'click .owm_sidebar_sub_menu_item_url': 'highlight'
    },

    initialize: function(){
        this.setElement($('#menuItem_'+this.model.get('mode')));
        this.model.on('change:selected', this.changeSelected, this);
    },

    render: function(){
        this.changeSelected();

        return this;
    },

    changeSelected: function(model, value, options){

        if (this.model.get('selected')){
            this.$el.addClass('owm_sidebar_sub_menu_item_active');
        }
        else{
            this.$el.removeClass('owm_sidebar_sub_menu_item_active');
        }
    },

    highlight: function(){
        this.model.set({selected: true});
    }

});

MAILBOX_SidebarMenuItemList = Backbone.Collection.extend({
    model: MAILBOX_SidebarMenuItem,

    initialize: function() {
        this.on('change:selected', this.onSelectedChanged, this);
    },

    onSelectedChanged: function(changedModel) {

        if (changedModel.get('selected') === true){
            this.each(function(model) {
                if (changedModel.get('mode') != model.get('mode') && model.get('selected') === true){
                    model.set('selected', false);
                }
            });
        }
    }
});

MAILBOX_SidebarMenu = Backbone.Model.extend({
    initialize: function(){
        this.itemList = new MAILBOX_SidebarMenuItemList(this.get('list'));
    }
});

MAILBOX_SidebarMenuView = Backbone.View.extend({
    el: function(){
        return $('#mailboxSidebarMenu');
    },

    initialize: function(){
        this.render();
    },

    render: function(){
        _.each(this.model.itemList.models, function (item){
            var view = new MAILBOX_SidebarMenuItemView({model: item});

            this.$el.append(view.render().$el);
        }, this);
    }
});

MAILBOX_ConversationItem = Backbone.Model.extend({
    selected: false,

    initialize: function(){

        var conversationUnread = OWM.Mailbox.unreadMessageList.findWhere({convId: this.get('conversationId')});
        if ( conversationUnread ){
            this.set({selected: true});
        }

        var that = this;
        OW.bind('mailbox.new_message_notification', function(data){
            if (data.message.convId == that.get('conversationId')){
                that.set({selected: true});
            }
        });
    }
});

MAILBOX_ConversationItemView = Backbone.View.extend({
    events: {
        'click .owm_user_list_item': 'openConversation'
    },

    initialize: function(){
        var template = _.template($('#mailboxSidebarItemPrototype').html());
        this.setElement(template(this.model.attributes));
        this.model.on('change:selected', this.changeSelected, this);
        this.model.on('change:lastMessageTimestamp', this.changeLastMessageTimestamp, this);
    },

    render: function(){

        if (this.model.get('mode') == 'mail'){
            $('#mailboxSidebarItemConversationsMode', this.$el).addClass('owm_sidebar_convers_status_mail');
        }

        if (this.model.get('mode') == 'chat'){
            $('#mailboxSidebarItemConversationsMode', this.$el).addClass('owm_sidebar_convers_status_chat');
        }

        if (this.model.get('onlineStatus') !== false){
            $('#mailboxSidebarConversationsItemOnlineStatus', this.$el).show();
        }

        this.changeSelected();

        return this;
    },

    changeLastMessageTimestamp: function(){
        $('#mailboxSidebarItemListConversations .owm_convers_list_cont').prepend(this.$el);
    },

    changeSelected: function(){
        if (this.model.get('selected')){
            this.$el.addClass('owm_convers_item_active');
        }
        else{
            this.$el.removeClass('owm_convers_item_active');
        }
    },

    openConversation: function(){
        window.location.href = this.model.get('url');
    }

});

MAILBOX_ConversationItemList = Backbone.Collection.extend({
    model: MAILBOX_ConversationItem,

    comparator: function(model){
        return -model.get('lastMessageTimestamp');
    }
});

MAILBOX_Conversations = Backbone.Model.extend({

    defaults: {
        active: false,
        loadMore: true
    },

    loadedConvCount: 0,

    initialize: function(){
        OWM.mailboxSidebarMenu.itemList.on('change:selected', this.changeActive, this);
        this.itemList = new MAILBOX_ConversationItemList();
        this.loadList();

        var selectedItem = OWM.mailboxSidebarMenu.itemList.findWhere({selected: true});

        if (selectedItem && selectedItem.get('mode') == 'conversations'){
            this.set({active: true});
        }
    },

    changeActive: function(model){
        if (model.get('mode') == 'conversations'){
            this.set({active: model.get('selected')});
        }
    },

    loadList: function(){

        var numberOfConvToLoad = 20;
        var n =this.loadedConvCount + numberOfConvToLoad;
        if (n > OWM.Mailbox.convList.length){
            n = OWM.Mailbox.convList.length;
        }

        for (var i=this.loadedConvCount; i < n; i++){
            if (typeof OWM.Mailbox.convList[i] != 'undefined'){
                this.itemList.add(OWM.Mailbox.convList[i]);
            }
        }
        this.loadedConvCount = i;

        if (this.loadedConvCount == OWM.Mailbox.convList.length){
            this.set('loadMore', false);
        }
        else{
            this.set('loadMore', true);
        }
    }

});

MAILBOX_ConversationsView = Backbone.View.extend({
    el: '#mailboxSidebarItemListConversations',

    initialize: function(){
        var that = this;

        this.model.on('change:active', this.changeActive, this);
        this.model.on('change:loadMore', this.changeLoadMore, this);
        this.model.itemList.on('add', this.renderItem, this);

        $('#mailboxConversationsLoadMore').click(function(){
            that.model.loadList();
        });

        this.render();
    },

    render: function(){
        _.each(this.model.itemList.models, function(item){
            this.renderItem(item);
        }, this);

        this.changeActive();
        this.changeLoadMore();
    },

    renderItem: function(item){
        if (item.get('mode') == 'mail'){
            item.set('previewText', item.get('subject'));
        }
        item.set('unreadMessageCount', item.get('newMessageCount'));

        var view = new MAILBOX_ConversationItemView({model: item});

        var itemIndex = this.model.itemList.indexOf(item);

        var html = view.render().$el;
        if (item.get('newMessageCount') == 0) {
            $('.mailboxSidebarConversationsItemNewCount',html).hide();
        }

        if (itemIndex == 0){
            $('.owm_convers_list_cont', this.$el).prepend(html);
        }
        else{
            $('.owm_convers_list_cont', this.$el).append(html);
        }

    },

    hideLoadMoreBtn: function(){
        $('#mailboxConversationsLoadMoreBlock').hide();
    },

    showLoadMoreBtn: function(){
        $('#mailboxConversationsLoadMoreBlock').show();
    },

    changeLoadMore: function(){
        if (this.model.get('loadMore')){
            this.showLoadMoreBtn();
        }
        else{
            this.hideLoadMoreBtn();
        }
    },

    changeActive: function(){
        if (this.model.get('active')){
            this.$el.addClass('active');
        }
        else{
            this.$el.removeClass('active');
        }
    }
});

MAILBOX_UserItem = Backbone.Model.extend({
    selected: false,

    remove: function(){
        this.trigger("remove");
    }
});

MAILBOX_UserItemView = Backbone.View.extend({
    events: {
        'click .owm_user_list_item': 'openConversation'
    },

    initialize: function(){
        var template = _.template($('#mailboxSidebarUserItemPrototype').html());
        this.setElement(template(this.model.attributes));
        this.model.on('remove', this.remove, this);
    },

    render: function(){

        if (this.model.get('status') != 'offline'){
            $('#mailboxSidebarConversationsItemOnlineStatus', this.$el).show();
        }

        return this;
    },

    openConversation: function(){
        window.location.href = this.model.get('url');
    },

    remove: function(){
        this.$el.remove();
    }
});

MAILBOX_UserItemList = Backbone.Collection.extend({
    model: MAILBOX_UserItem,

    removeItem: function( cid ){
        var model = this.get(cid);
        model.remove();

        this.remove(cid);
    }
});

MAILBOX_Users = Backbone.Model.extend({

    defaults: {
        active: false,
        loadMore: true
    },

    loadedUserCount: 0,

    initialize: function(){
        OWM.mailboxSidebarMenu.itemList.on('change:selected', this.changeActive, this);
        this.itemList = new MAILBOX_UserItemList();
        this.loadList();
    },

    changeActive: function(model){

        if (model.get('mode') == 'userlist'){
            this.set({active: model.get('selected')});
        }
    },

    loadList: function(){
        var numberOfUsersToLoad = 20;
        var n =this.loadedUserCount + numberOfUsersToLoad;
        if (n > OWM.Mailbox.userList.length){
            n = OWM.Mailbox.userList.length;
        }

        for (var i=this.loadedUserCount; i < n; i++){
            if (typeof OWM.Mailbox.userList[i] != 'undefined'){
                this.itemList.add(OWM.Mailbox.userList[i]);
            }
        }
        this.loadedUserCount = i;

        if (this.loadedUserCount == OWM.Mailbox.userList.length){
            this.set('loadMore', false);
        }
        else{
            this.set('loadMore', true);
        }

    },
});

MAILBOX_UsersView = Backbone.View.extend({
    el: '#mailboxSidebarItemListUserlist',

    initialize: function(){
        var that = this;

        this.model.on('change:active', this.changeActive, this);
        this.model.on('change:loadMore', this.changeLoadMore, this);
        this.model.itemList.on('add', this.renderItem, this);

        $('#mailboxUsersLoadMore').click(function(){
            that.model.loadList();
        });

        this.render();
    },

    render: function(){
        _.each(this.model.itemList.models, function(item){
            this.renderItem(item);
        }, this);

        this.changeLoadMore();
        return this;
    },

    renderItem: function(item){
        var view = new MAILBOX_UserItemView({model: item});
        $('.owm_convers_list_cont', this.$el).append(view.render().$el);
    },

    hideLoadMoreBtn: function(){
        $('#mailboxUsersLoadMoreBlock').hide();
    },

    showLoadMoreBtn: function(){
        $('#mailboxUsersLoadMoreBlock').show();
    },

    changeLoadMore: function(){
        if (this.model.get('loadMore')){
            this.showLoadMoreBtn();
        }
        else{
            this.hideLoadMoreBtn();
        }
    },

    changeActive: function(){
        if (this.model.get('active')){
            this.$el.addClass('active');
        }
        else{
            this.$el.removeClass('active');
        }
    }
});

MAILBOX_Search = Backbone.View.extend({

    initialize: function(){
        this.searchBtn = $('#mailboxSidebarSearchBtn');
        this.closeBtn = $('#mailboxSidebarCloseSearchBtn');

        var self = this;

        var formElement = new OwFormElement('mailboxSidebarSearchTextField', 'mailbox_search_users_btn');
        var parentResetValue = formElement.resetValue;

        formElement.resetValue = function(){
            parentResetValue.call(this);

            self.reset();
        };

        this.items = new MAILBOX_UserItemList();

        this.addItem = function( data ) {

            var item = new MAILBOX_UserItem(data);
            var view = new MAILBOX_UserItemView({model: item});

            $('#mailboxSidebarSearchItemList').append( view.render().$el );

            this.items.add(item);
        };

        this.reset = function(){

            var tmpList = this.items.slice(0);
            for (var i=0; i<tmpList.length; i++){
                var model = this.items.models[0];
                this.items.removeItem(model.cid);
            }

            $('.owm_user_not_found').hide();
        }

        this.updateList = function(name){
            var self = this;

            if (name == '')
            {
                $('.owm_mchat_block').removeClass('owm_sidebar_search_active');
                formElement.resetValue();
                $('#mailboxSidebarItemListConversations').show();
                $('#mailboxSidebarSearchItemList').hide();

                self.reset();
                for (var i=0; i<OWM.Mailbox.userList.length; i++){

                    var list = self.items.where({opponentId: OWM.Mailbox.userList[i].opponentId});
                    if (list.length == 0){
                        self.addItem(OWM.Mailbox.userList[i]);
                    }
                }
            }
            else
            {
                $('.owm_mchat_block').addClass('owm_sidebar_search_active');
                $('#mailboxSidebarItemListConversations').hide();
                $('#mailboxSidebarSearchItemList').show();

                self.reset();
                var expr = new RegExp(OWM.escapeRegExp(name) , 'i');
                for (var i=0; i<OWM.Mailbox.userList.length; i++){

                    var displayName = OWM.Mailbox.userList[i].displayName;
                    var userName = OWM.Mailbox.userList[i].userName;
                    if (expr.test(displayName) || expr.test(userName)){
                        var list = self.items.where({opponentId: OWM.Mailbox.userList[i].opponentId});
                        if (list.length == 0){
                            self.addItem(OWM.Mailbox.userList[i]);
                        }
                    }
                }

                if (self.items.length == 0)
                {
                    $('.owm_user_not_found').show();
                }
                else
                {
                    $('.owm_user_not_found').hide();
                }
            }
        }

        $(formElement.input).keyup(function(ev){

            if (ev.which === 13 && !ev.ctrlKey && !ev.shiftKey) {
                ev.preventDefault();

                return false;
            }

            self.updateList($(this).val());
        });

        this.searchBtn.bind('click', function(){
            $('.owm_mchat_block').addClass('owm_sidebar_search_active');
            self.updateList('');
        });

        this.closeBtn.bind('click', function(){
            $('.owm_mchat_block').removeClass('owm_sidebar_search_active');
            formElement.resetValue();
            $('.owm_user_not_found').hide();
        });
    }
});

MAILBOX_NewMessageForm = Backbone.Model.extend({

    initialize: function(){
        this.uid = OWM.Mailbox.uniqueId('mailbox_conversation_'+this.get('conversationId')+'_'+this.get('opponentId')+'_');
        this.embedAttachmentsValue = '';
        this.embedLinkResult = true;
        this.embedLinkDetected = false;
        var self = this;
        OWMLinkObserver.observeInput('newMessageForm'+' #newMessageText', function(link){
            self.embedLinkResult = false;
            self.embedLinkDetected = true;
            this.requestResult();

            this.onResult = function( r ){
                self.embedLinkResult = true;
                if ( r.type == 'link')
                {
                    self.embedAttachmentsValue = JSON.stringify(r);

                }

                OWM.trigger('mailbox.embed_link_request_result_'+self.get('conversationId'), r);
            }
        });
    },

    sendMessage: function(text){

        var that = this;
        if(text === "")
            return;
        var data = {
            'convId': this.get('conversationId'),
            'text': text,
            'uid': this.uid,
            'embedAttachments': this.embedAttachmentsValue
        };

        var ajaxData = {};

        if (!this.embedLinkDetected || (this.embedLinkDetected && this.embedLinkResult)){
            ajaxData['actionData'] = {
                'uniqueId': OWM.Mailbox.uniqueId('postMessage'),
                'name': 'postMessage',
                'data': data
            };

            ajaxData['actionCallbacks'] = {
                success: function(data){

                    if (data.message){
                        that.trigger('message_sent', data);
                        that.uid = OWM.Mailbox.uniqueId('mailbox_conversation_'+that.get('conversationId')+'_'+that.get('opponentId')+'_');
                    }

                    if (data.error){
                        OW.error(data.error);
//                    self.showSendMessageFailed(tmpMessageUid);
                    }
                },
                error: function(message){
                    OWM.error(message);
                },
                complete: function(){
                    that.trigger('message_submit', data);
                    that.embedLinkResult = true;
                    that.embedLinkDetected = false;
                    that.embedAttachmentsValue = '';
                }
            };

            OWM.Mailbox.sendData(ajaxData);
        }
        else {
            var self = this;
            OWM.bind('mailbox.embed_link_request_result_'+self.get('conversationId'), function(r){
                var data = {
                    'convId': self.get('conversationId'),
                    'text': text,
                    'uid': self.uid,
                    'embedAttachments': self.embedAttachmentsValue
                };

                ajaxData['actionData'] = {
                    'uniqueId': OWM.Mailbox.uniqueId('postMessage'),
                    'name': 'postMessage',
                    'data': data
                };

                ajaxData['actionCallbacks'] = {
                    success: function(data){

                        if (data.message){
                            that.trigger('message_sent', data);
                            that.uid = OWM.Mailbox.uniqueId('mailbox_conversation_'+that.get('conversationId')+'_'+that.get('opponentId')+'_');
                        }

                        if (data.error){
                            OW.error(data.error);
//                    self.showSendMessageFailed(tmpMessageUid);
                        }
                    },
                    error: function(message){
                        OWM.error(message);
                    },
                    complete: function(){
                        that.trigger('message_submit', data);
                        that.embedLinkResult = true;
                        that.embedLinkDetected = false;
                        that.embedAttachmentsValue = '';
                    }
                };

                OWM.Mailbox.sendData(ajaxData);
                OWM.unbind('mailbox.embed_link_request_result_'+self.get('conversationId'));
            });
        }

        OWMLinkObserver.getObserver('newMessageForm'+' #newMessageText').resetObserver();
    }
});

MAILBOX_NewMessageFormView = Backbone.View.extend({
    initialize: function(){
        var that = this;
        var formElement = new OwFormElement('newMessageText', 'newMessageText');

        // init pseudo auto click
        var $textA = $(formElement.input), $submitCont = $('#newMessageSubmitForm');

        if( !this.taMessage ){
            this.taMessage = $textA.val();
        }

        $textA.unbind('focus').one('focus', function(){

            if (that.model.get('mode') == 'chat'){
                $('#mailboxConversation').addClass('owm_chat_input_opened');
            }
            else{
                $('#mailboxMailConversation').addClass('owm_mail_input_opened');
            }

            $(this).val('');

            $('html, body').animate({scrollTop:$(document).height()}, 'slow');
        });

        if (this.model.get('mode') == 'chat'){
            $("#newmessage-att-file").change(function() {
                if (!this.files || !this.files[0]) {
                    return
                }
                var self2 = this;

                $.confirm({
                    backgroundDismiss: false, closeIcon: false,
                    content: '' +
                    '<div class="form-group" style="text-align: center;">' +
                    '<img id="attach-img" style="max-width: 250px;" /><br/><br/><br/>'+
                    '<input id="pic-caption" type="text" placeholder="' + OW.getLanguageText('mailbox', 'text') + '" class="name form-control" value="'+$('#newMessageText').val()+'" required />' +
                    '</div>',
                    buttons: {
                        sayMyName: {
                            text: OW.getLanguageText('mailbox', 'send'),
                            btnClass: 'btn-orange',
                            action: function () {
                                var input = this.$content.find('input#pic-caption');
                                $('#newMessageForm input[name="caption"]').val(input.val().trim());
                                $('#newMessageText').val('');
                                $('#newMessageAttBtn').click();
                                $("#newmessage-att-file").val('');
                            }
                        },
                        cancel: {
                            text: OW.getLanguageText('base', 'cancel'),
                            action: function () {
                                $("#newmessage-att-file").val('');
                            }
                        }
                    }
                });

                if ( window.FileReader ) {
                    var ext = '';
                    if(self2.files[0].name.lastIndexOf('.')>0){
                        ext = self2.files[0].name.substr(self2.files[0].name.lastIndexOf('.')+1);
                    }
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        var imageExts=['jpg','jpeg','png','gif','bmp']
                        if(imageExts.includes(ext.toLowerCase())) {
                            $('#attach-img').attr('src', e.target.result).attr('style', 'width: auto; height: auto; background: none;');
                        }
                    };
                    reader.readAsDataURL(self2.files[0]);
                }
            });

            $('#newMessageSendBtn').click(function(ev){
                var text = $textA.val();
                that.model.sendMessage(text);
                $textA.val('');
            });
            $('#newMessageText').on('keypress', function (e) {
                if(e.which === 13){
                    //Disable textbox to prevent multiple submit
                    $(this).attr("disabled", "disabled");

                    $('#newMessageSendBtn').click();

                    //Enable the textbox again if needed.
                    $(this).removeAttr("disabled");
                    $(this).focus();
                }
            });
        }
        else{
            $("#newmessage-mail-att-file").change(function() {
                var img = $('#newmessage-mail-att-file-prevew img');
                var name = $("#newmessage-mail-att-file-name span");

                img.hide();
                name.text("");

                if (!this.files || !this.files[0]) {
                    return
                };

                if ( window.FileReader ) {
                    var reader = new FileReader();

                    reader.onload = function (e) {
                        img.show().attr('src', e.target.result);
                    };

                    reader.readAsDataURL(this.files[0]);
                } else {
                    name.text(this.files[0].name);
                }
            });
        }

        this.model.on('message_sent', function(data){
            $(formElement.input).val('');
            $('html, body').animate({scrollTop:$(document).height()}, 'slow');
        });

        this.model.on('message_submit', function(data){
            $('#newmessage-mail-send-btn').removeClass('owm_preloader_circle');
        })
    }
});

MAILBOX_ComposeMessageFormView = Backbone.View.extend({
    el: '#composeMessageCmp',

//    events: {
//        'click .owm_mail_back': 'backBtnHandler'
//    },

    initialize: function(params){
        this.params = params;

        $("#newmessage-mail-att-file").change(function() {
            var img = $('#newmessage-mail-att-file-prevew img');
            var name = $("#newmessage-mail-att-file-name span");

            img.hide();
            name.text("");

            if (!this.files || !this.files[0]) {
                return
            }

            if ( window.FileReader ) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    img.show().attr('src', e.target.result);
                };

                reader.readAsDataURL(this.files[0]);
            } else {
                name.text(this.files[0].name);
            }
        });

        var h = $(window).outerHeight() - $('#newMessageForm').outerHeight() - $('#main header').outerHeight() - $('.owm_mail_info_wrap').outerHeight() - 40;
        $('.owm_mail_compose textarea').height( h );

        $('#mailboxBackToConversations').attr('onclick', "location.href='"+this.params.profileUrl+"'");
        OWM.bind("mobile.before_show_sidebar", function( data ){
            data.openDefaultTab = false;
        });
        OWM.bind("mobile.show_sidebar", function( data ){
            if ( data.type == "right" ) {
                OWM.trigger('mobile.open_sidebar_tab', {key: 'convers'});
            }
        });
    },

    backBtnHandler: function(){
        OWM.bind('mailbox.right_sidebar_loaded', function(data){
            OWM.Mailbox.openUsers();
        });
        OWM.trigger('mobile.open_sidebar_tab', {key: 'convers'});
    }
});

MAILBOX_Conversation = Backbone.Model.extend({

    defaults: {
        'historyLoadAllowed': false
    },

    initialize: function() {
        var messageListOptions = this.get('log');
        this.messageList = new MAILBOX_MessageList(messageListOptions);
        this.set('historyLoadAllowed', this.get('logLength') > 16); //TODO this is hard code

        OWM.Mailbox.openedConversationId = this.get('conversationId');
        OWM.Mailbox.openedOpponentId = this.get('opponentId');
    },

    afterAttachment: function(data){
        if (data.message){
            this.messageList.add(data.message);
            $('html, body').animate({scrollTop:$(document).height()}, 'slow');
        }

        $("#newmessage-mail-att-file-prevew img").attr("src", "");
        $("#newmessage-mail-send-btn").removeClass("owm_preloader_circle");

        if (data.error){
            OWM.error(data.error);
            return;
        }

        if (owForms["newMailMessageForm"])
        {
            $("#newmessage-mail-att-file-prevew img").hide().attr("src", "");
            $("#newmessage-mail-send-btn").removeClass("owm_preloader_circle");
            $("#mailboxConversationFooter #newMessageSendBtn").removeClass("owm_send_btn_available");

            owForms["newMailMessageForm"].elements.newMessageText.setValue("");
            owForms["newMailMessageForm"].elements.uid.setValue(OWM.Mailbox.uniqueId("mailbox_conversation_"+data.message.convId+"_"+data.message.recipientId+"_"));

            var inputFile = $("#newmessage-mail-att-file");
            inputFile.replaceWith(inputFile.clone(true));
        }
    },

    loadHistory: function(callback){

        if (!this.get('historyLoadAllowed')) return;

        var self = this;
        this.historyLoadInProgress = true;
        OWM.Mailbox.sendInProcess = true;
        $.ajax({
            url: OWM.Mailbox.get('getHistoryResponderUrl'),
            type: 'POST',
            data: {
                convId: this.get('conversationId'),
                messageId: this.messageList.at(0).get('id'),
            },
            success: function(data){

                if ( typeof data != 'undefined' )
                {
                    if (data.log.length > 0)
                    {
                        $(data.log).each(function(){
                            self.messageList.unshift(this);
                        });

                        if (self.get('logLength') > self.get('log').length)
                        {
                            self.set('historyLoadAllowed', true, {silent: true});
                            //callback.apply();
                        }
                    }
                    else
                    {
                        self.set('historyLoadAllowed', false, {silent: true});
                    }
                }
            },
            error: function(e){
                if (im_debug_mode) console.log(e);
            },
            complete: function(){
                self.historyLoadInProgress = false;
                OWM.Mailbox.sendInProcess = false;
                callback.apply();
            },
            dataType: 'json'
        });
    }
});

MAILBOX_ConversationView = Backbone.View.extend({
    el: $('#mailboxConversation'),

//    events: {
//        '#owm_header_right_btn': 'backBtnHandler'
//    },

    initialize: function() {
        var that = this;

        this.model.messageList.on('add', this.renderMessage, this);
        this.model.on('change:status', this.changeStatus, this);
        this.model.on('change:historyLoadAllowed', function(){
            if (this.model.get('historyLoadAllowed')){
                this.showLoadHistoryBtn();
            }
            else{
                this.hideLoadHistoryBtn();
            }

        }, this);

        this.status = $('#onlineStatusBlock', this.el);
//        this.backBtn = $('#mailboxBackToConversations', this.el);
        this.loadHistoryBtn = $('#mailboxLoadHistoryBtn', this.el);
        this.showDate = false;

        this.model.bind('change', this.render, this);
        this.model.bind('destroy', this.remove, this);

        this.form = new MAILBOX_NewMessageForm({mode: this.model.get('mode'), opponentId: this.model.get('opponentId'), conversationId: this.model.get('conversationId')});

        this.form.on('message_sent', this.messageSent, this);

        var formView = new MAILBOX_NewMessageFormView({model: this.form});

        if (that.model.get('historyLoadAllowed')){
            that.showLoadHistoryBtn();
        }

        this.afterInitialize();

        OWM.bind("mobile.before_show_sidebar", function( data ){
            data.openDefaultTab = false;
        });

        OWM.bind("mobile.show_sidebar", function( data ){
            if ( data.type == "right" ) {
                OWM.trigger('mobile.open_sidebar_tab', {key: 'convers'});
            }
        });

        $(window).load(function(){
            $('html, body').animate({scrollTop:$(document).height()}, 'fast');
        });

        this.loadHistoryBtn.on('click', function(e){
            that.hideLoadHistoryBtn();
            that.model.loadHistory(function(){
                if (that.model.get('historyLoadAllowed')){
                    that.showLoadHistoryBtn();
                }
                else{
                    that.hideLoadHistoryBtn();
                }
            });
        });

        this.render();
    },

    afterInitialize: function(){
        OWM.bind('mailbox.right_sidebar_loaded', function(data){
            OWM.Mailbox.openUsers();
        });

        $(document).ready(function(){
            var h = $(window).outerHeight() - $('#main header').outerHeight() - $('.owm_chat_info').outerHeight()-$('#mailboxConversationFooter').outerHeight() - 150;
            $('#messageList').css( 'min-height', h );

            $('html, body').animate({scrollTop:$(document).height()}, 'fast');
        });
    },

    render: function(){
        $('.owm_avatar img', this.el).attr('src', this.model.get('avatarUrl'));
        $('.owm_avatar img', this.el).attr('title', this.model.get('displayName'));
        $('.owm_avatar img', this.el).attr('alt', this.model.get('displayName'));
        $('.owm_avatar a', this.el).attr('href', this.model.get('profileUrl'));

        $('#mailboxBackToConversations', this.el).attr('onclick', "location.href='"+this.model.get('profileUrl')+"'");

        $('.owm_chat_name a', this.el).attr('href', this.model.get('profileUrl'));
        $('.owm_chat_name a span', this.el).html(this.model.get('displayName'));

        $('#mailboxLoadHistoryPreloader').hide();

        this.changeStatus();

        var that = this;
        _.each(this.model.messageList.models, function (message) {
            that.renderMessage(message);
        }, this);

//        var h = $(window).outerHeight() - ($('#newMessageSubmitForm').outerHeight() + $('#newMessageForm').outerHeight()) - $('.owm_chat_info_wrap').outerHeight() - $('#main header').outerHeight();
//        $('#messageList').height( h );

        return this;
    },

    backBtnHandler: function(){
        OWM.trigger('mobile.open_sidebar_tab', {key: 'convers'});
    },

    changeStatus: function(){
        if (this.model.get('status') == 'offline'){
            this.status.hide();
        }
        else{
            this.status.show();
        }
    },

    messageSent: function(data){
        if (data.message){
            this.model.messageList.add(data.message);
        }
    },

    renderMessage: function(message){
        $(".no_messages_yet").hide();
        var itemIndex;
        itemIndex = this.model.messageList.indexOf(message);

        var view = new MAILBOX_MessageView({model: message});

        if (this.lastMessageDate != message.get('date')){
            this.lastMessageDate = message.get('date');
            this.showDate = true;
        }
        else{
            this.showDate = false;
        }

        if ( message.get('timeLabel') != this.lastMessageTimeLabel || this.showDate ){
            this.lastMessageTimeLabel = message.get('timeLabel');
            var timeBlock = this.showTimeBlock(message);
        }

        if (itemIndex == 0){
            this.$('#messageList').prepend(view.render().$el);
            if (timeBlock){
                this.$('#messageList').prepend(timeBlock);
            }
        }
        else{
            if (timeBlock){
                this.$('#messageList').append(timeBlock);
            }
            this.$('#messageList').append(view.render().$el);
        }
        window.scrollTo(0,document.body.scrollHeight);
    },

    showTimeBlock: function(message){

        var timeBlock = $('#dialogTimeBlockPrototypeBlock').clone();
        timeBlock.attr('id', 'timeBlock'+message.get('timeStamp'));

        if (this.showDate){
            timeBlock.html(message.get('dateLabel'));// + '. ' + message.get('timeLabel')
        }
        else{
            return '';
            // timeBlock.html(message.get('timeLabel'));
        }

        return timeBlock;
    },

    showLoadHistoryBtn: function(){
        this.loadHistoryBtn.show();
        $('#mailboxLoadHistoryPreloader').hide();
    },

    hideLoadHistoryBtn: function(){

        this.loadHistoryBtn.hide();
        if (this.model.get('historyLoadAllowed')){
            $('#mailboxLoadHistoryPreloader').show();
        }
        else{
            $('#mailboxLoadHistoryPreloader').hide();
        }
    }

});

MAILBOX_MailConversationView = MAILBOX_ConversationView.extend({
    el: $('#main section'),


//    events: {
//        'click .owm_mail_back': 'backBtnHandler'
//    },

    afterInitialize: function(){
        OWM.bind('mailbox.right_sidebar_loaded', function(data){
            OWM.Mailbox.openConversations();
        });

        $(document).ready(function(){
            var h = $(window).outerHeight() - $('#main header').outerHeight() - $('.owm_mail_info').outerHeight() + 10 - 150;
            $('#messageList').css( 'min-height', h );

            $('html, body').animate({scrollTop:$(document).height()}, 'fast');
        });
    },

    render: function(){
        $('.owm_avatar img', this.el).attr('src', this.model.get('avatarUrl'));
        $('.owm_avatar img', this.el).attr('title', this.model.get('displayName'));
        $('.owm_avatar img', this.el).attr('alt', this.model.get('displayName'));
        $('.owm_avatar a', this.el).attr('href', this.model.get('profileUrl'));

        $('#mailboxBackToConversations').attr('onclick', "location.href='"+this.model.get('profileUrl')+"'");

        $('.owm_mail_name a', this.el).attr('href', this.model.get('profileUrl'));
        $('.owm_mail_name a span', this.el).html(this.model.get('displayName'));
        $('#mailboxPreviewText', this.el).html(this.model.get('subject'));

        $('#mailboxLoadHistoryPreloader').hide();

        this.changeStatus();

        var that = this;
        _.each(this.model.messageList.models, function (message) {
            console.log(message);
            that.renderMessage(message);
        }, this);

        return this;
    },

    backBtnHandler: function(){
        OWM.trigger('mobile.open_sidebar_tab', {key: 'convers'});

        OWM.bind('mobile.console_page_loaded', function(data){
            if (data.key == 'convers'){
                OWM.Mailbox.openConversations();
            }
        });
    },

    renderMessage: function(message){
        var itemIndex;
        itemIndex = this.model.messageList.indexOf(message);

        var view = new MAILBOX_MailMessageView({model: message});

        if (this.lastMessageDate != message.get('date')){
            this.lastMessageDate = message.get('date');
            this.showDate = true;
        }
        else{
            this.showDate = false;
        }

        if ( message.get('timeLabel') != this.lastMessageTimeLabel || this.showDate ){
            this.lastMessageTimeLabel = message.get('timeLabel');
            var timeBlock = this.showTimeBlock(message);
        }

        if (itemIndex == 0){
            this.$('#messageList').prepend(view.render().$el);
            if (timeBlock){
                this.$('#messageList').prepend(timeBlock);
            }
        }
        else{
            if (timeBlock){
                this.$('#messageList').append(timeBlock);
            }
            this.$('#messageList').append(view.render().$el);
        }
    }

});

MAILBOX_Mobile = Backbone.Model.extend({

    initialize: function(params){
        var self = this;

        self.convList = [];
        self.userList = [];
        self.pingInterval = params.pingInterval;
        self.lastMessageTimestamp = params.lastMessageTimestamp || 0;
        self.userOnlineCount = 0;
        self.defaultMode = 'conversations';
        self.readMessageList = new MAILBOX_MessageList;
        self.unreadMessageList = new MAILBOX_UnreadMessageList;
        self.conversationsCount = 0;
        self.addConverse = params.addConverse;
        self.currentOpponentId = -1;
        var readyStatus = 0;

        self.userListUrl = params.userListUrl;

        $.ajax({
            'url': self.userListUrl,
            'dataType': 'text',
            'success': function(data){
                self.userList = JSON.parse(atob(data));
                self.userListCollection = new Backbone.Collection(self.userList);
                readyStatus++;
                OW.trigger('mailbox.ready', readyStatus);
            }
        });

        self.convListUrl = params.convListUrl;

        $.ajax({
            'url': self.convListUrl,
            'dataType': 'text',
            'success': function(data){
                self.convList = JSON.parse(atob(data));
                self.convListCollection = new Backbone.Collection(self.convList);
                readyStatus++;
                OW.trigger('mailbox.ready', readyStatus);
            }
        });

        self.getParams = function(){
            var params = {};

            var date = new Date();
            var time = parseInt(date.getTime() / 1000);

            params.currentOpponentId = self.currentOpponentId;
            params.lastRequestTimestamp = time;
            params.lastMessageTimestamp = self.lastMessageTimestamp;
            params.readMessageList = self.readMessageList.pluck('id');
            params.unreadMessageList = self.unreadMessageList.pluck('id');
            params.userOnlineCount = self.userOnlineCount;
            params.convListLength = self.convList.length;
            params.ajaxActionData = self.ajaxActionData;
            params.conversationsCount = self.ajaxActionData;
            params.getAllConversations = 1;
            if(window.replyToMessage && params.ajaxActionData.length > 0){
                params.ajaxActionData[0].data.replyToId = window.replyToMessage;
                window.replyToMessage = null;
                $('#dialogReplyTo').remove();
            }
            self.ajaxActionData = [];
            if(self.currentOpponentId != -1){
                params.currentMessageList = [self.currentOpponentId];
            }

            if (params.readMessageList.length != 0)
            {
                self.clearReadMessageList();
            }

            return params;
        }

        self.beforePingStatus = true;
        self.ajaxActionData = [];
        self.ajaxActionCallbacks = {};

        self.addAjaxData = function(ajaxData){
            self.ajaxActionData.push(ajaxData['actionData']);
            self.ajaxActionCallbacks[ajaxData['actionData']['uniqueId']] = ajaxData['actionCallbacks'];
        }

        self.sendData = function(ajaxData){
            if (typeof ajaxData != 'undefined')
            {
                self.addAjaxData(ajaxData);
            }

            var requestData = JSON.stringify(self.getParams());

            self.beforePingStatus = false;
            $.ajax({
                url: OWM.Mailbox.get('pingResponderUrl'),
                type: 'POST',
                data: {'request': requestData},
                success: function(data){
                    self.setData(data);
                },
                complete: function(){
                    self.beforePingStatus = true;
                    $("#mailboxConversationFooter #newMessageSendBtn").removeClass("owm_send_btn_available");
                },
                dataType: 'json'
            });
        }

        self.setData = function(data){

            if (typeof data.userOnlineCount != 'undefined'){
                if (typeof data.userList != 'undefined')
                {
                    self.userList = self.sortUserList(data.userList);
                    if(data.userList.length > 0){
                        self.currentOpponentId = data.userList[0].opponentId;
                    }
                }

                OW.trigger('mailbox.user_online_count_update', {userOnlineCount: data.userOnlineCount});
            }

            if (typeof data.convList != 'undefined'){
                self.conversationsCount = data.conversationsCount;

                if (self.convList > data.convList)
                {
                    $(self.convList).each(function(){

                    });
                }
                else if (data.convList > self.convList)
                {
                    $(data.convList).each(function(){

                        var conv = this;
                        var exists = false;

                        $(self.convList).each(function(){
                            if (this.conversationId == conv.conversationId)
                            {
                                exists = true;
                            }
                        });

                        if (!exists)
                        {
                            OW.trigger('mailbox.new_conversation_created', conv);
                        }
                    });
                }

                self.convList = data.convList;
            }

            if (typeof data.messageList != 'undefined')
            {
                self.newMessageList = data.messageList;
                $.each(data.messageList, function(){
                    if (this.timeStamp != self.lastMessageTimestamp)
                    {
                        if (typeof OWM.mailboxConversations != "undefined"){
                            OWM.mailboxConversations.itemList.findWhere({conversationId: this.convId}).set('lastMessageTimestamp', this.timeStamp);
                        }

                        OW.trigger('mailbox.message', this);
                    }
                } );
                self.newMessageList = [];
            }

            //TODO self.ajaxActionCallbacks.error()
            if (typeof data.ajaxActionResponse != 'undefined'){

                var callbacksToDelete = [];
                $.each(data.ajaxActionResponse, function(uniqueId, item){
                    self.ajaxActionCallbacks[uniqueId].success(item);
                    self.ajaxActionCallbacks[uniqueId].complete();
                    callbacksToDelete.push(uniqueId);
                });

                for (var i=0; i<callbacksToDelete.length; i++){
                    delete self.ajaxActionCallbacks[callbacksToDelete[i]];
                }
            }

            if (typeof data.opponentUnreadMessageData != 'undefined' && typeof data.opponentLastDataId != 'undefined') {
                checkSeenMessageDataForMessageMobilePage(self.currentOpponentId, data.opponentUnreadMessageData, data.opponentLastDataId);
            }
            OWM.trigger('mailbox.after_ping',data);
        }

        OW.bind('mailbox.ready', function(readyStatus){
            if (readyStatus == 2){

                OW.bind('mailbox.message', function(message) {

                    if (typeof OWM.conversation != 'undefined' && OWM.conversation.get('conversationId') == message.convId){
                        OWM.conversation.messageList.add(message);
                        self.readMessageList.add(message);
                        self.lastMessageTimestamp = message.timeStamp;
                    }
                    else{
                        if (!message.isAuthor){
                            self.unreadMessageList.add(message);
                            OW.trigger('mailbox.new_message_notification', {message: message, unreadMessageList: self.unreadMessageList});
                        }
                    }
                });

                this.updateCounter = function(data){
                    var $tabCounter = $(".owm_sidebar_count_txt", "#console-tab-convers");

                    var counter = data.unreadMessageList.length;

                    //showing count
                    if(self.addConverse){
                        OWM.trigger('mobile.console_show_counter', {
                            counter: counter,
                            tab: false,
                            options: {tab: 'convers'}
                        });
                    }else {
                        if ($('.tabs .ow_content_menu li.menu_messages>a.title .owm_sidebar_count').length == 0)
                            $('.tabs .ow_content_menu li.menu_messages>a.title').append('<span class="owm_sidebar_count" style="right: initial;left: initial;"> <span class="owm_sidebar_count_txt">' + counter + '</span> </span>');
                        else
                            $('.tabs .ow_content_menu li.menu_messages>a.title .owm_sidebar_count_txt').html(counter);
                    }

                    var $tab = $("#console-tab-convers");

                    if ( $(".owm_sidebar_count", $tab).is(":visible") ) {
                        $tabCounter.html(counter);
                    }
                    else {
                        $tabCounter.html(counter);
                        $(".owm_sidebar_count", $tab).fadeIn();
                    }
                }

                OW.bind('mailbox.new_message_notification', this.updateCounter);

                OW.bind('mailbox.message_was_read', this.updateCounter);

                OW.getPing().addCommand('mailbox_ping', {
                    params: {},
                    before: function()
                    {
                        if (!self.beforePingStatus)
                        {
                            return false;
                        }

                        if (self.sendInProcess)
                        {
                            return false;
                        }

                        this.params = self.getParams();
                    },
                    after: function( data )
                    {
                        self.setData(data);
                        OW.trigger('userLoggedOutPopUp', {'data' : data});
                    }
                }).start(self.pingInterval);

                //Added for iismainpage plugin
                if(OWM.mailboxUsers!=null && OWM.mailboxUsers!='undefined' && OWM.mailboxUsers.loadedUserCount == 0){
                    OWM.mailboxUsers.loadList();
                }

                //Added for iismainpage plugin
                if(OWM.mailboxConversations!=null && OWM.mailboxConversations!='undefined' && OWM.mailboxConversations.loadedConvCount == 0){
                    OWM.mailboxConversations.loadList();
                }
            }
        });
    },

    clearReadMessageList: function(){
        this.readMessageList = new MAILBOX_MessageList;
    },

    formatAMPM: function(date) {
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var strTime = '00:00';

        if (OWMailbox.useMilitaryTime)
        {
            minutes = minutes < 10 ? '0'+minutes : minutes;
            hours = hours < 10 ? '0'+hours : hours;
            strTime = hours + ':' + minutes;
        }
        else
        {
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12;
            minutes = minutes < 10 ? '0'+minutes : minutes;
            hours = hours < 10 ? '0'+hours : hours;
            strTime = hours + ':' + minutes + ampm;
        }

        return strTime;
    },

    formatAttachmentFileName: function(fileName){
        var str = fileName;

        if (fileName.length > 20){
            str = fileName.substring(0, 10) + '...' + fileName.substring(fileName.length-10);
        }

        return str;
    },

    formatAttachmentFileSize: function(size){

        if (size >= 1024)
        {
            size = size / 1024;
            return '(' + size + 'MB)';
        }
        return '(' + size + 'KB)';
    },

    openConversations: function(){
        if (OWM.mailboxSidebarMenu){
            OWM.mailboxSidebarMenu.itemList.findWhere({mode: 'conversations'}).set({selected: true});
        }
    },

    openUsers: function(){
        if (OWM.mailboxSidebarMenu){
            OWM.mailboxSidebarMenu.itemList.findWhere({mode: 'userlist'}).set({selected: true});
        }
    },

    sortUserList: function(list){

        var sortedUserList = [];
        var usersWithCorrespondence = [];
        var usersFriendsOnline = [];
        var usersFriendsOffline = [];
        var usersMembersOnline = [];
        var usersMembersOffline = [];

        for (i in list)
        {
            var user = list[i];

            if (user.lastMessageTimestamp > 0)
            {
                usersWithCorrespondence.push(user);
            }
            else
            {
                if (user.isFriend)
                {
                    if (user.status != 'offline')
                    {
                        usersFriendsOnline.push(user);
                    }
                    else
                    {
                        usersFriendsOffline.push(user);
                    }
                }
                else
                {
                    if (user.status != 'offline')
                    {
                        usersMembersOnline.push(user);
                    }
                    else
                    {
                        usersMembersOffline.push(user);
                    }
                }
            }
        }

        usersWithCorrespondence.sort(function(user1,user2){
            return user2.lastMessageTimestamp - user1.lastMessageTimestamp;
        });

        for (i in usersWithCorrespondence)
        {
            sortedUserList.push(usersWithCorrespondence[i]);
        }

        usersFriendsOnline.sort(function(user1,user2){
            return user1.displayName.toLowerCase().localeCompare( user2.displayName.toLowerCase() );
        });

        for (i in usersFriendsOnline)
        {
            sortedUserList.push(usersFriendsOnline[i]);
        }

        usersFriendsOffline.sort(function(user1,user2){
            return user1.displayName.toLowerCase().localeCompare( user2.displayName.toLowerCase() );
        });

        for (i in usersFriendsOffline)
        {
            sortedUserList.push(usersFriendsOffline[i]);
        }

        usersMembersOnline.sort(function(user1,user2){
            return user1.displayName.toLowerCase().localeCompare( user2.displayName.toLowerCase() );
        });

        for (i in usersMembersOnline)
        {
            sortedUserList.push(usersMembersOnline[i]);
        }

        usersMembersOffline.sort(function(user1,user2){
            return user1.displayName.toLowerCase().localeCompare( user2.displayName.toLowerCase() );
        });

        for (i in usersMembersOffline)
        {
            sortedUserList.push(usersMembersOffline[i]);
        }

        return sortedUserList;
    },

    uniqueId: function(prefix){
        prefix = prefix || '';
        return prefix + Math.random().toString(36).substr(2, 9);
    }

});

$(function(){

    $.fn.extend({
        autolink: function(options){
            var exp =  new RegExp("(\\b(https?|ftp|file)://[-A-Z0-9+&amp;@#\\/%?=~_|!:,.;]*[-A-Z0-9+&amp;@#\\/%=~_|])(?![^<>]*>(?:(?!<\/?a\b).)*<\/a>)(?![^<>]*>(?:(?!<\/?img\b).)*)", "ig");
            /* Credit for the regex above goes to @elijahmanor on Twitter, so follow that awesome guy! */

            this.each( function(id, item){

                if ($(item).html() == ""){
                    return 1;
                }
                var text = $(item).html().replace(exp,"<a href='$1' target='_blank'>$1</a>");
                $(item).html( text );

            });

            return this;
        }
    });

    im_debug_mode = false;
});

function checkSeenMessageDataForMessageMobilePage(opponentId, opponentUnreadMessageData, opponentLastDataId) {
    $.each(opponentUnreadMessageData, function (recipientId, item) {
        if(opponentId == recipientId) {
            var allMessages = $("#messageList div[id^='messageItem'].owm_chat_bubble_mine_wrap");
            for (var i = 0; i < allMessages.length; i++) {
                var element = allMessages[i];
                var id = element.id;
                id = id.replace("messageItem", "");
                if (jQuery.inArray(id, item) == -1 && (opponentLastDataId[recipientId] != -1 &&  id <= opponentLastDataId[recipientId])) {
                    $(element).addClass("message_seen");
                } else {
                    //do nothing (not seen)
                }
            }
        }
    });
}