<?php

/**
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 */

class IISAPARATSUPPORT_CTRL_Load extends OW_ActionController
{

    public function __construct()
    {
    }
    public function init()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }
    }

    /***
     * @param $params
     * @throws AuthenticateException
     */
    public function get_aparat_info($params){
        if (!OW::getUser()->isAuthenticated()) {
            throw new AuthenticateException();
        }
        $vid = $params['vid'];
        $aparatUrl = 'https://www.aparat.com/etc/api/video/videohash/' . $vid;
        $response = UTIL_HttpResource::getContents($aparatUrl);

        $data = json_decode($response, true);

        if ( empty($response) || !isset($data['video']) || !isset($data['video']['id']))
        {
            exit(json_encode(array('result' => false)));
        }
        exit(json_encode(array('result' => true, 'vid' => $vid, 'title' => $data['video']['title'],  'desc' => $data['video']['description'],  'thumb' => $data['video']['small_poster'])));
    }

}

