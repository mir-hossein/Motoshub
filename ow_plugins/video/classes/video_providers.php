<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Video service providers class
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.video.classes
 * @since 1.0
 */
class VideoProviders
{
    private $code;

    const PROVIDER_YOUTUBE = 'youtube';
    const PROVIDER_GOOGLEVIDEO = 'googlevideo';
    const PROVIDER_APARAT = 'aparat';
    const PROVIDER_UNDEFINED = 'undefined';

    private static $provArr;

    public function __construct( $code )
    {
        $this->code = $code;

        $this->init();
    }

    private function init()
    {
        if ( !isset(self::$provArr) )
        {
            self::$provArr = array(
                self::PROVIDER_YOUTUBE => '//www.youtube(-nocookie)?.com/',
                self::PROVIDER_GOOGLEVIDEO => 'http://video.google.com/',
                self::PROVIDER_APARAT=>'https://www.aparat.com/'
            );
            $event = new OW_Event(IISEventManager::ON_AFTER_VIDEO_PROVIDERS_DEFINED);
            OW::getEventManager()->trigger($event);
            if (is_array($event->getData())){
                self::$provArr =  array_merge(self::$provArr,$event->getData());
            }
        }
    }

    public function detectProvider()
    {
        foreach ( self::$provArr as $name => $url )
        {
            if ( preg_match("~$url~", $this->code) )
            {
                return $name;
            }
        }
        return self::PROVIDER_UNDEFINED;
    }

    public function getProviderThumbUrl( $provider = null )
    {
        if ( !$provider )
        {
            $provider = $this->detectProvider();
        }

        $className = 'VideoProvider' . ucfirst($provider);

        /** @var $class VideoProviderUndefined */
        if ( class_exists($className) )
        {
            $class = new $className;
        }
        else
        {
            return VideoProviders::PROVIDER_UNDEFINED;
        }
        $thumb = $class->getThumbUrl($this->code);

        return $thumb;
    }
}

class VideoProviderYoutube
{
    const clipUidPattern = '\/\/www\.youtube(-nocookie)?\.com\/(v|embed)\/([^?&"]+)[?&"]';
    const thumbUrlPattern = 'http://img.youtube.com/vi/()/default.jpg';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[3] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $url = str_replace('()', $uid, self::thumbUrlPattern);

            return strlen($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderGooglevideo
{
    const clipUidPattern = 'http:\/\/video\.google\.com\/googleplayer\.swf\?docid=([^\"][a-zA-Z0-9-_]+)[&\"]';
    const thumbXmlPattern = 'http://video.google.com/videofeed?docid=()';

    private static function getUid( $code )
    {
        $pattern = self::clipUidPattern;

        return preg_match("~{$pattern}~", $code, $match) ? $match[1] : null;
    }

    public static function getThumbUrl( $code )
    {
        if ( ($uid = self::getUid($code)) !== null )
        {
            $xmlUrl = str_replace('()', $uid, self::thumbXmlPattern);

            $fileCont = OW::getStorage()->fileGetContent($xmlUrl, true);

            if ( strlen($fileCont) )
            {
                preg_match("/media:thumbnail url=\"([^\"]\S*)\"/siU", $fileCont, $match);

                $url = isset($match[1]) ? $match[1] : VideoProviders::PROVIDER_UNDEFINED;
            }

            return !empty($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
        }

        return VideoProviders::PROVIDER_UNDEFINED;
    }
}

class VideoProviderAparat
{
    private static $AparatNewEmbedCodeRegex = '/<div\s+id="\d+">\s*<script\s+type="text\/JavaScript"\s+src="((?:https|http):\/\/www\.aparat\.com\/[\/\w\?\[\]=\&]+)">\s*<\/script>\s*<\/div>/i';
    private static $AparatOldEmbedCodeRegex = '/<iframe\s+src="((?:https|http):\/\/www\.aparat\.com\/[\/\w\?\[\]=\&]+)"[\s\w="]+>\s*<\/iframe>/i';

    public static function getThumbUrl( $code )
    {
        $url = null;
        if (preg_match_all(self::$AparatNewEmbedCodeRegex, $code, $matches)){
            $uid = $matches[1][0];
            $content = OW::getStorage()->fileGetContent($uid, true);
            if(!preg_match_all('/(?:http|https):\/\/www\.aparat\.com\/video\/video\/embed\/videohash\/\w+\/vt\/frame/i', $content, $matches)){
                return VideoProviders::PROVIDER_UNDEFINED;
            }
            $url = $matches[0][0];
        }
        if (preg_match_all(self::$AparatOldEmbedCodeRegex, $code, $matches)){
            $url = $matches[1][0];
        }
        if (!$url)
            return VideoProviders::PROVIDER_UNDEFINED;

        $content = OW::getStorage()->fileGetContent($url, true);
        if(!preg_match_all('/style="background-image\:\s+url\(\'((?:http|https):\/\/[\w\.\/-]+)\'\)/i',$content,$matches)){
            return VideoProviders::PROVIDER_UNDEFINED;
        }
        $url = $matches[1][0];
//        $url = str_replace('https','http',$url);
        return !empty($url) ? $url : VideoProviders::PROVIDER_UNDEFINED;
    }
}
class VideoProviderUndefined
{
    public static function getThumbUrl( $code )
    {
        return VideoProviders::PROVIDER_UNDEFINED;
    }
}
