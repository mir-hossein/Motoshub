var selectedCount=0;
var forwardSelect = function( list, contextId,respondUrl,actionId,currentId,privacy,visibility,feedType,forwardDesType){
    this.list = list;
    this.actionId = actionId;
    this.respondUrl = respondUrl;
    this.resultList = [];
    this.$context = $('#'+contextId);
    this.currentId=currentId;
    this.privacy = privacy;
    this.visibility = visibility;
    this.feedType=feedType;
    this.forwardDesType=forwardDesType;
}

forwardSelect.prototype = {
    init: function(){
        var self = this;
        $.each( this.list,
            function(index, data){
                $('#'+data.linkId).click(
                    function(){
                        var ei = self.findIndex(data.entityId);
                        if( ei == null ){
                            self.resultList.push(data.entityId);
                            $(this).addClass('ow_mild_green');
                        }else{
                            self.resultList.splice(ei, 1);
                            $(this).removeClass('ow_mild_green');
                        }

                        var $countNode = $('div.count_label', self.$context);

                        if( $countNode.length > 0 )
                        {
                            $countNode.html($('input.count_label', self.$context).val().replace("#count#", self.resultList.length));
                        }

                        $( '.submit_cont input', self.$context ).val($('input.button_label').val().replace("#count#", self.resultList.length));

                    }
                );
            }
        );

        $('input.submit',this.$context).click(function(){
            self.submit();
        });
    },

    findIndex: function( value ){

        for( var i = 0; i < this.resultList.length; i++){
            if( value == this.resultList[i] ){
                return i;
            }
        }
        return null;
    },

    reset: function(){
        $('a.selected', this.$context).removeClass('selected');
        this.resultList = [];
    },

    submit: function(){
        var underSelf=this;
        if( underSelf.resultList.length == 0 )
        {
            if(underSelf.forwardDesType=='groups') {
                OW.warning(OW.getLanguageText('iisnewsfeedplus', 'group_select_empty_list_message'));
            }else{
                OW.warning(OW.getLanguageText('base', 'avatar_user_select_empty_list_message'));
            }
            return;
        }
        selectedCount=underSelf.resultList.length;
        forwardNewsfeedComponent.close();

        $.ajax({
            type: 'POST',
            url: underSelf.respondUrl,
            data: {"actionId":underSelf.actionId ,"sourceId": underSelf.currentId, "sendIdList": JSON.stringify(underSelf.resultList),"privacy":underSelf.privacy,"visibility":underSelf.visibility,"feedType":underSelf.feedType,"forwardType":underSelf.forwardDesType},
            dataType: 'json',
            complete: function (data) {
                if( data.status==200 )
                {
                    if(underSelf.forwardDesType=='groups') {
                        OW.info(OW.getLanguageText('iisnewsfeedplus', 'groups_invite_success_message', {count: selectedCount}));
                    }else{
                        OW.info(OW.getLanguageText('iisnewsfeedplus', 'users_forward_success_message', {count: selectedCount}));
                    }
                }

                else
                {
                    OW.error(OW.getLanguageText('iisnewsfeedplus', 'error_in_forward_progress'));
                }
            }
        });
    }
}
