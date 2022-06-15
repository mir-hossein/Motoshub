/**
 * Upgraded to 4.7.1
 * By @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 */
//STATIC
//CKEditor OW functions
//window.htmlAreaDataDefaults = {"editorCss":"http:\/\/whuw-one\/dev\/social\/ow\/ow_static\/plugins\/base\/css\/htmlarea_editor.css","themeImagesUrl":"http:\/\/whuw-one\/dev\/social\/ow\/ow_static\/themes\/macabre\/images\/","imagesUrl":"http:\/\/whuw-one\/dev\/social\/ow\/base\/media-panel\/index\/pluginKey\/blog\/id\/__id__\/","labels":{"buttons":{"bold":"Bold","italic":"Italic","underline":"Underline","orderedlist":"Insert Ordered List","unorderedlist":"Insert Unordered List","link":"Insert Link","image":"Insert Image","video":"Insert Video","html":"Insert HTML","more":"More","switchHtml":"Show\/Hide HTML Source View"},"common":{"buttonAdd":"Add","buttonInsert":"Insert","videoHeadLabel":"Insert video","htmlHeadLabel":"Insert HTML","htmlTextareaLabel":"Your html code:","videoTextareaLabel":"Embed your video code here:","linkTextLabel":"Text to display:","linkUrlLabel":"To what URL should this link go:","linkNewWindowLabel":"Open in new window"},"messages":{"imagesEmptyFields":"base+ws_image_empty_fields","linkEmptyFields":"Please fill `label` and `url` fields to insert the link","videoEmptyField":"Enter video code please"}},"buttonCode":"<span class=\"ow_button ow_ic_add mn_submit\"><span><input type=\"button\"  value=\"#label#\" class=\"ow_ic_add mn_submit\"  \/><\/span><\/span>","rtl":false};
//if (!window.htmlAreaData) {
//	window.htmlAreaData=window.htmlAreaDataDefaults;
//}
function iisadvanceeditor_get_ow_button_label(key,defaultLabel){
if (window.htmlAreaData && window.htmlAreaData.labels && window.htmlAreaData.labels.buttons && window.htmlAreaData.labels.buttons[key]) {
	return window.htmlAreaData.labels.buttons[key];
}
return defaultLabel;
}
CKEDITOR.plugins.add('ow_image',{
	init:function(editor){
		editor.addCommand('ow_image',{
			exec:function(editor){
				editor.element.$.jhtmlareaObject.image();
			}
		});
		editor.ui.addButton('ow_image',{
		label:iisadvanceeditor_get_ow_button_label('image', 'Insert Image'),
		command:'ow_image'
		});
	}
});
CKEDITOR.plugins.add('ow_video',{
	init:function(editor){
		editor.addCommand('ow_video',{
			exec:function(editor){
				editor.element.$.jhtmlareaObject.video();
			}
		});
		editor.ui.addButton('ow_video',{
		label:iisadvanceeditor_get_ow_button_label('video', 'Insert Video'),
		command:'ow_video'
		});
	}
});
CKEDITOR.plugins.add('ow_more',{
	init:function(editor){
		editor.addCommand('ow_more',{
			exec:function(editor){
				editor.element.$.jhtmlareaObject.more();
			}
		});
		editor.ui.addButton('ow_more',{
		label:iisadvanceeditor_get_ow_button_label('more', 'More'),
		command:'ow_more'
		});
	}
});

