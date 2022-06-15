var OwAvatarField = function( id, name, params ){
    var formElement = new OwFormElement(id, name);
    var $preview = $(formElement.input).closest(".ow_avatar_field").find(".ow_avatar_field_preview");
    var $img = $preview.find("img");

    $preview.click(function(){
        $(formElement.input).trigger('click');
    });

    $preview.find("span").click(function(e){
        e.stopPropagation();

        $img.attr("src", "");
        formElement.resetValue();
        $preview.hide();
        $(formElement.input).val("").show();

        // delete a tmp avatar
        if (!$("#" + id + "_preload_avatar").length) {
            $.ajax({
                url: params.ajaxResponder,
                type: 'POST',
                data: { ajaxFunc: 'ajaxDeleteImage' },
                dataType: 'json',
                success: function(data){ }
            });
        }

        $("#" + id + "_preload_avatar").remove();
    });

    $(formElement.input).change(function(e){
        document.avatarFloatBox = OW.ajaxFloatBox(
            "BASE_CMP_AvatarChange",
            { params : { step : 2, inputId : id, hideSteps: true, displayPreloader: true, changeUserAvatar:  params.changeUserAvatar} },
            { width : 749, title: OW.getLanguageText('base', 'avatar_change') }
        );
    });

    OW.bind('base.avatar_cropped', function(data){
        formElement.removeErrors();
        $(formElement.input).hide();
        formElement.setValue(data.url);
        $preview.show();

        var ts = new Date().getTime();
        $img.attr("src", data.url + "?" + ts);
        $("#" + id + "_preload_avatar").remove();
    });

    return formElement;
}