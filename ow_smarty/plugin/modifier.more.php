<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.oxwall.org/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Oxwall software.
 * The Initial Developer of the Original Code is Oxwall Foundation (http://www.oxwall.org/foundation).
 * All portions of the code written by Oxwall Foundation are Copyright (c) 2011. All Rights Reserved.

 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2011 Oxwall Foundation. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Oxwall community software
 * Attribution URL: http://www.oxwall.org/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */

/**
 * Smarty truncate modifier.
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow.ow_smarty.plugin
 * @since 1.0
 * @param $string
 * @param $length
 * @param bool $show_button
 * @return string
 */
function smarty_modifier_more( $string, $length, $show_button = true )
{
    if( strlen(strip_tags($string)) < $length + 50) {
        return $string;
    }
    $uniqId = IISSecurityProvider::generateUniqueId("more-");
    $seeMoreEmbed = '<a href="javascript://" class="ow_small ow_lbutton view_more " onclick="$(\'#' . $uniqId . '\').attr(\'data-collapsed\', 0);$(this).remove();">'
        . OW::getLanguage()->text("base", "comments_see_more_label")
        . '</a>';
    $truncated = mb_substr($string, 0, $length);
    if(mb_strpos($truncated,'<')!==false) {
        $string2 = '<p>' . $string . '</p>';
        $truncated = trim_html($string2, $length);
        if( $show_button ){
            $truncated2=mb_substr($truncated, 0, -4, 'UTF-8') . ' ... '.$seeMoreEmbed . '</p>';
        }
        else{
            $truncated2=mb_substr($truncated, 0, -4, 'UTF-8') . ' ... </p>';
        }
    }
    else{
        if( $show_button ){
            $truncated2=$truncated. ' ... '.$seeMoreEmbed;
        }
        else{
            $truncated2=$truncated. ' ... ';
        }
    }
    if ( strlen($string) - strlen($truncated) < 50 )
    {
        return $string;
    }

    return '
    <span class="ow_more_text" data-collapsed="1" id="' . $uniqId . '">
        <span data-text="full">' . $string . '</span>
        <span data-text="truncated">' . $truncated2 . '</span>
    </span>';
}

/***
 * @author Issa Annamoradnejad <i.moradnejad@gmail.com>
 * @param $html
 * @param $maxLength
 * @param bool $isUtf8
 * @return string
 */
function trim_html($html, $maxLength, $isUtf8=true)
{
    $printedLength = 0;
    $position = 0;
    $tags = array();

    $resp = "";

    // For UTF-8, we need to count multibyte sequences as one character.
    $re = $isUtf8
        ? '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;|[\x80-\xFF][\x80-\xBF]*}'
        : '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;}';

    while ($printedLength < $maxLength && preg_match($re, $html, $match, PREG_OFFSET_CAPTURE, $position))
    {
        list($tag, $tagPosition) = $match[0];

        // Print text leading up to the tag.
        $str = substr($html, $position, $tagPosition - $position);
        if ($printedLength + strlen($str) > $maxLength)
        {
            $resp .= substr($str, 0, $maxLength - $printedLength);
            $printedLength = $maxLength;
            break;
        }

        $resp .= ($str);
        $printedLength += strlen($str);
        if ($printedLength >= $maxLength) break;

        if ($tag[0] == '&' || ord($tag) >= 0x80)
        {
            // Pass the entity or UTF-8 multibyte sequence through unchanged.
            $resp .= ($tag);
            $printedLength++;
        }
        else
        {
            // Handle the tag.
            $tagName = $match[1][0];
            if ($tag[1] == '/')
            {
                // This is a closing tag.
                if(substr($tag,2,-1) == end($tags)){
                    array_pop($tags);
                }
                $resp .= ($tag);
            }
            else if ($tag[strlen($tag) - 2] == '/')
            {
                // Self-closing tag.
                $resp .= ($tag);
            }
            else
            {
                // Opening tag.
                $resp .= ($tag);
                $tags[] = $tagName;
            }
        }

        // Continue after the tag.
        $position = $tagPosition + strlen($tag);
    }

    // Print any remaining text.
    if ($printedLength < $maxLength && $position < strlen($html))
        $resp .= substr($html, $position, $maxLength - $printedLength);

    // Close any open tags.
    while (!empty($tags))
        $resp .= '</'.array_pop($tags).'>';
    return $resp;
}