//override htmlarea
$.fn.htmlarea = function(opts) {
return this.each(function() {
    new jHtmlAreaCKWrapper(this, opts);
});
};
var jHtmlAreaCKWrapper = window.jHtmlAreaCKWrapper = function(elem, options) {
if (elem.jquery) {
    return jHtmlAreaCKWrapper(elem[0], options);
}
if (elem.jhtmlareaObject) {
    return elem.jhtmlareaObject;
} else {
    return new jHtmlAreaCKWrapper.fn.init(elem, options);
}
};
jHtmlAreaCKWrapper.fn=jHtmlAreaCKWrapper.prototype={
	index:0,
	init: function(elem, options) {
		elem.jhtmlareaObject = this;
		elem.htmlareaFocus = function () {console.log('focus');};
		elem.htmlareaRefresh = function () {console.log('refresh');};
		this.elem=elem;
		this.initCK(elem, options);
	},
	initCK: function(elem, options) {
		var element=$(elem);
		var element_id=element.prop('id');
		if (element_id==undefined || element_id.length==0) {
			element_id='SITE-CKEditor-'+this.index;
			this.index++;
			element.attr('id',element_id);
		}

		//adjust toolbar to match desired options
		var config=window.CKCONFIG;
		/*if (options.toolbar) {
			config.toolbar+='Dynamic';
			var toolbarName='toolbar_'+config.toolbar;
			if (config[toolbarName]===undefined) {
				config[toolbarName]=[[]];
			}
			var configToolbar=config[toolbarName][0];
			//config[toolbarName].push([]);
			//var configToolbar=config[toolbarName][1];

			var index=$.inArray('orderedlist',options.toolbar);
			if (index>-1) {
				configToolbar.push('NumberedList');
			}
			index=$.inArray('unorderedlist',options.toolbar);
			if (index>-1) {
				configToolbar.push('BulletedList');
			}
			index=$.inArray('more',options.toolbar);
			if (index>-1) {
				configToolbar.push('ow_more');
			}
			index=$.inArray('link',options.toolbar);
			if (index>-1) {
				configToolbar.push('Link');
			}
			index=$.inArray('image',options.toolbar);
			if (index>-1) {
				configToolbar.push('ow_image');
			}
			index=$.inArray('video',options.toolbar);
			if (index>-1) {
				configToolbar.push('ow_video');
			}
			index=$.inArray('switchHtml',options.toolbar);
			if (index>-1) {
				configToolbar.push('Source');
			}
			configToolbar.push('Undo');
		}*/
        config.toolbar = [
            { name: 'firstrow', items: [ 'Source', '-', 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo', '-', 'Find', 'Replace'] },
            '/',
            { name: 'secondrow', items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', '-', 'Outdent', 'Indent', '-', 'NumberedList', 'BulletedList', '-', 'Blockquote', '-', 'Link', 'Unlink', '-', 'ow_image', 'ow_video', 'ow_more', '-', 'Table', 'Smiley', 'SpecialChar' ] },
            '/',
            { name: 'thirdrow', items: [ 'FontSize', '-', 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'TextColor', 'BGColor', '-', 'CopyFormatting', 'RemoveFormat' ] }
        ];

        // config.language = 'fa';
        config.skin = 'office2013';

        if(mobileAndTabletcheck()) {
            config.removeButtons = 'ow_video,PasteText,PasteFromWord,Paste';
        }else {
            config.removeButtons = 'ow_video';
        }

        // remove disable buttons
        jQuery.each( config.disable, function( index, value ) {
            config.removeButtons = config.removeButtons+','+value;
        });
		//init CK
		this.editor = CKEDITOR.replace( element_id, config);
		var editor=this.editor;

		//too late 
		//this.editor.on('blur',function(e){ editor.updateElement(); /*element.val(editor.getData());*/console.log(element.val())},null,null,1);
		iisadvanceeditor_form_refresh_before_submit(element.parents('form').get(0),editor);
	},
        insertImage: function(params){
            //this.restoreCaret();
            if( params.preview ){
                $html = $('<div><a href="'+params.src+'" target="_blank"><img style="padding:5px;" src="'+params.src+'" /></a></div>');
            }else{
                $html = $('<div><img style="padding:5px;" src="'+params.src+'" /></div>');
            }

            $img = $('img', $html);
            if( params.align ){
                $img.css({
                    'float':params.align
                });
            }
            if( params.resize ){
                $img.css({
                    'width':params.resize
                });
            }
            this.pasteHTML($html.html());
            this.tempFB.close();
        },
        pasteHTML: function(html) {
            this.editor.insertHtml( html, 'unfiltered_html');
	},
        image: function(){
            this.tempFB = new OW_FloatBox({
                $title: iisadvanceeditor_get_ow_button_label('image', 'Insert Image'),
                width: '600px',
                height: '100%',
                $contents: '<center><iframe style="min-width: 550px; min-height: 500px;" src="'+this.editor.config.ow_imagesUrl.replace('__id__', this.editor.element.getAttribute('id'))+'"></iframe></center>'
            });
        },
        video: function(){
            //this.saveCaret();
            var self = this;
            var $contents = $('<div>'+(window.htmlAreaData.labels.common.videoTextareaLabel || '') +'<br /><textarea name="code" style="height:200px;"></textarea><br /><br /></div>');
            var buttonCode = window.htmlAreaData.buttonCode;
            $contents.append('<div style="text-align:center;">'+buttonCode.replace('#label#', window.htmlAreaData.labels.common.buttonInsert)+'</div>');
            $('input[type=button].mn_submit', $contents).click(function(){
                self.insertVideo({
                    code:$('textarea[name=code]', $contents).val()
                })
            });

            this.tempFB = new OW_FloatBox({
                $title: window.htmlAreaData.labels.common.videoHeadLabel || '',
                width: '600px',
                height: '400px',
                $contents: $contents
            });
        },

        insertVideo: function(params){
            //this.restoreCaret();
            if( !params || !params.code ){
                OW.error(window.htmlAreaData.labels.messages.videoEmptyField);
                return;
            }
            $html = $('<div><span class="ow_ws_video"></span></div>');
            $('span', $html).append(params.code);
            this.pasteHTML($html.html());
            this.tempFB.close();
        },
        more: function(){
            $html = $('<div></div>');
            $html.append(document.createTextNode('<!--more-->'));
            this.pasteHTML($html.html());
        }
};
jHtmlAreaCKWrapper.fn.init.prototype = jHtmlAreaCKWrapper.fn;

window.mobileAndTabletcheck = function() {
    var check = false;
    (function(a){if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|android|ipad|playbook|silk/i.test(a)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0,4))) check = true;})(navigator.userAgent||navigator.vendor||window.opera);
    return check;
};

