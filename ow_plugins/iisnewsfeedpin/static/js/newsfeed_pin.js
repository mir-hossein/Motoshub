var IISNEWSFEEDPIN_PinItem = function (isPinned, autoId) {
    this.isPinned = isPinned;
    this.autoId = autoId;
};

IISNEWSFEEDPIN_PinItem.prototype =
    {
        construct: function (data) {
            this.$removePinAjaxURL = data.removeURL;
            this.$addPinAjaxURL = data.addURL;
            this.$entityId = data.entityId;
            this.$entityType = data.entityType;
            this.isPinnedPage = data.isPinnedPage;

            if (this.isPinned) {
                this.$pinBtn = $('#' + this.autoId + ' .iisnewsfeedpin_remove_pin');
                this.$pinBtn.click({self: this}, this.remove);
            } else {
                this.$pinBtn = $('#' + this.autoId + ' .iisnewsfeedpin_add_pin');
                this.$pinBtn.click({self: this}, this.pin);
            }
        },

        remove: function (event) {
            var self = event.data.self;
            var ajax_settings = {
                url: self.$removePinAjaxURL,
                type: 'POST',
                data: {className: $(this).attr('class')},
                dataType: 'json',
                success: function (data) {
                    if (data['error']) {
                        OW.error(data['msg']);
                    } else {
                        OW.info(data['msg']);
                        ow_iisnewsfeedpin_feed_list[self.autoId].isPinned = false;
                        var query = '#' + self.autoId;
                        $(query).removeClass('iisnewsfeedpin_pined_class');
                        if (self.isPinnedPage) {
                            $(query).hide();
                            window.hasPinned = false;
                            for (var index in window.ow_iisnewsfeedpin_feed_list) {
                                window.hasPinned = window.hasPinned || ow_iisnewsfeedpin_feed_list[index].isPinned;
                            }
                            if (!window.hasPinned) {
                                $('.ow_nocontent').show();
                                $('.owm_nocontent').show();
                            }
                        }
                        self.$pinBtn.removeClass('iisnewsfeedpin_remove_pin');
                        self.$pinBtn.addClass('iisnewsfeedpin_add_pin');
                        self.$pinBtn.html(data['button_value']);
                        self.$pinBtn.unbind("click");
                        self.$pinBtn.click({self: self}, self.pin);
                    }
                }
            };
            var jc = $.confirm($(this).data("confirm-msg"));

            jc.buttons.ok.action = function () {
                $.ajax(ajax_settings);
            }
        },
        pin: function (event) {
            var self = event.data.self;
            var ajax_settings = {
                url: self.$addPinAjaxURL,
                type: 'POST',
                data: {entityId: self.$entityId, entityType: self.$entityType},
                dataType: 'json',
                success: function (data) {
                    if (data['error']) {
                        OW.error(data['msg']);
                    } else {
                        OW.info(data['msg']);
                        $('#' + self.autoId).addClass('iisnewsfeedpin_pined_class');
                        self.$pinBtn.removeClass('iisnewsfeedpin_add_pin');
                        self.$pinBtn.addClass('iisnewsfeedpin_remove_pin');
                        self.$pinBtn.html(data['button_value']);
                        self.$pinBtn.unbind("click");
                        self.$pinBtn.click({self: self}, self.remove);
                        self.$isPinned = true;
                    }
                }
            };
            var jc = $.confirm($(this).data("confirm-msg"));
            jc.buttons.ok.action = function () {
                $.ajax(ajax_settings);
            }
        }
    };
window.ow_iisnewsfeedpin_feed_list = [];

var IISNEWSFEEDPIN_PinButton = function () {
    window.pinned = false;
    var pinId = '#IISNEWSFEEDPIN_Pin';
    $(pinId).click(
        function () {
            if (window.pinned === false) {
                $(pinId).removeClass("iisnewsfeedpin_pin");
                $(pinId).addClass("iisnewsfeedpin_unpin");
                window.pinned = true;
                $("#pin").val(true);
            } else {
                clear_pin();
            }
        }
    );
    var clear_pin = function () {
        $(pinId).removeClass("iisnewsfeedpin_unpin");
        $(pinId).addClass("iisnewsfeedpin_pin");
        window.pinned = false;
        $("#pin").val(false);
    };
    $('form[name = "newsfeed_update_status"]').submit(
        function (e) {
            clear_pin();
        }
    );
}