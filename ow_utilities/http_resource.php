<?php

require_once  OW_DIR_LIB . 'oembed' . DS. 'oembed.php';

class UTIL_HttpResource
{

    /**
     *
     * @param string $url
     * @return OW_HttpResource
     */
    public static function getContents( $url, $timeout = 20 )
    {
        $result = array();

        // Checks for the Authentication and blocks unauthenticated users.
        if ( !OW::getUser()->isAuthenticated() )
        {
            die(json_encode($result));
        }

        // Checks for the port number and blocks the requests that have port number.
        if( !empty($urlPort = parse_url($url, PHP_URL_PORT)) )
        {
            die(json_encode($result));
        }

        // Checks for schema (HTTP & HTTPS)
        $urlScheme = parse_url($url, PHP_URL_SCHEME);

        if ( empty($urlScheme) )
        {
            $url = 'https://' . $url;
        }
        else if ( !in_array($urlScheme, ["http", "https"]) )
        {
            die(json_encode($result));
        }

        // TODO:
        //
        // 1- Whitelist
        //
        // 2- Check by separated methods (SRP = Single Responsibility Principle)
        //

        $context = stream_context_create( array(
            'http' => array(
                'timeout' => $timeout,
                'header' => "User-Agent: Motoshub Content Fetcher\r\n",
                'max_redirects' => 1,
                'follow_location' => 0
            )
        ));

        return OW::getStorage()->fileGetContent($url, false, false, $context);
    }

    /**
     *
     * @param string $url
     * @return array
     */
    public static function getOEmbed( $url )
    {
        return OEmbed::parse($url);
    }
}