//element already on the page
function iisadvanceeditor_textarea_attach(elem){
	var element=$(elem);
	if (elem.htmlarea==undefined) {
		elem.htmlarea = function(){ element.htmlarea( {'size':300} );};
		elem.htmlarea();
	}
}
function iisadvanceeditor_textarea_check(){
	$('textarea#post_body,textarea[name=body],textarea[name=intro],textarea[name=maintenance_text]').each(function(index){
		iisadvanceeditor_textarea_attach(this);
	/*
		//if (element.htmlarea) {
			var element_id=element.prop('id');
			if (element_id==undefined) {
				element_id='SITE-CKEditor-'+index;
				element.attr('id',element_id);
			}
			var editor = CKEDITOR.replace( element_id, CKCONFIG );
			new jHtmlAreaCKWrapper(\$('#'+element_id).get(0));
			element.get(0).jhtmlareaObject.editor = editor;
		//}
	*/
	});
}

//fix for CK for ajax submitting before CK update happens
function iisadvanceeditor_form_refresh_before_submit(form,editor){
	if (form)
	for (var name in window.owForms){
		var thisForm=window.owForms[name]
		if (thisForm.form && thisForm.form===form) {
			$(thisForm.form).unbind('submit').bind('submit',{form:thisForm},function(e){
				editor.updateElement();
				return e.data.form.submitForm();
			});
			break;
		}
	}
}

//Drag and drop:customizing pages popup
if (window.OW_Components_DragAndDropAjaxHandler) {
	OW_Components_DragAndDropAjaxHandler.prototype.original_loadSettings=OW_Components_DragAndDropAjaxHandler.prototype.loadSettings;
	OW_Components_DragAndDropAjaxHandler.prototype.loadSettings=function(id, successFunction) {
		this.original_loadSettings(id, function(settingMarkup){successFunction(settingMarkup);iisadvanceeditor_load_after_loadSettings(id); });
	};
	function iisadvanceeditor_load_after_loadSettings(id){
		$('.floatbox_container textarea').each(function(index){
			var element=$(this);
			iisadvanceeditor_textarea_attach(this);
			var editor=this.jhtmlareaObject.editor;
			
		//	var element_id=id+index;
//			element.attr('id',element_id);
//			var editor = CKEDITOR.replace( element_id, CKCONFIG );
			//editor.on('blur',function(e){ editor.updateElement(); element.val(editor.getData());console.log(element.val())},null,null,1);
			//$(this).parents('.settings_form').bind('submit',function(){ editor.updateElement(); });

			$('.floatbox_container input.dd_save').off('click').on('click',function(){ editor.updateElement();element.parents('.settings_form').submit(); });

			//iisadvanceeditor_form_refresh_before_submit(element.parents('form').get(0),editor);
		});
	};
}

/*
if (window.massMailing) {
	massMailing.addVarOriginal=massMailing.addVar;
	massMailing.addVar=function($varname){
		editor.element.$.jhtmlareaObject.pasteHTML($varname);
	};
}
*/

/* Here we are latching on an event ... in this case, the dialog open event */
CKEDITOR.on('dialogDefinition', function(ev) {
    try {
		/* this just gets the name of the dialog */
        var dialogName = ev.data.name;
		/* this just gets the contents of the opened dialog */
        var dialogDefinition = ev.data.definition;
		/* Make sure that the dialog opened is the link plugin ... otherwise do nothing */
        if(dialogName == 'link') {
			/* Getting the contents of the Target tab */
            var informationTab = dialogDefinition.getContents('target');
			/* Getting the contents of the dropdown field "Target" so we can set it */
            var targetField = informationTab.get('linkTargetType');
			/* Now that we have the field, we just set the default to _blank
			 A good modification would be to check the value of the URL field
			 and if the field does not start with "mailto:" or a relative path,
			 then set the value to "_blank" */
            targetField['default'] = '_blank';
        }
    } catch(exception) {
        $.alert('Error ' + ev.message);
    }
});
