<?php

/**
 * @author Issa Annamoradnejad
 */

class IISAPARATSUPPORT_BOL_Service
{

    private static $classInstance;
    
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    public function addVideoUploader(){
        $videoUpload = new TextField('aparatURL');
        $videoUpload->setId('aparatURL');
        $videoUpload->setLabel(OW::getLanguage()->text('iisaparatsupport', 'add_aparat_url_element_label'));
        $videoUpload->setDescription(OW::getLanguage()->text('iisaparatsupport', 'add_aparat_url_element_desc'));
        $videoUpload->addValidator(new IISAPARATSUPPORT_CMP_Validserviceproviders());
        return $videoUpload;
    }

    public function onBeforeVideoUploadFormRenderer(OW_Event $event)
    {
        $params = $event->getParams();

        if(isset($params['form'])){
            $form = $params['form'];
            /* @var $form Form */
            $form->addElement($this->addVideoUploader());
            if($form->getElement('code')!=null) {
                $form->getElement('code')->setRequired(false)->setLabel(OW::getLanguage()->text('video', 'code'));
            }
            if(isset($params['component'])){
                //edit form
                $params['component']->assign('aparatSupport', true);
            }

            $ajaxUrl = OW::getRouter()->urlForRoute('iisaparatsupport.load', ['vid'=>'']);
            $js = "
var lastVid = '';
function clear_thumb(){
    $('#thumb_preview').remove();
}
function check_aparat(first_try){
    url = $('#aparatURL')[0].value;
    var aparatUrlChecker = new RegExp('^(http://|https://|)(www.|)aparat.com/v/[a-zA-Z0-9\.\_\%\+\-]{4,6}$');
    if (aparatUrlChecker.test(url)) {
        var arr = url.split('/');
        var vid = arr[arr.length - 1];
        if (vid !== lastVid){
            clear_thumb();
            lastVid = vid;
            if(first_try) return;
            var ApiUrl = '$ajaxUrl' + vid;
            $.ajax({
                type: 'post',
                url: ApiUrl,
                dataType: 'json',
                success: function (resp) {
                    if (resp.result){
                        if (lastVid != resp.vid){
                            return;
                        }
                        if( $('input[name=title]').val()==''){
                            $('input[name=title]').val(resp.title);
                        }
                        if( $('iframe.cke_wysiwyg_frame.cke_reset').contents().find('body').length > 0)
                        {
                            if ($('iframe.cke_wysiwyg_frame.cke_reset').contents().find('body')[0].innerText.length <= 1){
                                $('iframe.cke_wysiwyg_frame.cke_reset').contents().find('body')[0].innerText = resp.desc;
                            }
                        }
                        else if ($('.owm_suitup-editor').length > 0)
                        {
                            if ($('.owm_suitup-editor')[0].innerHTML.length <= 1){
                                $('.owm_suitup-editor')[0].innerHTML = resp.desc;
                                $('textarea[name=description]').val(resp.desc);
                            }
                        }
                        $('#aparatURL').after('<img id=\"thumb_preview\" src=\"'+resp.thumb+'\" style=\"margin: 10px 0;max-height: 100px;\" \>');
                    }
                },
                complete: function () {
                }
            });
        }
    }else{
        clear_thumb();
    }
}

check_aparat(true);
$('#aparatURL').on('change keydown paste input',function () {
    check_aparat(false)
});
            ";
            OW::getDocument()->addOnloadScript($js);
        }
    }

    public function onBeforeVideoUploadComponentRenderer(OW_Event $event)
    {
        $params = $event->getParams();
        if(isset($params['form']) && isset($params['component'])){
            $form = $params['form'];
            /* @var $form Form */
            if($form->getElement('aparatURL')!=null){
                $params['component']->assign('aparatSupport',true);
            }
        }
    }
}
