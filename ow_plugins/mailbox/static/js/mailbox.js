String.prototype.nl2br = function(is_xhtml) {
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
    return (this + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
}

String.prototype.width = function(font) {

    var f = font || '12px arial',
        o = $('<div>').text(this)
            .css({'position': 'absolute', 'float': 'left', 'visibility': 'hidden', 'word-wrap': 'break-word', 'word-spacing': 'normal', 'white-space': 'nowrap', 'font': f})
            .appendTo($('body')),
        w = o.width();

    o.remove();

    return w;
}

MAILBOX_Message = Backbone.Model.extend({
    idAttribute: 'id',
});

MAILBOX_MessageCollection = Backbone.Collection.extend({
    model: MAILBOX_Message,
    comparator: function(model){
        return model.get('id');
    }
});

MAILBOX_Conversation = Backbone.Model.extend({
    idAttribute: 'conversationId',
    defaults: {
        conversationId: null,
        opponentId: null,

        conversationRead: 1,
        displayName: '',
        lastMessageTimestamp: 0,
        newMessageCount: 0,
        wasCreatedByScroll: false,
        show: false,
        shortUserData: '',
        messages: new MAILBOX_MessageCollection(),
        firstMessageId: 0,
        lastMessageId: 0
    },

    show: function(){
        this.set('show', true);
    },

    hide: function(){
        this.set('show', false);
    }
});

MAILBOX_ConversationsCollection = Backbone.Collection.extend({
    model: MAILBOX_Conversation,
    comparator: function(model){
        return -model.get('lastMessageTimestamp');
    }
});

MAILBOX_User = Backbone.Model.extend({
    idAttribute: 'opponentId',
    defaults: {
        convId: null,
        opponentId: null,
        status: '',
        lastMessageTimestamp: 0,
        displayName: '',
        profileUrl: '',
        avatarUrl: '',
        wasCorrespondence: false,
        isFriend: false,
        unreadMessagesCount: 0,
    },

    initialize: function(){

        var self = this;

        this.conversation = new MAILBOX_Conversation;

        OW.bind('mailbox.application_started', function(){
            if ( self.get('convId') == null ){
                OW.Mailbox.conversationsCollection.on('add', self.bindConversation, this);
            }
            else{
                var conversation = OW.Mailbox.conversationsCollection.findWhere({opponentId: self.get('opponentId'), mode: 'chat'});
                if (conversation){
                    self.bindConversation(conversation);
                }
            }
        });
    },

    bindConversation: function(conversation){
        if (conversation.get('mode') == 'chat' && conversation.get('opponentId') == this.get('opponentId')){

            this.conversation = conversation;
            this.conversation.on('change:newMessageCount', this.changeNewMessageCount, this);
            this.conversation.on('change:lastMessageTimestamp', this.changeLastMessageTimestamp, this);
            this.set('convId', conversation.get('conversationId'));
            this.changeNewMessageCount();
            this.changeLastMessageTimestamp();
        }
    },

    changeNewMessageCount: function(){
        this.set('unreadMessagesCount', this.conversation.get('newMessageCount'));
    },

    changeLastMessageTimestamp: function(){
        this.set('lastMessageTimestamp', this.conversation.get('lastMessageTimestamp'));
    }

});

MAILBOX_UsersCollection = Backbone.Collection.extend({
    model: MAILBOX_User,
    comparator: function(model){
        return model.get('displayName');
    }
})

OWMailbox = {}

OWMailbox.Application = function(params){

    var self = this;

    this.pingInterval = params.pingInterval || 5000;
    this.beforePingStatus = true;
    this.sendInProcess = false;
    this.lastMessageTimestamp = params.lastMessageTimestamp || 0;
    this.userOnlineCount = 0;
    this.usersCollection = new MAILBOX_UsersCollection;
    this.conversationsCollection = new MAILBOX_ConversationsCollection;
    this.conversationsCount = 0;
    this.currentOpponentId = -1;
    this.loadedOpponentIds = [];

    this.ajaxActionData = [];
    this.ajaxActionCallbacks = {};
    this.markedUnreadConversationList = [];
    this.markedUnreadNotViewedConversationList = [];
    this.appStarted = false;

    var storage = OWMailbox.getStorage();
    storage.setItem('lastMessageTimestamp', this.lastMessageTimestamp);

    this.addAjaxData = function(ajaxData){
        this.ajaxActionData.push(ajaxData['actionData']);
        this.ajaxActionCallbacks[ajaxData['actionData']['uniqueId']] = ajaxData['actionCallbacks'];
    }

    this.sendData = function(ajaxData){
        if (typeof ajaxData != 'undefined'){
            this.addAjaxData(ajaxData);
        }

        var requestData = JSON.stringify(self.getParams());
        self.beforePingStatus = false;
        $.ajax({
            url: OWMailbox.pingResponderUrl,
            type: 'POST',
            data: {'request': requestData},
            success: function(data){
                self.setData(data);
            },
            complete: function(){
                self.beforePingStatus = true;
            },
            dataType: 'json'
        });
    }

    this.getParams = function(){
        var params = {};

        var date = new Date();
        var time = parseInt(date.getTime() / 1000);

        params.currentOpponentId = self.currentOpponentId;
        params.lastRequestTimestamp = time;
        params.lastMessageTimestamp = self.lastMessageTimestamp;
        params.readMessageList = self.contactManager.getReadMessageList();
        params.unreadMessageList = self.contactManager.getUnreadMessageList();
        params.viewedConversationList = self.contactManager.getViewedConversationList();
        var userIdList = self.contactManager.getCurrentMessageListId();
        if(self.currentOpponentId != -1){
            userIdList.push(self.currentOpponentId);
            for(var j=0; j < self.loadedOpponentIds.length; j++){
                userIdList.push(self.loadedOpponentIds[j]);
            }
            params.currentMessageList = userIdList;
        }
        params.userOnlineCount = self.userOnlineCount;
        params.userListLength = self.usersCollection.length;
        params.convListLength = self.conversationsCollection.length;
        params.conversationsCount = self.conversationsCount;
        params.ajaxActionData = self.ajaxActionData;
        if(window.replyToMessage && params.ajaxActionData.length > 0){
            params.ajaxActionData[0].data.replyToId = window.replyToMessage;
            window.replyToMessage = null;
            $('#dialogReplyTo').remove();
        }
        self.ajaxActionData = [];

        if (params.readMessageList.length != 0){
            self.contactManager.clearReadMessageList();
        }

        if (params.viewedConversationList.length != 0){
            self.contactManager.clearViewedConversationList();
        }

        return params;
    }

    this.setData = function(data){

        // HOTFIX; update old messages timestamps firstly, it needs for correct messages ordering  (skalfa/workflow#35)
        if (typeof data.ajaxActionResponse != 'undefined'){
            $.each(data.ajaxActionResponse, function(uniqueId, item){
                var actionCallback = self.ajaxActionCallbacks[uniqueId];

                if (typeof item.message != 'undefined') {
                    if (item.message.mode == 'chat' && typeof actionCallback.tmpMessageUid != 'undefined') {
                        $(".message[data-tmp-id='messageItem" + actionCallback.tmpMessageUid + "']").attr('data-timestamp', item.message.timeStamp);
                    }
                }
                if(item.opponentId){
                    self.currentOpponentId = item.opponentId;
                    if(typeof data.opponentUnreadMessageData != 'undefined' && typeof data.opponentLastDataId != 'undefined') {
                        checkSeenMessageData(data.opponentUnreadMessageData, data.opponentLastDataId, self.currentOpponentId);
                    }
                }
            });
        }

        if (typeof data.userOnlineCount != 'undefined'){
            if (typeof data.userList != 'undefined'){
                self.usersCollection.set(data.userList);
            }

            self.userOnlineCount = data.userOnlineCount;
            OW.trigger('mailbox.user_online_count_update', {userOnlineCount: data.userOnlineCount});
        }

        if (typeof data.convList != 'undefined'){
            self.conversationsCount = data.conversationsCount;
            self.conversationsCollection.set(data.convList);
            for(var k = 0; k< data.convList.length; k++){
                self.loadedOpponentIds.push(data.convList[k].opponentId);
            }
        }

        if (typeof data.markedUnreadConversationList != 'undefined'){
            self.markedUnreadConversationList = data.markedUnreadConversationList;
            OW.trigger('mailbox.console_update_counter', { unreadMessageList: [] });
            OW.MailboxConsole.updateCounter();
        }

        if (typeof data.messageList != 'undefined'){
            ///var tmpLastMessageTimestamp = OW.Mailbox.lastMessageTimestamp;
            $.each(data.messageList, function(){
                // if (this.timeStamp != self.lastMessageTimestamp){
                OW.trigger('mailbox.message', this);
                //   tmpLastMessageTimestamp = parseInt(this.timeStamp);
                //}
            });
            //OW.Mailbox.lastMessageTimestamp = tmpLastMessageTimestamp;
        }

        //TODO self.ajaxActionCallbacks.error
        if (typeof data.ajaxActionResponse != 'undefined'){

            var callbacksToDelete = [];
            $.each(data.ajaxActionResponse, function(uniqueId, item){
                self.ajaxActionCallbacks[uniqueId].success(item);
                self.ajaxActionCallbacks[uniqueId].complete();
                callbacksToDelete.push(uniqueId);
                if(item.opponentId){
                    self.currentOpponentId = item.opponentId;
                }
            });

            for (var i=0; i<callbacksToDelete.length; i++){
                delete self.ajaxActionCallbacks[callbacksToDelete[i]];
            }
        }

        if (typeof data.opponentUnreadMessageData != 'undefined' && typeof data.opponentLastDataId != 'undefined') {
            checkSeenMessageData(data.opponentUnreadMessageData, data.opponentLastDataId, -1);
        }

        if (!self.appStarted) {
            OW.trigger('mailbox.ready');
        }

        OW.trigger('mailbox.after_ping',data);
        OW.MailboxConsole.updateCounter();
    }

    OW.getPing().addCommand('mailbox_ping', {
        params: {},
        before: function()
        {
            if (!self.beforePingStatus){
                return false;
            }

            if (self.sendInProcess){
                return false;
            }

            this.params = self.getParams();
        },
        after: function( data )
        {
            if (typeof data != 'undefined'){
                self.setData(data);
                OW.trigger('userLoggedOutPopUp', {'data' : data});
            }
            else{
                if (im_debug_mode){console.log('Ping data is empty for some reason');}
            }
        }
    }).start(this.pingInterval);
}

OWMailbox.getStorage = function(){
    try {
        if ('localStorage' in window && window['localStorage'] !== null){
            return localStorage;
        }
    } catch (e) {
        return {
            getItem: function(key){
                return im_readCookie(key);
            },

            setItem: function(key, value){
                im_createCookie(key, value, 1);
            },

            removeItem: function(key){
                im_eraseCookie(key);
            }
        }
    }
}

OWMailbox.getOpenedDialogsCookie = function(){
    var storage = OWMailbox.getStorage();
    var openedDialogs = {};
    var openedDialogsJson = storage.getItem('mailbox.openedDialogs');

    if (openedDialogsJson != null){
        openedDialogs = JSON.parse(openedDialogsJson);
    }

    return openedDialogs;
}

OWMailbox.setOpenedDialogsCookie = function(value){

    var storage = OWMailbox.getStorage();
    var openedDialogsJson = JSON.stringify(value);

    storage.setItem('mailbox.openedDialogs', openedDialogsJson);
}

OWMailbox.sortUserList = function(list){

    var sortedUserList = [];
    var usersWithCorrespondence = [];
    var usersFriendsOnline = [];
    var usersFriendsOffline = [];
    var usersMembersOnline = [];
    var usersMembersOffline = [];

    for (i in list)
    {
        var user = list[i];

        if (user.lastMessageTimestamp > 0){
            usersWithCorrespondence.push(user);
        }
        else{
            if (user.isFriend){
                if (user.status != 'offline'){
                    usersFriendsOnline.push(user);
                }
                else{
                    usersFriendsOffline.push(user);
                }
            }
            else{
                if (user.status != 'offline'){
                    usersMembersOnline.push(user);
                }
                else{
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
}

OWMailbox.log = function(text){
    if (im_debug_mode){console.log(text);}
}

OWMailbox.makeObservableSubject = function(){
    var observers = [];
    var addObserver = function (o) {
        if (typeof o !== 'function') {
            throw new Error('observer must be a function');
        }
        for (var i = 0, ilen = observers.length; i < ilen; i += 1) {
            var observer = observers[i];
            if (observer === o) {
                throw new Error('observer already in the list');
            }
        }
        observers.push(o);
    };
    var removeObserver = function (o) {
        for (var i = 0, ilen = observers.length; i < ilen; i += 1) {
            var observer = observers[i];
            if (observer === o) {
                observers.splice(i, 1);
                return;
            }
        }
        throw new Error('could not find observer in list of observers');
    };
    var notifyObservers = function (data) {
        // Make a copy of observer list in case the list
        // is mutated during the notifications.
        var observersSnapshot = observers.slice(0);
        for (var i = 0, ilen = observersSnapshot.length; i < ilen; i += 1) {
            observersSnapshot[i](data);
        }
    };
    return {
        addObserver: addObserver,
        removeObserver: removeObserver,
        notifyObservers: notifyObservers,
        notify: notifyObservers
    };
}

OWMailbox.uniqueId = function(prefix){

    prefix = prefix || '';

    return prefix + Math.random().toString(36).substr(2, 9);
}

OWMailbox.formatAMPM = function(date) {
    var hours = date.getHours();
    var minutes = date.getMinutes();
    var strTime = '00:00';

    if (OWMailbox.useMilitaryTime){
        minutes = minutes < 10 ? '0'+minutes : minutes;
        hours = hours < 10 ? '0'+hours : hours;
        strTime = hours + ':' + minutes;
    }
    else{
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        minutes = minutes < 10 ? '0'+minutes : minutes;
        hours = hours < 10 ? '0'+hours : hours;
        strTime = hours + ':' + minutes + ampm;
    }

    return strTime;
}

OWMailbox.formatAttachmentFileName = function(fileName){
    var str = fileName;

    if (fileName.length > 15){
        str = fileName.substring(0, 8) + '...' + fileName.substring(fileName.length-7);
    }

    return str;
}

OWMailbox.formatAttachmentFileSize = function(size){

    if (size >= 1024){
        size = size / 1024;
        return '(' + size.toFixed(1) + 'MB)';
    }
    return '(' + size + 'KB)';
}

OWMailbox.NewMessageForm = {};

OWMailbox.NewMessageForm.Model = function(){
    var self = this;

}

OWMailbox.NewMessageForm.Controller = function(model){
    var self = this;

    self.model = model;
    self.newMessageWindowControl = $('#newMessageWindow');
    self.newMessageBtn = $('#newMessageBtn');
    self.minimizeBtn = $('#newMessageWindowMinimizeBtn');
    self.closeBtn = $('#newMessageWindowCloseBtn');
    self.deleteBtn = $('#userFieldDeleteBtn');
    self.form = owForms['mailbox-new-message-form'];

    self.unselectedCapMinimizeBtn = $('#newMessageWindowUnselectedCapMinimizeBtn');
    self.unselectedCapCloseBtn = $('#newMessageWindowUnselectedCapCloseBtn');

    /**
     * Check new message window active mode
     *
     * @return boolean
     */
    this.isNewMessageWindowActive = function() {
        return self.newMessageWindowControl.length && self.newMessageWindowControl.is(":visible");
    }

    /**
     * Close active mailbox window with confirmation
     *
     * @param integer activeChats
     * @return boolean
     */
    this.closeNewMessageWindowWithConfirmation = function(activeChats) {
        if (this.isNewMessageWindowActive() && !activeChats) {
            var subject = owForms['mailbox-new-message-form'].elements['subject'].getValue();
            var message = owForms['mailbox-new-message-form'].elements['message'].getValue();

            // close the window without confirmation
            if (!$.trim(subject) && !$.trim(message)) {
                this.close();
                return true;
            }

            var result = confirm(OW.getLanguageText('mailbox', 'close_new_message_window_confirmation'));
            if (result) {
                // close the new mailbox window
                this.close();
            }

            return result;
        }

        return true;
    }

    this.close = function(){
        OW.trigger('mailbox.close_new_message_form');
    };

    this.minimize = function(e){
        if ($(e.target).attr('id') == self.deleteBtn.attr('id')){
            return;
        }

        if (self.newMessageWindowControl.hasClass('ow_active')){
            OW.trigger('mailbox.minimize_new_message_form');
        }
        else{
            OW.trigger('mailbox.open_new_message_form');
        }
    };

    this.setUser = function( data ){

        $('#userFieldProfileLink', this.newMessageWindowControl).attr('href', data.profileUrl);
        $('#userFieldDisplayname', this.newMessageWindowControl).html( data.displayName );
        $('#userFieldAvatar', this.newMessageWindowControl).attr('src', data.avatarUrl);

        $('.ow_chat_block', this.newMessageWindowControl).removeClass('ow_mailchat_select_user_wrap');
        $('.ow_chat_block', this.newMessageWindowControl).addClass('ow_mailchat_selected_user_wrap');
    };

    this.resetUser = function(){
        $('.ow_chat_block', this.newMessageWindowControl).addClass('ow_mailchat_select_user_wrap');
        $('.ow_chat_block', this.newMessageWindowControl).removeClass('ow_mailchat_selected_user_wrap');
    };

    this._prepareOpponent = function(data) {
        var opponent = OW.Mailbox.usersCollection.findWhere({opponentId: data.opponentId});

        if (!opponent) {
            OW.Mailbox.usersCollection.add(data);
        }
    };

    this.openForm = function( data ) {
        self.newMessageWindowControl.addClass('ow_open');
        self.newMessageWindowControl.addClass('ow_active');
        self.newMessageWindowControl.removeClass('ow_chat_dialog_active');

        var storage = OWMailbox.getStorage();
        storage.setItem('mailbox.new_message_form_opened', 1);

        if (data) {

            self._prepareOpponent(data);
            self.form.elements['opponentId'].setValue(data.opponentId);
        }

        OW.trigger('mailbox.new_message_form_opened');
    };

    this.openFormMinimized = function( data ) {
        self.newMessageWindowControl.addClass('ow_open');
        self.newMessageWindowControl.addClass('ow_chat_dialog_active');
    };

    this.closeForm = function() {
        self.form.elements['opponentId'].resetValue();
        self.form.elements['subject'].resetValue();
        self.form.elements['message'].resetValue();

        self.newMessageWindowControl.removeClass('ow_open');
        self.newMessageWindowControl.removeClass('ow_active');

        var storage = OWMailbox.getStorage();
        storage.removeItem('mailbox.new_message_form_opened');
        storage.removeItem('mailbox.new_message_form_opponent_id');
        storage.removeItem('mailbox.new_message_form_opponent_info');
        storage.removeItem('mailbox.new_message_form_subject');
        storage.removeItem('mailbox.new_message_form_message');

        OW.trigger('mailbox.new_message_form_closed');
        OW.removeScroll( $('#userFieldAutocompleteControl') );
    };

    this.minimizeForm = function() {
        self.newMessageWindowControl.removeClass('ow_active');
        self.newMessageWindowControl.addClass('ow_chat_dialog_active');

        var storage = OWMailbox.getStorage();
        storage.setItem('mailbox.new_message_form_opened', 0);

        OW.trigger('mailbox.new_message_form_minimized');
    };

    self.deleteBtn.bind('click', function(){
        self.form.elements['opponentId'].resetValue();
        self.form.elements['opponentId'].focus();
    });
    self.newMessageBtn.bind('click', function(){
        OW.trigger('mailbox.open_new_message_form');
    });
    self.minimizeBtn.bind('click', this.minimize);
    self.unselectedCapMinimizeBtn.bind('click', this.minimize);

    self.unselectedCapCloseBtn.bind('click', this.close);
    self.closeBtn.bind('click', this.close);

    $('.newMessageWindowSubjectInputControl').keyup(function(ev){

        var storage = OWMailbox.getStorage();
        storage.setItem('mailbox.new_message_form_subject', $(this).val());

    });

    $('.newMessageWindowMessageInputControl').keyup(function(ev){

        if (ev.which === 13 && !ev.ctrlKey && !ev.shiftKey) {
            ev.preventDefault();
            return false;
        }

        var storage = OWMailbox.getStorage();
        storage.setItem('mailbox.new_message_form_message', $(this).val());
    });

//
//    $(self.form.elements['subject'].input).bind('blur.invitation', {formElement:self.form.elements['subject']},
//        function(e){
//            el = $(this);
//            if( el.val() == '' || el.val() == e.data.formElement.invitationString){
//                el.addClass('invitation');
//                el.val(e.data.formElement.invitationString);
//            }
//            else{
//                el.unbind('focus.invitation').unbind('blur.invitation');
//            }
//        });

//    $(self.form.elements['message'].input).bind('blur.invitation', {formElement:self.form.elements['message']},
//        function(e){
//            el = $(this);
//            if( el.val() == '' || el.val() == e.data.formElement.invitationString){
//                el.addClass('invitation');
//                el.val(e.data.formElement.invitationString);
//            }
//            else{
//                el.unbind('focus.invitation').unbind('blur.invitation');
//            }
//        });

    // Global Binds

    OW.bind('mailbox.open_new_message_form', function(data){
        self.openForm(data);
    });

    OW.bind('mailbox.open_new_message_form_minimized', function(data) {
        self.openFormMinimized(data);
    });

    OW.bind('mailbox.close_new_message_form', function(){
        self.closeForm();
    });

    OW.bind('mailbox.minimize_new_message_form', function(){
        self.minimizeForm();
    });
};

OWMailbox.NewMessageForm.restoreForm = function(){
    var storage = OWMailbox.getStorage();

    var newMessageFormOpened = storage.getItem('mailbox.new_message_form_opened');
    if (typeof newMessageFormOpened != 'undefined' && newMessageFormOpened != null){
        if (newMessageFormOpened == "1"){
            OW.trigger('mailbox.open_new_message_form');
        }
        else{
            OW.trigger('mailbox.open_new_message_form_minimized');
        }
    }

    var opponentInfo = storage.getItem('mailbox.new_message_form_opponent_info');
    if (typeof opponentInfo != 'undefined' && opponentInfo != null){
        owForms['mailbox-new-message-form'].elements['opponentId'].setValue(JSON.parse(opponentInfo));
    }
    else{
        var opponentId = storage.getItem('mailbox.new_message_form_opponent_id');
        if (typeof opponentId != 'undefined' && opponentId != null){
            owForms['mailbox-new-message-form'].elements['opponentId'].setValue(opponentId);
        }

    }

    var subject = storage.getItem('mailbox.new_message_form_subject');
    if (typeof subject != 'undefined' && subject != null){
        owForms['mailbox-new-message-form'].elements['subject'].setValue(subject);
    }

    var message = storage.getItem('mailbox.new_message_form_message');
    if (typeof message != 'undefined' && message != null){
        owForms['mailbox-new-message-form'].elements['message'].setValue(message);
    }

}

OW_MailboxConsole = function( itemKey, params ){
    var self = this;
    var listLoaded = false;

    self.model = OW.Console.getData(itemKey);
    var list = OW.Console.getItem(itemKey);
    var counter = new OW_DataModel();

    counter.addObserver(this);

    this.onDataChange = function( data ){
        var counterNumber = 0,
            newCount = data.get('counter.new');
        counterNumber = newCount > 0 ? newCount : data.get('counter.all');

        list.setCounter(counterNumber, newCount > 0);

        if ( counterNumber > 0 ){
            list.showItem();
        }
    };

    this.setCounterData = function( data ){
        var counterNumber = 0,
            newCount = data.new;
        counterNumber = newCount > 0 ? newCount : data.all;

        list.setCounter(counterNumber, newCount > 0);

        if ( counterNumber > 0 ){
            list.showItem();
        }
    };

    list.onHide = function(){
        list.setCounter(counter.get('all'), false);
        self.model.set('counter', counter.get());
    };

    list.onShow = function(){
        if ( params.issetMails == false && counter.get('all') <= 0 ){
            this.showNoContent();

            return;
        }

        this.loadList();
    };

    self.model.addObserver(function(){
        if ( !list.opened ){
            counter.set(self.model.get('counter'));
        }
    });

    this.updateCounter = function(conversation){

        var markedUnreadNotViewedConversations = OW.Mailbox.conversationsCollection.where({conversationViewed: false});
        var markedUnreadConversations = OW.Mailbox.conversationsCollection.where({conversationRead: 0});

        //var all = markedUnreadConversations.length;
        //if (OW.Mailbox.markedUnreadConversationList.length > markedUnreadConversations.length){
        //    all = OW.Mailbox.markedUnreadConversationList.length;
        //}
        var all = OW.Mailbox.markedUnreadConversationList.length;

        var data = {'new': markedUnreadNotViewedConversations.length, 'all': all};
//        this.setCounterData( data );
        this.model.set('counter', data, true);
    }

    OW.bind('mailbox.application_started', function(){
        OW.Mailbox.conversationsCollection.on('add', self.updateCounter, self);
        OW.Mailbox.conversationsCollection.on('change', self.updateCounter, self);

        self.updateCounter();
    });

    this.sendMessageBtn = $('#mailboxConsoleListSendMessageBtn');

    this.sendMessageBtn.bind('click', function(){
        OW.trigger('mailbox.open_new_message_form');
        OW.Console.getItem('mailbox').hideContent();
    });

}

OW.MailboxConsole = null;

var SearchField = function( id, name ){

    var self = this;
    var formElement = new OwFormElement(id, name);
    formElement.handler = null;

    formElement.setHandler = function(obj){
        formElement.handler = obj;
    }

    $(formElement.input).keydown(function(ev){

        if (ev.which === 13 && !ev.ctrlKey && !ev.shiftKey) {
            ev.preventDefault();

            return false;
        }
    });

    $(formElement.input).keyup(function(ev){
        if (this.value.length === 0)
            $(this).addClass("chat_empty_search_box");
        else
            $(this).removeClass("chat_empty_search_box");
        if (ev.which === 13 && !ev.ctrlKey && !ev.shiftKey) {
            ev.preventDefault();

            return false;
        }
        if (formElement.handler)
            formElement.handler.updateList($(this).val());
    });

    $('#'+name+'_close_btn_search').click(function(){
        $(formElement.input).val('');
        $(formElement.input).focus().addClass("chat_empty_search_box");

        formElement.handler.updateList($(formElement.input).val());
        $('#mailboxConvOptionSelectAll').prop('checked', false);
    });

    return formElement;
}
var MailboxUserField = function( id, name ){
    var self = this;

    var formElement = new OwFormElement(id, name);

    var textFormElement = new OwFormElement('mailbox_new_message_user', 'mailbox_new_message_user');
//    if( invitationString ){
//        addInvitationBeh(textFormElement, invitationString);
//        $(textFormElement.input).bind('blur', {formElement:textFormElement},
//            function(e){
//                el = $(this);
//                if( el.val() == '' || el.val() == e.data.formElement.invitationString){
//                    el.addClass('invitation');
//                    el.val(e.data.formElement.invitationString);
//                }
//
//            });
//    }

    this.contacts = {};
    this.inputControl = $('.userFieldInputControl');
    this.autocompleteControl = $('#userFieldAutocompleteControl');
    this.userList = $('#userFieldUserList');
    this.userListItemPrototype = $('#userFieldUserListItemPrototype');
    this.syncing = false;

    $(document).click(function( e ){
        if ( !$(e.target).is(':visible') ){
            return;
        }

        var isContent = self.autocompleteControl.find(e.target).length;
        if ( !isContent ){
            self.autocompleteControl.hide();
        }
    });

    this.addItem = function( data ) {

        if (typeof this.contacts[data.get('opponentId')] != 'undefined'){
            return $('#userFieldUserListItem-'+data.get('opponentId'));
        }

        var item = $('#userFieldUserListItemPrototype').clone();
        $('#userFieldUserListItemAvatarUrl', item).attr('src', data.get('avatarUrl'));
        $('#userFieldUserListItemUsername', item).html(data.get('displayName'));
        item.attr('id', 'userFieldUserListItem-'+data.get('opponentId'));
        item.data(data);

        item.click(function(){
            var data = $(this).data();
            formElement.setValue(data);
            self.reset();
        });

        this.userList.append(item);

        this.contacts[data.get('opponentId')] = data;

        return item;
    };

    this.reset = function(){
        self.autocompleteControl.hide();
        $.each(self.contacts, function(id, contact){
            self.removeItem(contact.opponentId);
        });
    }

    formElement.setValue = function(value){

        var storage = OWMailbox.getStorage();

        if (value == ''){
            $(formElement.input).val(value);
            storage.setItem('mailbox.new_message_form_opponent_id', null);
            storage.setItem('mailbox.new_message_form_opponent_info', null);
        }
        else{
            var user = null;
            var opponentId = parseInt(value);
            if (opponentId > 0){
                user = OW.Mailbox.usersCollection.findWhere({opponentId: opponentId});
            }
            else{
                user = value;
            }

            if (user.hasOwnProperty('opponentId')){
                $(formElement.input).val(user.opponentId);
                $(textFormElement.input).val(user.displayName);
                OW.Mailbox.newMessageFormController.setUser(user);

                storage.setItem('mailbox.new_message_form_opponent_id', user.opponentId);
                storage.setItem('mailbox.new_message_form_opponent_info', JSON.stringify(user));
                return;
            }
            else{
                if (user){
                    $(formElement.input).val(user.get('opponentId'));
                    $(textFormElement.input).val(user.get('displayName'));
                    OW.Mailbox.newMessageFormController.setUser(user.attributes);

                    storage.setItem('mailbox.new_message_form_opponent_id', user.get('opponentId'));
                    storage.setItem('mailbox.new_message_form_opponent_info', JSON.stringify(user.attributes));
                    return;
                }
            }
        }
    }

    formElement.resetValue = function(){
        var storage = OWMailbox.getStorage();

        $(formElement.input).val('');
        $(textFormElement.input).val('');
        OW.Mailbox.newMessageFormController.resetUser();

        storage.removeItem('mailbox.new_message_form_opponent_id');
        storage.removeItem('mailbox.new_message_form_opponent_info');
    }

    formElement.focus = function(){
        $(textFormElement.input).focus();
    }

    this.removeItem = function( opponentId ){
        $('#userFieldUserListItem-'+opponentId).remove();
        delete self.contacts[opponentId];
//        OW.updateScroll(self.autocompleteControl);
    };

    this.updateList = function(name){
        var self = this;
        var contactList = this.contacts;

        $('#userFieldUserListItem-notfound').hide();
//        OW.removeScroll(self.autocompleteControl);

        if (name == ''){

            self.reset();
        }
        else{

            if (name.length < 2){
                return;
            }

            self.autocompleteControl.show();

            var expr = new RegExp(OW.escapeRegExp(name) , 'i');

            $.each(contactList, function(id, contact){
                if (!expr.test(contact.get('displayName'))){
                    self.removeItem(contact.get('opponentId'));
                }
                else{
                    $('#userFieldUserListItem-'+contact.get('opponentId')).show();
                }
            });

            _.each(OW.Mailbox.usersCollection.models, function(user){

                if (expr.test(user.get('displayName'))){
                    if (!contactList.hasOwnProperty(user.get('opponentId'))){
                        var item = self.addItem(user);
                        item.show();
                    }
                }
            });

            if (!self.syncing){
                self.syncing = true;
                $.getJSON(OWMailbox.userSearchResponderUrl, {term: name, idList: {}, context: 'user'}, function( data ) {

                    _.each(data, function(user){
                        var usr = new MAILBOX_User(user.data);
                        var item = self.addItem(usr);
                        item.show();
                    });

                    var size = 0;

                    for (key in self.contacts) {
                        if (self.contacts.hasOwnProperty(key)) size++;
                    }

                    //TODO show not found only when it really not found on server after ajax call
                    if (size == 0){
                        $('#userFieldUserListItem-notfound').show();
                    }
                    else{
                        $('#userFieldUserListItem-notfound').hide();
//                        if (size > 8){
//                            if (self.autocompleteControl.hasClass('ow_scrollable')){
//                                OW.updateScroll($('#userFieldAutocompleteControl'));
//                            }
//                            else{
//                                OW.addScroll($('#userFieldAutocompleteControl'));
//                            }
//                        }
                    }

                    self.syncing = false;
                });
            }

        }
    }

    $("#userFieldUserList").mouseover(function(){
        $("#userFieldUserList li.userFieldUserListItem").removeClass("selected");
    });

//    $('.ow_mailchat_autocomplete_inner').on('scroll', function(){
//
//        if ( $('.ow_mailchat_autocomplete_inner').scrollTop() + $('#userFieldUserList').position().top == 0 )
//        {
//            console.log('load more');
//        }
//    });

    $(textFormElement.input).keydown(function(ev){

        if (ev.keyCode == 13) {
            if ($("#userFieldUserList").is(":visible")) {

                $('#userFieldUserList li.selected').click();

            } else {
                self.autocompleteControl.show();
            }

            ev.preventDefault();
            return false;
        }

        if (ev.keyCode == 38){
            var selected = $("#userFieldUserList li.userFieldUserListItem.selected");
            $("#userFieldUserList li.userFieldUserListItem").removeClass("selected");

            if (selected.prev().length == 0) {
                selected.siblings().last().addClass("selected");
            } else {
                selected.prev().addClass("selected");
            }

            ev.preventDefault();
        }

        if (ev.keyCode == 40) {
            var selected = $("#userFieldUserList li.userFieldUserListItem.selected");

            if (selected.length == 0){
                selected = $("#userFieldUserList li.userFieldUserListItem");
                selected = $(selected[0]);
                selected.addClass("selected");
                return;
            }

            $("#userFieldUserList li.userFieldUserListItem").removeClass("selected");
            if (selected.next().length == 0) {
                var first = $("#userFieldUserList li.userFieldUserListItem");
                first = $(first[0]);
                first.addClass("selected");
            } else {
                selected.next().addClass("selected");
            }

            ev.preventDefault();
        }
    });

    $(textFormElement.input).keyup(function(ev){

        if (ev.which === 13 && !ev.ctrlKey && !ev.shiftKey) {
            ev.preventDefault();

            return false;
        }

        if (ev.which == 38 || ev.which == 40){
            return false;
        }

        self.updateList($(this).val());
    });

    return formElement;
}

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

    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE){
        string = string.replace(/'/g, '&#039;');
    }
    if (!noquotes){
        string = string.replace(/"/g, '&quot;');
    }
    string = string.replace(/\n/g, '<br />');
    return string;
}

function im_createCookie(name,value,days) {
    if (days) {
        var date = new Date();
        date.setTime(date.getTime()+(days*24*60*60*1000));
        var expires = "; expires="+date.toGMTString();
    }
    else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
}

function im_readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if ( c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function im_eraseCookie(name) {
    im_createCookie(name,"",-1);
}

//if (typeof window.owFileAttachments == 'undefined'){
//    $.getScript('/ow_static/plugins/base/js/attachments.js');
//}

$(function(){

    $.fn.extend({
        autolink: function(options){
            var exp =  new RegExp("(\\b(https?|ftp|file)://[-A-Z0-9+&amp;@#\\/%?=~_|!:,.;]*[-A-Z0-9+&amp;@#\\/%=~_|])(?![^<>]*>(?:(?!<\/?a\b).)*<\/a>)(?![^<>]*>(?:(?!<\/?img\b).)*)", "ig");
            /* Credit for the regex above goes to @elijahmanor on Twitter, so follow that awesome guy! */

            this.each( function(id, item){

                if ($(item).html() == ""){
                    return 1;
                }
                var text = $(item).html().replace('&nbsp;',' ').replace(exp,"<a href='$1' target='_blank'>$1</a>");
                $(item).html( text );

            });

            return this;
        },

        dialogAutosize: function(options, action){

            var self = this;

            this.adjust = function(){
                var textWidth = this.val().width(this.css('font'));
                var lines = this.val().split("\n");
                var linesLength = 1;

                if (textWidth > this.width()){
                    linesLength = Math.ceil( textWidth / this.width() );
                    if (linesLength < lines.length){
                        linesLength = lines.length;
                    }
                }
                else{
                    linesLength = lines.length;
                }

                this.attr('rows', linesLength);
                var offset = 0;
                for (var i=1; i<=linesLength; i++){

                    if (i == 2){
                        offset = offset + 12;
                        $('.ow_chat_message', options.control).removeClass('scroll');
                    }
                    else{
                        if (i >= 3 && i <= 6){
                            offset = offset + 17;
                            $('.ow_chat_message', options.control).removeClass('scroll');
                        }
                        else{
                            if (i > 6){
                                $('.ow_chat_message', options.control).addClass('scroll');
                                offset = 80;
                                break;
                            }
                        }
                    }
                }
                this.css('height', options.textareaHeight + offset);
                options.messageListControl.height( options.dialogWindowHeight - offset );
                options.scrollDialog();
            }

            if (!action){

                this.adjust();


                this.bind('paste', function(e){
                    var element = this;
                    setTimeout(function(){
                        self.adjust();
                    }, 50);
                });

                this.bind('cut', function(e){
                    var element = this;
                    setTimeout(function(){
                        self.adjust();
                    }, 50);
                });

                this.keypress(function(ev){
                    self.adjust();
                });

                this.keyup(function (ev) {
                    if (ev.which === 13 && ev.shiftKey){
                        self.adjust();
                    }

                    if (ev.which === 8){
                        self.adjust();
                    }
                });

                this.keydown(function (ev) {

                    if (ev.which === 13 && !ev.shiftKey){
                        ev.preventDefault();

                        var body = $(this).val();

                        if ( $.trim(body) == '')
                            return;

                        options.sendMessage(body);

                        if (options.dialogWindowHeight > 0){
                            options.messageListControl.height( options.dialogWindowHeight );
                        }

                        $(this).attr('rows', 1);
                        $(this).css('height', options.textareaHeight);

                        options.scrollDialog();
                    }
                    else if (ev.which === 13 && ev.shiftKey){
                        self.adjust();
                    }
                    else if (ev.which === 8){
                        self.adjust();
                    }
                    else{
                        self.adjust();
                    }
                });
            }
            else{
                if (action == 'adjust'){
                    this.adjust();
                }
            }
        }
    });

    //AudioPlayer.setup(OWMailbox.soundSwfUrl, { width: 100 });

    onerror = function(e) {
        return false;
    };

    onunload = function() {
        return false;
    };

    OW.bind('mailbox.ready', function(){
        OW.trigger('mailbox.application_started');
        OW.Mailbox.appStarted = true;
        $(document).on("click", ".ow_chat_block_main .ow_chat_list .jspPane ul li", function () {
            $(".no_messages_yet").hide();
        });
    });

    OW.bind('base.online_now_click',
        function(userId){

            if (parseInt(userId) != OWMailbox.userDetails.userId){
                $('#ow_chat_now_'+userId).addClass('ow_hidden');
                $('#ow_preloader_content_'+userId).removeClass('ow_hidden');

                $.post(OWMailbox.openDialogResponderUrl, {
                    userId: userId
                }, function(data){

                    if ( typeof data != 'undefined'){
                        if ( typeof data['warning'] != 'undefined' && data['warning'] ){
                            OW.message(data['message'], data['type']);
                            return;
                        }
                        else{
                            if (data['use_chat'] && data['use_chat'] == 'promoted'){
                                OW.Mailbox.contactManagerView.showPromotion();
                            }
                            else{
                                OW.Mailbox.usersCollection.add(data);
                                OW.trigger('mailbox.open_dialog', {convId: data['convId'], opponentId: data['opponentId'], mode: 'chat'});
                            }
                        }
                    }
                }, 'json').complete(function(){

                    $('#ow_chat_now_'+userId).removeClass('ow_hidden');
                    $('#ow_preloader_content_'+userId).addClass('ow_hidden');
                });
            }
        });

//    OW.bind('base.sign_out_click',
//        function(userId){
//        });

});


function checkSeenMessageData(opponentUnreadMessageData, opponentLastDataId, opponentId) {
    $.each(opponentUnreadMessageData, function (recipientId, item) {
        if(opponentId == -1 || opponentId == recipientId) {
            var allMessages = $("#main_tab_contact_" + recipientId + " div[id^='messageItem'],#conversationContainer div[id^='messageItem'], #conversationContainer .conversationMessageGroup div[id^='messageItem']");
            for (var i = 0; i < allMessages.length; i++) {
                var element = allMessages[i];
                var selfMessages = $(element).find('.ow_dialog_item.even');
                if (selfMessages && selfMessages.length > 0) {
                    var id = element.id;
                    id = id.replace("messageItem", "");
                    if (jQuery.inArray(id, item) == -1 && (opponentLastDataId[recipientId] != -1 &&  id <= opponentLastDataId[recipientId])) {
                        $(element).addClass("message_seen");
                    } else {
                        //do nothing (not seen)
                    }
                }
            }
        }
    });
}


var last_mailbox_search_q = '';
function add_mailbox_search_content_events(ajax_url) {

    $('#conversation_search').on('change keydown paste input', function () {
        q = $(this)[0].value;
        var listSelector = '#messagesContainerControl #conversationContainer';
        var loaded_messages = $(listSelector);
        var found_messages =  $(listSelector).next(".ow_mailbox_right").find(".found_messages");
        var pre_loader = $(found_messages).siblings(".ow_preloader");
        var no_content = $(found_messages).siblings(".ow_nocontent");
        OW.removeScroll(found_messages);

        if(q === last_mailbox_search_q){
            return;
        }
        if(last_mailbox_search_q ===''){
            $(listSelector + ' > .ow_list_item_with_image').hide();
            $(listSelector + ' > .ow_nocontent').hide();
            $(listSelector + ' > .ow_preloader').show();
            $(found_messages).hide();
        }
        last_mailbox_search_q = q;
        if (q === '') {
            $(loaded_messages).show();
            $(found_messages).hide();
            $(".ow_mailbox_right_preloading").hide();
            pre_loader.hide();
            no_content.hide();

        } else {
            $(loaded_messages).hide();
            $(found_messages).hide();
            $(no_content).hide();
            $(pre_loader).show();
            $(listSelector + ' > .ow_preloader').show();
            $.ajax({
                type: "POST",
                dataType: "json",
                data: {
                    'q': q
                },
                url: ajax_url,
                success:
                    function (response) {
                        $(no_content).hide();
                        $(pre_loader).hide();
                        var data = response;//jQuery.parseJSON(response);
                        if (data.result === 'ok') {
                            if (data.q !== last_mailbox_search_q)
                                return;
                            if(data.results.length === 0){
                                $(no_content).show();
                            } else {
                                var html = '';
                                $.each(data.results, function (index, value) {
                                    html += '<div class="clearfix message message_seen" id="messageItem138" style="border-top: 1px solid rgb(238, 238, 238);">' +
                                        '<div class="clearfix" onclick="location.href = ' + "'" + value.conversationUrl + "'" + ' ">' +
                                        '<span id="avatar" href="' + value.opponentUrl + '" target="_blank" class="ow_chat_in_item_author_href ow_chat_in_item_photo_wrap" title="admin">' +
                                        '<span style="margin: 5px;">' +
                                        '<span class="ow_chat_item_photo">' +
                                        '<span class="ow_chat_in_item_photo">' +
                                        '<img width="32px" height="32px" id="contactItemAvatarUrl" src="' + value.avatarUrl + '">' +
                                        '</span>' +
                                        '<span>' + value.opponentName +
                                        '</span>' +
                                        '</span>' +
                                        '</span>' +
                                        '</span>' +
                                        '<div id="message_item" class="ow_dialog_item even">' +
                                        '<div class="ow_dialog_in_item " id="dialogMessageWrapper">' +
                                        '<div class="ow_chat_reply" id="replyMessage" style="background: rgba(130, 130, 130, 0.3) !important;padding:  5px;margin: 5px;display: none !important;">' +
                                        '<div class="sender"></div>' +
                                        '<div class="msg">' +
                                        '<p class="msg"></p>' +
                                        '</div>' +
                                        '</div>' +
                                        '<div id="select_link"><p id="dialogMessageText" dir="auto" emoji="3">' + value.text + '</p></div>' +
                                        '</div>' +
                                        '<i></i>' +
                                        '</div>' +
                                        '</div>' +
                                        '</div>';
                                });
                                html = "<h1>" + OW.getLanguageText('mailbox', 'results_for') + " <i>" +  q + "</i></h1>" + html;
                                $(found_messages).html(html);
                                $(found_messages).show();
                                $(pre_loader).hide();
                                OW.addScroll(found_messages);
                            }
                        }
                    }
            })
        }
    });
}